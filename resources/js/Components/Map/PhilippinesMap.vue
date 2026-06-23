<template>
    <div ref="wrapEl" class="ph-map-root">
        <!-- Island / Region / Province filters are operated by the top sticky
             filter bar in the parent dashboard. The map only renders here. -->
        <div class="ph-map-wrap">
        <div ref="mapEl" class="ph-map"></div>

        <!-- Info Panel -->
        <transition name="panel-slide">
            <div v-if="panel" class="map-info-panel" :class="{ 'map-info-panel--region': panel.type === 'region', 'map-info-panel--island': panel.type === 'island' }">
                <!-- Province / CSTC panel (same structure) -->
                <template v-if="panel.type === 'province' || panel.type === 'cstc'">
                    <div class="panel-header">
                        <div class="panel-header-main">
                            <div class="panel-caption">
                                <span class="panel-caption-tier" :style="{ color: catColor(panel.category) }">{{ panel.category?.toUpperCase() }}</span>
                                <span class="panel-caption-sep">·</span>
                                <span class="panel-caption-year">{{ panel.year }}</span>
                            </div>
                            <a v-if="panel.provinceUrlId" :href="`/province-directories/${panel.provinceUrlId}`" class="panel-title panel-name-link">{{ panel.name }}</a>
                            <div v-else class="panel-title">{{ panel.name }}</div>
                            <div v-if="panel.region" class="panel-region-tag">{{ panel.region }}</div>
                            <a v-if="panel.directorId" :href="`/profile/${panel.directorId}`" class="panel-subtitle panel-name-link">{{ panel.director }}</a>
                            <div v-else class="panel-subtitle">{{ panel.director || 'Vacant' }}</div>
                        </div>
                        <button class="panel-close" @click="panel = null">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <div class="panel-body">
                        <!-- Hero: score + inline status -->
                        <div class="panel-hero">
                            <div class="panel-hero-row">
                                <div class="panel-score-num" :style="{ color: panel.tierColor }">
                                    {{ panel.score != null ? panel.score + '%' : '-' }}
                                </div>
                                <div class="panel-hero-status" :style="{ color: panel.tierColor }">
                                    {{ panel.tier }}<span v-if="panel.rank" class="panel-hero-rank">#{{ panel.rank }}</span>
                                </div>
                            </div>
                            <div class="panel-score-bar-wrap" v-if="panel.score != null">
                                <div class="panel-score-bar" :style="{ width: `${Math.min(panel.score, 100)}%`, background: panel.tierColor }"></div>
                            </div>
                        </div>

                        <!-- Category breakdown: all three rows in matrix-weight order -->
                        <div v-if="panel.categories?.length" class="panel-section">
                            <div class="panel-section-label">Category breakdown</div>
                            <div class="cat-rows">
                                <div v-for="c in panel.categories" :key="c.code"
                                     class="cat-row"
                                     :class="{ 'cat-row--top': c.isStrongest, 'cat-row--bottom': c.isWeakest }">
                                    <span class="cat-row-marker">{{ c.isStrongest ? '▲' : c.isWeakest ? '▼' : '·' }}</span>
                                    <span class="cat-row-name">{{ c.name }}</span>
                                    <span class="cat-row-score">{{ c.score.toFixed(1) }}%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Trend sparkline: score across all reporting years -->
                        <div v-if="panel.trend" class="panel-section">
                            <div class="panel-section-header">
                                <span class="panel-section-label">Score over time</span>
                                <span class="trend-delta" :style="{ color: panel.trend.delta >= 0 ? '#15803d' : '#b91c1c' }">
                                    {{ panel.trend.delta >= 0 ? '▲' : '▼' }} {{ Math.abs(panel.trend.delta).toFixed(1) }}%
                                </span>
                            </div>
                            <svg viewBox="0 0 100 42" preserveAspectRatio="none" class="sparkline">
                                <line x1="0" y1="23" x2="100" y2="23" stroke="#e5e7eb" stroke-width="0.3" stroke-dasharray="1,1" />
                                <polyline
                                    :points="panel.trend.polyline"
                                    fill="none"
                                    :stroke="panel.trend.lineColor"
                                    stroke-width="1.6"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <circle
                                    v-for="p in panel.trend.points" :key="p.year"
                                    :cx="p.x" :cy="p.y"
                                    :r="p.isActive ? 2.4 : 1.5"
                                    :fill="p.isActive ? '#fff' : (p.bucket ? BUCKET_COLOR[p.bucket] : '#94a3b8')"
                                    :stroke="p.isActive ? (p.bucket ? BUCKET_COLOR[p.bucket] : '#94a3b8') : 'none'"
                                    :stroke-width="p.isActive ? 1.5 : 0"
                                >
                                    <title>{{ p.year }}: {{ p.total_pct?.toFixed?.(2) ?? p.pct.toFixed(2) }}%{{ p.bucket ? ' (' + p.bucket + ')' : '' }}{{ p.rank ? ' · #' + p.rank : '' }}</title>
                                </circle>
                            </svg>
                            <div class="panel-trend-years">
                                <span v-for="p in panel.trend.points" :key="p.year" :class="{ 'panel-trend-year-active': p.isActive }">
                                    {{ p.year }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Island panel -->
                <template v-else-if="panel.type === 'island'">
                    <div class="panel-header panel-header-island">
                        <div class="panel-header-main">
                            <div class="panel-title-row">
                                <button class="panel-expand-btn" @click="regionListOpen = !regionListOpen" :class="{ 'panel-expand-btn--active': regionListOpen }" title="View all regions">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                                </button>
                                <div class="panel-title">{{ panel.label }}</div>
                            </div>
                            <div class="panel-subtitle">{{ panel.provinceCount }} province{{ panel.provinceCount !== 1 ? 's' : '' }} · {{ panel.regionCount }} region{{ panel.regionCount !== 1 ? 's' : '' }}</div>
                        </div>
                        <button class="panel-close" @click="panel = null; regionListOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <div class="panel-body">
                        <div v-if="panel.provinceCount > 0">
                            <div class="panel-score-block">
                                <div class="panel-score-num" :style="{ color: panel.avgColor }">{{ panel.avgScore }}%</div>
                                <div class="panel-score-bar-wrap">
                                    <div class="panel-score-bar" :style="{ width: `${Math.min(panel.avgScore, 100)}%`, background: panel.avgColor }"></div>
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <div class="panel-tier-label" :style="{ color: panel.avgColor }">Island Average</div>
                                    <div v-if="panel.rank" class="panel-meta-chips">
                                        <span class="panel-meta-chip panel-rank-chip">#{{ panel.rank }} of {{ panel.totalIslands }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="panel-divider"></div>

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
                        <div v-else class="panel-nodata">No score data for this island in the selected year / KPI filter.</div>
                    </div>
                </template>

                <!-- Region panel -->
                <template v-else-if="panel.type === 'region'">
                    <div class="panel-header panel-header-region">
                        <div class="panel-header-main">
                            <div class="panel-title-row">
                                <button class="panel-expand-btn" @click="provinceListOpen = !provinceListOpen" :class="{ 'panel-expand-btn--active': provinceListOpen }" title="View all provinces">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                                </button>
                                <div class="panel-title">{{ panel.shortLabel }}</div>
                            </div>
                            <div class="panel-subtitle">{{ panel.provinceCount }} province{{ panel.provinceCount !== 1 ? 's' : '' }} with data</div>
                        </div>
                        <button class="panel-close" @click="panel = null; provinceListOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <div class="panel-body">
                        <div v-if="panel.provinceCount > 0">
                            <div class="panel-score-block">
                                <div class="panel-score-num" :style="{ color: panel.avgColor }">{{ panel.avgScore }}%</div>
                                <div class="panel-score-bar-wrap">
                                    <div class="panel-score-bar" :style="{ width: `${Math.min(panel.avgScore, 100)}%`, background: panel.avgColor }"></div>
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <div class="panel-tier-label" :style="{ color: panel.avgColor }">Regional Average</div>
                                    <div v-if="panel.rank" class="panel-meta-chips">
                                        <span class="panel-meta-chip panel-rank-chip">#{{ panel.rank }} of {{ panel.totalRegions }}</span>
                                    </div>
                                </div>
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

        <!-- Region list panel (left side, shown when island is selected) -->
        <transition name="panel-slide-left">
            <div v-if="panel && panel.type === 'island' && regionListOpen" class="map-province-list-panel">
                <div class="plist-header">
                    <div class="plist-header-main">
                        <div class="plist-subtitle">All Regions</div>
                    </div>
                    <button class="panel-close" @click="regionListOpen = false">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="plist-body">
                    <div v-for="region in panel.allRegions" :key="region.code"
                         class="plist-item"
                         @click="selectRegionFromList(region.code)">
                        <div class="plist-dot" :style="{ background: region.color }"></div>
                        <div class="plist-info">
                            <span class="plist-name">{{ region.label }}</span>
                            <span class="plist-director">{{ region.provinceCount }} province{{ region.provinceCount !== 1 ? 's' : '' }}</span>
                        </div>
                        <span class="plist-score" :style="{ color: region.color }">
                            {{ region.score != null ? region.score + '%' : '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Province list panel (left side, shown when region is selected) -->
        <transition name="panel-slide-left">
            <div v-if="panel && panel.type === 'region' && provinceListOpen" class="map-province-list-panel">
                <div class="plist-header">
                    <div class="plist-header-main">
                        <div class="plist-subtitle">Provinces</div>
                    </div>
                    <button class="panel-close" @click="provinceListOpen = false">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="plist-body">
                    <div v-for="prov in panel.allProvinces" :key="prov.name"
                         class="plist-item"
                         @click="selectProvinceFromList(prov.name)">
                        <div class="plist-dot" :style="{ background: prov.color }"></div>
                        <div class="plist-info">
                            <span class="plist-name">{{ prov.name }}</span>
                            <span class="plist-director">{{ prov.director || 'Vacant' }}</span>
                        </div>
                        <span class="plist-score" :style="{ color: prov.color }">
                            {{ prov.score != null ? prov.score + '%' : '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Reset view -->
        <button class="reset-btn" @click="resetView" title="Reset map">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"/>
                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
            </svg>
        </button>

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
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    scores:           { type: Array,  default: () => [] },
    // Broader pool used to compute Island / Region rankings. When the caller
    // narrows `scores` by island or region (e.g. dashboard's geo filter), the
    // map would otherwise rank a single island as "#1 of 1". Callers should
    // pass an array filtered only by tier/category so an island's rank is
    // always taken against all 3 islands, and a region's rank against every
    // region in its island. Falls back to `scores` so existing callers that
    // don't supply this prop continue to work.
    rankingPool:      { type: Array,  default: null },
    trends:           { type: Object, default: () => ({}) },  // province name → [{year, total_pct, bucket, ...}]
    selectedYear:     { type: Number, default: null },
    selectedTier:     { type: String, default: 'all' },
    height:           { type: String, default: '460px' },
    // External filter values driven by the dashboard's sticky filter bar.
    // 'all' / '' maps to "no filter" - the watchers below translate dashboard
    // formats ('Luzon', 'Region I', 'Bohol') to the map's internal formats
    // ('luzon', region code 100000000, 'Bohol') and trigger the existing
    // smart-fly logic so the map zooms to match the picked scope.
    island:           { type: String, default: 'all' },
    region:           { type: String, default: 'all' },
    province:         { type: String, default: 'all' },
});

const GEO_TO_DB = {
    'Cebu':            'Cebu Province',
    'Cotabato':        'Cotabato (North)',
    'Samar':           'Samar (Western Samar)',
    'Dinagat Islands': 'Dinagat Island',
};
const SKIP = (name) => !name || name.includes('Not a Province') || name.includes('NCR,');

// Region-code (adm1_psgc) overrides applied to the basemap so the choropleth
// matches the DOST region scheme rather than the GeoJSON's PSGC vintage:
//   • Negros Occidental/Oriental + Siquijor → Negros Island Region (XVIII)
//   • Sulu → Region IX (it sits under BARMM in PSGC, but DOST tracks it in IX)
const NIR_CODE = 1800000000;
const REGION_CODE_OVERRIDE = {
    'Negros Occidental': NIR_CODE,
    'Negros Oriental':   NIR_CODE,
    'Siquijor':          NIR_CODE,
    'Sulu':              900000000,
};

const REGION_LABELS = {
    100000000:  'Region I - Ilocos',
    200000000:  'Region II - Cagayan Valley',
    300000000:  'Region III - Central Luzon',
    400000000:  'Region IV-A - CALABARZON',
    1700000000: 'Region IV-B - MIMAROPA',
    500000000:  'Region V - Bicol',
    600000000:  'Region VI - Western Visayas',
    700000000:  'Region VII - Central Visayas',
    800000000:  'Region VIII - Eastern Visayas',
    [NIR_CODE]: 'NIR - Negros Island Region',
    900000000:  'Region IX - Zamboanga Peninsula',
    1000000000: 'Region X - Northern Mindanao',
    1100000000: 'Region XI - Davao Region',
    1200000000: 'Region XII - SOCCSKSARGEN',
    1400000000: 'CAR - Cordillera',
    1600000000: 'Region XIII - Caraga',
    1300000000: 'NCR',
};

const REGION_TO_ISLAND = {
    100000000:  'luzon',    // Region I - Ilocos
    200000000:  'luzon',    // Region II - Cagayan Valley
    300000000:  'luzon',    // Region III - Central Luzon
    400000000:  'luzon',    // Region IV-A - CALABARZON
    1700000000: 'luzon',    // Region IV-B - MIMAROPA
    500000000:  'luzon',    // Region V - Bicol
    1400000000: 'luzon',    // CAR - Cordillera
    1300000000: 'luzon',    // NCR
    600000000:  'visayas',  // Region VI - Western Visayas
    700000000:  'visayas',  // Region VII - Central Visayas
    800000000:  'visayas',  // Region VIII - Eastern Visayas
    [NIR_CODE]: 'visayas',  // NIR - Negros Island Region
    900000000:  'mindanao', // Region IX - Zamboanga Peninsula
    1000000000: 'mindanao', // Region X - Northern Mindanao
    1100000000: 'mindanao', // Region XI - Davao Region
    1200000000: 'mindanao', // Region XII - SOCCSKSARGEN
    1600000000: 'mindanao', // Region XIII - Caraga
    1900000000: 'mindanao', // BARMM (no DOST PSTD provinces; not a selectable region)
};

const ISLANDS = [
    { value: 'luzon',    label: 'Luzon'    },
    { value: 'visayas',  label: 'Visayas'  },
    { value: 'mindanao', label: 'Mindanao' },
];

// CSTC clusters are first-class peers of provinces inside the ranking data, but
// they don't have province features in the basemap (NCR has no province polygons
// at all; ZCIC and Davao City overlap their parent province polygons). This
// lookup lets region/island aggregates, the region panel, and the geo-filter
// styling treat clusters as members of their parent region.
const CSTC_REGION_CODE = {
    'CAMANAVA':    1300000000,  // NCR
    'MUNTAPARLAS': 1300000000,
    'PAMAMAZON':   1300000000,
    'PAMAMARISAN': 1300000000,
    'ZCIC':         900000000,  // Region IX - Zamboanga Peninsula
    'Davao City':  1100000000,  // Region XI - Davao Region
};
const cstcNamesForRegion = (regionCode) =>
    Object.entries(CSTC_REGION_CODE)
        .filter(([, code]) => code === regionCode)
        .map(([name]) => name);

// The province's bucket (Top / Average / Under) drives color for individual
// provinces, matching RankingService's rank-percentile bucketing. For
// aggregates (region / island averages) the bucket field doesn't apply, so we
// fall back to score-threshold coloring.
// Gold / silver / bronze - matches the podium, charts, and leaderboard buckets.
const BUCKET_COLOR = { Top: '#ca8a04', Average: '#64748b', Low: '#9a3412' };
const BUCKET_LABEL = { Top: 'Top Performers', Average: 'Average Performers', Low: 'Low Performers' };
const tierInfo = (entry) => {
    if (!entry) return { color: '#ffffff', label: 'No data' };
    if (entry.status === 'no_director') return { color: '#ffffff', label: 'No director assigned' };
    if (entry.status === 'no_data')     return { color: '#ffffff', label: 'No data submitted' };
    if (entry.bucket == null)           return { color: '#ffffff', label: 'Unbucketed' };
    if (!entry.score)                   return { color: '#ffffff', label: 'No score' };
    return { color: BUCKET_COLOR[entry.bucket] ?? '#ffffff', label: BUCKET_LABEL[entry.bucket] ?? '-' };
};
const tierColor = (entry) => tierInfo(entry).color;

// Score-threshold coloring for raw averages (regions, islands). Calibrated so
// a typical weighted score range (~0-70%) splits roughly into thirds.
const scoreColor = (score) => {
    if (!score)      return '#ffffff';   // 0 / no data -> white
    if (score >= 50) return '#ca8a04';   // gold
    if (score >= 25) return '#64748b';   // silver
    return '#9a3412';                    // bronze
};

// Full per-category breakdown for the side panel. Always returns CORE → STRATEGIC
// → SUPPORT in matrix-weight order, with isStrongest/isWeakest flags so the
// panel can mark the leader and laggard with arrow markers.
const CATEGORY_FULL_NAMES = { CORE: 'Core', STRATEGIC: 'Strategic', SUPPORT: 'Support' };
const CATEGORY_ORDER      = ['CORE', 'STRATEGIC', 'SUPPORT'];
const categoryBreakdown = (entry) => {
    if (!entry?.subtotals_pct) return [];
    const list = CATEGORY_ORDER
        .filter(code => entry.subtotals_pct[code] !== undefined)
        .map(code => ({
            code,
            name:  CATEGORY_FULL_NAMES[code] ?? code,
            score: Math.round(entry.subtotals_pct[code] * 10) / 10,
        }));
    if (list.length === 0) return [];
    const sorted = [...list].sort((a, b) => b.score - a.score);
    const top  = sorted[0]?.code;
    const bot  = sorted.length > 1 ? sorted[sorted.length - 1].code : null;
    return list.map(c => ({
        ...c,
        isStrongest: c.code === top  && c.score > 0,
        isWeakest:   c.code === bot  && bot !== top,
    }));
};

// Build the sparkline points (SVG viewBox coordinates 0..100 × 0..30) for a
// province's year-by-year history. Returns null when there's no history or
// just one data point (a single dot isn't a trend).
const buildTrend = (provinceName) => {
    const history = props.trends?.[provinceName];
    if (!history || history.length < 2) return null;

    const xs = history.map(h => h.year);
    const minX = Math.min(...xs);
    const maxX = Math.max(...xs);
    const xSpan = Math.max(1, maxX - minX);

    // Y axis: 0..100% always, so trends are visually comparable across provinces
    const PAD_TOP = 4, PAD_BOTTOM = 8, H = 42;
    const yFor = (pct) => PAD_TOP + (1 - Math.min(100, Math.max(0, pct)) / 100) * (H - PAD_TOP - PAD_BOTTOM);

    const points = history.map(h => ({
        year:    h.year,
        pct:     h.total_pct,
        bucket:  h.bucket,
        status:  h.status,
        rank:    h.rank,
        x:       ((h.year - minX) / xSpan) * 92 + 4,   // 4..96 inside the 100-wide viewBox
        y:       yFor(h.total_pct),
        isActive: h.year === props.selectedYear,
    }));

    const polyline = points.map(p => `${p.x.toFixed(2)},${p.y.toFixed(2)}`).join(' ');
    const lastBucket = points[points.length - 1].bucket;
    const lineColor  = BUCKET_COLOR[lastBucket] ?? '#94a3b8';

    // Direction arrow: did this province climb or drop vs first year?
    const firstPct = points[0].pct;
    const lastPct  = points[points.length - 1].pct;
    const delta    = Math.round((lastPct - firstPct) * 10) / 10;

    return { points, polyline, lineColor, delta, firstPct, lastPct, H };
};

const catColor = (cat) => ({ micro:'#546e7a', small:'#00796b', medium:'#3949ab', large:'#5e35b1' }[cat] ?? '#666');
const catBg    = (cat) => ({ micro:'#607d8b18', small:'#00968818', medium:'#3f51b518', large:'#673ab718' }[cat] ?? '#00000010');

// ── Refs ──────────────────────────────────────────────────────────────────────
const mapEl        = ref(null);
const wrapEl       = ref(null);
const isFullscreen = ref(false);
const ready        = ref(false);
const selIsland    = ref('');
const selRegion    = ref('');
const selProvince  = ref('');
const selCstc      = ref('');
const panel              = ref(null);
const provinceListOpen   = ref(false);
const regionListOpen     = ref(false);

let map       = null;
let geoLayer  = null;
let cstcLayer = null;
let pendingFly = false;

const provinces  = [];
const regionList = ref([]);
const cstcList   = ref([]);
let   cstcData   = {};

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
    const trend  = buildTrend(dbName);

    if (!entry) return {
        type: 'province', name: geoName, score: null, tier: 'No data',
        tierColor: '#94a3b8', director: '-', category: null,
        rank: null, year: props.selectedYear, categories: [], trend,
        region: null, provinceUrlId: null, directorId: null,
    };

    const info = tierInfo(entry);
    return {
        type:          'province',
        name:          geoName,
        region:        entry.region ?? null,
        provinceUrlId: entry.province_url_id ?? null,
        category:      entry.category,
        director:      entry.director,
        directorId:    entry.director_id ?? null,
        status:        entry.status,
        score:         Math.round(entry.score * 100) / 100,
        tier:          info.label,
        tierColor:     info.color,
        rank:          entry.rank ?? null,
        year:          props.selectedYear,
        categories:    categoryBreakdown(entry),
        trend,
    };
};

// The pool used to compute Island/Region rankings. Falls back to `scores` if
// the caller didn't pass a separate `rankingPool` (back-compat with callers
// that don't narrow the scores prop by geo).
const rankingPool = () => props.rankingPool ?? props.scores;

// Average score per island across the 3 major islands, sorted desc. Always
// computed on the broader rankingPool so an island's rank reflects its place
// among ALL islands, never just the ones surviving the dashboard's geo filter.
const computeIslandRanking = () => {
    const pool = rankingPool();
    const poolByProvince = {};
    for (const s of pool) poolByProvince[s.province] = s;

    const buckets = new Map();
    for (const p of provinces) {
        const island = REGION_TO_ISLAND[p.regionCode];
        if (!island) continue;
        const dbName = GEO_TO_DB[p.name] ?? p.name;
        const entry  = poolByProvince[dbName];
        if (!entry) continue;
        if (!buckets.has(island)) buckets.set(island, []);
        buckets.get(island).push(entry.score);
    }
    // Include CSTC clusters in their parent island's bucket - Luzon's average
    // shouldn't silently exclude NCR just because NCR has no province polygons.
    for (const [cstcName, regionCode] of Object.entries(CSTC_REGION_CODE)) {
        const island = REGION_TO_ISLAND[regionCode];
        if (!island) continue;
        const entry = poolByProvince[cstcName];
        if (!entry) continue;
        if (!buckets.has(island)) buckets.set(island, []);
        buckets.get(island).push(entry.score);
    }
    const arr = [];
    for (const [island, scores] of buckets.entries()) {
        const avg = scores.reduce((a, b) => a + b, 0) / scores.length;
        arr.push({ island, avg: Math.round(avg * 10) / 10 });
    }
    arr.sort((a, b) => b.avg - a.avg);
    return arr;
};

const buildIslandPanel = (islandValue) => {
    const meta = ISLANDS.find(i => i.value === islandValue);
    const label = meta?.label ?? islandValue;

    const islandRegionCodes = Object.entries(REGION_TO_ISLAND)
        .filter(([, v]) => v === islandValue)
        .map(([code]) => Number(code));

    const islandProvs = provinces.filter(p => islandRegionCodes.includes(p.regionCode));
    const islandProvNames = islandProvs.map(p => GEO_TO_DB[p.name] ?? p.name);
    // CSTC clusters belonging to any of this island's regions count as island
    // members too (e.g. NCR's clusters under Luzon). Without this, Luzon would
    // skip NCR's scores in every aggregate on the panel.
    const islandCstcNames = Object.entries(CSTC_REGION_CODE)
        .filter(([, code]) => islandRegionCodes.includes(code))
        .map(([name]) => name);
    const islandMemberNames = [...islandProvNames, ...islandCstcNames];
    const islandScores = props.scores.filter(s => islandMemberNames.includes(s.province));

    const presentRegionCodes = new Set();
    for (const p of islandProvs) {
        if (islandScores.some(s => s.province === (GEO_TO_DB[p.name] ?? p.name))) {
            presentRegionCodes.add(p.regionCode);
        }
    }
    for (const [cstcName, code] of Object.entries(CSTC_REGION_CODE)) {
        if (!islandRegionCodes.includes(code)) continue;
        if (islandScores.some(s => s.province === cstcName)) presentRegionCodes.add(code);
    }
    const regionCount = presentRegionCodes.size;

    const allRegions = islandRegionCodes
        .filter(code => REGION_LABELS[code])
        .map(code => {
            const regProvNames = provinces
                .filter(p => p.regionCode === code)
                .map(p => GEO_TO_DB[p.name] ?? p.name);
            const regCstcNames = cstcNamesForRegion(code);
            const regMembers   = [...regProvNames, ...regCstcNames];
            const regScores = props.scores.filter(s => regMembers.includes(s.province));
            const avg = regScores.length
                ? Math.round(regScores.reduce((a, s) => a + s.score, 0) / regScores.length * 10) / 10
                : null;
            const fullLabel = REGION_LABELS[code];
            const shortLabel = fullLabel.replace(/^Region [IVXLCD\d-]+\s*-\s*/i, '').trim() || fullLabel;
            return {
                code,
                label:         shortLabel,
                provinceCount: regScores.length,
                score:         avg,
                color:         avg != null ? scoreColor(avg) : '#94a3b8',
            };
        })
        .sort((a, b) => (b.score ?? -1) - (a.score ?? -1));

    if (!islandScores.length) return {
        type: 'island', label, provinceCount: 0, regionCount: 0,
        avgScore: 0, tierCounts: { top: 0, avg: 0, low: 0 }, topProv: null, lowProv: null,
        rank: null, totalIslands: 3, allRegions,
    };

    const avg    = Math.round(islandScores.reduce((a, s) => a + s.score, 0) / islandScores.length * 10) / 10;
    const sorted = [...islandScores].sort((a, b) => b.score - a.score);

    const ranking = computeIslandRanking();
    const rankIdx = ranking.findIndex(r => r.island === islandValue);

    return {
        type:          'island',
        label,
        provinceCount: islandScores.length,
        regionCount,
        avgScore:      avg,
        avgColor:      scoreColor(avg),
        rank:          rankIdx >= 0 ? rankIdx + 1 : null,
        totalIslands:  ranking.length,
        tierCounts: {
            top: islandScores.filter(s => s.bucket === 'Top').length,
            avg: islandScores.filter(s => s.bucket === 'Average').length,
            low: islandScores.filter(s => s.bucket === 'Low').length,
        },
        topProv: { name: sorted[0].province,                 score: Math.round(sorted[0].score * 100) / 100 },
        lowProv: { name: sorted[sorted.length - 1].province, score: Math.round(sorted[sorted.length - 1].score * 100) / 100 },
        allRegions,
    };
};

// Average score per region across all regions present on the map, sorted desc.
// Uses the broader rankingPool - same reason as island ranking: a region's
// rank should reflect every region in the country (or every region in its
// island when the caller narrows by island), not just the regions surviving
// the current Region/Province filter.
const computeRegionRanking = () => {
    const pool = rankingPool();
    const poolByProvince = {};
    for (const s of pool) poolByProvince[s.province] = s;

    const buckets = new Map();
    for (const p of provinces) {
        const dbName = GEO_TO_DB[p.name] ?? p.name;
        const entry  = poolByProvince[dbName];
        if (!entry) continue;
        if (!buckets.has(p.regionCode)) buckets.set(p.regionCode, []);
        buckets.get(p.regionCode).push(entry.score);
    }
    // Pull CSTC clusters into their parent region's bucket too - without this,
    // NCR (which has no provinces in the basemap) would never appear in the
    // ranking and its region panel would show rank = null.
    for (const [cstcName, regionCode] of Object.entries(CSTC_REGION_CODE)) {
        const entry = poolByProvince[cstcName];
        if (!entry) continue;
        if (!buckets.has(regionCode)) buckets.set(regionCode, []);
        buckets.get(regionCode).push(entry.score);
    }
    const arr = [];
    for (const [code, scores] of buckets.entries()) {
        const avg = scores.reduce((a, b) => a + b, 0) / scores.length;
        arr.push({ code, avg: Math.round(avg * 10) / 10 });
    }
    arr.sort((a, b) => b.avg - a.avg);
    return arr;
};

const buildRegionPanel = (regionCode) => {
    const label = REGION_LABELS[regionCode] ?? `Region ${regionCode}`;
    // short label: strip "Region X -" prefix for the header title
    const shortLabel = label.replace(/^Region [IVXLCD\d-]+\s*-\s*/i, '').trim() || label;

    const sm = scoreMap();
    const regionProvs = provinces.filter(p => p.regionCode === regionCode);
    const regionProvNames = regionProvs.map(p => GEO_TO_DB[p.name] ?? p.name);

    // Some regions also "own" CSTC clusters that sit outside the province
    // layer - NCR is composed entirely of these. Include them as members so
    // the region panel surfaces their score, count, best/worst, etc. instead
    // of treating NCR as an empty region.
    const regionCstcNames = cstcNamesForRegion(regionCode);
    const memberNames     = [...regionProvNames, ...regionCstcNames];

    const regionScores = props.scores.filter(s => memberNames.includes(s.province));

    const provinceEntries = regionProvs.map(p => {
        const dbName = GEO_TO_DB[p.name] ?? p.name;
        const entry  = sm[dbName] ?? null;
        const info   = entry ? tierInfo(entry) : { color: '#94a3b8', label: 'No data' };
        return {
            name:     p.name,
            director: entry?.director ?? null,
            score:    entry ? Math.round(entry.score * 100) / 100 : null,
            color:    info.color,
            tier:     info.label,
        };
    });
    const cstcEntries = regionCstcNames.map(name => {
        const entry = sm[name] ?? null;
        const info  = entry ? tierInfo(entry) : { color: '#94a3b8', label: 'No data' };
        return {
            name,
            director: entry?.director ?? null,
            score:    entry ? Math.round(entry.score * 100) / 100 : null,
            color:    info.color,
            tier:     info.label,
        };
    });
    const allProvinces = [...provinceEntries, ...cstcEntries]
        .sort((a, b) => (b.score ?? -1) - (a.score ?? -1));

    if (!regionScores.length) return {
        type: 'region', shortLabel, provinceCount: 0,
        avgScore: 0, tierCounts: { top: 0, avg: 0, low: 0 }, topProv: null, lowProv: null,
        rank: null, totalRegions: 0, allProvinces,
    };

    const avg    = Math.round(regionScores.reduce((a, s) => a + s.score, 0) / regionScores.length * 10) / 10;
    const sorted = [...regionScores].sort((a, b) => b.score - a.score);

    const ranking     = computeRegionRanking();
    const rankIdx     = ranking.findIndex(r => r.code === regionCode);
    const rank        = rankIdx >= 0 ? rankIdx + 1 : null;
    const totalRegions = ranking.length;

    return {
        type:         'region',
        shortLabel,
        provinceCount: regionScores.length,
        avgScore:      avg,
        avgColor:      scoreColor(avg),
        rank,
        totalRegions,
        tierCounts: {
            top: regionScores.filter(s => s.bucket === 'Top').length,
            avg: regionScores.filter(s => s.bucket === 'Average').length,
            low: regionScores.filter(s => s.bucket === 'Low').length,
        },
        topProv: { name: sorted[0].province,                 score: Math.round(sorted[0].score * 100) / 100 },
        lowProv: { name: sorted[sorted.length - 1].province, score: Math.round(sorted[sorted.length - 1].score * 100) / 100 },
        allProvinces,
    };
};

const buildCstcPanel = (cstcName) => {
    const sm    = scoreMap();
    const entry = sm[cstcName] ?? null;
    const trend = buildTrend(cstcName);

    if (!entry) return {
        type: 'cstc', name: cstcName, score: null, tier: 'No data',
        tierColor: '#94a3b8', director: '-', category: 'large',
        rank: null, year: props.selectedYear, categories: [], trend,
        region: null, provinceUrlId: null, directorId: null,
    };

    const info = tierInfo(entry);
    return {
        type:          'cstc',
        name:          cstcName,
        region:        entry.region ?? null,
        provinceUrlId: entry.province_url_id ?? null,
        category:      'large',
        director:      entry.director,
        directorId:    entry.director_id ?? null,
        status:        entry.status,
        score:         Math.round(entry.score * 100) / 100,
        tier:          info.label,
        tierColor:     info.color,
        rank:          entry.rank ?? null,
        year:          props.selectedYear,
        categories:    categoryBreakdown(entry),
        trend,
    };
};

// ── Style ─────────────────────────────────────────────────────────────────────
const styleForCstc = (feature, sm, selReg, selIsl) => {
    const cstcName       = feature.properties.cstc;
    const entry          = sm[cstcName] ?? null;
    const cstcRegionCode = CSTC_REGION_CODE[cstcName];
    const cstcIsland     = REGION_TO_ISLAND[cstcRegionCode];

    // Match the province layer's dimming logic: when the user filters to a
    // specific region or island, clusters that don't belong there fade to the
    // same neutral grey as out-of-scope provinces.
    const hasRegFilter    = !!selReg;
    const hasIslandFilter = !!selIsl;
    const isInSelRegion   = hasRegFilter    && cstcRegionCode === Number(selReg);
    const isInSelIsland   = hasIslandFilter && cstcIsland === selIsl;
    if (hasRegFilter && !isInSelRegion)
        return { fillColor: '#cbd5e1', weight: 0.3, color: '#e2e8f0', fillOpacity: 0.18 };
    if (!hasRegFilter && hasIslandFilter && !isInSelIsland)
        return { fillColor: '#cbd5e1', weight: 0.3, color: '#e2e8f0', fillOpacity: 0.18 };

    // CSTC clusters (e.g. Zamboanga City / ZCIC) overlap the province polygons in the
    // basemap - Zamboanga City's land is baked into Zamboanga del Sur. With no score in
    // the active filter we render the cluster as a neutral cut-out so it masks the
    // province's highlight beneath it rather than inheriting that province's colour.
    if (!entry)
        return { fillColor: '#e2e8f0', weight: 0.6, color: '#fff', fillOpacity: 0.95 };

    const baseColor = tierColor(entry);
    if (selCstc.value === cstcName)
        return { fillColor: baseColor, weight: 3.5, color: '#fff', fillOpacity: 0.97 };
    // Lift in-scope clusters slightly when a region filter is active so the
    // four NCR clusters (or ZCIC, or Davao City) read as "the highlighted set".
    if (isInSelRegion)
        return { fillColor: baseColor, weight: 1.6, color: '#fff', fillOpacity: 0.95 };
    return { fillColor: baseColor, weight: 0.6, color: '#fff', fillOpacity: 0.85 };
};

const refreshCstcStyle = () => {
    if (!cstcLayer) return;
    const sm = scoreMap();
    cstcLayer.setStyle(f => styleForCstc(f, sm, selRegion.value, selIsland.value));
};

const styleFor = (feature, sm, selReg, selProv, selIsl) => {
    const name       = feature.properties.adm2_en;
    const regionCode = feature.properties.adm1_psgc;
    if (SKIP(name)) return { fillColor: '#e2e8f0', weight: 0.4, color: '#cbd5e1', fillOpacity: 0.25 };

    const dbName    = GEO_TO_DB[name] ?? name;
    const entry     = sm[dbName] ?? null;
    const baseColor = entry ? tierColor(entry) : '#ffffff';
    // Blank (no data / vacant / 0) provinces render white with a light outline
    // so they stay visible against the basemap instead of disappearing.
    const blank     = baseColor === '#ffffff';
    const bd        = blank ? '#cbd5e1' : '#fff';

    const isSelectedProv = selProv && name === selProv;
    const isInSelRegion  = selReg  && regionCode === Number(selReg);
    const isInSelIsland  = selIsl  && REGION_TO_ISLAND[regionCode] === selIsl;
    const hasRegFilter    = !!selReg;
    const hasProvFilter   = !!selProv;
    const hasIslandFilter = !!selIsl;

    if (isSelectedProv)
        return { fillColor: baseColor, weight: 3.5, color: bd, fillOpacity: blank ? 1 : 0.97 };

    if (hasRegFilter) {
        if (isInSelRegion)
            return hasProvFilter
                ? { fillColor: baseColor, weight: 0.8, color: bd, fillOpacity: blank ? 1 : 0.45 }
                : { fillColor: baseColor, weight: 1.2, color: bd, fillOpacity: blank ? 1 : 0.85 };
        return { fillColor: '#cbd5e1', weight: 0.3, color: '#e2e8f0', fillOpacity: 0.18 };
    }

    if (hasIslandFilter) {
        if (isInSelIsland)
            return { fillColor: baseColor, weight: 0.8, color: bd, fillOpacity: blank ? 1 : 0.85 };
        return { fillColor: '#cbd5e1', weight: 0.3, color: '#e2e8f0', fillOpacity: 0.18 };
    }

    return { fillColor: baseColor, weight: 0.6, color: bd, fillOpacity: blank ? 1 : 0.78 };
};

const refreshStyle = () => {
    if (!geoLayer) return;
    const sm = scoreMap();
    geoLayer.setStyle(f => styleFor(f, sm, selRegion.value, selProvince.value, selIsland.value));
};

// ── Fly helpers ───────────────────────────────────────────────────────────────
const HOME = { center: [12.2, 122.5], zoom: 5 };

// smartFlyTo - used by FILTER-driven changes (Island/Region/Province from the
// top sticky bar). If the target isn't currently visible, it first pulls back
// to overview so the user gets visual context, then flies in. Acceptable
// here because the user explicitly changed scope.
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

// softFlyTo - used by CLICK-driven changes (clicking on a province/CSTC or
// picking from the panel's expand-list). Pan/zoom directly to the target
// without the overview detour - clicking a nearby province should feel like
// a gentle slide-over, not a "zoom-out-then-back-in" flourish.
const softFlyTo = (targetBounds, { maxZoom = 9, padding = [50, 50] } = {}) => {
    if (!map) return;
    map.stop();
    pendingFly = false;
    map.flyToBounds(targetBounds, { padding, maxZoom, duration: 0.8, easeLinearity: 0.4 });
};

// Compute leaflet bounds for a region. Most regions are the union of their
// province polygons; NCR has no province polygons at all (and a couple of other
// regions have CSTC clusters that aren't represented as provinces), so we union
// in the CSTC bounds when the region has cluster members. Returns null if the
// region has neither - caller decides what to do.
const regionBounds = (code) => {
    const regionProvs = provinces.filter(p => p.regionCode === code);
    let b = null;
    for (const p of regionProvs) {
        if (!b) b = L.latLngBounds(p.bounds);
        else    b.extend(p.bounds);
    }
    for (const name of cstcNamesForRegion(code)) {
        const cb = cstcData[name];
        if (!cb) continue;
        if (!b) b = L.latLngBounds(cb.getSouthWest(), cb.getNorthEast());
        else    b.extend(cb);
    }
    return b;
};

// NCR's bounding box is ~25 km across - zooming to maxZoom 8 (the default for
// regions) would still show the whole of Luzon. Pick a tighter cap so users
// actually see the four NCR clusters when they pick NCR from the filter bar.
const regionMaxZoom = (code) => (code === 1300000000 ? 11 : 8);

// ── Event handlers ────────────────────────────────────────────────────────────
const onIslandChange = () => {
    selRegion.value = '';
    selProvince.value = '';
    pendingFly = false;
    refreshStyle();
    refreshCstcStyle();
    if (!selIsland.value) { resetView(); return; }

    panel.value = buildIslandPanel(selIsland.value);

    const islandRegionCodes = Object.entries(REGION_TO_ISLAND)
        .filter(([, v]) => v === selIsland.value)
        .map(([code]) => Number(code));
    const islandProvs = provinces.filter(p => islandRegionCodes.includes(p.regionCode));
    if (!islandProvs.length) return;
    let bounds = L.latLngBounds(islandProvs[0].bounds);
    for (const p of islandProvs.slice(1)) bounds.extend(p.bounds);
    smartFlyTo(bounds, { maxZoom: 7, padding: [30, 30] });
};

const onRegionChange = () => {
    selProvince.value = '';
    pendingFly = false;

    // Keep island in sync so the dropdown filter and map highlight stay consistent
    if (selRegion.value) {
        const island = REGION_TO_ISLAND[Number(selRegion.value)];
        if (island && selIsland.value !== island) selIsland.value = island;
    }

    refreshStyle();
    refreshCstcStyle();
    if (!selRegion.value) {
        if (selIsland.value) { onIslandChange(); return; }
        resetView();
        return;
    }

    const code = Number(selRegion.value);
    panel.value = buildRegionPanel(code);

    const bounds = regionBounds(code);
    if (!bounds) return;
    smartFlyTo(bounds, { maxZoom: regionMaxZoom(code), padding: [30, 30] });
};

// Resolve a province/cluster name to its CSTC city footprint. The former CSTCs
// (CAMANAVA, PAMAMAZON, PAMAMARISAN, MUNTAPARLAS, ZCIC, Davao City) are now
// classified as Large and appear in the province list, but they're city clusters
// rather than province polygons, so they must zoom via cstcData. Matching is
// case-tolerant because the rankings data and the geojson `cstc` may differ.
const findCstc = (name) => {
    if (!name) return null;
    if (cstcData[name]) return { key: name, bounds: cstcData[name] };
    const lower = name.toLowerCase();
    const key = Object.keys(cstcData).find(k => k.toLowerCase() === lower);
    return key ? { key, bounds: cstcData[key] } : null;
};

const onProvinceChange = () => {
    pendingFly = false;
    if (!selProvince.value) { selCstc.value = ''; refreshStyle(); refreshCstcStyle(); panel.value = null; return; }

    const prov = provinces.find(p => p.name === selProvince.value);
    if (prov) {
        selCstc.value = '';
        refreshStyle(); refreshCstcStyle();
        panel.value = buildProvincePanel(selProvince.value);
        smartFlyTo(prov.bounds, { maxZoom: 9, padding: [50, 50] });
        return;
    }
    const cstc = findCstc(selProvince.value);
    if (cstc) {
        selCstc.value = cstc.key;
        refreshStyle(); refreshCstcStyle();
        panel.value = buildCstcPanel(cstc.key);
        smartFlyTo(cstc.bounds, { maxZoom: 13, padding: [40, 40] });
        return;
    }
    refreshStyle();
    panel.value = buildProvincePanel(selProvince.value);
};

// ── External (dashboard sticky filter) → internal map state ───────────────────
// Maps the dashboard's region-short-name back to its numeric PSGC code so the
// existing handlers don't need to change. Built from REGION_LABELS once.
const REGION_NAME_TO_CODE = Object.fromEntries(
    Object.entries(REGION_LABELS).map(([code, label]) => [label.split(' - ')[0], Number(code)])
);

watch(() => props.island, (v) => {
    const mapped = (v && v !== 'all') ? v.toLowerCase() : '';
    if (selIsland.value === mapped) return;
    selIsland.value = mapped;
    if (ready.value) onIslandChange();
});

watch(() => props.region, (v) => {
    const mapped = (v && v !== 'all') ? String(REGION_NAME_TO_CODE[v] ?? '') : '';
    if (selRegion.value === mapped) return;
    selRegion.value = mapped;
    if (ready.value) onRegionChange();
});

watch(() => props.province, (v) => {
    const mapped = (v && v !== 'all') ? v : '';
    if (selProvince.value === mapped) return;
    selProvince.value = mapped;
    if (ready.value) onProvinceChange();
});

const selectRegionFromList = (code) => {
    const island = REGION_TO_ISLAND[code];
    if (island) selIsland.value = island;
    selRegion.value = String(code);
    selProvince.value = '';
    pendingFly = false;
    refreshStyle();
    refreshCstcStyle();
    panel.value = buildRegionPanel(code);
    const bounds = regionBounds(code);
    if (bounds) softFlyTo(bounds, { maxZoom: regionMaxZoom(code), padding: [30, 30] });
    regionListOpen.value = false;
};

const selectProvinceFromList = (name) => {
    selProvince.value = name;
    pendingFly = false;
    const prov = provinces.find(p => p.name === name);
    if (prov) {
        selCstc.value = '';
        refreshStyle(); refreshCstcStyle();
        panel.value = buildProvincePanel(name);
        softFlyTo(prov.bounds, { maxZoom: 9, padding: [50, 50] });
        provinceListOpen.value = false;
        return;
    }
    const cstc = findCstc(name);
    if (cstc) {
        selCstc.value = cstc.key;
        refreshStyle(); refreshCstcStyle();
        panel.value = buildCstcPanel(cstc.key);
        softFlyTo(cstc.bounds, { maxZoom: 13, padding: [40, 40] });
        provinceListOpen.value = false;
    }
};

const resetView = () => {
    selIsland.value = selRegion.value = selProvince.value = selCstc.value = '';
    panel.value = null;
    pendingFly  = false;
    map?.stop();
    refreshStyle();
    refreshCstcStyle();
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

    // Normalize basemap region codes to the DOST scheme (NIR, Sulu → Region IX)
    // before anything reads adm1_psgc, so styling and grouping stay consistent.
    for (const feature of geojson.features) {
        const ov = REGION_CODE_OVERRIDE[feature.properties.adm2_en];
        if (ov) feature.properties.adm1_psgc = ov;
    }

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
                if (!entry) return;
                const info = tierInfo(entry);
                layer.bindTooltip(
                    `<div class="map-tip">
                        <strong>${name}</strong>
                        <span style="color:${info.color};font-weight:600;">${info.label}</span>
                        <span class="map-tip-score">${entry.score}%</span>
                    </div>`,
                    { sticky: true, className: 'map-tooltip' }
                ).openTooltip(e.latlng);
                // Keep dimmed provinces dim when a filter is active and they fall outside it
                const inActiveFilter =
                    (!selRegion.value || Number(selRegion.value) === regionCode) &&
                    (!selIsland.value || REGION_TO_ISLAND[regionCode] === selIsland.value);
                if (inActiveFilter) e.target.setStyle({ weight: 3, fillOpacity: 0.97 });
            });
            layer.on('mouseout', () => {
                layer.setStyle(styleFor(layer.feature, scoreMap(), selRegion.value, selProvince.value, selIsland.value));
            });

            layer.on('click', () => {
                // Sync dropdowns
                const island = REGION_TO_ISLAND[regionCode];
                if (island && selIsland.value !== island) {
                    selIsland.value = island;
                }
                if (regionCode !== Number(selRegion.value)) {
                    selRegion.value = String(regionCode);
                }
                selProvince.value = name;
                pendingFly = false;
                refreshStyle();

                // Show province panel
                panel.value = buildProvincePanel(name);

                const prov = provinces.find(p => p.name === name);
                if (prov) softFlyTo(prov.bounds, { maxZoom: 9, padding: [50, 50] });
            });
        },
    }).addTo(map);

    // ── CSTC city layer ───────────────────────────────────────────────────────
    map.createPane('cstcPane');
    map.getPane('cstcPane').style.zIndex = 450;

    const cstcRes     = await fetch('/geo/cstc-cities.geojson');
    const cstcGeojson = await cstcRes.json();
    const csm         = scoreMap();

    // Build per-CSTC bounds for zooming
    for (const feature of cstcGeojson.features) {
        const cstcName   = feature.properties.cstc;
        const featBounds = L.geoJSON(feature).getBounds();
        if (cstcData[cstcName]) cstcData[cstcName].extend(featBounds);
        else                    cstcData[cstcName] = featBounds;
    }
    cstcList.value = Object.keys(cstcData).sort();

    cstcLayer = L.geoJSON(cstcGeojson, {
        pane:  'cstcPane',
        style: f => styleForCstc(f, csm, selRegion.value, selIsland.value),
        onEachFeature: (feature, layer) => {
            const cstcName = feature.properties.cstc;
            const cityName = feature.properties.name;

            layer.on('mouseover', (e) => {
                const entry = scoreMap()[cstcName] ?? null;
                if (!entry) return;
                const info = tierInfo(entry);
                layer.bindTooltip(
                    `<div class="map-tip">
                        <strong>${cstcName}</strong>
                        <span style="color:#94a3b8;font-size:10px;margin-top:-1px;">${cityName}</span>
                        <span style="color:${info.color};font-weight:600;">${info.label}</span>
                        <span class="map-tip-score">${entry.score}%</span>
                    </div>`,
                    { sticky: true, className: 'map-tooltip' }
                ).openTooltip(e.latlng);
                e.target.setStyle({ weight: 3, fillOpacity: 0.97 });
            });
            layer.on('mouseout', () => {
                layer.setStyle(styleForCstc(layer.feature, scoreMap(), selRegion.value, selIsland.value));
            });

            layer.on('click', () => {
                selRegion.value = selProvince.value = '';
                selCstc.value = cstcName;
                refreshCstcStyle();
                panel.value = buildCstcPanel(cstcName);
                const bounds = cstcData[cstcName];
                if (bounds) softFlyTo(bounds, { maxZoom: 13, padding: [50, 50] });
            });
        },
    });

    // Keep the CSTC city clusters as an always-on overlay: they're independent of the
    // province polygons, so this both surfaces them and masks their footprints out of
    // the province choropleth (e.g. Zamboanga City no longer highlights as part of
    // Zamboanga del Sur). They colour by score on Large/All and render neutral otherwise.
    cstcLayer.addTo(map);

    ready.value = true;
});

const rebuildPanel = () => {
    if (!panel.value) return;
    if (panel.value.type === 'province')    panel.value = buildProvincePanel(panel.value.name);
    else if (panel.value.type === 'cstc')   panel.value = buildCstcPanel(panel.value.name);
    else if (panel.value.type === 'region') panel.value = buildRegionPanel(Number(selRegion.value));
    else if (panel.value.type === 'island') panel.value = buildIslandPanel(selIsland.value);
};

watch(() => props.selectedTier, () => {
    // Overlay stays on across tiers; just recolour clusters for the active filter.
    if (!map || !cstcLayer) return;
    refreshCstcStyle();
});

watch(() => props.scores,       () => { refreshStyle(); refreshCstcStyle(); rebuildPanel(); }, { deep: true });
watch(panel, (p) => {
    if (!p || p.type !== 'region') provinceListOpen.value = false;
    if (!p || p.type !== 'island') regionListOpen.value = false;
});
// Sparkline highlights the active year, so the panel needs to rebuild when the
// year changes (especially during time-lapse playback). Trends prop only
// updates if a full reload happens - usually not during the session.
watch(() => props.selectedYear, () => { rebuildPanel(); });
watch(() => props.trends,       () => { rebuildPanel(); }, { deep: true });

onBeforeUnmount(() => {
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    map?.remove();
});
</script>

<style scoped>
.ph-map-root { width: 100%; }
.ph-map-wrap { position: relative; width: 100%; }

.ph-map {
    height: v-bind(height); width: 100%; z-index: 0;
    border-radius: 0 0 8px 8px;
}

/* Fullscreen: keep the filter bar on top and let the map fill the rest */
.ph-map-root:fullscreen, .ph-map-root:-webkit-full-screen { background: #fff; display: flex; flex-direction: column; }
.ph-map-root:fullscreen .ph-map-wrap, .ph-map-root:-webkit-full-screen .ph-map-wrap { flex: 1; min-height: 0; }
.ph-map-root:fullscreen .ph-map, .ph-map-root:-webkit-full-screen .ph-map { height: 100%; border-radius: 0; }

/* ── Filter bar (Island / Region / Province) - matches the other dashboard filter rows ── */
.map-filter-bar {
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
    padding: 10px 16px; background: #fff; border-bottom: 1px solid rgba(0,0,0,0.06);
}
.mfb-group { display: flex; align-items: center; gap: 8px; }
.mfb-label {
    font-size: 13px; font-weight: 700; color: #475569;
    letter-spacing: 0.02em; text-transform: uppercase;
}
.mfb-seg {
    display: inline-flex; align-items: center; gap: 2px; padding: 3px;
    background: #f1f5f9; border-radius: 9px; border: 1px solid #e2e8f0;
}
.mfb-seg-btn {
    appearance: none; border: none; background: transparent; color: #475569;
    font-size: 14px; font-weight: 600; padding: 6px 14px; border-radius: 6px;
    cursor: pointer; font-family: inherit; line-height: 1.3; white-space: nowrap;
    transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
}
.mfb-seg-btn:hover:not(.active) { color: #0f172a; background: rgba(255,255,255,0.6); }
.mfb-seg-btn.active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.04); }
.mfb-select {
    height: 38px; padding: 0 32px 0 12px; font-size: 14px; font-family: inherit;
    color: #334155; cursor: pointer; min-width: 210px; appearance: none; outline: none;
    border: 1px solid #e2e8f0; border-radius: 8px;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2394a3b8'/%3E%3C/svg%3E") no-repeat right 12px center;
    transition: border-color 0.15s;
}
.mfb-select:focus { border-color: #6366f1; }
.mfb-reset {
    margin-left: auto; display: inline-flex; align-items: center; gap: 6px;
    height: 38px; padding: 0 16px; font-size: 14px; font-weight: 600;
    color: #475569; background: #f1f5f9; border: 1px solid #e2e8f0;
    border-radius: 8px; cursor: pointer; transition: background 0.15s, color 0.15s;
}
.mfb-reset:hover { background: #e2e8f0; color: #1e293b; }
.mfb-reset svg { width: 16px; height: 16px; }

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
    gap: 8px; padding: 13px 14px 12px;
    border-bottom: 1px solid #f1f5f9;
}
.panel-header-region { border-left: 3px solid #6366f1; padding-left: 11px; }
.panel-header-island { border-left: 3px solid #0ea5e9; padding-left: 11px; }
.panel-header-main   { flex: 1; min-width: 0; }
.panel-caption {
    display: flex; align-items: center; gap: 5px;
    font-size: 9.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.06em; margin-bottom: 4px;
}
.panel-caption-tier { color: #6366f1; }
.panel-caption-sep  { color: #cbd5e1; font-weight: 400; }
.panel-caption-year { color: #94a3b8; }

.panel-title    { font-size: 17px; font-weight: 700; color: #0f172a; line-height: 1.2; }
.panel-subtitle { font-size: 12px; color: #64748b; margin-top: 3px; line-height: 1.3; }
.panel-region-tag { font-size: 11px; color: #94a3b8; font-weight: 500; margin-top: 2px; line-height: 1.2; }
.panel-name-link {
    text-decoration: none !important; color: inherit; display: block;
    transition: color 0.15s;
}
.panel-name-link:hover { color: #4f46e5 !important; text-decoration: underline !important; }

.panel-close {
    flex-shrink: 0; width: 22px; height: 22px; padding: 3px;
    background: none; border: none; cursor: pointer; color: #cbd5e1;
    display: flex; align-items: center; justify-content: center;
    border-radius: 4px; transition: background 0.12s, color 0.12s;
}
.panel-close:hover { background: #f1f5f9; color: #475569; }
.panel-close svg    { width: 14px; height: 14px; }

.panel-body { padding: 12px 14px 14px; display: flex; flex-direction: column; gap: 14px; }

.map-info-panel--region,
.map-info-panel--island { height: 325px; display: flex; flex-direction: column; }
.map-info-panel--region .panel-body,
.map-info-panel--island .panel-body { overflow-y: auto; flex: 1; min-height: 0; }
.map-info-panel--region .panel-body > div:first-child,
.map-info-panel--island .panel-body > div:first-child {
    flex: 1; display: flex; flex-direction: column; justify-content: space-between;
}

/* Hero: large score number paired with inline tier/rank - no chips */
.panel-hero          { display: flex; flex-direction: column; gap: 6px; }
.panel-hero-row      { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
.panel-score-num     { font-size: 28px; font-weight: 800; line-height: 1; letter-spacing: -0.02em; }
.panel-hero-status   { font-size: 11.5px; font-weight: 600; line-height: 1.3; text-align: right; }
.panel-hero-rank     { font-weight: 700; margin-left: 4px; }
.panel-score-bar-wrap {
    height: 4px; background: #f1f5f9; border-radius: 3px; overflow: hidden;
}
.panel-score-bar { height: 100%; border-radius: 3px; transition: width 0.5s ease; opacity: 0.85; }

/* Section labels - small uppercase tags above each block */
.panel-section          { display: flex; flex-direction: column; gap: 6px; }
.panel-section-header   { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
.panel-section-label {
    font-size: 9.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.06em; color: #94a3b8;
}

/* Category rows - flat list, ▲/▼/· markers, score on the right */
.cat-rows { display: flex; flex-direction: column; gap: 1px; }
.cat-row {
    display: grid;
    grid-template-columns: 14px 1fr auto;
    align-items: center;
    gap: 8px;
    padding: 5px 0;
    border-top: 1px solid #f1f5f9;
}
.cat-row:first-child { border-top: none; }
.cat-row-marker      { color: #cbd5e1; text-align: center; font-size: 11px; line-height: 1; }
.cat-row-name        { font-size: 12px; color: #475569; }
.cat-row-score       { font-size: 12px; font-weight: 700; color: #0f172a; }
.cat-row--top .cat-row-marker { color: #16a34a; }
.cat-row--top .cat-row-score  { color: #15803d; }
.cat-row--bottom .cat-row-marker { color: #dc2626; }
.cat-row--bottom .cat-row-score  { color: #b91c1c; }

/* Trend delta badge in the section header */
.trend-delta { font-size: 11px; font-weight: 700; }

/* Shared classes - also used by the Island + Region panel variants */
.panel-divider { height: 1px; background: #f1f5f9; margin: 0 -14px; }
.panel-row {
    display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;
}
.panel-label { font-size: 11px; color: #94a3b8; font-weight: 500; flex-shrink: 0; }
.panel-value { font-size: 12px; color: #334155; font-weight: 600; text-align: right; line-height: 1.4; }
.panel-value small { font-weight: 400; font-size: 10.5px; }
.panel-tier-label { font-size: 12px; font-weight: 600; }

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

/* Trend sparkline (score across reporting years) */
.panel-trend            { display: flex; flex-direction: column; gap: 4px; }
.panel-trend-header     { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
.panel-trend-label      { font-size: 11px; color: #94a3b8; font-weight: 600; }
.panel-trend-delta      { font-size: 11px; font-weight: 700; white-space: nowrap; }
.panel-trend-delta-since {
    font-size: 9.5px; font-weight: 500; color: #94a3b8; margin-left: 2px;
}
.sparkline {
    width: 100%;
    height: 46px;
    display: block;
    overflow: visible;
}
.sparkline circle { transition: r 0.25s ease; cursor: default; }
.panel-trend-years {
    display: flex;
    justify-content: space-between;
    font-size: 9.5px;
    color: #94a3b8;
    font-weight: 600;
    padding: 0 4px;
    margin-top: -2px;
}
.panel-trend-year-active {
    color: #4338ca;
    font-weight: 800;
}

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

.reset-btn {
    position: absolute; top: 10px; right: 50px; z-index: 1000;
    width: 32px; height: 32px; padding: 6px;
    background: rgba(255,255,255,0.95); border: 1px solid #e2e8f0; border-radius: 6px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 1px 4px rgba(0,0,0,0.12); color: #475569;
    backdrop-filter: blur(4px); transition: background 0.15s, color 0.15s;
}
.reset-btn:hover { background: #f1f5f9; color: #1e293b; }
.reset-btn svg   { width: 16px; height: 16px; }

.ph-map-wrap:fullscreen .map-nav,
.ph-map-wrap:-webkit-full-screen .map-nav { top: 14px; left: 14px; }
.ph-map-wrap:fullscreen .fs-btn,
.ph-map-wrap:-webkit-full-screen .fs-btn  { top: 14px; right: 14px; }
.ph-map-wrap:fullscreen .reset-btn,
.ph-map-wrap:-webkit-full-screen .reset-btn  { top: 14px; right: 54px; }
.ph-map-wrap:fullscreen .map-info-panel,
.ph-map-wrap:-webkit-full-screen .map-info-panel { bottom: 32px; right: 14px; width: 300px; }
.ph-map-wrap:fullscreen .map-province-list-panel,
.ph-map-wrap:-webkit-full-screen .map-province-list-panel { bottom: 32px; right: 324px; }

/* ── Panel slide-in transition ─────────────── */
.panel-slide-enter-active { transition: transform 0.22s ease, opacity 0.22s ease; }
.panel-slide-leave-active { transition: transform 0.18s ease, opacity 0.18s ease; }
.panel-slide-enter-from   { transform: translateX(20px); opacity: 0; }
.panel-slide-leave-to     { transform: translateX(20px); opacity: 0; }

/* ── Province list panel (left of region card) ── */
.map-province-list-panel {
    position: absolute; bottom: 28px; right: 310px; z-index: 999;
    width: 256px; height: 325px;
    background: rgba(255,255,255,0.97);
    border: 1px solid #e2e8f0; border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.14);
    backdrop-filter: blur(6px);
    overflow: hidden;
    display: flex; flex-direction: column;
    font-family: inherit;
}
.plist-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 8px; padding: 12px 14px 11px;
    border-bottom: 1px solid #f1f5f9;
    border-left: 3px solid #6366f1; padding-left: 11px;
    flex-shrink: 0;
}
.plist-header-main { flex: 1; min-width: 0; }
.plist-title    { font-size: 14px; font-weight: 700; color: #0f172a; line-height: 1.2; }
.plist-subtitle { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-top: 3px; }
.plist-body {
    overflow-y: auto; flex: 1; min-height: 0;
    padding: 4px 0;
}
.plist-item {
    display: flex; align-items: center; gap: 9px;
    padding: 7px 14px; cursor: pointer;
    transition: background 0.12s;
}
.plist-item:hover { background: #f8fafc; }
.plist-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.plist-info {
    flex: 1; min-width: 0;
    display: flex; flex-direction: column; gap: 1px;
}
.plist-name     { font-size: 12px; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.plist-director { font-size: 10px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.plist-score    { font-size: 12px; font-weight: 700; flex-shrink: 0; }

/* ── Region panel: title row with expand button ── */
.panel-title-row {
    display: flex; align-items: center; gap: 6px;
}
.panel-expand-btn {
    flex-shrink: 0; width: 20px; height: 20px; padding: 3px;
    background: none; border: none; cursor: pointer; color: #94a3b8;
    display: flex; align-items: center; justify-content: center;
    border-radius: 4px; transition: background 0.12s, color 0.12s;
}
.panel-expand-btn:hover,
.panel-expand-btn--active { background: #ede9fe; color: #6366f1; }
.panel-expand-btn svg { width: 13px; height: 13px; }

/* ── Province list slide-in from right (expands leftward from region card) ── */
.panel-slide-left-enter-active { transition: transform 0.22s ease, opacity 0.22s ease; }
.panel-slide-left-leave-active { transition: transform 0.18s ease, opacity 0.18s ease; }
.panel-slide-left-enter-from   { transform: translateX(20px); opacity: 0; }
.panel-slide-left-leave-to     { transform: translateX(20px); opacity: 0; }
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
