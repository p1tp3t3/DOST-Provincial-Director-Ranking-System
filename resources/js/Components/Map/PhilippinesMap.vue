<template>
    <div ref="wrapEl" class="ph-map-wrap">
        <div ref="mapEl" class="ph-map"></div>

        <!-- Navigation panel -->
        <div class="map-nav" v-if="ready">
            <select v-model="selRegion" class="map-nav-sel" @change="onRegionChange">
                <option value="">All Regions</option>
                <option v-for="r in regionList" :key="r.code" :value="r.code">{{ r.label }}</option>
            </select>
            <select v-model="selProvince" class="map-nav-sel" @change="onProvinceChange">
                <option value="">— Select Province —</option>
                <option v-for="p in visibleProvinces" :key="p.name" :value="p.name">{{ p.name }}</option>
            </select>
            <button class="map-nav-reset" @click="resetView" title="Reset view">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
                </svg>
            </button>
        </div>

        <!-- Info Panel -->
        <transition name="panel-slide">
            <div v-if="panel" class="map-info-panel">
                <!-- Province panel -->
                <template v-if="panel.type === 'province'">
                    <div class="panel-header" :style="{ borderLeftColor: panel.tierColor }">
                        <div class="panel-header-main">
                            <div class="panel-title">{{ panel.name }}</div>
                            <span class="panel-cat-chip" :style="{ color: catColor(panel.category), background: catBg(panel.category) }">
                                {{ panel.category?.toUpperCase() }}
                            </span>
                        </div>
                        <button class="panel-close" @click="panel = null">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <div class="panel-body">
                        <div class="panel-score-block">
                            <div class="panel-score-num" :style="{ color: panel.tierColor }">{{ panel.score }}%</div>
                            <div class="panel-score-bar-wrap">
                                <div class="panel-score-bar" :style="{ width: `${Math.min(panel.score, 200) / 200 * 100}%`, background: panel.tierColor }"></div>
                            </div>
                            <div class="d-flex align-items-center gap-2" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <div class="panel-tier-label" :style="{ color: panel.tierColor }">{{ panel.tier }}</div>
                                <div class="panel-meta-chips">
                                    <span v-if="panel.rank" class="panel-meta-chip panel-rank-chip">#{{ panel.rank }}</span>
                                    <span v-if="panel.year" class="panel-meta-chip panel-year-chip">{{ panel.year }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="panel-divider"></div>

                        <div class="panel-row">
                            <span class="panel-label">Director</span>
                            <span class="panel-value">{{ panel.director || '—' }}</span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-label">Indicators tracked</span>
                            <span class="panel-value">{{ panel.count }}</span>
                        </div>

                        <template v-if="panel.bestKpi || panel.worstKpi">
                            <div class="panel-divider"></div>
                            <div class="panel-kpi-highlights">
                                <div v-if="panel.bestKpi" class="panel-kpi-row">
                                    <div class="panel-kpi-badge" style="background:#dcfce7;color:#15803d;">▲ KPI {{ panel.bestKpi.id }}</div>
                                    <div class="panel-kpi-info">
                                        <div class="panel-kpi-title">{{ panel.bestKpi.title }}</div>
                                        <div class="panel-kpi-score" style="color:#15803d;">{{ panel.bestKpi.score }}%</div>
                                    </div>
                                </div>
                                <div v-if="panel.worstKpi" class="panel-kpi-row">
                                    <div class="panel-kpi-badge" style="background:#fee2e2;color:#b91c1c;">▼ KPI {{ panel.worstKpi.id }}</div>
                                    <div class="panel-kpi-info">
                                        <div class="panel-kpi-title">{{ panel.worstKpi.title }}</div>
                                        <div class="panel-kpi-score" style="color:#b91c1c;">{{ panel.worstKpi.score }}%</div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Region panel -->
                <template v-else-if="panel.type === 'region'">
                    <div class="panel-header panel-header-region">
                        <div class="panel-header-main">
                            <div class="panel-title">{{ panel.shortLabel }}</div>
                            <div class="panel-subtitle">{{ panel.provinceCount }} province{{ panel.provinceCount !== 1 ? 's' : '' }} with data</div>
                        </div>
                        <button class="panel-close" @click="panel = null">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <div class="panel-body">
                        <div v-if="panel.provinceCount > 0">
                            <div class="panel-score-block">
                                <div class="panel-score-num" :style="{ color: tierColor(panel.avgScore) }">{{ panel.avgScore }}%</div>
                                <div class="panel-score-bar-wrap">
                                    <div class="panel-score-bar" :style="{ width: `${Math.min(panel.avgScore, 200) / 200 * 100}%`, background: tierColor(panel.avgScore) }"></div>
                                </div>
                                <div class="panel-tier-label" :style="{ color: tierColor(panel.avgScore) }">Regional Average</div>
                            </div>

                            <div class="panel-divider"></div>

                            <!-- Tier breakdown pills -->
                            <div class="panel-tier-pills">
                                <div class="panel-tier-pill" style="--tc:#15803d;">
                                    <span class="pill-count">{{ panel.tierCounts.top }}</span>
                                    <span class="pill-label">Top</span>
                                </div>
                                <div class="panel-tier-pill" style="--tc:#ca8a04;">
                                    <span class="pill-count">{{ panel.tierCounts.avg }}</span>
                                    <span class="pill-label">Average</span>
                                </div>
                                <div class="panel-tier-pill" style="--tc:#b91c1c;">
                                    <span class="pill-count">{{ panel.tierCounts.low }}</span>
                                    <span class="pill-label">Low</span>
                                </div>
                            </div>

                            <div class="panel-divider"></div>

                            <div v-if="panel.topProv" class="panel-row">
                                <span class="panel-label">Best</span>
                                <span class="panel-value" style="color:#15803d;">{{ panel.topProv.name }}<br><small>{{ panel.topProv.score }}%</small></span>
                            </div>
                            <div v-if="panel.lowProv" class="panel-row">
                                <span class="panel-label">Lowest</span>
                                <span class="panel-value" style="color:#b91c1c;">{{ panel.lowProv.name }}<br><small>{{ panel.lowProv.score }}%</small></span>
                            </div>
                        </div>
                        <div v-else class="panel-nodata">No score data for this region in the selected year / KPI filter.</div>
                    </div>
                </template>
            </div>
        </transition>

        <!-- Fullscreen toggle -->
        <button class="fs-btn" @click="toggleFullscreen" :title="isFullscreen ? 'Exit fullscreen' : 'Fullscreen'">
            <svg v-if="!isFullscreen" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/>
                <line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/>
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/>
                <line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/>
            </svg>
        </button>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    scores:       { type: Array,  default: () => [] },
    yearData:     { type: Object, default: () => ({}) },
    kpiOutcomes:  { type: Array,  default: () => [] },
    selectedYear: { type: Number, default: null },
    height:       { type: String, default: '460px' },
});

const GEO_TO_DB = {
    'Cebu':            'Cebu Province',
    'Cotabato':        'Cotabato (North)',
    'Samar':           'Samar (Western Samar)',
    'Dinagat Islands': 'Dinagat Island',
};
const SKIP = (name) => !name || name.includes('Not a Province') || name.includes('NCR,');

const REGION_LABELS = {
    100000000:  'Region I — Ilocos',
    200000000:  'Region II — Cagayan Valley',
    300000000:  'Region III — Central Luzon',
    400000000:  'Region IV-A — CALABARZON',
    1900000000: 'Region IV-B — MIMAROPA',
    500000000:  'Region V — Bicol',
    600000000:  'Region VI — Western Visayas',
    700000000:  'Region VII — Central Visayas',
    800000000:  'Region VIII — Eastern Visayas',
    900000000:  'Region IX — Zamboanga Peninsula',
    1000000000: 'Region X — Northern Mindanao',
    1100000000: 'Region XI — Davao Region',
    1200000000: 'Region XII — SOCCSKSARGEN',
    1400000000: 'CAR — Cordillera',
    1600000000: 'Region XIII — Caraga',
    1700000000: 'BARMM',
    1300000000: 'NCR',
};

const tierInfo = (score) => {
    if (score >= 100) return { color: '#15803d', label: 'Top Performing'    };
    if (score >= 70)  return { color: '#ca8a04', label: 'Average Performers' };
    return              { color: '#b91c1c', label: 'Low Performers'       };
};
const tierColor = (score) => tierInfo(score).color;

const catColor = (cat) => ({ micro:'#546e7a', small:'#00796b', medium:'#3949ab', large:'#5e35b1', cstc:'#c2185b' }[cat] ?? '#666');
const catBg    = (cat) => ({ micro:'#607d8b18', small:'#00968818', medium:'#3f51b518', large:'#673ab718', cstc:'#e91e6318' }[cat] ?? '#00000010');

// ── Refs ──────────────────────────────────────────────────────────────────────
const mapEl        = ref(null);
const wrapEl       = ref(null);
const isFullscreen = ref(false);
const ready        = ref(false);
const selRegion    = ref('');
const selProvince  = ref('');
const panel        = ref(null);

let map       = null;
let geoLayer  = null;
let pendingFly = false;

const provinces  = [];
const regionList = ref([]);

// ── Score helpers ─────────────────────────────────────────────────────────────
const scoreMap = () => {
    const m = {};
    for (const s of props.scores) m[s.province] = s;
    return m;
};

// ── Panel builders ────────────────────────────────────────────────────────────
const buildProvincePanel = (geoName) => {
    const sm     = scoreMap();
    const dbName = GEO_TO_DB[geoName] ?? geoName;
    const entry  = sm[dbName] ?? null;

    // Find best and worst KPI across all 7 KPIs regardless of current filter
    let bestKpi = null, worstKpi = null;
    if (props.yearData?.kpi && props.kpiOutcomes.length) {
        const kpiScores = [];
        for (const kpi of props.kpiOutcomes) {
            const kpiEntry = (props.yearData.kpi[kpi.id] ?? []).find(s => s.province === dbName);
            if (kpiEntry) kpiScores.push({ id: kpi.id, title: kpi.title, score: kpiEntry.score });
        }
        if (kpiScores.length) {
            kpiScores.sort((a, b) => b.score - a.score);
            bestKpi  = kpiScores[0];
            worstKpi = kpiScores[kpiScores.length - 1];
            if (bestKpi.id === worstKpi.id) worstKpi = null; // only 1 KPI with data
        }
    }

    if (!entry) return {
        type: 'province', name: geoName, score: null, tier: 'No data',
        tierColor: '#94a3b8', director: '—', count: 0, category: null,
        rank: null, year: props.selectedYear,
        bestKpi, worstKpi,
    };

    const info = tierInfo(entry.score);
    return {
        type:      'province',
        name:      geoName,
        category:  entry.category,
        director:  entry.director,
        score:     entry.score,
        count:     entry.count,
        tier:      info.label,
        tierColor: info.color,
        rank:      entry.rank ?? null,
        year:      props.selectedYear,
        bestKpi,
        worstKpi,
    };
};

const buildRegionPanel = (regionCode) => {
    const sm    = scoreMap();
    const label = REGION_LABELS[regionCode] ?? `Region ${regionCode}`;
    // short label: strip "Region X —" prefix for the header title
    const shortLabel = label.replace(/^Region [IVXLCD\d-]+\s*—\s*/i, '').trim() || label;

    const regionProvNames = provinces
        .filter(p => p.regionCode === regionCode)
        .map(p => GEO_TO_DB[p.name] ?? p.name);

    const regionScores = props.scores.filter(s => regionProvNames.includes(s.province));

    if (!regionScores.length) return {
        type: 'region', shortLabel, provinceCount: 0,
        avgScore: 0, tierCounts: { top: 0, avg: 0, low: 0 }, topProv: null, lowProv: null,
    };

    const avg    = Math.round(regionScores.reduce((a, s) => a + s.score, 0) / regionScores.length * 10) / 10;
    const sorted = [...regionScores].sort((a, b) => b.score - a.score);

    return {
        type:         'region',
        shortLabel,
        provinceCount: regionScores.length,
        avgScore:      avg,
        tierCounts: {
            top: regionScores.filter(s => s.score >= 100).length,
            avg: regionScores.filter(s => s.score >= 70 && s.score < 100).length,
            low: regionScores.filter(s => s.score < 70).length,
        },
        topProv: { name: sorted[0].province,                       score: sorted[0].score },
        lowProv: { name: sorted[sorted.length - 1].province,       score: sorted[sorted.length - 1].score },
    };
};

// ── Style ─────────────────────────────────────────────────────────────────────
const styleFor = (feature, sm, selReg, selProv) => {
    const name       = feature.properties.adm2_en;
    const regionCode = feature.properties.adm1_psgc;
    if (SKIP(name)) return { fillColor: '#e2e8f0', weight: 0.4, color: '#cbd5e1', fillOpacity: 0.25 };

    const dbName    = GEO_TO_DB[name] ?? name;
    const entry     = sm[dbName] ?? null;
    const baseColor = entry ? tierColor(entry.score) : '#94a3b8';

    const isSelectedProv = selProv && name === selProv;
    const isInSelRegion  = selReg  && regionCode === Number(selReg);
    const hasRegFilter   = !!selReg;
    const hasProvFilter  = !!selProv;

    if (isSelectedProv)
        return { fillColor: baseColor, weight: 3.5, color: '#fff', fillOpacity: 0.97 };

    if (hasRegFilter) {
        if (isInSelRegion)
            return hasProvFilter
                ? { fillColor: baseColor, weight: 0.8, color: '#fff', fillOpacity: 0.45 }
                : { fillColor: baseColor, weight: 1.2, color: '#fff', fillOpacity: 0.85 };
        return { fillColor: '#cbd5e1', weight: 0.3, color: '#e2e8f0', fillOpacity: 0.18 };
    }

    return { fillColor: baseColor, weight: 0.6, color: '#fff', fillOpacity: 0.78 };
};

const refreshStyle = () => {
    if (!geoLayer) return;
    const sm = scoreMap();
    geoLayer.setStyle(f => styleFor(f, sm, selRegion.value, selProvince.value));
};

// ── Province dropdown ─────────────────────────────────────────────────────────
const visibleProvinces = computed(() =>
    selRegion.value
        ? provinces.filter(p => p.regionCode === Number(selRegion.value))
        : provinces
);

// ── Smart fly ─────────────────────────────────────────────────────────────────
const HOME = { center: [12.2, 122.5], zoom: 5 };

const smartFlyTo = (targetBounds, { maxZoom = 9, padding = [50, 50] } = {}) => {
    if (!map) return;
    map.stop();
    const alreadyVisible = map.getBounds().contains(targetBounds);
    const atOverview     = map.getZoom() <= HOME.zoom + 0.5;

    if (alreadyVisible || atOverview) {
        map.flyToBounds(targetBounds, { padding, maxZoom, duration: 1.0, easeLinearity: 0.22 });
    } else {
        pendingFly = true;
        map.flyTo(HOME.center, HOME.zoom, { duration: 0.65, easeLinearity: 0.5 });
        map.once('moveend', () => {
            if (!pendingFly) return;
            pendingFly = false;
            setTimeout(() => map.flyToBounds(targetBounds, { padding, maxZoom, duration: 1.1, easeLinearity: 0.22 }), 60);
        });
    }
};

// ── Event handlers ────────────────────────────────────────────────────────────
const onRegionChange = () => {
    selProvince.value = '';
    pendingFly = false;
    refreshStyle();
    if (!selRegion.value) { resetView(); return; }

    const code = Number(selRegion.value);
    panel.value = buildRegionPanel(code);

    const regionProvs = provinces.filter(p => p.regionCode === code);
    if (!regionProvs.length) return;
    let bounds = L.latLngBounds(regionProvs[0].bounds);
    for (const p of regionProvs.slice(1)) bounds.extend(p.bounds);
    smartFlyTo(bounds, { maxZoom: 8, padding: [30, 30] });
};

const onProvinceChange = () => {
    pendingFly = false;
    refreshStyle();
    if (!selProvince.value) { panel.value = null; return; }
    panel.value = buildProvincePanel(selProvince.value);
    const prov = provinces.find(p => p.name === selProvince.value);
    if (prov) smartFlyTo(prov.bounds, { maxZoom: 9, padding: [50, 50] });
};

const resetView = () => {
    selRegion.value = selProvince.value = '';
    panel.value = null;
    pendingFly  = false;
    map?.stop();
    refreshStyle();
    map?.flyTo(HOME.center, HOME.zoom, { duration: 1.0, easeLinearity: 0.3 });
};

// ── Fullscreen ────────────────────────────────────────────────────────────────
const toggleFullscreen = () => {
    if (!document.fullscreenElement) wrapEl.value?.requestFullscreen();
    else document.exitFullscreen();
};
const onFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
    setTimeout(() => map?.invalidateSize(), 100);
};

// ── Mount ─────────────────────────────────────────────────────────────────────
onMounted(async () => {
    document.addEventListener('fullscreenchange', onFullscreenChange);

    map = L.map(mapEl.value, {
        center: HOME.center, zoom: HOME.zoom,
        zoomControl: false, attributionControl: true,
        scrollWheelZoom: false, zoomAnimation: true, fadeAnimation: true,
    });
    L.control.zoom({ position: 'bottomleft' }).addTo(map);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
        subdomains: 'abcd', maxZoom: 18,
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    }).addTo(map);

    const res     = await fetch('/geo/philippines-provinces.geojson');
    const geojson = await res.json();
    const sm      = scoreMap();

    const regionCodes = new Set();
    for (const feature of geojson.features) {
        const name = feature.properties.adm2_en;
        if (SKIP(name)) continue;
        const bounds     = L.geoJSON(feature).getBounds();
        const regionCode = feature.properties.adm1_psgc;
        provinces.push({ name, regionCode, bounds });
        regionCodes.add(regionCode);
    }
    provinces.sort((a, b) => a.name.localeCompare(b.name));
    regionList.value = [...regionCodes]
        .sort((a, b) => a - b)
        .filter(c => REGION_LABELS[c])
        .map(c => ({ code: c, label: REGION_LABELS[c] }));

    geoLayer = L.geoJSON(geojson, {
        style:         f => styleFor(f, sm, selRegion.value, selProvince.value),
        onEachFeature: (feature, layer) => {
            const name       = feature.properties.adm2_en;
            const regionCode = feature.properties.adm1_psgc;
            if (SKIP(name)) return;
            const dbName = GEO_TO_DB[name] ?? name;

            layer.on('mouseover', (e) => {
                const entry = scoreMap()[dbName] ?? null;
                const info  = entry ? tierInfo(entry.score) : null;
                layer.bindTooltip(
                    `<div class="map-tip">
                        <strong>${name}</strong>
                        ${info
                            ? `<span style="color:${info.color};font-weight:600;">${info.label}</span>
                               <span class="map-tip-score">${entry.score}%</span>`
                            : `<span class="map-tip-nodata">No data</span>`}
                    </div>`,
                    { sticky: true, className: 'map-tooltip' }
                ).openTooltip(e.latlng);
                e.target.setStyle({ weight: 3, fillOpacity: 0.97 });
            });
            layer.on('mouseout', (e) => { geoLayer.resetStyle(e.target); });

            layer.on('click', () => {
                // Sync dropdowns
                if (regionCode !== Number(selRegion.value)) {
                    selRegion.value = String(regionCode);
                }
                selProvince.value = name;
                pendingFly = false;
                refreshStyle();

                // Show province panel
                panel.value = buildProvincePanel(name);

                const prov = provinces.find(p => p.name === name);
                if (prov) smartFlyTo(prov.bounds, { maxZoom: 9, padding: [50, 50] });
            });
        },
    }).addTo(map);

    ready.value = true;
});

const rebuildPanel = () => {
    if (!panel.value) return;
    if (panel.value.type === 'province') panel.value = buildProvincePanel(panel.value.name);
    else if (panel.value.type === 'region') panel.value = buildRegionPanel(Number(selRegion.value));
};

watch(() => props.scores,   () => { refreshStyle(); rebuildPanel(); }, { deep: true });
watch(() => props.yearData, () => { rebuildPanel(); },                  { deep: true });

onBeforeUnmount(() => {
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    map?.remove();
});
</script>

<style scoped>
.ph-map-wrap { position: relative; width: 100%; }

.ph-map {
    height: v-bind(height); width: 100%; z-index: 0;
    border-radius: 0 0 8px 8px;
}
.ph-map-wrap:fullscreen .ph-map,
.ph-map-wrap:-webkit-full-screen .ph-map { height: 100vh; border-radius: 0; }

/* ── Nav panel ─────────────────────────────── */
.map-nav {
    position: absolute; top: 10px; left: 10px; z-index: 1000;
    display: flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.95); border: 1px solid #e2e8f0;
    border-radius: 8px; padding: 6px 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.12); backdrop-filter: blur(4px);
}
.map-nav-sel {
    height: 30px; padding: 0 24px 0 8px; font-size: 12px; font-family: inherit;
    border: 1px solid #e2e8f0; border-radius: 5px;
    background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2394a3b8'/%3E%3C/svg%3E") no-repeat right 8px center;
    appearance: none; color: #334155; cursor: pointer; min-width: 160px;
    outline: none; transition: border-color 0.15s;
}
.map-nav-sel:focus { border-color: #6366f1; }
.map-nav-reset {
    width: 30px; height: 30px; padding: 5px; background: #f1f5f9;
    border: 1px solid #e2e8f0; border-radius: 5px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #64748b; flex-shrink: 0; transition: background 0.15s, color 0.15s;
}
.map-nav-reset:hover { background: #e2e8f0; color: #1e293b; }
.map-nav-reset svg { width: 14px; height: 14px; }

/* ── Info panel ────────────────────────────── */
.map-info-panel {
    position: absolute; bottom: 28px; right: 10px; z-index: 1000;
    width: 290px;
    background: rgba(255,255,255,0.97);
    border: 1px solid #e2e8f0; border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.14);
    backdrop-filter: blur(6px);
    overflow: hidden;
    font-family: inherit;
}
.panel-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 8px; padding: 14px 14px 11px;
    border-bottom: 1px solid #f1f5f9;
    border-left: 4px solid #94a3b8;
}
.panel-header-region { border-left-color: #6366f1; }
.panel-header-main   { flex: 1; min-width: 0; }
.panel-title         { font-size: 14px; font-weight: 700; color: #1e293b; line-height: 1.3; }
.panel-subtitle      { font-size: 11.5px; color: #94a3b8; margin-top: 2px; }
.panel-cat-chip {
    display: inline-block; margin-top: 5px;
    font-size: 10.5px; font-weight: 700; letter-spacing: 0.04em;
    padding: 2px 8px; border-radius: 20px;
}
.panel-close {
    flex-shrink: 0; width: 22px; height: 22px; padding: 3px;
    background: none; border: none; cursor: pointer; color: #94a3b8;
    display: flex; align-items: center; justify-content: center;
    border-radius: 4px; transition: background 0.12s, color 0.12s;
}
.panel-close:hover { background: #f1f5f9; color: #334155; }
.panel-close svg    { width: 14px; height: 14px; }

.panel-body { padding: 12px 14px 14px; display: flex; flex-direction: column; gap: 9px; }

.panel-score-block { display: flex; flex-direction: column; gap: 5px; }
.panel-score-num   { font-size: 26px; font-weight: 800; line-height: 1; }
.panel-score-bar-wrap {
    height: 7px; background: #f1f5f9; border-radius: 4px; overflow: hidden;
}
.panel-score-bar {
    height: 100%; border-radius: 4px;
    transition: width 0.5s ease;
}
.panel-tier-label { font-size: 12px; font-weight: 600; }

.panel-meta-chips  { display: flex; align-items: center; gap: 4px; }
.panel-meta-chip   { font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 20px; }
.panel-rank-chip   { background: #ede9fe; color: #5b21b6; }
.panel-year-chip   { background: #f1f5f9; color: #64748b; }

.panel-divider { height: 1px; background: #f1f5f9; margin: 0 -14px; }

.panel-row {
    display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;
}
.panel-label { font-size: 11px; color: #94a3b8; font-weight: 500; flex-shrink: 0; }
.panel-value { font-size: 12px; color: #334155; font-weight: 600; text-align: right; line-height: 1.4; }
.panel-value small { font-weight: 400; font-size: 10.5px; }

.panel-tier-pills {
    display: flex; gap: 6px;
}
.panel-tier-pill {
    flex: 1; display: flex; flex-direction: column; align-items: center;
    padding: 6px 4px; border-radius: 6px;
    background: color-mix(in srgb, var(--tc) 10%, transparent);
    border: 1px solid color-mix(in srgb, var(--tc) 20%, transparent);
}
.pill-count { font-size: 16px; font-weight: 800; color: var(--tc); line-height: 1; }
.pill-label { font-size: 9px;  font-weight: 600; color: var(--tc); opacity: 0.85; margin-top: 2px; }

.panel-nodata { font-size: 11px; color: #94a3b8; font-style: italic; text-align: center; padding: 8px 0; }

.panel-kpi-highlights { display: flex; flex-direction: column; gap: 7px; }
.panel-kpi-row        { display: flex; align-items: flex-start; gap: 8px; }
.panel-kpi-badge      { font-size: 9.5px; font-weight: 700; padding: 3px 7px; border-radius: 4px; flex-shrink: 0; white-space: nowrap; margin-top: 1px; }
.panel-kpi-info       { flex: 1; min-width: 0; }
.panel-kpi-title      { font-size: 11px; color: #475569; line-height: 1.35; word-break: break-word; }
.panel-kpi-score      { font-size: 12px; font-weight: 700; line-height: 1.4; margin-top: 1px; }

/* ── Fullscreen button ─────────────────────── */
.fs-btn {
    position: absolute; top: 10px; right: 10px; z-index: 1000;
    width: 32px; height: 32px; padding: 6px;
    background: rgba(255,255,255,0.95); border: 1px solid #e2e8f0; border-radius: 6px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 1px 4px rgba(0,0,0,0.12); color: #475569;
    backdrop-filter: blur(4px); transition: background 0.15s, color 0.15s;
}
.fs-btn:hover { background: #f1f5f9; color: #1e293b; }
.fs-btn svg   { width: 16px; height: 16px; }

.ph-map-wrap:fullscreen .map-nav,
.ph-map-wrap:-webkit-full-screen .map-nav { top: 14px; left: 14px; }
.ph-map-wrap:fullscreen .fs-btn,
.ph-map-wrap:-webkit-full-screen .fs-btn  { top: 14px; right: 14px; }
.ph-map-wrap:fullscreen .map-info-panel,
.ph-map-wrap:-webkit-full-screen .map-info-panel { bottom: 32px; right: 14px; width: 300px; }

/* ── Panel slide-in transition ─────────────── */
.panel-slide-enter-active { transition: transform 0.22s ease, opacity 0.22s ease; }
.panel-slide-leave-active { transition: transform 0.18s ease, opacity 0.18s ease; }
.panel-slide-enter-from   { transform: translateX(20px); opacity: 0; }
.panel-slide-leave-to     { transform: translateX(20px); opacity: 0; }
</style>

<style>
.map-tooltip {
    background: #fff; border: none; border-radius: 6px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15); padding: 0;
}
.map-tooltip .leaflet-tooltip-content { padding: 0; }
.map-tip {
    display: flex; flex-direction: column; gap: 2px;
    padding: 8px 12px; font-family: inherit; font-size: 12px; min-width: 140px;
}
.map-tip strong  { font-size: 13px; margin-bottom: 2px; }
.map-tip-score   { color: #64748b; font-size: 11px; }
.map-tip-nodata  { color: #94a3b8; font-style: italic; }
</style>
