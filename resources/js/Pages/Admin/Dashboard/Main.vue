<template>
    <Head title="Dashboard" />
    <div class="d-flex flex-column gap-3 dashboard-root">

        <!-- Sticky filter shell - wraps the filter bar so we can pin them
             together while keeping a visible white shelf between the navbar
             and the bar itself when scrolled. -->
        <div class="dashboard-sticky-shell">
        <div class="dashboard-sticky-filters">
            <div class="d-flex align-center gap-2 px-3 py-2 flex-wrap">
                <v-select
                    v-model="selectedTier"
                    :items="tierSelectItems"
                    item-title="label"
                    item-value="value"
                    label="Tier"
                    density="compact"
                    variant="outlined"
                    hide-details
                    class="filter-select"
                    @update:model-value="searchTerm = ''"
                />

                <v-divider vertical class="filter-divider" />

                <!-- Geography group: Island → Region → Province cascade together -->
                <v-select
                    v-model="selectedIsland"
                    :items="islandSelectItems"
                    item-title="label"
                    item-value="value"
                    label="Island"
                    density="compact"
                    variant="outlined"
                    hide-details
                    class="filter-select"
                    @update:model-value="setIsland($event)"
                />
                <v-select
                    v-model="selectedRegion"
                    :items="regionOptions"
                    item-title="label"
                    item-value="value"
                    label="Region"
                    density="compact"
                    variant="outlined"
                    hide-details
                    class="filter-select filter-select--wide"
                    @update:model-value="setRegion($event)"
                />
                <!-- Province slot: single-select for most views; multi-select checklist for trend view -->
                <v-autocomplete
                    v-if="viewMode === 'trend' && trendProvinceList.length"
                    v-model="selectedProvinces"
                    :items="trendProvinceList"
                    multiple
                    density="compact"
                    variant="outlined"
                    label="Provinces"
                    placeholder="Search provinces…"
                    hide-details
                    :menu-props="{ maxHeight: 320 }"
                    class="filter-select filter-select--wide trend-province-select"
                >
                    <template #selection></template>

                    <template #prepend-inner>
                        <span v-if="selectedProvinces.length" class="d-inline-flex align-center text-caption" style="max-width:130px;">
                            <span class="legend-dot mr-1 flex-shrink-0" :style="{ background: provinceColorMap[selectedProvinces[0]] }"></span>
                            <span class="text-truncate">{{ trendSelectionLabel }}</span>
                        </span>
                    </template>

                    <template #prepend-item>
                        <v-list-item title="Select all" density="compact" @click="toggleSelectAllTrend">
                            <template #prepend>
                                <v-checkbox-btn
                                    density="compact"
                                    :model-value="allTrendSelected"
                                    :indeterminate="someTrendSelected && !allTrendSelected"
                                />
                            </template>
                        </v-list-item>
                        <v-divider class="mt-1" />
                    </template>

                    <template #item="{ item, props: itemProps }">
                        <v-list-item v-bind="itemProps" density="compact">
                            <template #prepend="{ isSelected }">
                                <v-checkbox-btn density="compact" :model-value="isSelected" />
                                <span class="legend-dot ml-1" :style="{ background: provinceColorMap[item.raw] }"></span>
                            </template>
                        </v-list-item>
                    </template>
                </v-autocomplete>
                <v-select
                    v-else
                    v-model="selectedProvince"
                    :items="provinceOptions"
                    item-title="label"
                    item-value="value"
                    label="Province"
                    density="compact"
                    variant="outlined"
                    hide-details
                    class="filter-select filter-select--wide"
                />

                <v-divider vertical class="filter-divider" />

                <v-select
                    v-model="selectedCategory"
                    :items="categorySelectItems"
                    item-title="label"
                    item-value="value"
                    label="Category"
                    density="compact"
                    variant="outlined"
                    hide-details
                    class="filter-select"
                />
                <v-select
                    v-model="selectedYear"
                    :items="available_years"
                    label="Year"
                    density="compact"
                    variant="outlined"
                    hide-details
                    class="filter-select filter-select--narrow"
                />

                <v-btn-toggle v-model="viewMode" mandatory density="compact" variant="outlined" divided class="ml-auto">
                    <v-btn value="podium" size="small" title="Podium view"><v-icon size="15">mdi-podium-gold</v-icon></v-btn>
                    <v-btn value="table"  size="small" title="Table view"><v-icon size="15">mdi-table-large</v-icon></v-btn>
                    <v-btn value="trend"  size="small" title="Trend view"><v-icon size="15">mdi-chart-line</v-icon></v-btn>
                    <v-btn value="map"    size="small" title="Map view"><v-icon size="15">mdi-map-outline</v-icon></v-btn>
                </v-btn-toggle>
            </div>
        </div>
        </div>

        <!-- Performance Distribution - one bell-curve card replaces the
             three Top/Average/Low cards. The histogram bars give per-bin
             detail (tooltip lists the provinces in each 5% band) and the
             smooth area overlay is the Gaussian fitted to the actual mean
             and standard deviation, so the card always reads as a bell. -->
        <v-row dense>
            <v-col cols="12">
                <v-card border elevation="0" rounded="lg" class="overflow-hidden bell-card">
                    <div class="chart-header px-4 pt-3 pb-2 d-flex align-center justify-space-between flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-center gap-2">
                                <v-icon size="15" color="indigo">mdi-chart-bell-curve</v-icon>
                                <span class="text-body-2 font-weight-bold">Performance Distribution</span>
                                <v-tooltip location="bottom" max-width="340">
                                    <template #activator="{ props: tip }">
                                        <v-icon v-bind="tip" size="14" color="blue-grey" class="cursor-pointer">mdi-information-outline</v-icon>
                                    </template>
                                    <div class="pa-2">
                                        <div class="font-weight-bold mb-1">Performance Distribution</div>
                                        <div class="text-caption mb-2" style="opacity:.85;">Ranked provinces split into three groups by overall standing:</div>
                                        <ul class="text-caption" style="margin:0; padding-left:16px; list-style:disc; line-height:1.7;">
                                            <li><b>Top</b> - best 20% of provinces</li>
                                            <li><b>Average</b> - middle 70%</li>
                                            <li><b>Low</b> - bottom 10%</li>
                                        </ul>
                                        <div class="text-caption mt-2" style="opacity:.75; line-height:1.6;">
                                            <div><b>Bars / Bell</b> toggle shows the same data two ways.</div>
                                            <div>Hover a group to list its provinces; click to keep it open.</div>
                                        </div>
                                    </div>
                                </v-tooltip>
                            </div>
                        </div>
                        <div v-if="distributionData" class="d-flex align-center gap-2 flex-wrap">
                            <v-btn-toggle
                                v-model="chartView"
                                density="compact"
                                variant="outlined"
                                divided
                                mandatory
                                color="indigo"
                                class="bell-view-toggle mr-1"
                            >
                                <v-btn value="bars" size="small" prepend-icon="mdi-chart-bar">Bars</v-btn>
                                <v-btn value="bell" size="small" prepend-icon="mdi-chart-bell-curve">Bell</v-btn>
                            </v-btn-toggle>
                            <v-chip size="x-small" variant="tonal" color="blue-grey">{{ distributionData.stats.n }} provinces</v-chip>
                        </div>
                    </div>
                    <div class="bell-card-body">
                        <!-- Bars view: Top / Average / Low performer charts (per-tier buckets) -->
                        <div v-if="distributionData && chartView === 'bars'" class="perf-row">
                            <div class="perf-col">
                                <div class="perf-col-head">
                                    <div class="d-flex align-center gap-2">
                                        <v-icon size="15" color="success">mdi-star-circle-outline</v-icon>
                                        <span class="text-body-2 font-weight-bold">Top Performers</span>
                                    </div>
                                    <div class="text-caption text-medium-emphasis">Top 20% of each tier · {{ top10Data.names.length }} {{ top10Data.names.length === 1 ? 'province' : 'provinces' }}</div>
                                </div>
                                <div class="perf-col-body">
                                    <VueApexCharts
                                        v-if="top10Data.names.length"
                                        type="bar"
                                        height="500"
                                        :options="top10Options"
                                        :series="top10Series"
                                        :key="`top10-${selectedYear}-${selectedTier}-${selectedCategory}`"
                                    />
                                    <div v-else class="perf-empty">
                                        <v-icon size="40" color="blue-grey">mdi-podium-gold</v-icon>
                                        <div class="text-body-2 font-weight-medium">No Top Performers</div>
                                        <div class="text-caption text-medium-emphasis">Tier is too small to bucket, or no rankings yet</div>
                                    </div>
                                </div>
                            </div>
                            <div class="perf-col">
                                <div class="perf-col-head">
                                    <div class="d-flex align-center gap-2">
                                        <v-icon size="15" color="warning">mdi-trending-neutral</v-icon>
                                        <span class="text-body-2 font-weight-bold">Average Performers</span>
                                    </div>
                                    <div class="text-caption text-medium-emphasis">Middle 70% of each tier · {{ avgData.names.length }} {{ avgData.names.length === 1 ? 'province' : 'provinces' }}</div>
                                </div>
                                <div class="perf-col-body">
                                    <div v-if="avgData.names.length" class="avg-chart-scroll">
                                        <VueApexCharts
                                            type="bar"
                                            :height="avgChartHeight"
                                            :options="avgOptions"
                                            :series="avgSeries"
                                            :key="`avg-${selectedYear}-${selectedTier}-${selectedCategory}`"
                                        />
                                    </div>
                                    <div v-else class="perf-empty">
                                        <v-icon size="40" color="blue-grey">mdi-trending-neutral</v-icon>
                                        <div class="text-body-2 font-weight-medium">No Average Performers</div>
                                        <div class="text-caption text-medium-emphasis">Tier is too small to bucket, or no rankings yet</div>
                                    </div>
                                </div>
                            </div>
                            <div class="perf-col">
                                <div class="perf-col-head">
                                    <div class="d-flex align-center gap-2">
                                        <v-icon size="15" color="error">mdi-alert-circle-outline</v-icon>
                                        <span class="text-body-2 font-weight-bold">Low Performers</span>
                                    </div>
                                    <div class="text-caption text-medium-emphasis">Bottom 10% of each tier · {{ underData.names.length }} {{ underData.names.length === 1 ? 'province' : 'provinces' }}</div>
                                </div>
                                <div class="perf-col-body">
                                    <VueApexCharts
                                        v-if="underData.names.length"
                                        type="bar"
                                        height="500"
                                        :options="underOptions"
                                        :series="underSeries"
                                        :key="`under-${selectedYear}-${selectedTier}-${selectedCategory}`"
                                    />
                                    <div v-else class="perf-empty">
                                        <v-icon size="40" color="success">mdi-check-decagram-outline</v-icon>
                                        <div class="text-body-2 font-weight-medium">No Low Performers</div>
                                        <div class="text-caption text-medium-emphasis">Tier is too small to bucket, or no rankings yet</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Bell view: shaded curve + hover/lock side list -->
                        <div v-else-if="distributionData" class="bell-layout">
                            <div class="bell-wrap">
                                <svg class="bell-svg" :viewBox="`0 0 ${bellCurve.W} ${bellCurve.H}`" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient
                                            v-for="r in bellCurve.regions"
                                            :key="`grad-${r.key}`"
                                            :id="`bellgrad-${r.key}`"
                                            x1="0" y1="0" x2="0.35" y2="1"
                                        >
                                            <stop offset="0%" :stop-color="r.color" />
                                            <stop offset="100%" :stop-color="r.light" />
                                        </linearGradient>
                                    </defs>
                                    <path
                                        v-for="r in bellCurve.regions"
                                        :key="r.key"
                                        :d="r.path"
                                        :fill="`url(#bellgrad-${r.key})`"
                                        :fill-opacity="!activeRegionKey ? 0.82 : (activeRegionKey === r.key ? 0.95 : 0.38)"
                                    />
                                    <line
                                        v-for="(dv, i) in bellCurve.dividers"
                                        :key="i"
                                        :x1="dv.x" :y1="dv.y1" :x2="dv.x" :y2="dv.y2"
                                        stroke="#ffffff" stroke-width="1.5" stroke-dasharray="5 4"
                                        opacity="0.85" vector-effect="non-scaling-stroke"
                                    />
                                    <path :d="bellCurve.strokePath" fill="none" stroke="#0f172a" stroke-width="2" opacity="0.45" vector-effect="non-scaling-stroke" />
                                    <line x1="0" :y1="bellCurve.baseY" :x2="bellCurve.W" :y2="bellCurve.baseY" stroke="#e2e8f0" stroke-width="1" vector-effect="non-scaling-stroke" />
                                    <rect
                                        v-for="r in bellCurve.regions"
                                        :key="'hit-' + r.key"
                                        :x="r.x0" y="0" :width="r.w" :height="bellCurve.H"
                                        fill="transparent" pointer-events="all" style="cursor:pointer;"
                                        @mouseenter="onRegionEnter(r.key)"
                                        @mouseleave="onRegionLeave"
                                        @click="onRegionClick(r.key)"
                                    />
                                </svg>
                                <div class="bell-region-labels">
                                    <div
                                        v-for="r in bellCurve.regions"
                                        :key="r.key"
                                        class="bell-region-label"
                                        :class="{ 'is-active': activeRegionKey === r.key }"
                                        :style="{ left: r.leftPct + '%' }"
                                        @mouseenter="onRegionEnter(r.key)"
                                        @mouseleave="onRegionLeave"
                                        @click="onRegionClick(r.key)"
                                    >
                                        <span class="brl-name" :style="{ color: r.color }">{{ r.band.label }}</span>
                                        <span class="brl-pct">{{ r.band.pct }}%</span>
                                        <span class="brl-count">{{ r.band.count }} {{ r.band.count === 1 ? 'province' : 'provinces' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="bell-side">
                                <template v-if="activeRegion">
                                    <div class="bell-side-head">
                                        <span class="bell-side-dot" :style="{ background: activeRegion.color }"></span>
                                        <span class="bell-side-title">{{ activeRegion.label }}</span>
                                        <span class="bell-side-meta">{{ activeRegion.count }} · {{ activeRegion.pct }}%</span>
                                        <v-icon v-if="lockedRegion === activeRegion.key" size="13" color="indigo" class="ml-auto">mdi-lock</v-icon>
                                    </div>
                                    <div class="bell-side-list">
                                        <div
                                            v-for="(p, i) in (activeRegion.provinces || [])"
                                            :key="p.name"
                                            class="bell-side-row"
                                        >
                                            <span class="rank">{{ i + 1 }}</span>
                                            <span class="name">{{ p.name }}</span>
                                            <span class="score">{{ p.score.toFixed(1) }}%</span>
                                        </div>
                                    </div>
                                </template>
                                <div v-else class="bell-side-empty">
                                    <v-icon size="22" color="blue-grey">mdi-gesture-tap</v-icon>
                                    <div>Hover a group to list its provinces</div>
                                    <div class="hint">Click to keep it open</div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="bell-empty">
                            <v-icon size="48" color="blue-grey">mdi-chart-bell-curve</v-icon>
                            <div class="text-body-2 font-weight-medium">No ranked provinces</div>
                            <div class="text-caption text-medium-emphasis">Filter selection has no provinces with rankings yet</div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Leaderboard Card -->
        <v-row dense>
            <v-col cols="12">
                <v-card border elevation="0" rounded="lg">

                    <div class="px-4 pt-3 pb-0">
                        <div class="d-flex align-center justify-space-between flex-wrap gap-3 mb-2">
                            <div class="d-flex align-center gap-2">
                                <v-icon size="15" color="indigo">mdi-trophy-outline</v-icon>
                                <span class="text-body-2 font-weight-bold">PRISM Ranking Matrix</span>
                                <v-tooltip location="bottom" max-width="360">
                                    <template #activator="{ props: tip }">
                                        <v-chip v-bind="tip" size="x-small" variant="tonal" color="blue-grey" prepend-icon="mdi-information-outline" class="cursor-pointer">
                                            Scoring
                                        </v-chip>
                                    </template>
                                    <div class="pa-2" style="max-width:340px;">
                                        <div class="font-weight-bold mb-1">Weighted PRISM Matrix Score</div>

                                        <div class="text-caption font-weight-medium mt-1">How each of the 37 KPIs is scored</div>
                                        <ul class="text-caption" style="margin:2px 0 0; padding-left:16px; list-style:disc; line-height:1.6; opacity:.85;">
                                            <li>Accomplishment % vs target becomes an adjective rating</li>
                                            <li>Outstanding 1.0 &middot; Very Satisfactory 0.8 &middot; Satisfactory 0.6</li>
                                            <li>Average 0.4 &middot; Unsatisfactory 0.2 &middot; Poor 0.0</li>
                                            <li>That rating is multiplied by the KPI's weight</li>
                                        </ul>

                                        <div class="text-caption font-weight-medium mt-2">Category weights</div>
                                        <ul class="text-caption" style="margin:2px 0 0; padding-left:16px; list-style:disc; line-height:1.6; opacity:.85;">
                                            <li><b>CORE</b> 60%</li>
                                            <li><b>STRATEGIC</b> 30%</li>
                                            <li><b>SUPPORT</b> 10%</li>
                                        </ul>

                                        <div class="text-caption font-weight-medium mt-2">Ranking &amp; tiers</div>
                                        <ul class="text-caption" style="margin:2px 0 0; padding-left:16px; list-style:disc; line-height:1.6; opacity:.85;">
                                            <li>Ranked within size tier (Micro / Small / Medium / Large)</li>
                                            <li>Top 20% = Top &middot; Middle 70% = Average &middot; Bottom 10% = Low</li>
                                        </ul>
                                    </div>
                                </v-tooltip>
                                <v-chip size="x-small" color="primary" variant="tonal" class="font-weight-medium">
                                    {{ rankedScores.length }}
                                    <template v-if="selectedTier !== 'all'"> · {{ tierLabel }}</template>
                                </v-chip>
                            </div>
                            <div class="d-flex align-center gap-3 flex-wrap">
                                <div class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#ca8a04;"></span>
                                    <span class="text-caption text-medium-emphasis">Top ({{ bucketCounts.Top }})</span>
                                </div>
                                <div class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#64748b;"></span>
                                    <span class="text-caption text-medium-emphasis">Average ({{ bucketCounts.Average }})</span>
                                </div>
                                <div class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#9a3412;"></span>
                                    <span class="text-caption text-medium-emphasis">Low ({{ bucketCounts.Low }})</span>
                                </div>
                                <div v-if="unrankedProvinces.length" class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#94a3b8;"></span>
                                    <span class="text-caption text-medium-emphasis">Pending ({{ unrankedProvinces.length }})</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <v-divider />

                    <!-- Unified view area - fixed height so switching between Podium,
                         Table, Trend, and Map never shifts page layout. Each view sizes
                         itself to fill (and scrolls internally if needed). -->
                    <div class="lb-view-area">

                    <!-- Search row - visible for both Podium and Table views.
                         Searching scrolls + pulses the matching province in place
                         rather than filtering everything else away. -->
                    <div v-if="viewMode === 'table' || viewMode === 'podium'" class="px-4 py-2 d-flex justify-end">
                        <v-text-field
                            v-model="searchTerm"
                            placeholder="Search province or director…"
                            variant="solo-filled"
                            density="compact"
                            hide-details
                            clearable
                            prepend-inner-icon="mdi-magnify"
                            style="max-width:280px;"
                        />
                    </div>

                    <!-- Table View - note: NO :search prop. Search highlights the
                         matching row in place instead of filtering everyone else out.
                         Height = .lb-view-area (600) − search row (52) so the table
                         fills the leaderboard card exactly. -->
                    <v-data-table
                        v-if="viewMode === 'table'"
                        :headers="tableHeaders"
                        :items="rankedScores"
                        :row-props="tableRowProps"
                        density="compact"
                        fixed-header
                        height="548"
                        hide-default-footer
                        :items-per-page="-1"
                        class="leaderboard-table"
                    >
                        <template #item.rank="{ item }">
                            <span v-if="!isRanked(item)" class="text-caption text-disabled">-</span>
                            <span v-else-if="item.rank === 1" class="rank-medal rank-gold">1st</span>
                            <span v-else-if="item.rank === 2" class="rank-medal rank-silver">2nd</span>
                            <span v-else-if="item.rank === 3" class="rank-medal rank-bronze">3rd</span>
                            <span v-else class="text-caption text-medium-emphasis">#{{ item.rank }}</span>
                        </template>

                        <template #item.bucket="{ item }">
                            <v-chip v-if="item.status === 'no_director'" color="blue-grey" size="x-small" variant="tonal" class="font-weight-medium">
                                No director
                            </v-chip>
                            <v-chip v-else-if="item.status === 'no_data'" color="blue-grey" size="x-small" variant="tonal" class="font-weight-medium">
                                No data
                            </v-chip>
                            <v-chip v-else-if="item.bucket" :color="bucketColor(item.bucket)" size="x-small" variant="tonal" class="font-weight-medium">
                                {{ bucketDisplay(item.bucket) }}
                            </v-chip>
                            <span v-else class="text-caption text-disabled">-</span>
                        </template>

                        <template #item.province="{ item }">
                            <a :href="`/province-directories/${item.province_url_id}`"
                               class="text-body-2 font-weight-medium province-link"
                               :class="{ 'text-disabled': !isRanked(item) }">
                                {{ item.province }}
                            </a>
                            <div v-if="item.region" class="text-caption text-disabled" style="line-height:1.2;margin-top:1px;">{{ item.region }}</div>
                        </template>

                        <template #item.director="{ item }">
                            <a v-if="item.director && item.director_id"
                               :href="`/profile/${item.director_id}`"
                               class="text-body-2 director-link"
                               :class="!isRanked(item) ? 'text-disabled font-italic' : 'text-medium-emphasis'">
                                {{ item.director }}
                            </a>
                            <span v-else class="text-body-2" :class="!isRanked(item) ? 'text-disabled font-italic' : 'text-medium-emphasis'">
                                {{ item.director || 'Vacant' }}
                            </span>
                        </template>

                        <template #item.category="{ item }">
                            <v-chip :color="tierColor(item.category)" size="x-small" variant="tonal" class="font-weight-medium text-uppercase">
                                {{ item.category }}
                            </v-chip>
                        </template>

                        <template #item.core="{ item }">
                            <span v-if="isRanked(item)" class="text-caption cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'CORE' }">
                                {{ item.subtotals_pct.CORE.toFixed(1) }}%
                            </span>
                            <span v-else class="text-caption text-disabled">-</span>
                        </template>
                        <template #item.strategic="{ item }">
                            <span v-if="isRanked(item)" class="text-caption cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'STRATEGIC' }">
                                {{ item.subtotals_pct.STRATEGIC.toFixed(1) }}%
                            </span>
                            <span v-else class="text-caption text-disabled">-</span>
                        </template>
                        <template #item.support="{ item }">
                            <span v-if="isRanked(item)" class="text-caption cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'SUPPORT' }">
                                {{ item.subtotals_pct.SUPPORT.toFixed(1) }}%
                            </span>
                            <span v-else class="text-caption text-disabled">-</span>
                        </template>

                        <template #item.total_pct="{ item }">
                            <span v-if="isRanked(item)" class="score-pct cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'overall' }" :style="{ color: bucketColor(item.bucket) }">
                                {{ item.total_pct.toFixed(2) }}%
                            </span>
                            <span v-else class="text-caption text-disabled">-</span>
                        </template>

                        <template #no-data>
                            <div class="text-center py-8 text-medium-emphasis text-body-2">No provinces found</div>
                        </template>
                    </v-data-table>

                    <!-- Trend View - chart height = .lb-view-area (600) − pt-3 pb-5 (32). -->
                    <div v-else-if="viewMode === 'trend'" class="px-4 pt-3 pb-5">
                        <VueApexCharts
                            v-if="trendSeries.length"
                            type="line"
                            height="568"
                            :options="trendOptions"
                            :series="trendSeries"
                        />
                        <div v-else class="text-center py-8 text-medium-emphasis text-body-2">
                            {{ trendProvinceList.length ? 'Select at least one province to display' : 'No ranked provinces found for this filter' }}
                        </div>
                    </div>

                    <!-- Map View - fills .lb-view-area (600) since the wrapper is pa-0. -->
                    <div v-else-if="viewMode === 'map'" class="pa-0">
                        <PhilippinesMap
                            key="ph-map-dashboard"
                            :scores="mapFilteredScores"
                            :ranking-pool="mapRankingPool"
                            :trends="trendsByProvince"
                            :selected-year="selectedYear"
                            :selected-tier="selectedTier"
                            :island="selectedIsland"
                            :region="selectedRegion"
                            :province="selectedProvince"
                            height="600px"
                        />
                    </div>

                    <!-- Podium View -->
                    <div v-else class="px-5 pt-3 pb-5">
                        <div v-if="top3[0]" class="podium-banner mb-4">
                            <v-icon size="16" color="amber-darken-2">mdi-trophy</v-icon>
                            <span class="text-body-2 ml-2">
                                <a :href="`/province-directories/${top3[0].province_url_id}`" class="province-link font-weight-bold">{{ top3[0].province }}</a> leads {{ selectedYear }}<template v-if="selectedTier !== 'all'"> in {{ tierLabel }}</template><template v-if="selectedCategory !== 'overall'"> on {{ selectedCategory }}</template> with
                                <span class="font-weight-bold" :style="{ color: bucketColor(top3[0].bucket) }">{{ getScore(top3[0]).toFixed(2) }}%</span>
                            </span>
                        </div>

                        <div class="d-flex gap-5">
                            <div ref="podiumStageRef" class="podium-stage">
                                <div class="podium-items">
                                    <div v-if="top3[0]" class="podium-item" :class="rowHighlightClass(top3[0].province)" :data-province="top3[0].province">
                                        <div class="podium-info">
                                            <v-icon color="amber-darken-1" size="26" class="mb-1">mdi-trophy</v-icon>
                                            <div class="podium-province"><a :href="`/province-directories/${top3[0].province_url_id}`" class="province-link">{{ top3[0].province }}</a></div>
                                            <div v-if="top3[0].region" class="podium-region">{{ top3[0].region }}</div>
                                            <div class="podium-score" :style="{ color: bucketColor(top3[0].bucket) }">{{ getScore(top3[0]).toFixed(2) }}%</div>
                                        </div>
                                        <div class="podium-block podium-gold">
                                            <v-icon color="white" size="26">mdi-crown</v-icon>
                                            <span class="podium-rank-num">1st</span>
                                        </div>
                                    </div>
                                    <div v-if="top3[1]" class="podium-item" :class="rowHighlightClass(top3[1].province)" :data-province="top3[1].province">
                                        <div class="podium-info">
                                            <div class="podium-province"><a :href="`/province-directories/${top3[1].province_url_id}`" class="province-link">{{ top3[1].province }}</a></div>
                                            <div v-if="top3[1].region" class="podium-region">{{ top3[1].region }}</div>
                                            <div class="podium-score" :style="{ color: bucketColor(top3[1].bucket) }">{{ getScore(top3[1]).toFixed(2) }}%</div>
                                        </div>
                                        <div class="podium-block podium-silver">
                                            <v-icon color="white" size="22">mdi-medal</v-icon>
                                            <span class="podium-rank-num">2nd</span>
                                        </div>
                                    </div>
                                    <div v-if="top3[2]" class="podium-item" :class="rowHighlightClass(top3[2].province)" :data-province="top3[2].province">
                                        <div class="podium-info">
                                            <div class="podium-province"><a :href="`/province-directories/${top3[2].province_url_id}`" class="province-link">{{ top3[2].province }}</a></div>
                                            <div v-if="top3[2].region" class="podium-region">{{ top3[2].region }}</div>
                                            <div class="podium-score" :style="{ color: bucketColor(top3[2].bucket) }">{{ getScore(top3[2]).toFixed(2) }}%</div>
                                        </div>
                                        <div class="podium-block podium-bronze">
                                            <v-icon color="white" size="22">mdi-medal-outline</v-icon>
                                            <span class="podium-rank-num">3rd</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Top 3 category-subtotal breakdown -->
                                <div v-if="top3.length" class="podium-breakdown">
                                    <div v-for="(p, idx) in top3" :key="p.province" class="breakdown-col">
                                        <div v-for="cat in ['CORE','STRATEGIC','SUPPORT']" :key="cat" class="breakdown-row">
                                            <span class="breakdown-label">{{ cat }}</span>
                                            <span class="breakdown-score">{{ p.subtotals_pct[cat].toFixed(1) }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <v-divider vertical class="mx-1" />

                            <div ref="podiumRestScrollRef" class="flex-1 podium-rest-scroll" :style="podiumStageHeight ? { maxHeight: podiumStageHeight + 'px' } : null">
                                <div
                                    v-for="item in restList"
                                    :key="item.province"
                                    class="podium-rest-row"
                                    :class="rowHighlightClass(item.province)"
                                    :data-province="item.province"
                                >
                                    <span class="podium-rest-rank">{{ isRanked(item) ? '#' + item.rank : '-' }}</span>
                                    <div class="flex-1" style="min-width:0;">
                                        <div class="text-body-2 font-weight-medium text-truncate">
                                            <a :href="`/province-directories/${item.province_url_id}`" class="province-link">{{ item.province }}</a>
                                        </div>
                                        <div v-if="item.region" class="text-caption text-disabled text-truncate" style="line-height:1.2;">{{ item.region }}</div>
                                    </div>
                                    <v-chip v-if="item.bucket && selectedTier !== 'all'" :color="bucketColor(item.bucket)" size="x-small" variant="tonal" class="font-weight-medium flex-shrink-0">
                                        {{ bucketDisplay(item.bucket) }}
                                    </v-chip>
                                    <v-chip :color="tierColor(item.category)" size="x-small" variant="tonal" class="font-weight-medium text-uppercase flex-shrink-0">
                                        {{ item.category }}
                                    </v-chip>
                                    <div class="score-cell podium-rest-cell">
                                        <template v-if="isRanked(item)">
                                            <div class="score-track">
                                                <div class="score-fill" :style="{ width: `${Math.min(getScore(item), 100)}%`, background: bucketBar(item.bucket) }" />
                                            </div>
                                            <div class="score-text">
                                                <span class="score-pct" :style="{ color: bucketColor(item.bucket) }">{{ getScore(item).toFixed(2) }}%</span>
                                                <span class="score-counts">CORE {{ item.subtotals_pct.CORE.toFixed(1) }} · STRAT {{ item.subtotals_pct.STRATEGIC.toFixed(1) }} · SUPP {{ item.subtotals_pct.SUPPORT.toFixed(1) }}</span>
                                            </div>
                                        </template>
                                        <span v-else class="text-caption text-disabled" style="margin:auto;">No data</span>
                                    </div>
                                </div>
                                <div v-if="!restList.length" class="text-center py-8 text-caption text-medium-emphasis">
                                    Only {{ top3.length }} province(s) in this filter
                                </div>
                            </div>
                        </div>
                    </div>

                    </div><!-- /.lb-view-area -->
                </v-card>
            </v-col>
        </v-row>

    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import PhilippinesMap from '@/Components/Map/PhilippinesMap.vue';
import { ISLANDS, REGIONS, PROVINCE_REGIONS } from '@/Data/provinceGeography';
const props = defineProps({
    total_users:                { type: Number, default: 0 },
    active_reporting_provinces: { type: Number, default: 0 },
    total_directors:            { type: Number, default: 0 },
    total_employees:            { type: Number, default: 0 },
    total_provinces:            { type: Number, default: 0 },
    rankings_by_year:           { type: Object, default: () => ({}) },
    kpi_categories:             { type: Array,  default: () => [] },
    available_years:            { type: Array,  default: () => [] },
    regions:                    { type: Array,  default: () => [] },
});


const selectedYear     = ref(props.available_years[0] ?? new Date().getFullYear());
const selectedTier     = ref('micro');
const selectedCategory = ref('overall'); // 'overall' | 'CORE' | 'STRATEGIC' | 'SUPPORT'
const selectedIsland   = ref('all');
const selectedRegion   = ref('all');
const selectedProvince = ref('all');
const searchTerm       = ref('');
const viewMode         = ref('podium');

// Island/Region/Province cascade: changing a wider scope resets the narrower ones
// so the user never sees a province option that doesn't belong to the chosen region.
const setIsland = (island) => {
    selectedIsland.value   = island;
    selectedRegion.value   = 'all';
    selectedProvince.value = 'all';
};
const setRegion = (region) => {
    selectedRegion.value   = region;
    selectedProvince.value = 'all';
};

const regionOptions = computed(() => {
    const regions = selectedIsland.value === 'all'
        ? REGIONS
        : REGIONS.filter(r => r.island === selectedIsland.value);
    return [{ value: 'all', label: 'All Regions' }, ...regions];
});

// Province options cascade off the current Island and Region selections.
const provinceOptions = computed(() => {
    const all = Object.entries(PROVINCE_REGIONS).map(([name, info]) => ({
        value: name, label: name, region: info.region, island: info.island,
    }));
    let filtered = all;
    if (selectedRegion.value !== 'all') {
        filtered = filtered.filter(p => p.region === selectedRegion.value);
    } else if (selectedIsland.value !== 'all') {
        filtered = filtered.filter(p => p.island === selectedIsland.value);
    }
    filtered.sort((a, b) => a.label.localeCompare(b.label));
    return [{ value: 'all', label: 'All Provinces' }, ...filtered];
});

const tiers = [
    { value: 'micro',  label: 'Micro'  },
    { value: 'small',  label: 'Small'  },
    { value: 'medium', label: 'Medium' },
    { value: 'large',  label: 'Large'  },
];

// Category options derived from the kpi_categories prop so the weight labels
// stay in sync with whatever the matrix says.
const categoryOptions = computed(() =>
    props.kpi_categories.map(c => ({
        value:  c.code,
        label:  c.code,
        weight: `${Math.round(c.weight * 100)}%`,
    }))
);

const tierLabel = computed(() =>
    selectedTier.value === 'all'
        ? 'All Tiers'
        : (tiers.find(t => t.value === selectedTier.value)?.label ?? '')
);

// Rank-percentile bucketing matches RankingService::rankAndBucket on the backend.
// Mirrored client-side so the Category filter can re-bucket within the active
// tier when the user picks CORE / STRATEGIC / SUPPORT instead of Overall.
// If config/ranking.php values change, update these to match.
const BUCKET_TOP_PCT   = 0.20;
const BUCKET_UNDER_PCT = 0.10;
const MIN_GROUP_FOR_BUCKETS = 5;

const getScore = (row) =>
    selectedCategory.value === 'overall'
        ? row.total_pct
        : (row.subtotals_pct?.[selectedCategory.value] ?? 0);

// Buckets only the ranked rows (status === 'ranked'); unranked rows keep their
// null rank/bucket and get appended at the end alphabetically.
const isRanked = (r) => (r.status ?? 'ranked') === 'ranked';

const assignBuckets = (sortedRows) => {
    const ranked   = sortedRows.filter(isRanked);
    const unranked = sortedRows.filter(r => !isRanked(r))
        .slice().sort((a, b) => a.province.localeCompare(b.province))
        .map(r => ({ ...r, rank: null, bucket: null }));

    const n = ranked.length;
    if (n < MIN_GROUP_FOR_BUCKETS) {
        return [...ranked.map((r, i) => ({ ...r, rank: i + 1, bucket: null })), ...unranked];
    }
    const topN = Math.max(1, Math.round(n * BUCKET_TOP_PCT));
    let   undN = Math.max(1, Math.round(n * BUCKET_UNDER_PCT));
    if (topN + undN >= n) undN = Math.max(1, n - topN - 1);
    const avgEnd = n - undN;
    const bucketed = ranked.map((r, i) => ({
        ...r,
        rank:   i + 1,
        bucket: i < topN ? 'Top' : i < avgEnd ? 'Average' : 'Low',
    }));
    return [...bucketed, ...unranked];
};

// Pull this year's per-tier rankings from the controller payload, flatten into
// one array with rank already assigned per tier. When "All" is selected we
// resort globally by total_pct so the table is comparable across tiers - but
// the per-tier rank field is still useful so we surface it as `tier_rank`.
const allTierRows = computed(() => {
    const yearData = props.rankings_by_year[selectedYear.value] ?? {};
    const out = [];
    for (const [tier, rows] of Object.entries(yearData)) {
        for (const r of rows) {
            out.push({ ...r, tier_rank: r.rank });
        }
    }
    return out;
});

// ── Map view data ─────────────────────────────────────────────────────────
// Mirrors Map/Main.vue logic: per-tier rebucketing by selected category and
// a score field used by PhilippinesMap for coloring provinces.
const mapAllTierRows = computed(() => {
    const yearData = props.rankings_by_year[selectedYear.value] ?? {};
    const out = [];
    for (const tierRows of Object.values(yearData)) {
        const ranked = selectedCategory.value === 'overall'
            ? tierRows
            : assignBuckets(tierRows.slice().sort((a, b) => getScore(b) - getScore(a)));
        for (const r of ranked) {
            out.push({ ...r, tier_rank: r.rank, score: getScore(r) });
        }
    }
    return out;
});

const mapFilteredScores = computed(() => {
    let rows = mapAllTierRows.value;
    if (selectedTier.value !== 'all')     rows = rows.filter(r => r.category === selectedTier.value);
    if (selectedIsland.value !== 'all')   rows = rows.filter(r => PROVINCE_REGIONS[r.province]?.island === selectedIsland.value);
    if (selectedRegion.value !== 'all')   rows = rows.filter(r => PROVINCE_REGIONS[r.province]?.region === selectedRegion.value);
    if (selectedProvince.value !== 'all') rows = rows.filter(r => r.province === selectedProvince.value);
    return rows;
});

// Broader pool for rank computations inside PhilippinesMap. Filtered only by
// tier+category - never by island/region/province - so the map's Island
// Average "#K of N" chip compares across all 3 islands (and Regional Average
// compares across every region) regardless of how the user has narrowed the
// dashboard's geo filter.
const mapRankingPool = computed(() => {
    let rows = mapAllTierRows.value;
    if (selectedTier.value !== 'all') rows = rows.filter(r => r.category === selectedTier.value);
    return rows;
});

const trendsByProvince = computed(() => {
    const out = {};
    for (const [year, tierData] of Object.entries(props.rankings_by_year)) {
        for (const rows of Object.values(tierData)) {
            for (const r of rows) {
                if (!out[r.province]) out[r.province] = [];
                out[r.province].push({
                    year:      Number(year),
                    total_pct: r.total_pct,
                    bucket:    r.bucket,
                    status:    r.status,
                    rank:      r.rank,
                });
            }
        }
    }
    for (const name of Object.keys(out)) {
        out[name].sort((a, b) => a.year - b.year);
    }
    return out;
});

const baseRows = computed(() => {
    let rows = allTierRows.value;
    if (selectedIsland.value !== 'all') {
        rows = rows.filter(r => PROVINCE_REGIONS[r.province]?.island === selectedIsland.value);
    }
    if (selectedRegion.value !== 'all') {
        rows = rows.filter(r => r.region_name === selectedRegion.value);
    }
    return rows;
});

const rankedScores = computed(() => {
    const isOverall = selectedCategory.value === 'overall';

    if (selectedTier.value === 'all') {
        // Global view: ranked rows sorted by active score, unranked appended at the
        // bottom alphabetically. Performer column is hidden in All Tiers mode.
        const all      = baseRows.value;
        const ranked   = all.filter(isRanked)
            .slice().sort((a, b) => getScore(b) - getScore(a))
            .map((r, i) => ({ ...r, rank: i + 1 }));
        const unranked = all.filter(r => !isRanked(r))
            .slice().sort((a, b) => a.province.localeCompare(b.province))
            .map(r => ({ ...r, rank: null, bucket: null }));
        return [...ranked, ...unranked];
    }

    const tierRows = baseRows.value.filter(r => r.category === selectedTier.value);

    // Overall view: backend already excluded unranked from bucketing. Trust its
    // ordering - unranked rows are appended at the end with null rank/bucket.
    if (isOverall) return tierRows;

    // Category view: re-sort + re-bucket only the ranked rows within the tier.
    const sorted = tierRows.slice().sort((a, b) => {
        // Unranked sinks to the bottom regardless of category sort
        if (!isRanked(a) && isRanked(b)) return 1;
        if (isRanked(a) && !isRanked(b)) return -1;
        return getScore(b) - getScore(a);
    });
    return assignBuckets(sorted);
});

const tierCounts = computed(() => {
    const counts = { all: baseRows.value.length };
    for (const r of baseRows.value) counts[r.category] = (counts[r.category] ?? 0) + 1;
    return counts;
});

// Items for the dropdown filters in the sticky bar. Counts/weights are appended
// to the label so users still see the same context as the old segmented buttons.
const tierSelectItems = computed(() =>
    tiers.map(t => ({ value: t.value, label: `${t.label} (${tierCounts.value[t.value] ?? 0})` }))
);

const islandSelectItems = computed(() => [
    { value: 'all', label: 'All Islands' },
    ...ISLANDS.map(isl => ({ value: isl, label: isl })),
]);

const categorySelectItems = computed(() => [
    { value: 'overall', label: 'Overall' },
    ...categoryOptions.value.map(c => ({ value: c.value, label: `${c.label} (${c.weight})` })),
]);

const bucketCounts = computed(() => {
    const counts = { Top: 0, Average: 0, Low: 0 };
    for (const r of rankedScores.value) {
        if (r.bucket && counts[r.bucket] !== undefined) counts[r.bucket]++;
    }
    return counts;
});

// The podium shows only the top 3 RANKED provinces. The rest list mirrors the
// table: ranked provinces #4 onward, then unranked / no-data provinces at the
// bottom (shown with a "No data" placeholder) so nothing is hidden.
const rankedOnly       = computed(() => rankedScores.value.filter(isRanked));
const unrankedProvinces = computed(() => rankedScores.value.filter(r => !isRanked(r)));
const top3     = computed(() => rankedOnly.value.slice(0, 3));
const restList = computed(() => [...rankedOnly.value.slice(3), ...unrankedProvinces.value]);

// Performer (Top / Average / Low) is bucketed per-tier so it's only meaningful
// when a single tier is selected. In the "All Tiers" view we hide the column
// entirely - mixing buckets across tiers would put a SMALL "Top" below a
// LARGE "Average" on the absolute % axis, which reads as inconsistent.
const tableHeaders = computed(() => {
    const cols = [
        { title: 'Rank',     key: 'rank',     width: '70px',  sortable: false },
        { title: 'Performer', key: 'bucket',  width: '110px', sortable: true  },
        { title: 'Province', key: 'province', sortable: true  },
        { title: 'Director', key: 'director', sortable: false },
        { title: 'Tier',     key: 'category', width: '90px',  align: 'center', sortable: true  },
        { title: 'CORE 60%', key: 'core',     width: '90px',  align: 'center', sortable: false },
        { title: 'STRAT 30%', key: 'strategic', width: '90px', align: 'center', sortable: false },
        { title: 'SUPP 10%', key: 'support',  width: '90px',  align: 'center', sortable: false },
        { title: 'Total',    key: 'total_pct', width: '110px', align: 'center', sortable: true  },
    ];
    return selectedTier.value === 'all' ? cols.filter(c => c.key !== 'bucket') : cols;
});

// Gold / silver / bronze - matching the podium so the whole dashboard's
// Top / Average / Low colours are consistent.
const bucketColor = (b) => ({ Top: '#ca8a04', Average: '#64748b', Low: '#9a3412' }[b] ?? '#94a3b8');
// Lighter "glow" end of each colour. Gold and silver lift only to a saturated
// tone (not washed out to near-white) so they match bronze's gentler fade.
const bucketLight = (b) => ({ Top: '#eab308', Average: '#94a3b8', Low: '#c2410c' }[b] ?? '#cbd5e1');
// Gradient fill for score bars - solid colour -> glow tone, like the podium.
const bucketBar = (b) => `linear-gradient(90deg, ${bucketColor(b)}, ${bucketLight(b)})`;
const bucketDisplay = (b) => ({ Top: 'Top', Average: 'Average', Low: 'Low' }[b] ?? '-');
const tierColor = (cat) => ({ micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple' }[cat] ?? 'grey');

// ── Performance groups (20 / 70 / 10) ──────────────────────────────────────
// Provinces are grouped by their per-tier ranking bucket (Top 20% / Average 70%
// / Low 10% of each size tier) - the same buckets the leaderboard and the bar
// view use, so the bell and bars always show identical counts. BAND_DEFS.pct is
// the intended split (used for the bell's shaded areas and the labels); province
// counts are whatever the per-tier integer split produces.
const BAND_DEFS = [
    { key: 'Low',     color: '#9a3412', pct: 10 },
    { key: 'Average', color: '#64748b', pct: 70 },
    { key: 'Top',     color: '#ca8a04', pct: 20 },
];

const distributionData = computed(() => {
    // Use the per-tier buckets already on each row (Top 20% / Average 70% /
    // Low 10% of each size tier) - the same buckets the bar view and leaderboard
    // use - so the bell and bars always show identical counts.
    const ranked = rankedOnly.value;
    if (!ranked.length) return null;
    const n    = ranked.length;
    const mean = ranked.reduce((acc, r) => acc + getScore(r), 0) / n;

    const counts = { Top: 0, Average: 0, Low: 0 };
    for (const r of ranked) {
        if (counts[r.bucket] !== undefined) counts[r.bucket]++;
    }

    const listFor = (key) => ranked
        .filter(r => r.bucket === key)
        .map(r => ({ name: r.province, score: getScore(r) }))
        .sort((a, b) => b.score - a.score);

    // Below the minimum group size assignBuckets leaves buckets null; show one
    // neutral full-width band so a tiny filter selection still reads sensibly.
    if (!(counts.Top + counts.Average + counts.Low)) {
        return {
            stats: { n, mean },
            bands: [{
                key: 'All', label: 'All provinces', color: '#94a3b8',
                count: n, pct: 100, width: 100, note: 'too few to rank into groups',
                provinces: listFor(null),
            }],
        };
    }

    const bands = BAND_DEFS.map(d => ({
        key:       d.key,
        label:     d.key,
        color:     d.color,
        count:     counts[d.key],
        pct:       d.pct,
        provinces: listFor(d.key),
    }));

    return { stats: { n, mean }, bands };
});

// SVG geometry for the bell: a standard-normal curve whose AREA is split into
// Low (bottom 10%), Average (middle 70%) and Top (top 20%) at the 10th & 80th
// percentiles, so the three shaded regions always cover 100% of the curve. The
// x-axis is relative standing (low -> high), deliberately not weighted score.
const bellCurve = computed(() => {
    const d = distributionData.value;
    if (!d) return null;

    const W = 1000, H = 320;
    const padX = 18, padTop = 26, padBottom = 14;
    const zMin = -3.4, zMax = 3.4;
    const B1 = -1.2816, B2 = 0.8416;          // 10th & 80th percentile of N(0,1)
    const dens  = (z) => Math.exp(-(z * z) / 2);
    const xOf   = (z) => padX + ((z - zMin) / (zMax - zMin)) * (W - 2 * padX);
    const yOf   = (v) => (H - padBottom) - v * (H - padTop - padBottom);
    const baseY = yOf(0);

    const N = 200;
    const pts = [];
    for (let i = 0; i <= N; i++) {
        const z = zMin + (i / N) * (zMax - zMin);
        pts.push({ z, x: xOf(z), y: yOf(dens(z)) });
    }
    const strokePath = 'M ' + pts.map(p => `${p.x.toFixed(1)} ${p.y.toFixed(1)}`).join(' L ');

    const areaPath = (zLo, zHi) => {
        const inner = pts.filter(p => p.z > zLo && p.z < zHi)
            .map(p => `${p.x.toFixed(1)} ${p.y.toFixed(1)}`);
        const top = [
            `${xOf(zLo).toFixed(1)} ${yOf(dens(zLo)).toFixed(1)}`,
            ...inner,
            `${xOf(zHi).toFixed(1)} ${yOf(dens(zHi)).toFixed(1)}`,
        ];
        return `M ${xOf(zLo).toFixed(1)} ${baseY.toFixed(1)} L ${top.join(' L ')} L ${xOf(zHi).toFixed(1)} ${baseY.toFixed(1)} Z`;
    };

    const byKey  = Object.fromEntries(d.bands.map(b => [b.key, b]));
    const single = d.bands.length === 1;       // fallback: too few to rank

    const defs = single
        ? [{ key: 'All', color: '#94a3b8', zLo: zMin, zHi: zMax }]
        : [
            { key: 'Low',     color: '#9a3412', zLo: zMin, zHi: B1 },
            { key: 'Average', color: '#64748b', zLo: B1,   zHi: B2 },
            { key: 'Top',     color: '#ca8a04', zLo: B2,   zHi: zMax },
        ];

    const regions = defs.map(r => ({
        key:     r.key,
        color:   r.color,
        light:   bucketLight(r.key),
        path:    areaPath(r.zLo, r.zHi),
        x0:      xOf(r.zLo),
        w:       xOf(r.zHi) - xOf(r.zLo),
        leftPct: (xOf((r.zLo + r.zHi) / 2) / W) * 100,
        band:    byKey[r.key] ?? byKey.All,
    }));

    const dividers = single ? [] : [B1, B2].map(z => ({
        x: xOf(z), y1: yOf(dens(z)), y2: baseY,
    }));

    return { W, H, baseY, strokePath, regions, dividers };
});

// Which display: 'bars' (proportional 20/70/10 blocks) or 'bell' (shaded curve).
// Both views show the same Top/Average/Low data and share the side list below.
const chartView = ref('bars');

// Region interaction: hovering a region/block previews its provinces in the side
// list; clicking locks it open (click the same one again, or another, to change).
// The locked region survives mouse-leave.
const hoveredRegion = ref(null);
const lockedRegion  = ref(null);
const activeRegionKey = computed(() => lockedRegion.value ?? hoveredRegion.value);
// Resolve straight from the bands so the side list works in either view.
const activeRegion = computed(() =>
    distributionData.value?.bands.find(b => b.key === activeRegionKey.value) ?? null,
);
const onRegionEnter = (key) => { hoveredRegion.value = key; };
const onRegionLeave = ()    => { hoveredRegion.value = null; };
const onRegionClick = (key) => {
    lockedRegion.value = lockedRegion.value === key ? null : key;
};

// ── Bars view: Top / Average / Low performer charts ─────────────────────────
// Each lists the provinces in that bucket as a horizontal bar of their weighted
// score. Buckets are per-tier (Top 20% / Average 70% / Low 10% of each size
// tier), matching the "of each tier" subtitles.
const perfSlice = (bucket) => {
    const slice = rankedOnly.value
        .filter(s => s.bucket === bucket)
        .slice()
        .sort((a, b) => getScore(b) - getScore(a));
    return {
        names:     slice.map(s => s.province),
        scores:    slice.map(s => getScore(s)),
        directors: slice.map(s => s.director || '-'),
        buckets:   slice.map(s => s.bucket),
    };
};
const top10Data = computed(() => perfSlice('Top'));
const avgData   = computed(() => perfSlice('Average'));
const underData = computed(() => perfSlice('Low'));

const perfTooltip = (data, i) => {
    const score = (data.scores[i] ?? 0).toFixed(2);
    const dir   = data.directors[i] ?? '-';
    const name  = data.names[i]     ?? '';
    const bk    = data.buckets[i]   ?? '-';
    const color = bucketColor(bk);
    const label = selectedCategory.value === 'overall' ? 'weighted score' : `${selectedCategory.value} contribution`;
    return `<div style="padding:8px 12px;font-size:12px;font-family:inherit;min-width:200px;">
                <div style="font-weight:700;margin-bottom:4px;">${name}</div>
                <div style="color:#64748b;margin-bottom:2px;">Director: ${dir}</div>
                <div style="color:#64748b;margin-bottom:6px;">Performer: ${bucketDisplay(bk)}</div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:${color};flex-shrink:0;"></span>
                    <strong style="color:${color};">${score}% ${label}</strong>
                </div>
            </div>`;
};

const makeHorizOptions = (data, big = false) => {
    const max = Math.max(...data.scores, 0);
    const min = data.scores.length ? Math.min(...data.scores) : 0;
    const computedMax = Math.max(10, Math.ceil((max * 1.15) / 5) * 5);
    // Start the axis just below the lowest bar (floored to a 5% step) so the
    // spread between provinces is visible instead of squashed against 0%.
    const computedMin = Math.max(0, Math.floor((min * 0.95) / 5) * 5);
    return {
        chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit',
                 animations: { enabled: true, speed: 700, animateGradually: { enabled: true, delay: 80 } } },
        plotOptions: { bar: { horizontal: true, barHeight: big ? '82%' : '68%', borderRadius: big ? 4 : 3, distributed: true, dataLabels: { position: 'center' } } },
        colors: data.buckets.map(bucketColor),
        fill: {
            type: 'gradient',
            gradient: {
                type: 'horizontal',
                shadeIntensity: 0,
                gradientToColors: data.buckets.map(bucketLight),
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100],
            },
        },
        legend: { show: false },
        grid: { borderColor: '#f1f5f9',
                xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } },
                padding: { left: 0, right: 12, top: -8, bottom: 0 } },
        dataLabels: { enabled: true, formatter: v => `${v.toFixed(1)}%`,
                      style: { fontSize: big ? '12px' : '10px', fontFamily: 'inherit', fontWeight: '600', colors: ['#fff'] } },
        xaxis: { categories: data.names, min: computedMin, max: computedMax,
                 labels: { formatter: v => `${v}%`, style: { fontSize: big ? '12px' : '10px', fontFamily: 'inherit', colors: '#94a3b8' } },
                 axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { fontSize: big ? '14px' : '10.5px', fontFamily: 'inherit', colors: '#475569' }, maxWidth: big ? 150 : 115 } },
        tooltip: { theme: 'light', custom: ({ dataPointIndex }) => perfTooltip(data, dataPointIndex) },
    };
};

const top10Options = computed(() => makeHorizOptions(top10Data.value, true));
const top10Series  = computed(() => [{ name: 'Weighted Score', data: top10Data.value.scores }]);
const underOptions = computed(() => makeHorizOptions(underData.value, true));
const underSeries  = computed(() => [{ name: 'Weighted Score', data: underData.value.scores }]);
// Average uses the same "big" bar styling as Top/Low so the bars match in size.
const avgOptions   = computed(() => makeHorizOptions(avgData.value, true));
const avgSeries    = computed(() => [{ name: 'Weighted Score', data: avgData.value.scores }]);

// Average can hold far more provinces than Top/Low. Size each bar so AVG_VISIBLE
// (16, the Top chart's max) fit in the column at once at the same bar size as Top,
// and let the wrapper scroll to reveal the rest. Floor at the column height so a
// small Average bucket still fills the column exactly like Top/Low.
const AVG_COL_HEIGHT = 500;
const AVG_VISIBLE    = 16;
const avgChartHeight = computed(() =>
    Math.max(AVG_COL_HEIGHT, Math.ceil(avgData.value.names.length * (AVG_COL_HEIGHT / AVG_VISIBLE))),
);


// ── Trend view: weighted score per province across years ──────────────────
const TREND_LIMIT = 15;

// Builds { years: [ascending...], provinceMap: { province: { year: score } } }
// honoring the active Tier / Island / Region filters and the active score
// (overall total or selected category subtotal).
const trendData = computed(() => {
    const years = [...props.available_years].sort((a, b) => a - b);
    const provinceMap = {};

    for (const year of years) {
        const yearData = props.rankings_by_year[year] ?? {};
        for (const [tier, rows] of Object.entries(yearData)) {
            if (selectedTier.value !== 'all' && tier !== selectedTier.value) continue;

            for (const r of rows) {
                if (!isRanked(r)) continue;

                if (selectedIsland.value !== 'all' && PROVINCE_REGIONS[r.province]?.island !== selectedIsland.value) continue;
                if (selectedRegion.value !== 'all' && r.region_name !== selectedRegion.value) continue;

                if (!provinceMap[r.province]) provinceMap[r.province] = {};
                provinceMap[r.province][year] = getScore(r);
            }
        }
    }

    return { years, provinceMap };
});

// All provinces under the active filters, sorted by their most recent year's
// score (best first) so the chip list and default selection are consistent.
const trendProvinceList = computed(() => {
    const { years, provinceMap } = trendData.value;
    const lastYear = years[years.length - 1];
    return Object.keys(provinceMap)
        .sort((a, b) => (provinceMap[b][lastYear] ?? -1) - (provinceMap[a][lastYear] ?? -1));
});

const TREND_COLORS = ['#4f46e5', '#16a34a', '#dc2626', '#d97706', '#0891b2', '#9333ea',
                       '#db2777', '#65a30d', '#0d9488', '#7c3aed', '#ea580c', '#2563eb',
                       '#be123c', '#15803d', '#a16207'];

// Stable color per province (by rank position) so a province keeps its color
// whether or not it's currently selected.
const provinceColorMap = computed(() => {
    const map = {};
    trendProvinceList.value.forEach((p, i) => { map[p] = TREND_COLORS[i % TREND_COLORS.length]; });
    return map;
});

// Provinces currently plotted, picked via the dropdown's checkboxes - only
// selected provinces are shown in the graph. Defaults to the top TREND_LIMIT
// whenever the filtered province list changes.
const selectedProvinces = ref([]);

watch(trendProvinceList, (list) => {
    selectedProvinces.value = list.slice(0, TREND_LIMIT);
}, { immediate: true });

const allTrendSelected  = computed(() => trendProvinceList.value.length > 0 && selectedProvinces.value.length === trendProvinceList.value.length);
const someTrendSelected = computed(() => selectedProvinces.value.length > 0);

const toggleSelectAllTrend = () => {
    selectedProvinces.value = allTrendSelected.value ? [] : [...trendProvinceList.value];
};

// Summary text shown inside the Provinces field - the field's own input is
// reserved for the search query, so the selection summary is rendered
// separately via the prepend-inner slot.
const trendSelectionLabel = computed(() => {
    const n = selectedProvinces.value.length;
    if (n === 0) return '';
    if (n === 1) return selectedProvinces.value[0];
    return `${n} provinces selected`;
});

const trendSeries = computed(() => {
    const { years, provinceMap } = trendData.value;
    return trendProvinceList.value
        .filter(p => selectedProvinces.value.includes(p))
        .map(p => ({
            name:  p,
            data:  years.map(y => provinceMap[p][y] ?? null),
            color: provinceColorMap.value[p],
        }));
});

const trendOptions = computed(() => ({
    chart: { type: 'line', toolbar: { show: true }, fontFamily: 'inherit',
             animations: { enabled: true, speed: 500 } },
    stroke: { curve: 'smooth', width: 2.5 },
    markers: { size: 4 },
    xaxis: { categories: trendData.value.years,
             title: { text: 'Year', style: { fontSize: '14px', fontFamily: 'inherit', fontWeight: '600' } },
             labels: { style: { fontSize: '14px', fontFamily: 'inherit' } } },
    yaxis: { min: 0, max: 100,
             title: { text: 'Weighted Score (%)', style: { fontSize: '14px', fontFamily: 'inherit', fontWeight: '600' } },
             labels: { formatter: v => `${v}%`, style: { fontSize: '14px', fontFamily: 'inherit' } } },
    grid: { borderColor: '#f1f5f9' },
    legend: { position: 'bottom', fontSize: '14px', fontFamily: 'inherit', showForSingleSeries: true,
              markers: { size: 8 }, itemMargin: { horizontal: 10, vertical: 6 } },
    tooltip: { y: { formatter: v => v == null ? '-' : `${v.toFixed(2)}%` } },
}));

// Match the right-side rest list's max height to the podium stage so the two
// columns line up flush at the bottom regardless of how many top-3 details or
// breakdown rows render. Without this the rest list either leaves a gap below
// the last row or overflows past the breakdown when many provinces are listed.
const podiumStageRef    = ref(null);
const podiumStageHeight = ref(0);
let podiumResizeObserver = null;

const updatePodiumHeight = () => {
    if (podiumStageRef.value) podiumStageHeight.value = podiumStageRef.value.offsetHeight;
};

onMounted(() => {
    nextTick(updatePodiumHeight);
    if (typeof ResizeObserver !== 'undefined') {
        podiumResizeObserver = new ResizeObserver(updatePodiumHeight);
        if (podiumStageRef.value) podiumResizeObserver.observe(podiumStageRef.value);
    }
});

onUnmounted(() => {
    if (podiumResizeObserver) podiumResizeObserver.disconnect();
});

// Re-measure when the podium re-renders (view toggle, filter change) since the
// ResizeObserver is rebound to a new DOM node when v-if flips podium ↔ table.
watch([viewMode, selectedYear, selectedTier, selectedCategory, selectedIsland, selectedRegion], async () => {
    await nextTick();
    updatePodiumHeight();
    if (podiumResizeObserver && podiumStageRef.value) {
        podiumResizeObserver.disconnect();
        podiumResizeObserver.observe(podiumStageRef.value);
    }
});

// ── Search-as-spotlight ───────────────────────────────────────────────────────
// Instead of filtering, search picks ONE row to spotlight: scroll the row into
// view and pulse a highlight a few times before settling. Works for Table and
// Podium. Match priority: province name prefix > province name substring >
// director name substring - surfaces the most intuitive guess first.
const podiumRestScrollRef = ref(null);

const searchTarget = computed(() => {
    const q = searchTerm.value?.trim().toLowerCase();
    if (!q) return null;
    const rows = rankedScores.value;
    return (
        rows.find(r => r.province.toLowerCase().startsWith(q))?.province ??
        rows.find(r => r.province.toLowerCase().includes(q))?.province ??
        rows.find(r => (r.director || '').toLowerCase().includes(q))?.province ??
        null
    );
});

const rowHighlightClass = (provinceName) =>
    provinceName && provinceName === searchTarget.value ? 'search-highlight' : '';

const tableRowProps = ({ item }) => ({
    'data-province': item.province,
    class: item.province === searchTarget.value ? 'search-highlight' : '',
});

// Restart the CSS animation on each new search by toggling the class off,
// forcing a reflow, then on - otherwise the same .search-highlight class
// just stays applied and the @keyframes never re-runs.
const restartHighlightAnimation = (el) => {
    if (!el) return;
    el.classList.remove('search-highlight');
    void el.offsetWidth;
    el.classList.add('search-highlight');
};

// When the search target changes, find the matching DOM row (whether it's a
// podium block, a podium-rest-row, or a data-table row), scroll it into view
// smoothly, and re-trigger the pulse.
watch(searchTarget, async (province) => {
    if (!province) return;
    await nextTick();
    // querySelectorAll finds it in any view; one of these will match.
    const targets = document.querySelectorAll(`[data-province="${CSS.escape(province)}"]`);
    targets.forEach(restartHighlightAnimation);
    if (targets[0]) {
        targets[0].scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
    }
});
</script>

<style scoped>
/* ── Type system ───────────────────────────────────────────────────────────
   Two families, both loaded at modest weights only (max 600) so nothing on
   the page reads as heavy/blocky - this is the look most modern SaaS
   dashboards have settled on (Linear, Vercel, Stripe, Plane).

   • Inter (text) - every word on the page. 400 for body, 500 for table
     headers / chip labels / sub-headers, 600 only for the strongest
     hierarchy (section titles, KPI values). Nothing 700+. The taller
     x-height and humanist proportions keep dense rows legible without
     having to lean on weight to create emphasis.
   • IBM Plex Mono (numeric) - ranks, percentages, breakdown scores. Plex
     Mono reads more like a corporate report than a code editor (less of
     the JetBrains "developer" feel), and its tabular numerals keep number
     columns from jittering between rows.

   We then *cap* weight on bold utility classes inside the dashboard so the
   inherited Vuetify `font-weight-bold` (700) and `font-weight-black` (900)
   that exist throughout the template land at 600 instead. This is the
   single biggest change in feel - no thick text anywhere. */
.dashboard-root {
    --font-text: 'Inter', 'Figtree', system-ui, -apple-system, 'Segoe UI', sans-serif;
    --font-num:  'IBM Plex Mono', ui-monospace, 'SFMono-Regular', Menlo, monospace;

    font-family: var(--font-text);
    font-feature-settings: 'cv11', 'ss01', 'ss03';
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    color: #1f2937;
}

/* Cap weight across the dashboard. font-weight-bold (700) → 600,
   font-weight-black (900) → 600. Keeps hierarchy distinguishable from
   normal text without anything ever crossing into "thick". */
.dashboard-root :deep(.font-weight-bold),
.dashboard-root :deep(b),
.dashboard-root :deep(strong) { font-weight: 600 !important; }
.dashboard-root :deep(.font-weight-black) { font-weight: 600 !important; }
.dashboard-root :deep(.font-weight-medium) { font-weight: 500 !important; }

/* Section titles (Top / Average / Low Performers, PRISM Ranking Matrix,
   podium banner) - semibold + tighter tracking, but still Inter, so they
   sit in the same family as the rows beneath them. */
.dashboard-root .chart-header :deep(.font-weight-bold),
.dashboard-root .podium-banner :deep(.text-body-2),
.dashboard-root :deep(.qc-value) {
    font-weight: 600 !important;
    letter-spacing: -0.005em;
}

/* The three sizes used most: text-h4 (KPI values), text-body-2 (section
   titles, table cells), text-caption (subtitles, metadata). Hold them to
   600/500/400 respectively - gives a 3-step hierarchy without ever going
   heavy. */
.dashboard-root :deep(.text-h4),
.dashboard-root :deep(.text-h5),
.dashboard-root :deep(.text-h6) {
    font-weight: 600 !important;
    letter-spacing: -0.015em;
}

/* Tone down the podium - the gold/silver/bronze blocks were the heaviest
   text on the page. 500/600 weights here keep the visual order (1st bigger
   than 2nd bigger than 3rd) but lose the "shouty" feel. */
.dashboard-root .podium-province { font-weight: 600; letter-spacing: -0.01em; }
.dashboard-root .podium-region   { font-weight: 400; }
.dashboard-root .podium-score    { font-weight: 600; letter-spacing: -0.02em; }
.dashboard-root .podium-rank-num { font-weight: 500; letter-spacing: 0.02em; }

/* Numeric family - ranks, percentages, score breakdowns. Tabular nums +
   `zero` (slashed zero) keep number columns aligned and readable. */
.dashboard-root .rank-medal,
.dashboard-root .podium-rest-rank,
.dashboard-root .breakdown-score,
.dashboard-root .score-pct,
.dashboard-root .score-counts,
.dashboard-root .cat-cell,
.dashboard-root :deep(.qc-subtitle) {
    font-family: var(--font-num);
    font-feature-settings: 'tnum', 'zero';
    font-weight: 500;
    letter-spacing: -0.01em;
}

/* Table headers - small caps treatment via uppercase + tracking, NOT bold.
   This is the clean way to separate header from body in a dense table
   without resorting to heavy weight. */
.dashboard-root .leaderboard-table :deep(thead th) {
    font-family: var(--font-text);
    font-weight: 500 !important;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
}

/* QuantityCard title - same small-caps treatment so the KPI cards visually
   echo the table header style. */
.dashboard-root :deep(.qc-title) {
    font-weight: 500 !important;
    letter-spacing: 0.06em;
    color: #64748b;
}

.filter-label {
    font-size: 13px;
    font-weight: 700;
    color: #475569;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}
.segmented {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 3px;
    background: #f1f5f9;
    border-radius: 9px;
    border: 1px solid #e2e8f0;
}
.segmented-btn {
    appearance: none;
    border: none;
    background: transparent;
    color: #475569;
    font-size: 14px;
    font-weight: 600;
    padding: 7px 15px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    font-family: inherit;
    line-height: 1.3;
    white-space: nowrap;
}
.segmented-btn:hover:not(.active) { color: #0f172a; background: rgba(255, 255, 255, 0.6); }
.segmented-btn.active {
    background: #ffffff;
    color: #0f172a;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
}

.seg-count {
    display: inline-block;
    margin-left: 6px;
    padding: 2px 9px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 700;
    line-height: 1.5;
    background: #e2e8f0;
    color: #334155;
}
.seg-count--all    { background: #e0e7ff; color: #4338ca; }
.seg-count--micro  { background: #cfd8dc; color: #455a64; }
.seg-count--small  { background: #b2dfdb; color: #00695c; }
.seg-count--medium { background: #c5cae9; color: #283593; }
.seg-count--large  { background: #d1c4e9; color: #4527a0; }

.category-btn-micro.active  { color: #455a64; background: #eceff1; box-shadow: 0 1px 2px rgba(69,90,100,0.10); }
.category-btn-small.active  { color: #00695c; background: #e0f2f1; box-shadow: 0 1px 2px rgba(0,105,92,0.10); }
.category-btn-medium.active { color: #283593; background: #e8eaf6; box-shadow: 0 1px 2px rgba(40,53,147,0.10); }
.category-btn-large.active  { color: #4527a0; background: #ede7f6; box-shadow: 0 1px 2px rgba(69,39,160,0.10); }

.chart-header { border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); }
.legend-dot   { display: inline-block; width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
.legend-dot--sm { width: 8px; height: 8px; }

.leaderboard-table :deep(tr) { cursor: default; }

.province-link, .director-link {
    text-decoration: none !important;
    color: inherit;
    transition: color 0.15s;
}
.province-link:hover { color: #4f46e5 !important; text-decoration: underline !important; }
.director-link:hover { color: #4f46e5 !important; text-decoration: underline !important; }
.leaderboard-table :deep(thead th) { font-size: 13.5px !important; font-weight: 700 !important; }
.leaderboard-table :deep(tbody td) { font-size: 14px !important; }
.leaderboard-table :deep(table) { width: 100% !important; }

.rank-medal { display: inline-block; font-size: 13px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
.rank-gold   { background: #fef9c3; color: #854d0e; }
.rank-silver { background: #f1f5f9; color: #475569; }
.rank-bronze { background: #ffedd5; color: #9a3412; }

.score-track { flex: 1; height: 10px; background: #f1f5f9; border-radius: 5px; overflow: hidden; }
.score-fill  { height: 100%; border-radius: 4px; transition: width 0.6s ease; }

.podium-banner {
    display: flex; align-items: center;
    background: linear-gradient(90deg, #fef9c3 0%, #fef3c7 60%, #fff 100%);
    border-left: 3px solid #ca8a04;
    border-radius: 0 6px 6px 0;
    padding: 8px 14px;
    box-shadow: 0 1px 2px rgba(202, 138, 4, 0.08);
}
.podium-stage   { width: 42%; flex-shrink: 0; display: flex; flex-direction: column; align-self: flex-start; }
.podium-items   { display: flex; align-items: flex-end; gap: 8px; }
.podium-item    { flex: 1; display: flex; flex-direction: column; align-items: center; }
.podium-info    { text-align: center; padding-bottom: 10px; }
.podium-province { font-size: 20px; font-weight: 700; line-height: 1.3; max-width: 185px; word-wrap: break-word; }
.podium-region   { font-size: 16.5px; color: #64748b; font-weight: 600; margin-top: 3px; line-height: 1.2; }
.podium-score   { font-size: 30px; font-weight: 800; line-height: 1; margin-top: 2px; }
.podium-block   { width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; border-radius: 8px 8px 0 0; color: white; }
.podium-rank-num { font-size: 22px; font-weight: 800; color: white; }
.podium-gold    { height: 210px; background: linear-gradient(160deg, #ca8a04, #fde047); }
.podium-silver  { height: 160px; background: linear-gradient(160deg, #64748b, #cbd5e1); }
.podium-bronze  { height: 120px; background: linear-gradient(160deg, #78350f, #c2410c); }

.podium-rest-row { display: flex; align-items: center; gap: 12px; padding: 7px 0; border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); }
.podium-rest-rank { width: 36px; text-align: right; flex-shrink: 0; font-size: 14px; color: #64748b; font-weight: 700; }
.podium-rest-scroll { overflow-y: auto; }

.podium-breakdown { display: flex; gap: 12px; margin-top: 18px; padding-top: 16px; border-top: 1px solid rgba(0,0,0,0.06); }
.breakdown-col    { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 5px; }
.breakdown-row    { display: flex; align-items: center; gap: 6px; cursor: default; }
/* Label width tuned so the longest label ("STRATEGIC") fits at 13px without
   crowding the score. Was 120px which overflowed when the podium-stage
   shrinks (42% width) - that's what was pushing the SUPPORT row off-screen
   or causing horizontal overflow per column. */
.breakdown-label  { font-size: 13px; font-weight: 600; color: #475569; width: 78px; flex-shrink: 0; white-space: nowrap; }
.breakdown-score  { font-size: 14px; font-weight: 700; min-width: 50px; text-align: right; flex-shrink: 0; margin-left: auto; }

.score-text { display: flex; flex-direction: column; line-height: 1.25; }
.score-pct  { font-size: 18px; font-weight: 700; white-space: nowrap; }
.score-counts { font-size: 13px; color: rgba(0, 0, 0, 0.6); white-space: nowrap; }

/* Highlights the column matching the active category filter so the user can
   instantly see which value drives the current sort + bucket. */
.cat-cell {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    transition: background 0.15s ease;
}
.cat-cell--active {
    background: rgba(99, 102, 241, 0.10);
    font-weight: 700;
}

/* Small weight chip embedded in the Category segmented buttons */
.cat-weight {
    display: inline-block;
    margin-left: 6px;
    padding: 2px 7px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
    background: #e0e7ff;
    color: #3730a3;
    line-height: 1.5;
}

/* Widened from 330 → 380 because the elderly-friendly font override
   (.score-cell .score-counts → 15px) made "CORE X · STRAT X · SUPP X"
   exceed the original 210px text slot and truncate to "SUP...".
   Score-text widened in lockstep so all three category values fit. */
.score-cell { display: flex; align-items: center; gap: 10px; width: 420px; flex-shrink: 0; }
.score-cell .score-track { flex: unset; width: 105px; flex-shrink: 0; }
.score-cell .score-text  { width: 300px; flex-shrink: 0; }
.score-cell .score-pct   { font-size: 15px; }
.score-cell .score-counts { font-size: 12.5px; overflow: hidden; text-overflow: ellipsis; }
.podium-rest-cell { width: 420px; }

/* Tighten the province dropdown's menu rows so more items fit without scrolling */
.trend-province-select :deep(.v-list-item) { min-height: 32px; padding-top: 2px; padding-bottom: 2px; }
.trend-province-select :deep(.v-list-item-title) { font-size: 13.5px; }
.trend-province-select :deep(.v-checkbox-btn) { min-height: unset; }
.trend-province-select :deep(.v-selection-control) { min-height: unset; }

/* ── Elderly-friendly legibility ───────────────────────────────────────────
   Higher-up reviewers need larger, higher-contrast text. These lift Vuetify's
   utility text and dense components inside the dashboard without reflowing the
   layout. Scoped, so they don't leak to other pages. */
.text-caption        { font-size: 16px !important; line-height: 1.5 !important; }
.text-body-2         { font-size: 18px !important; line-height: 1.5 !important; }
.text-body-1         { font-size: 20px !important; line-height: 1.5 !important; }
.text-subtitle-2     { font-size: 18px !important; line-height: 1.5 !important; }
.text-subtitle-1     { font-size: 20px !important; line-height: 1.5 !important; }
.text-h6             { font-size: 1.6rem  !important; line-height: 1.35 !important; }
.text-h5             { font-size: 1.9rem  !important; line-height: 1.3  !important; }
.text-h4             { font-size: 2.75rem !important; line-height: 1.2  !important; }
.text-h3             { font-size: 3.25rem !important; line-height: 1.15 !important; }

/* Chips, list items, buttons */
.leaderboard-table :deep(.v-chip),
:deep(.v-chip--size-x-small) { font-size: 15px !important; }
:deep(.v-chip--size-small)   { font-size: 17px !important; }
:deep(.v-list-item-title)    { font-size: 17px !important; }
:deep(.v-btn--size-small)    { font-size: 15px !important; }
:deep(.v-btn--size-x-small)  { font-size: 13.5px !important; }
:deep(.v-btn)                { font-size: 16px !important; }

/* Form fields (filter selects, search input, etc.) */
:deep(.v-field__input)                    { font-size: 18px; }
:deep(.v-field__input input)::placeholder { font-size: 17px; }
:deep(.v-field__label)                    { font-size: 16px; }

/* Data tables (PRISM ranking matrix) */
:deep(.v-data-table) { font-size: 17px !important; }
:deep(.v-data-table th) { font-size: 14px !important; }

/* Local font-size declarations sprinkled through the file - push them up too */
.cat-weight   { font-size: 15px !important; }
.score-cell .score-pct    { font-size: 19px !important; }
.score-cell .score-counts { font-size: 15px !important; }

.trend-province-select :deep(.v-selection-control__wrapper) { width: 28px; height: 28px; }
.trend-province-select :deep(.v-list-item-title) { font-size: 17px !important; }

/* Filter bar input/label overrides take precedence over the generic ones above */
.filter-select :deep(.v-field__input) { font-size: 17px !important; }
.filter-select :deep(.v-field__label) { font-size: 16px !important; }

/* QuantityCard title overline-style label needs more breathing room at the
   larger size - and the number itself wants to stand out as the headline */
:deep(.v-card-subtitle.text-caption) {
    font-size: 14px !important;
    letter-spacing: 0.08em !important;
}

/* Keep the field a fixed single-line height regardless of selection count -
   selections are rendered as a text summary via prepend-inner instead of chips. */
.trend-province-select :deep(.v-field__input) { flex-wrap: nowrap; }

/* Sticky filter shell - pins the filter bar just below the app navbar
   (v-toolbar default height = 64px) and reserves a small white "shelf"
   above the bar so it never reads as flush with the navbar when scrolled.
   The negative margin-top swallows the page's py-5 (20px) top padding so
   the gap above the bar at page top matches the 12px gap-3 below it. */
.dashboard-sticky-shell {
    position: sticky;
    top: 64px;
    z-index: 4;
    padding-top: 12px;
    margin-top: -20px;
    background: #ffffff;
}

/* The filter bar - consistent with the white cards around it (border,
   rounded corners, white background) but with a thin indigo top accent for
   identity and a stronger drop shadow than the cards so it visibly "lifts"
   above the page content when sticky. */
.dashboard-sticky-filters {
    background: #ffffff;
    border: 1px solid rgba(15, 23, 42, 0.12);
    border-top: 2px solid #4f46e5;
    border-radius: 8px;
    box-shadow: 0 8px 20px -6px rgba(15, 23, 42, 0.18),
                0 2px 4px rgba(15, 23, 42, 0.06);
}

/* Unified view-area inside the leaderboard card. All four views (Podium,
   Table, Trend, Map) live here and are sized to fill a fixed height so the
   card's overall height never changes when the user switches views - the
   page below the leaderboard stays put, making comparison between views
   "in place" rather than requiring a re-scroll each time. The Podium view
   scrolls internally if its content exceeds the slot.

   Height bumped from 540 → 600 so the third (SUPPORT) row of the podium
   category breakdown stays visible without scrolling. With the elderly-
   friendly font scaling, banner + podium-info + 210px gold block + 3
   breakdown rows + paddings clears 540 and was clipping SUPPORT. */
.lb-view-area {
    height: 600px;
    overflow: hidden;
    position: relative;
}
.lb-view-area > div[class*="px-5 pt-3 pb-5"] {
    height: 100%;
    overflow-y: auto;
}

/* Compact, single-line dropdown filters. Widths are tuned so the full row
   (Tier · Island · Region · Category · Year · view toggle) fits on one line
   at typical desktop widths, and wraps gracefully on narrow screens. We let
   Vuetify handle field height + the floating-label notch - overriding the
   internal padding or the notch ::before/::after misaligns the label and
   makes the border appear to cut through the label text. */
.filter-select               { width: 160px; flex-shrink: 0; }
.filter-select--wide         { width: 220px; }
.filter-select--narrow       { width: 110px; }

/* Thin slate divider between the Tier · Geography · Category groups in the
   sticky filter bar - quietly signals that Island/Region/Province belong
   together (they cascade) without adding background colour. */
.filter-divider {
    height: 32px;
    align-self: center;
    margin: 0 4px;
    opacity: 0.6;
}

/* Search highlight - three amber pulses, then settles into a quiet indigo
   tint so the searched province stays findable after the animation. Applied
   to data-table rows, podium top-3 blocks, and podium-rest rows. */
@keyframes search-pulse {
    0%, 100% { background-color: rgba(245, 158, 11, 0.45); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.55); }
    50%      { background-color: rgba(245, 158, 11, 0.15); box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.00); }
}
.search-highlight {
    background-color: rgba(99, 102, 241, 0.16) !important;
    animation: search-pulse 0.55s ease-in-out 3;
    transition: background-color 0.25s ease;
    border-radius: 6px;
}
/* v-data-table rows are <tr>s; the highlight applies to all cells inside */
.leaderboard-table :deep(tr.search-highlight) td {
    background-color: rgba(99, 102, 241, 0.14) !important;
}
.leaderboard-table :deep(tr.search-highlight) {
    animation: search-pulse 0.55s ease-in-out 3;
}

/* Performance Distribution card - fixed chart slot keeps page layout stable
   regardless of how many provinces are ranked in the active filter. */
.bell-card-body {
    height: 560px;
    overflow: hidden;
    position: relative;
    padding: 4px 4px 0;
}
.bell-empty {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

/* Performance bell - a normal curve whose area is shaded into Low 10% /
   Average 70% / Top 20% at the 10th and 80th percentiles, with a hover/lock
   side list of the provinces in each region. */
.bell-layout {
    display: flex;
    height: 100%;
}
.bell-wrap {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    flex-direction: column;
    padding: 6px 6px 8px 10px;
}
.bell-svg {
    flex: 1 1 auto;
    width: 100%;
    min-height: 0;
    display: block;
}
.bell-region-labels {
    position: relative;
    height: 64px;
    margin-top: 2px;
    padding-bottom: 4px;
}
.bell-region-label {
    position: absolute;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    line-height: 1.15;
    cursor: pointer;
    white-space: nowrap;
    transition: transform .1s ease;
}
.bell-region-label.is-active { transform: translateX(-50%) scale(1.06); }
.brl-name  { font-size: 13px; font-weight: 700; }
.brl-pct   { font-size: 20px; font-weight: 800; color: #0f172a; }
.brl-count { font-size: 11px; color: #64748b; }

/* Bars view: Top / Average / Low performer columns (toggle alternative to bell). */
.perf-row {
    display: flex;
    height: 100%;
}
.perf-col {
    flex: 1 1 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
    padding: 4px 8px 6px;
}
.perf-col + .perf-col {
    border-left: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.perf-col-head { padding: 2px 2px 4px; }
.perf-col-body {
    flex: 1 1 auto;
    min-height: 0;
    position: relative;
    overflow: hidden;
}
.perf-empty {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-align: center;
}
.avg-chart-scroll {
    height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
}
.avg-chart-scroll::-webkit-scrollbar       { width: 8px; }
.avg-chart-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.avg-chart-scroll::-webkit-scrollbar-track { background: transparent; }

/* Compact bar/bell toggle */
.bell-view-toggle { height: 28px; }
.bell-view-toggle :deep(.v-btn) { text-transform: none; letter-spacing: 0; }

.bell-side {
    flex: 0 0 248px;
    width: 248px;
    display: flex;
    flex-direction: column;
    min-height: 0;
    border-left: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    padding: 8px 4px 8px 12px;
}
.bell-side-head {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 0 6px 8px;
    margin-bottom: 4px;
    border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.bell-side-dot   { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.bell-side-title { font-size: 13px; font-weight: 700; }
.bell-side-meta  { font-size: 11px; color: #64748b; }
.bell-side-list  { flex: 1 1 auto; overflow-y: auto; min-height: 0; padding-right: 2px; }
.bell-side-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 3px 6px;
    border-radius: 6px;
    font-size: 12px;
}
.bell-side-row:hover { background: rgba(99, 102, 241, 0.07); }
.bell-side-row .rank  { width: 20px; text-align: right; color: #94a3b8; font-size: 11px; flex-shrink: 0; }
.bell-side-row .name  { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bell-side-row .score { font-variant-numeric: tabular-nums; color: #475569; flex-shrink: 0; }
.bell-side-empty {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    text-align: center;
    color: #94a3b8;
    font-size: 12px;
    padding: 12px;
}
.bell-side-empty .hint { font-size: 11px; opacity: 0.8; }
</style>
