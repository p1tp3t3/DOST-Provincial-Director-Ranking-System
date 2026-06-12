<template>
    <div ref="wrapEl" class="rmap-wrap" :style="{ height }">
        <div ref="mapEl" class="rmap"></div>

        <!-- Top controls: island filter -->
        <div v-if="ready" class="rmap-controls">
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

        <!-- Legend (regions grouped by island) -->
        <div v-if="ready" class="rmap-legend" :class="{ 'rmap-legend--open': legendOpen }">
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

        <!-- Info panel -->
        <transition name="rmap-panel">
            <div v-if="panel" class="rmap-panel">
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

        <button class="rmap-fs" :title="isFullscreen ? 'Exit fullscreen' : 'Fullscreen'" @click="toggleFullscreen">
            <svg v-if="!isFullscreen" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/></svg>
        </button>
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

defineProps({ height: { type: String, default: '560px' } });

const mapEl        = ref(null);
const wrapEl       = ref(null);
const ready        = ref(false);
const isFullscreen = ref(false);
const legendOpen   = ref(true);
const selIsland    = ref('');
const selRegion    = ref(null);
const panel        = ref(null);
const legendGroups = ref([]);

let map = null;
let geoLayer = null;
const provinces = [];                 // [{ name, displayName, code, bounds }]
const regionStats = new Map();        // code → { count, provinces: [displayName] }

const HOME = { center: [12.2, 122.5], zoom: 5 };

// ── Styling ────────────────────────────────────────────────────────────────
const styleFor = (feature) => {
    const name = feature.properties.adm2_en;
    if (SKIP(name)) return { fillColor: '#eef2f7', weight: 0.4, color: '#dbe3ec', fillOpacity: 0.35 };

    const code  = feature.properties.adm1_psgc;
    const color = regionColor(code);

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

const refreshStyle = () => geoLayer && geoLayer.setStyle(styleFor);

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
    map.flyToBounds(bounds, { padding: [40, 40], maxZoom: kind === 'region' ? 8 : 6.5, duration: 0.9 });
};

const setIsland = (island) => {
    selIsland.value = island;
    selRegion.value = null;
    panel.value = null;
    refreshStyle();
    if (island) flyToProvinces(island, 'island');
    else map?.flyTo(HOME.center, HOME.zoom, { duration: 0.8 });
};

const selectRegion = (code) => {
    const island = islandOf(code);
    if (island) selIsland.value = island;
    selRegion.value = code;
    refreshStyle();
    panel.value = buildRegionPanel(code);
    flyToProvinces(code, 'region');
};

const selectProvince = (displayName) => {
    const prov = provinces.find(p => p.displayName === displayName);
    if (!prov) return;
    selRegion.value = prov.code;
    selIsland.value = islandOf(prov.code) ?? '';
    refreshStyle();
    panel.value = buildProvincePanel(displayName);
    map?.flyToBounds(prov.bounds, { padding: [50, 50], maxZoom: 8.5, duration: 0.9 });
};

const resetView = () => {
    selIsland.value = '';
    selRegion.value = null;
    panel.value = null;
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
    setTimeout(() => map?.invalidateSize(), 120);
};

// ── Mount ─────────────────────────────────────────────────────────────────────
onMounted(async () => {
    document.addEventListener('fullscreenchange', onFullscreenChange);

    map = L.map(mapEl.value, {
        center: HOME.center, zoom: HOME.zoom,
        zoomControl: false, scrollWheelZoom: false,
        zoomAnimation: true, fadeAnimation: true, attributionControl: true,
    });
    L.control.zoom({ position: 'bottomright' }).addTo(map);
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

            layer.on('mouseover', (e) => {
                if ((selIsland.value && islandOf(code) !== selIsland.value) ||
                    (selRegion.value && selRegion.value !== code)) return;
                layer.bindTooltip(
                    `<div class="rmap-tip"><strong>${displayName}</strong><span style="color:${regionColor(code)}">${regionShort(code)}</span></div>`,
                    { sticky: true, className: 'rmap-tooltip' }
                ).openTooltip(e.latlng);
                e.target.setStyle({ weight: 2.4, fillOpacity: 0.95 });
            });
            layer.on('mouseout', () => layer.setStyle(styleFor(layer.feature)));
            layer.on('click', () => selectProvince(displayName));
        },
    }).addTo(map);

    ready.value = true;
});

onBeforeUnmount(() => {
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    map?.remove();
    map = null;
});
</script>

<style scoped>
.rmap-wrap { position: relative; width: 100%; border-radius: 16px; overflow: hidden; background: #f8fafc; }
.rmap      { width: 100%; height: 100%; }
.rmap :deep(.leaflet-container) { background: #f1f5f9; font-family: inherit; }

/* Controls */
.rmap-controls {
    position: absolute; top: 14px; left: 14px; right: 14px; z-index: 500;
    display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; pointer-events: none;
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

/* Legend */
.rmap-legend {
    position: absolute; bottom: 16px; left: 14px; z-index: 500; width: 234px;
    background: rgba(255,255,255,0.96); backdrop-filter: blur(8px);
    border: 1px solid #e8edf3; border-radius: 14px; box-shadow: 0 8px 28px rgba(15,23,42,0.12); overflow: hidden;
}
.rmap-legend-toggle {
    width: 100%; display: flex; align-items: center; justify-content: space-between;
    padding: 11px 14px; background: none; border: none; cursor: pointer;
    font-size: 13px; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; color: #1e293b;
}
.rmap-legend-toggle svg { width: 16px; height: 16px; color: #64748b; transition: transform 0.2s ease; }
.rmap-legend-body { max-height: 300px; overflow-y: auto; padding: 4px 8px 10px; }
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

.rmap-fs {
    position: absolute; bottom: 16px; right: 58px; z-index: 500; width: 38px; height: 38px;
    display: grid; place-items: center; border: 1px solid #e2e8f0; background: rgba(255,255,255,0.92);
    color: #475569; border-radius: 10px; cursor: pointer; box-shadow: 0 1px 3px rgba(15,23,42,0.08);
}
.rmap-fs:hover { color: #1e3a8a; }
.rmap-fs svg { width: 17px; height: 17px; }

.rmap-panel-enter-active, .rmap-panel-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.rmap-panel-enter-from, .rmap-panel-leave-to { opacity: 0; transform: translateY(-6px); }
</style>

<style>
/* Tooltip (Leaflet renders these outside the scoped component root) */
.rmap-tooltip { background: #0f172a !important; border: none !important; box-shadow: 0 6px 20px rgba(0,0,0,0.25) !important; border-radius: 8px !important; padding: 0 !important; }
.rmap-tooltip::before { display: none !important; }
.rmap-tip { display: flex; flex-direction: column; gap: 1px; padding: 7px 11px; }
.rmap-tip strong { color: #fff; font-size: 13px; font-weight: 700; }
.rmap-tip span { font-size: 11.5px; font-weight: 600; }
</style>
