<template>
    <div ref="wrapEl" class="rmap-wrap z-5" :style="{ height }">
        <div ref="mapEl" class="rmap"></div>

        <!-- Top controls: island filter + region dropdown -->
        <div v-if="ready" class="rmap-controls">
            <!-- Row 1: island chips + reset -->
            <div class="rmap-controls-row">
                <div class="rmap-islands">
                    <button class="rmap-chip" :class="{ 'rmap-chip--active': selIsland === '' }" @click="setIsland('')">
                        All Philippines
                    </button>
                    <button
                        v-for="i in ISLANDS" :key="i.value"
                        class="rmap-chip"
                        :class="{ 'rmap-chip--active': selIsland === i.value }"
                        @click="setIsland(i.value)"
                    >{{ i.label }}</button>
                </div>
                <button class="rmap-reset" title="Reset view" @click="resetView">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
                    </svg>
                </button>
            </div>

            <!-- Row 2: region dropdown -->
            <div class="rmap-legend" :class="{ 'rmap-legend--open': legendOpen }">
                <button class="rmap-legend-toggle" @click="legendOpen = !legendOpen">
                    <span>Regions</span>
                    <svg :style="{ transform: legendOpen ? 'rotate(180deg)' : '' }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div v-show="legendOpen" class="rmap-legend-body">
                    <template v-for="grp in legendGroups" :key="grp.island">
                        <div class="rmap-legend-island">{{ grp.label }}</div>
                        <button
                            v-for="r in grp.regions" :key="r.code"
                            class="rmap-legend-row"
                            :class="{ 'rmap-legend-row--active': selRegion === r.code, 'rmap-legend-row--dim': selRegion && selRegion !== r.code }"
                            @click="selectRegion(r.code)"
                        >
                            <span class="rmap-swatch" :style="{ background: r.color }"></span>
                            <span class="rmap-legend-name">{{ r.short }}</span>
                            <span class="rmap-legend-count">{{ r.count }}</span>
                        </button>
                    </template>
                </div>
            </div>

        </div>

        <!-- Info panel -->
        <transition name="rmap-panel">
            <div v-if="panel && !hidePanel" class="rmap-panel">
                <button class="rmap-panel-close" @click="panel = null">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>

                <!-- Province view -->
                <template v-if="panel.type === 'province'">
                    <div class="rmap-panel-kicker" :style="{ color: panel.color }">{{ panel.island }}</div>
                    <div class="rmap-panel-title">{{ panel.name }}</div>
                    <div class="rmap-panel-region">
                        <span class="rmap-swatch" :style="{ background: panel.color }"></span>
                        {{ panel.region }}
                    </div>
                    <div class="rmap-panel-sub">{{ panel.siblings.length }} other province{{ panel.siblings.length !== 1 ? 's' : '' }} in this region</div>
                    <div class="rmap-panel-siblings">
                        <button v-for="s in panel.siblings" :key="s" class="rmap-pill" @click="selectProvince(s)">{{ s }}</button>
                    </div>
                </template>

                <!-- Region view -->
                <template v-else-if="panel.type === 'region'">
                    <div class="rmap-panel-kicker" :style="{ color: panel.color }">{{ panel.island }}</div>
                    <div class="rmap-panel-title">{{ panel.region }}</div>
                    <div class="rmap-panel-sub">{{ panel.provinces.length }} province{{ panel.provinces.length !== 1 ? 's' : '' }}</div>
                    <div class="rmap-panel-siblings">
                        <button v-for="s in panel.provinces" :key="s" class="rmap-pill" @click="selectProvince(s)">{{ s }}</button>
                    </div>
                </template>
            </div>
        </transition>

        <!-- Slot for custom panels (e.g. landing page image panel) rendered inside the fullscreen root -->
        <slot :selected-panel="panel" />

    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import {
    ISLANDS, ISLAND_LABELS, REGION_ORDER, REGION_CODE_OVERRIDE, SKIP, GEO_TO_DB,
    REGION_TO_ISLAND, regionColor, regionLabel, regionShort, islandOf,
} from '@/Data/mapRegions';

const props = defineProps({
    height:     { type: String,  default: '560px' },
    hidePanel:  { type: Boolean, default: false },
    panelWidth: { type: Number,  default: 0 },
});
const emit = defineEmits(['province-selected']);

const mapEl        = ref(null);
const wrapEl       = ref(null);
const ready        = ref(false);
const isFullscreen = ref(false);
const legendOpen   = ref(false);
const selIsland    = ref('');
const selRegion    = ref(null);
const selProvince  = ref('');
const panel        = ref(null);
const legendGroups = ref([]);

let map = null;
let geoLayer = null;
let fsBtnEl = null;
const provinces = [];                 // [{ name, displayName, code, bounds }]
const regionStats = new Map();        // code → { count, provinces: [displayName] }
const layerIndex = new Map();         // displayName → { layer, code }

const HOME = { center: [12.2, 122.5], zoom: 5 };

// ── Styling ────────────────────────────────────────────────────────────────
const styleFor = (feature) => {
    const name = feature.properties.adm2_en;
    if (SKIP(name)) return { fillColor: '#eef2f7', weight: 0.4, color: '#dbe3ec', fillOpacity: 0.35 };

    const code        = feature.properties.adm1_psgc;
    const displayName = GEO_TO_DB[name] ?? name;
    const color       = regionColor(code);

    if (selProvince.value) {
        if (displayName === selProvince.value)
            return { fillColor: color, weight: 2.2, color: '#ffffff', fillOpacity: 0.96 };
        if (code === selRegion.value)
            return { fillColor: color, weight: 0.7, color: '#ffffff', fillOpacity: 0.70 };
        return { fillColor: '#d7dee7', weight: 0.3, color: '#eef2f7', fillOpacity: 0.50 };
    }

    const dimByIsland = selIsland.value && islandOf(code) !== selIsland.value;
    const dimByRegion = selRegion.value && code !== selRegion.value;

    if (dimByIsland || dimByRegion)
        return { fillColor: '#d7dee7', weight: 0.3, color: '#eef2f7', fillOpacity: 0.25 };

    const emphasised = selRegion.value === code;
    return {
        fillColor:   color,
        weight:      emphasised ? 1.4 : 0.7,
        color:       '#ffffff',
        fillOpacity: emphasised ? 0.92 : 0.8,
    };
};

const refreshStyle = () => {
    geoLayer && geoLayer.setStyle(styleFor);
    refreshLabels();
};

const refreshLabels = () => {
    const hasFilter = selIsland.value || selRegion.value || selProvince.value;
    for (const [name, { layer, code }] of layerIndex) {
        let opacity = 0;
        if (hasFilter) {
            if (selProvince.value) {
                if (code !== selRegion.value) { opacity = 0; }
                else opacity = name === selProvince.value ? 1 : 0.65;
            } else {
                const dimByIsland = selIsland.value && islandOf(code) !== selIsland.value;
                const dimByRegion = selRegion.value && code !== selRegion.value;
                opacity = (!dimByIsland && !dimByRegion) ? 1 : 0;
            }
        }
        layer.getTooltip()?.setOpacity(opacity);
    }
};

// ── Panels ─────────────────────────────────────────────────────────────────
const buildProvincePanel = (displayName) => {
    const prov = provinces.find(p => p.displayName === displayName);
    if (!prov) return null;
    const stat = regionStats.get(prov.code);
    return {
        type:     'province',
        name:     displayName,
        region:   regionLabel(prov.code),
        island:   ISLAND_LABELS[islandOf(prov.code)] ?? '',
        color:    regionColor(prov.code),
        siblings: (stat?.provinces ?? []).filter(n => n !== displayName).sort((a, b) => a.localeCompare(b)),
    };
};

const buildRegionPanel = (code) => {
    const stat = regionStats.get(code);
    return {
        type:      'region',
        region:    regionLabel(code),
        island:    ISLAND_LABELS[islandOf(code)] ?? '',
        color:     regionColor(code),
        provinces: (stat?.provinces ?? []).slice().sort((a, b) => a.localeCompare(b)),
    };
};

// ── Navigation ──────────────────────────────────────────────────────────────
const flyToProvinces = (codeOrIsland, kind) => {
    const subset = provinces.filter(p =>
        kind === 'region' ? p.code === codeOrIsland : islandOf(p.code) === codeOrIsland
    );
    if (!subset.length || !map) return;
    let bounds = L.latLngBounds(subset[0].bounds);
    for (const p of subset.slice(1)) bounds.extend(p.bounds);
    // paddingTopLeft=[left,top], paddingBottomRight=[right,bottom]
    const topPad   = props.panelWidth ? 110 : 60;
    const rightPad = props.panelWidth ? props.panelWidth + 20 : 20;
    map.flyToBounds(bounds, {
        paddingTopLeft:     [20, topPad],
        paddingBottomRight: [rightPad, 20],
        maxZoom: kind === 'region' ? 8 : 6.5,
        duration: 0.9,
    });
};

const setIsland = (island) => {
    selIsland.value   = island;
    selRegion.value   = null;
    selProvince.value = '';
    panel.value       = null;
    refreshStyle();
    if (island) flyToProvinces(island, 'island');
    else map?.flyTo(HOME.center, HOME.zoom, { duration: 0.8 });
};

const selectRegion = (code) => {
    const island = islandOf(code);
    if (island) selIsland.value = island;
    selRegion.value   = code;
    selProvince.value = '';
    refreshStyle();
    panel.value = buildRegionPanel(code);
    flyToProvinces(code, 'region');
};

const selectProvince = (displayName) => {
    const prov = provinces.find(p => p.displayName === displayName);
    if (!prov) return;
    selProvince.value = displayName;
    selRegion.value   = prov.code;
    selIsland.value   = islandOf(prov.code) ?? '';
    refreshStyle();
    panel.value = buildProvincePanel(displayName);
    // paddingTopLeft=[left,top], paddingBottomRight=[right,bottom]
    const topPad   = props.panelWidth ? 110 : 60;
    const rightPad = props.panelWidth ? props.panelWidth + 20 : 20;
    map?.flyToBounds(prov.bounds, {
        paddingTopLeft:     [20, topPad],
        paddingBottomRight: [rightPad, 20],
        maxZoom: 8.5,
        duration: 0.9,
    });
    emit('province-selected', buildProvincePanel(displayName));
};

const resetView = () => {
    selIsland.value   = '';
    selRegion.value   = null;
    selProvince.value = '';
    panel.value       = null;
    refreshStyle();
    map?.flyTo(HOME.center, HOME.zoom, { duration: 0.8 });
};

// ── Fullscreen ───────────────────────────────────────────────────────────────
const toggleFullscreen = () => {
    if (!document.fullscreenElement) wrapEl.value?.requestFullscreen();
    else document.exitFullscreen();
};
const onFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
    if (fsBtnEl) {
        fsBtnEl.innerHTML = isFullscreen.value
            ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/></svg>`
            : `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>`;
    }
    setTimeout(() => map?.invalidateSize(), 120);
};

// Returns the centroid of the largest polygon ring in a GeoJSON feature.
// This keeps labels inside the actual polygon body rather than outside concave shapes.
const visualCentroid = (feature) => {
    const geo = feature.geometry;
    const rings = geo.type === 'MultiPolygon'
        ? geo.coordinates.map(p => p[0])
        : [geo.coordinates[0]];
    const ring = rings.reduce((a, b) => (b.length > a.length ? b : a));
    let lat = 0, lng = 0;
    for (const [x, y] of ring) { lng += x; lat += y; }
    return L.latLng(lat / ring.length, lng / ring.length);
};

// ── Mount ─────────────────────────────────────────────────────────────────────
onMounted(async () => {
    document.addEventListener('fullscreenchange', onFullscreenChange);

    map = L.map(mapEl.value, {
        center: HOME.center, zoom: HOME.zoom,
        zoomControl: false, scrollWheelZoom: false,
        zoomAnimation: true, fadeAnimation: true, attributionControl: true,
    });
    const zoomCtrl = L.control.zoom({ position: 'bottomleft' });
    zoomCtrl.addTo(map);
    // Append fullscreen button directly into the zoom control group (below the - button)
    const zoomEl = zoomCtrl.getContainer();
    fsBtnEl = L.DomUtil.create('a', 'leaflet-control-zoom-fullscreen', zoomEl);
    fsBtnEl.title = 'Toggle fullscreen';
    fsBtnEl.setAttribute('role', 'button');
    fsBtnEl.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>`;
    L.DomEvent.on(fsBtnEl, 'click', L.DomEvent.stop).on(fsBtnEl, 'click', toggleFullscreen);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
        subdomains: 'abcd', maxZoom: 18,
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    }).addTo(map);

    const geojson = await (await fetch('/geo/philippines-provinces.geojson')).json();

    // Normalize region codes to the DOST scheme, then index provinces + region stats.
    for (const feature of geojson.features) {
        const name = feature.properties.adm2_en;
        const ov = REGION_CODE_OVERRIDE[name];
        if (ov) feature.properties.adm1_psgc = ov;
        if (SKIP(name)) continue;

        const code        = feature.properties.adm1_psgc;
        const displayName  = GEO_TO_DB[name] ?? name;
        const bounds      = L.geoJSON(feature).getBounds();
        provinces.push({ name, displayName, code, bounds });

        if (!regionStats.has(code)) regionStats.set(code, { count: 0, provinces: [] });
        const s = regionStats.get(code);
        s.count++;
        s.provinces.push(displayName);
    }

    // Legend grouped by island, only for labelled DOST regions present on the map.
    const groups = {};
    for (const code of REGION_ORDER) {
        const stat = regionStats.get(code);
        if (!stat) continue;
        const isl = islandOf(code);
        (groups[isl] ??= []).push({ code, short: regionShort(code), color: regionColor(code), count: stat.count });
    }
    legendGroups.value = ISLANDS.map(i => ({ island: i.value, label: i.label, regions: groups[i.value] ?? [] }))
        .filter(g => g.regions.length);

    geoLayer = L.geoJSON(geojson, {
        style: styleFor,
        onEachFeature: (feature, layer) => {
            const name = feature.properties.adm2_en;
            if (SKIP(name)) return;
            const code        = feature.properties.adm1_psgc;
            const displayName  = GEO_TO_DB[name] ?? name;

            // Permanent province name label - hidden until a region/island is selected
            layer.bindTooltip(displayName, {
                permanent:  true,
                direction:  'center',
                className:  'rmap-label',
                opacity:    0,
            });
            // Store visual centroid so we can override Leaflet's bbox-center after addTo
            layer._labelCenter = visualCentroid(feature);
            layerIndex.set(displayName, { layer, code });

            layer.on('mouseover', (e) => {
                if (selProvince.value && displayName !== selProvince.value) return;
                if ((selIsland.value && islandOf(code) !== selIsland.value) ||
                    (selRegion.value && selRegion.value !== code)) return;
                e.target.setStyle({ weight: 2.4, fillOpacity: 0.97 });
            });
            layer.on('mouseout', () => layer.setStyle(styleFor(layer.feature)));
            layer.on('click', () => selectProvince(displayName));
        },
    }).addTo(map);

    // After addTo, Leaflet has opened all permanent tooltips using getBounds().getCenter().
    // Override each tooltip's position with the visual centroid of the largest polygon ring.
    geoLayer.eachLayer(layer => {
        if (layer._labelCenter && layer.getTooltip()) {
            layer.getTooltip().setLatLng(layer._labelCenter);
        }
    });

    ready.value = true;
});

onBeforeUnmount(() => {
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    map?.remove();
    map = null;
});
</script>

<style scoped>
.rmap-wrap { position: relative; z-index: 5; width: 100%; border-radius: 16px; overflow: hidden; background: #f8fafc; }
.rmap      { width: 100%; height: 100%; }
.rmap :deep(.leaflet-container) { background: #f1f5f9; font-family: inherit; }

/* Controls - column stack anchored top-left */
.rmap-controls {
    position: absolute; top: 14px; left: 14px; z-index: 500;
    display: flex; flex-direction: column; align-items: flex-start; gap: 6px; pointer-events: none;
}
.rmap-controls-row {
    display: flex; align-items: center; gap: 6px; pointer-events: none;
}
.rmap-islands { display: flex; flex-wrap: wrap; gap: 6px; pointer-events: auto; }
.rmap-chip {
    appearance: none; border: 1px solid #e2e8f0; background: rgba(255,255,255,0.92);
    backdrop-filter: blur(6px); color: #334155; font-size: 13.5px; font-weight: 600;
    padding: 8px 14px; border-radius: 999px; cursor: pointer; box-shadow: 0 1px 3px rgba(15,23,42,0.08);
    transition: all 0.15s ease;
}
.rmap-chip:hover { color: #0f172a; border-color: #cbd5e1; }
.rmap-chip--active { background: #1e3a8a; color: #fff; border-color: #1e3a8a; }
.rmap-reset {
    pointer-events: auto; display: grid; place-items: center; width: 38px; height: 38px;
    border: 1px solid #e2e8f0; background: rgba(255,255,255,0.92); color: #475569;
    border-radius: 10px; cursor: pointer; box-shadow: 0 1px 3px rgba(15,23,42,0.08); flex-shrink: 0;
}
.rmap-reset:hover { color: #1e3a8a; border-color: #cbd5e1; }

/* Legend - inline in top controls row, drops down as a floating panel */
.rmap-legend {
    position: relative; z-index: 600; pointer-events: auto;
}
.rmap-legend-toggle {
    display: flex; align-items: center; gap: 6px;
    padding: 7px 12px; background: rgba(255,255,255,0.92); backdrop-filter: blur(6px);
    border: 1px solid #e2e8f0; border-radius: 10px;
    cursor: pointer; font-size: 13.5px; font-weight: 600; color: #334155;
    box-shadow: 0 1px 3px rgba(15,23,42,0.08); white-space: nowrap;
}
.rmap-legend--open .rmap-legend-toggle { color: #1e3a8a; border-color: #cbd5e1; }
.rmap-legend-toggle svg { width: 14px; height: 14px; color: #64748b; transition: transform 0.2s ease; flex-shrink: 0; }
.rmap-legend-body {
    position: absolute; top: calc(100% + 6px); left: 0; width: 234px;
    max-height: 320px; overflow-y: auto; padding: 4px 8px 10px;
    background: rgba(255,255,255,0.97); backdrop-filter: blur(8px);
    border: 1px solid #e8edf3; border-radius: 14px; box-shadow: 0 8px 28px rgba(15,23,42,0.14);
}
.rmap-legend-island {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
    color: #94a3b8; padding: 8px 6px 4px;
}
.rmap-legend-row {
    width: 100%; display: flex; align-items: center; gap: 9px; padding: 6px 7px;
    background: none; border: none; border-radius: 8px; cursor: pointer; transition: background 0.12s ease;
}
.rmap-legend-row:hover { background: #f1f5f9; }
.rmap-legend-row--active { background: #eef2ff; }
.rmap-legend-row--dim { opacity: 0.45; }
.rmap-swatch { width: 14px; height: 14px; border-radius: 4px; flex-shrink: 0; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.06); }
.rmap-legend-name { flex: 1; text-align: left; font-size: 13.5px; font-weight: 600; color: #334155; }
.rmap-legend-count {
    font-size: 12px; font-weight: 700; color: #64748b; background: #f1f5f9;
    padding: 1px 8px; border-radius: 999px; min-width: 26px; text-align: center;
}

/* Info panel */
.rmap-panel {
    position: absolute; top: 14px; right: 14px; z-index: 500; width: 290px; max-width: calc(100% - 28px);
    background: #fff; border: 1px solid #e8edf3; border-radius: 16px; padding: 18px 18px 16px;
    box-shadow: 0 12px 40px rgba(15,23,42,0.16);
}
.rmap-panel-close {
    position: absolute; top: 12px; right: 12px; width: 28px; height: 28px; display: grid; place-items: center;
    border: none; background: #f1f5f9; border-radius: 8px; color: #64748b; cursor: pointer;
}
.rmap-panel-close:hover { background: #e2e8f0; color: #0f172a; }
.rmap-panel-close svg { width: 15px; height: 15px; }
.rmap-panel-kicker { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; }
.rmap-panel-title { font-size: 23px; font-weight: 800; color: #0f172a; line-height: 1.15; margin: 3px 0 8px; padding-right: 28px; }
.rmap-panel-region { display: flex; align-items: center; gap: 8px; font-size: 14.5px; font-weight: 600; color: #334155; }
.rmap-panel-sub { font-size: 13px; color: #64748b; margin: 12px 0 8px; font-weight: 600; }
.rmap-panel-siblings { display: flex; flex-wrap: wrap; gap: 6px; max-height: 220px; overflow-y: auto; }
.rmap-pill {
    font-size: 12.5px; font-weight: 600; color: #1e293b; background: #f1f5f9; border: 1px solid #e8edf3;
    padding: 4px 10px; border-radius: 999px; cursor: pointer; transition: all 0.12s ease;
}
.rmap-pill:hover { background: #1e3a8a; color: #fff; border-color: #1e3a8a; }


.rmap-panel-enter-active, .rmap-panel-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.rmap-panel-enter-from, .rmap-panel-leave-to { opacity: 0; transform: translateY(-6px); }

/* Zoom controls sit at the far left */
.rmap :deep(.leaflet-bottom.leaflet-left) {
    left: 14px;
}

/* Fullscreen button injected into Leaflet zoom control */
.rmap :deep(.leaflet-control-zoom-fullscreen) {
    display: flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; color: #555;
    border-top: 1px solid #ccc; cursor: pointer;
}
.rmap :deep(.leaflet-control-zoom-fullscreen:hover) { color: #333; background: #f4f4f4; }
</style>

<style>
/* ── Province panel - Google Maps style (light theme) ─────────────────────── */
.prov-panel {
    position: absolute;
    top: 12px; right: 12px; bottom: 12px;
    width: 360px; z-index: 500;
    border-radius: 16px; overflow: hidden;
    box-shadow: 0 8px 40px rgba(0,0,0,.22);
    display: flex; flex-direction: column;
    background: #fff;
}

/* Photo header */
.prov-photo { position: relative; height: 175px; flex-shrink: 0; overflow: hidden; }
.prov-photo img { width: 100%; height: 100%; object-fit: cover; }
.prov-photo-gradient {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,.08) 0%, rgba(0,0,0,.38) 100%);
}

/* Close button - sits on photo */
.prov-close {
    position: absolute; top: 10px; right: 10px; z-index: 2;
    width: 30px; height: 30px; border-radius: 50%; border: none;
    background: rgba(255,255,255,.88); color: #374151; cursor: pointer;
    display: grid; place-items: center; transition: background .15s ease;
    box-shadow: 0 1px 4px rgba(0,0,0,.18);
}
.prov-close:hover { background: #fff; }

/* Scrollable white body */
.prov-body { flex: 1; overflow-y: auto; padding: 16px 16px 20px; }
.prov-body::-webkit-scrollbar { width: 4px; }
.prov-body::-webkit-scrollbar-track { background: transparent; }
.prov-body::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 999px; }

/* Province name + region + island chip */
.prov-title { font-size: 1.65rem; font-weight: 800; color: #0f172a; line-height: 1.15; margin-bottom: 4px; }
.prov-region-row {
    display: flex; align-items: center; gap: 6px;
    font-size: 15px; font-weight: 600; color: #334155; margin-bottom: 8px;
}
.prov-swatch { width: 11px; height: 11px; border-radius: 3px; flex-shrink: 0; box-shadow: inset 0 0 0 1px rgba(0,0,0,.08); }
.prov-island-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 13px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
    color: #0b57a8; background: #eaf2fb; border: 1px solid #c7ddf6;
    padding: 3px 10px; border-radius: 999px;
}

/* Separator */
.prov-sep { border: none; border-top: 1px solid #f1f5f9; margin: 12px 0; }

/* Director card */
.prov-dir-label {
    font-size: 13px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
    color: #94a3b8; margin-bottom: 8px;
}
.prov-dir-card {
    display: flex; align-items: center; gap: 12px;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px;
}
.prov-dir-avatar {
    width: 48px; height: 48px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: flex-end; justify-content: center;
    overflow: hidden; border: 2px solid rgba(255,255,255,.7);
    box-shadow: 0 2px 8px rgba(0,0,0,.18);
}
.prov-dir-avatar-icon { width: 32px; height: 32px; color: rgba(255,255,255,.92); margin-bottom: -2px; }
.prov-dir-info { flex: 1; min-width: 0; }
.prov-dir-name { font-size: 15px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.prov-dir-service { font-size: 13px; color: #64748b; margin-top: 2px; }
.prov-dir-badge {
    font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    color: #94a3b8; background: #f1f5f9; border: 1px solid #e2e8f0;
    padding: 2px 7px; border-radius: 999px; flex-shrink: 0;
}

/* Description */
.prov-desc {
    font-size: 14.5px; line-height: 1.65; color: #4b5563;
    margin-top: 12px; margin-bottom: 4px;
}

/* Info rows */
.prov-rows { display: flex; flex-direction: column; gap: 11px; margin-top: 10px; }
.prov-row { display: flex; align-items: flex-start; gap: 12px; font-size: 15px; color: #374151; line-height: 1.4; }
.prov-row-icon { width: 18px; height: 18px; color: #64748b; flex-shrink: 0; margin-top: 1px; }

/* Sign-in teaser */
.prov-signin {
    background: #f0f7ff; border: 1px solid #bcd6f2; border-radius: 12px; padding: 14px;
}
.prov-signin-head {
    display: flex; align-items: center; gap: 6px;
    font-size: 13px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
    color: #1e40af; margin-bottom: 6px;
}
.prov-signin-text { font-size: 14.5px; color: #334155; line-height: 1.6; margin-bottom: 10px; }
.prov-signin-text strong { color: #0f172a; }
.prov-signin-btn {
    display: inline-flex; align-items: center; gap: 5px; font-size: 14px; font-weight: 700;
    color: #fff; background: #0b57a8; padding: 7px 14px; border-radius: 8px;
    text-decoration: none !important; transition: background .15s ease;
}
.prov-signin-btn:hover { background: #0a3f7d; }

/* Co-province pills */
.prov-section-label {
    font-size: 10.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
    color: #94a3b8; margin-bottom: 8px;
}
.prov-pills { display: flex; flex-wrap: wrap; gap: 5px; }
.prov-pill {
    font-size: 11.5px; font-weight: 600; color: #334155;
    background: #f1f5f9; border: 1px solid #e2e8f0;
    padding: 3px 10px; border-radius: 999px;
}
.prov-pill--more { color: #94a3b8; }

/* Fullscreen / TV */
.rmap-wrap:fullscreen .prov-panel,
.rmap-wrap:-webkit-full-screen .prov-panel,
.rmap-wrap:-moz-full-screen .prov-panel {
    width: clamp(420px, 28vw, 720px);
    top: 16px; right: 16px; bottom: 16px; border-radius: 20px;
}
.rmap-wrap:fullscreen .prov-photo,
.rmap-wrap:-webkit-full-screen .prov-photo,
.rmap-wrap:-moz-full-screen .prov-photo { height: 220px; }
.rmap-wrap:fullscreen .prov-title,
.rmap-wrap:-webkit-full-screen .prov-title,
.rmap-wrap:-moz-full-screen .prov-title { font-size: 1.85rem; }
.rmap-wrap:fullscreen .prov-body,
.rmap-wrap:-webkit-full-screen .prov-body,
.rmap-wrap:-moz-full-screen .prov-body { padding: 20px 20px 24px; }
.rmap-wrap:fullscreen .prov-dir-avatar,
.rmap-wrap:-webkit-full-screen .prov-dir-avatar,
.rmap-wrap:-moz-full-screen .prov-dir-avatar { width: 60px; height: 60px; }
.rmap-wrap:fullscreen .prov-dir-name,
.rmap-wrap:-webkit-full-screen .prov-dir-name,
.rmap-wrap:-moz-full-screen .prov-dir-name { font-size: 15px; }
.rmap-wrap:fullscreen .prov-pill,
.rmap-wrap:-webkit-full-screen .prov-pill,
.rmap-wrap:-moz-full-screen .prov-pill { font-size: 13px; padding: 4px 13px; }
.prov-slide-enter-active, .prov-slide-leave-active { transition: opacity .2s ease, transform .2s ease; }
.prov-slide-enter-from, .prov-slide-leave-to { opacity: 0; transform: translateX(16px); }

/* Province panel — extra detail rows */
.prov-row-sample { color: #94a3b8; font-size: 12px; font-weight: 500; }

/* Province panel — PRISM scoring mini-bar */
.prov-prism-head {
    font-size: 11px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
    color: #94a3b8; margin-bottom: 8px;
}
.prov-prism-bar {
    display: flex; height: 30px; border-radius: 8px; overflow: hidden;
    box-shadow: 0 2px 8px rgba(11,87,168,.12);
}
.prov-prism-seg {
    display: flex; align-items: center; justify-content: center;
    font-size: 10.5px; font-weight: 700; color: #fff;
    text-shadow: 0 1px 2px rgba(0,0,0,.2); white-space: nowrap; overflow: hidden;
}
.prov-prism-labels {
    font-size: 11.5px; color: #94a3b8; margin-top: 7px; font-weight: 600; text-align: center;
}

/* Province name labels (permanent tooltips) */
.rmap-label {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    color: #000 !important;
    text-shadow: 0 1px 2px rgba(255,255,255,.8), 0 0 6px rgba(255,255,255,.6) !important;
    white-space: nowrap !important;
    pointer-events: none !important;
    text-align: center !important;
    letter-spacing: 0.01em !important;
}
.rmap-label::before { display: none !important; }

/* Tooltip (Leaflet renders these outside the scoped component root) */
.rmap-tooltip { background: #0f172a !important; border: none !important; box-shadow: 0 6px 20px rgba(0,0,0,0.25) !important; border-radius: 8px !important; padding: 0 !important; }
.rmap-tooltip::before { display: none !important; }
.rmap-tip { display: flex; flex-direction: column; gap: 1px; padding: 7px 11px; }
.rmap-tip strong { color: #fff; font-size: 13px; font-weight: 700; }
.rmap-tip span { font-size: 11.5px; font-weight: 600; }
</style>
