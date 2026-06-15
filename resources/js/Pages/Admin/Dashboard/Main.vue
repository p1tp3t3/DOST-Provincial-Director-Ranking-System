<template>
    <Head title="Dashboard" />
    <div class="d-flex flex-column gap-3">

        <!-- Sticky filter bar — single compact row of dropdowns + view toggle,
             shown identically across all view modes (podium/table/trend/map). -->
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
                />
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

                <!-- Provinces multi-select only used by trend view -->
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

                <v-btn-toggle v-model="viewMode" mandatory density="compact" variant="outlined" divided class="ml-auto">
                    <v-btn value="podium" size="small" title="Podium view"><v-icon size="15">mdi-podium-gold</v-icon></v-btn>
                    <v-btn value="table"  size="small" title="Table view"><v-icon size="15">mdi-table-large</v-icon></v-btn>
                    <v-btn value="trend"  size="small" title="Trend view"><v-icon size="15">mdi-chart-line</v-icon></v-btn>
                    <v-btn value="map"    size="small" title="Map view"><v-icon size="15">mdi-map-outline</v-icon></v-btn>
                </v-btn-toggle>
            </div>
        </div>

        <!-- Stat Cards -->
        <v-row dense>
            <v-col cols="12" sm="3">
                <QuantityCard title="Total Provinces"            :quantity="total_provinces"            :icon="RiBuildingLine"      color="indigo" />
            </v-col>
            <v-col cols="12" sm="3">
                <QuantityCard title="Provincial Directors"       :quantity="total_directors"            :icon="RiUserStarLine"      color="indigo" />
            </v-col>
            <v-col cols="12" sm="3">
                <QuantityCard title="Total Employees"            :quantity="total_employees"            :icon="RiGroupLine"         color="indigo" />
            </v-col>
            <v-col cols="12" sm="3">
                <QuantityCard title="Active Reporting Provinces" :quantity="active_reporting_provinces" :icon="RiCheckboxCircleLine" color="indigo" />
            </v-col>
        </v-row>

        <!-- Top · Average · Low Performers, side by side. Average scrolls
             internally so its 40+ entries don't dominate page height.
             All three cards share .perf-card so the chart slots are pinned
             to the exact same height — no perceptible drift between them. -->
        <v-row dense>
            <v-col cols="12" md="4">
                <v-card border elevation="0" rounded="lg" class="overflow-hidden perf-card">
                    <div class="chart-header px-4 pt-3 pb-2">
                        <div class="d-flex align-center gap-2">
                            <v-icon size="15" color="success">mdi-star-circle-outline</v-icon>
                            <span class="text-body-2 font-weight-bold">Top Performers</span>
                        </div>
                        <div class="text-caption text-medium-emphasis">
                            Top 20% of each tier · {{ tierLabel }} · {{ selectedYear }}
                        </div>
                    </div>
                    <div class="perf-card-body">
                        <VueApexCharts
                            v-if="top10Data.names.length"
                            type="bar"
                            height="360"
                            :options="top10Options"
                            :series="top10Series"
                            :key="`top10-${selectedYear}-${selectedTier}`"
                        />
                        <div v-else class="perf-empty">
                            <v-icon size="48" color="blue-grey">mdi-podium-gold</v-icon>
                            <div class="text-body-2 font-weight-medium">No Top Performers</div>
                            <div class="text-caption text-medium-emphasis">Tier is too small to bucket, or no rankings yet</div>
                        </div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card border elevation="0" rounded="lg" class="overflow-hidden perf-card">
                    <div class="chart-header px-4 pt-3 pb-2">
                        <div class="d-flex align-center gap-2">
                            <v-icon size="15" color="warning">mdi-trending-neutral</v-icon>
                            <span class="text-body-2 font-weight-bold">Average Performers</span>
                        </div>
                        <div class="text-caption text-medium-emphasis">
                            Middle 60% of each tier · {{ tierLabel }} · {{ selectedYear }}
                        </div>
                    </div>
                    <div class="perf-card-body">
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
                            <v-icon size="48" color="blue-grey">mdi-trending-neutral</v-icon>
                            <div class="text-body-2 font-weight-medium">No Average Performers</div>
                            <div class="text-caption text-medium-emphasis">Tier is too small to bucket, or no rankings yet</div>
                        </div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card border elevation="0" rounded="lg" class="overflow-hidden perf-card">
                    <div class="chart-header px-4 pt-3 pb-2">
                        <div class="d-flex align-center gap-2">
                            <v-icon size="15" color="error">mdi-alert-circle-outline</v-icon>
                            <span class="text-body-2 font-weight-bold">Low Performers</span>
                        </div>
                        <div class="text-caption text-medium-emphasis">
                            Bottom 20% of each tier · {{ tierLabel }} · {{ selectedYear }}
                        </div>
                    </div>
                    <div class="perf-card-body">
                        <VueApexCharts
                            v-if="underData.names.length"
                            type="bar"
                            height="360"
                            :options="underOptions"
                            :series="underSeries"
                            :key="`under-${selectedYear}-${selectedTier}`"
                        />
                        <div v-else class="perf-empty">
                            <v-icon size="48" color="success">mdi-check-decagram-outline</v-icon>
                            <div class="text-body-2 font-weight-medium">No Low Performers</div>
                            <div class="text-caption text-medium-emphasis">Tier is too small to bucket, or no rankings yet</div>
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
                                <span class="text-body-2 font-weight-bold">PSTD Ranking Matrix</span>
                                <v-tooltip location="bottom" max-width="360">
                                    <template #activator="{ props: tip }">
                                        <v-chip v-bind="tip" size="x-small" variant="tonal" color="blue-grey" prepend-icon="mdi-information-outline" class="cursor-pointer">
                                            Scoring
                                        </v-chip>
                                    </template>
                                    <div class="pa-1">
                                        <div class="font-weight-bold mb-1">Weighted PSTD Matrix Score</div>
                                        <div class="text-caption mb-2 opacity-80">
                                            For each of the 37 KPIs we compute accomplishment % vs target, map it to an adjective score (Outstanding 1.0 / VS 0.8 / Sat 0.6 / Avg 0.4 / Unsat 0.2 / Poor 0.0), then multiply by the KPI's weight. CORE = 60%, FUNCTIONAL = 30%, SUPPORT = 10%.
                                        </div>
                                        <div class="text-caption opacity-70">
                                            Provinces are ranked within their size tier. Top 20% by rank = Top Performers, next 60% = Average, bottom 20% = Low.
                                        </div>
                                    </div>
                                </v-tooltip>
                                <v-chip size="x-small" color="primary" variant="tonal" class="font-weight-medium">
                                    {{ rankedScores.length }}
                                    <template v-if="selectedTier !== 'all'"> · {{ tierLabel }}</template>
                                </v-chip>
                            </div>
                            <div class="d-flex align-center gap-3 flex-wrap">
                                <div class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#15803d;"></span>
                                    <span class="text-caption text-medium-emphasis">Top ({{ bucketCounts.Top }})</span>
                                </div>
                                <div class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#ca8a04;"></span>
                                    <span class="text-caption text-medium-emphasis">Average ({{ bucketCounts.Average }})</span>
                                </div>
                                <div class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#b91c1c;"></span>
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

                    <!-- Tier segmented + Table/Podium view toggle -->
                    <div class="d-flex align-center gap-3 px-4 py-2 flex-wrap">
                        <div class="d-flex align-center gap-2">
                            <span class="filter-label">Tier</span>
                            <div class="segmented category-segmented">
                                <button class="segmented-btn" :class="{ active: selectedTier === 'all' }" @click="selectedTier = 'all'; searchTerm = ''">
                                    All <span class="seg-count seg-count--all">{{ tierCounts.all }}</span>
                                </button>
                                <button
                                    v-for="t in tiers"
                                    :key="t.value"
                                    class="segmented-btn"
                                    :class="['category-btn-' + t.value, { active: selectedTier === t.value }]"
                                    @click="selectedTier = t.value; searchTerm = ''"
                                >
                                    {{ t.label }}
                                    <span class="seg-count" :class="'seg-count--' + t.value">{{ tierCounts[t.value] ?? 0 }}</span>
                                </button>
                            </div>
                        </div>
                        <v-btn-toggle v-model="viewMode" mandatory density="compact" variant="outlined" divided class="ml-auto">
                            <v-btn value="podium" size="small" title="Podium view"><v-icon size="15">mdi-podium-gold</v-icon></v-btn>
                            <v-btn value="table"  size="small" title="Table view"><v-icon size="15">mdi-table-large</v-icon></v-btn>
                            <v-btn value="trend"  size="small" title="Trend view"><v-icon size="15">mdi-chart-line</v-icon></v-btn>
                            <v-btn value="map"    size="small" title="Map view"><v-icon size="15">mdi-map-outline</v-icon></v-btn>
                        </v-btn-toggle>
                    </div>

                    <!-- Island + Region filter row -->
                    <div v-if="viewMode !== 'map'" class="d-flex align-center gap-3 px-4 py-2 flex-wrap" style="border-top:1px solid rgba(0,0,0,0.06);">
                        <div class="d-flex align-center gap-2">
                            <span class="filter-label">Island</span>
                            <div class="segmented">
                                <button class="segmented-btn" :class="{ active: selectedIsland === 'all' }" @click="setIsland('all')">All</button>
                                <button
                                    v-for="isl in ISLANDS" :key="isl.value"
                                    class="segmented-btn"
                                    :class="{ active: selectedIsland === isl.value }"
                                    @click="setIsland(isl.value)"
                                >{{ isl.label }}</button>
                            </div>
                        </div>
                        <div class="d-flex align-center gap-2">
                            <span class="filter-label">Region</span>
                            <v-select
                                v-model="selectedRegion"
                                :items="regionOptions"
                                item-title="label"
                                item-value="value"
                                density="compact"
                                variant="outlined"
                                hide-details
                                style="min-width:220px;"
                            />
                        </div>
                        <div v-if="viewMode === 'trend'" class="d-flex align-center gap-2">
                            <v-autocomplete
                                v-show="trendProvinceList.length"
                                v-model="selectedProvinces"
                                :items="trendProvinceList"
                                multiple
                                density="compact"
                                variant="outlined"
                                label="Provinces"
                                placeholder="Search provinces…"
                                hide-details
                                location="bottom start"
                                :menu-props="{ maxHeight: 320 }"
                                class="trend-province-select"
                                style="min-width:220px; max-width:280px;"
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
                        </div>
                    </div>

                    <!-- Category segmented (Overall / CORE / FUNCTIONAL / SUPPORT) + Year segmented -->
                    <div class="d-flex align-center gap-3 px-4 py-2 flex-wrap" style="border-top:1px solid rgba(0,0,0,0.06);">
                        <div class="d-flex align-center gap-2">
                            <span class="filter-label">Category</span>
                            <div class="segmented outcome-segmented">
                                <button
                                    class="segmented-btn"
                                    :class="{ active: selectedCategory === 'overall' }"
                                    @click="selectedCategory = 'overall'"
                                >Overall</button>
                                <button
                                    v-for="c in categoryOptions" :key="c.value"
                                    class="segmented-btn"
                                    :class="{ active: selectedCategory === c.value }"
                                    @click="selectedCategory = c.value"
                                >{{ c.label }} <span class="cat-weight">{{ c.weight }}</span></button>
                            </div>
                        </div>
                        <span v-if="selectedCategory !== 'overall'" class="text-caption text-medium-emphasis">
                            Ranking by <strong>{{ selectedCategory }}</strong> contribution
                            <template v-if="selectedTier !== 'all'"> · re-bucketed within {{ tierLabel }}</template>
                        </span>
                        <div class="ml-auto d-flex align-center gap-2">
                            <span class="filter-label">Year</span>
                            <div class="segmented">
                                <button
                                    v-for="y in available_years" :key="y"
                                    class="segmented-btn"
                                    :class="{ active: selectedYear === y }"
                                    @click="selectedYear = y"
                                >{{ y }}</button>
                            </div>
                        </div>
                    </div>

                    <v-divider />

                    <!-- Search row (table view only) -->
                    <div v-if="viewMode === 'table'" class="px-4 py-2 d-flex justify-end">
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

                    <!-- Table View -->
                    <v-data-table
                        v-if="viewMode === 'table'"
                        :headers="tableHeaders"
                        :items="rankedScores"
                        :search="searchTerm"
                        density="compact"
                        fixed-header
                        height="460"
                        hide-default-footer
                        :items-per-page="-1"
                        class="leaderboard-table"
                    >
                        <template #item.rank="{ item }">
                            <span v-if="!isRanked(item)" class="text-caption text-disabled">—</span>
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
                            <span v-else class="text-caption text-disabled">—</span>
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
                            <span v-else class="text-caption text-disabled">—</span>
                        </template>
                        <template #item.functional="{ item }">
                            <span v-if="isRanked(item)" class="text-caption cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'FUNCTIONAL' }">
                                {{ item.subtotals_pct.FUNCTIONAL.toFixed(1) }}%
                            </span>
                            <span v-else class="text-caption text-disabled">—</span>
                        </template>
                        <template #item.support="{ item }">
                            <span v-if="isRanked(item)" class="text-caption cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'SUPPORT' }">
                                {{ item.subtotals_pct.SUPPORT.toFixed(1) }}%
                            </span>
                            <span v-else class="text-caption text-disabled">—</span>
                        </template>

                        <template #item.total_pct="{ item }">
                            <span v-if="isRanked(item)" class="score-pct cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'overall' }" :style="{ color: bucketColor(item.bucket) }">
                                {{ item.total_pct.toFixed(2) }}%
                            </span>
                            <span v-else class="text-caption text-disabled">—</span>
                        </template>

                        <template #no-data>
                            <div class="text-center py-8 text-medium-emphasis text-body-2">No provinces found</div>
                        </template>
                    </v-data-table>

                    <!-- Trend View -->
                    <div v-else-if="viewMode === 'trend'" class="px-4 pt-3 pb-5">
                        <VueApexCharts
                            v-if="trendSeries.length"
                            type="line"
                            height="420"
                            :options="trendOptions"
                            :series="trendSeries"
                        />
                        <div v-else class="text-center py-8 text-medium-emphasis text-body-2">
                            {{ trendProvinceList.length ? 'Select at least one province to display' : 'No ranked provinces found for this filter' }}
                        </div>
                    </div>

                    <!-- Map View -->
                    <div v-else-if="viewMode === 'map'" class="pa-0">
                        <PhilippinesMap
                            key="ph-map-dashboard"
                            :scores="mapFilteredScores"
                            :trends="trendsByProvince"
                            :selected-year="selectedYear"
                            :selected-tier="selectedTier"
                            height="calc(100vh - 360px)"
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
                                    <div v-if="top3[0]" class="podium-item">
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
                                    <div v-if="top3[1]" class="podium-item">
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
                                    <div v-if="top3[2]" class="podium-item">
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
                                        <div v-for="cat in ['CORE','FUNCTIONAL','SUPPORT']" :key="cat" class="breakdown-row">
                                            <span class="breakdown-label">{{ cat }}</span>
                                            <span class="breakdown-score">{{ p.subtotals_pct[cat].toFixed(1) }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <v-divider vertical class="mx-1" />

                            <div class="flex-1 podium-rest-scroll" :style="podiumStageHeight ? { maxHeight: podiumStageHeight + 'px' } : null">
                                <div v-for="item in restList" :key="item.province" class="podium-rest-row">
                                    <span class="podium-rest-rank">#{{ item.rank }}</span>
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
                                        <div class="score-track">
                                            <div class="score-fill" :style="{ width: `${Math.min(getScore(item), 100)}%`, background: bucketColor(item.bucket) }" />
                                        </div>
                                        <div class="score-text">
                                            <span class="score-pct" :style="{ color: bucketColor(item.bucket) }">{{ getScore(item).toFixed(2) }}%</span>
                                            <span class="score-counts">CORE {{ item.subtotals_pct.CORE.toFixed(1) }} · FUNC {{ item.subtotals_pct.FUNCTIONAL.toFixed(1) }} · SUPP {{ item.subtotals_pct.SUPPORT.toFixed(1) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="!restList.length" class="text-center py-8 text-caption text-medium-emphasis">
                                    Only {{ top3.length }} province(s) in this filter
                                </div>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import QuantityCard from '@/Components/Cards/QuantityCard.vue';
import PhilippinesMap from '@/Components/Map/PhilippinesMap.vue';
import { RiGroupLine, RiUserStarLine, RiCheckboxCircleLine, RiBuildingLine } from '@remixicon/vue';
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

// Island groupings come from the `region.island_under` enum (luzon/visayas/mindanao).
const ISLANDS = [
    { value: 'luzon',    label: 'Luzon'    },
    { value: 'visayas',  label: 'Visayas'  },
    { value: 'mindanao', label: 'Mindanao' },
];

const selectedYear     = ref(props.available_years[0] ?? new Date().getFullYear());
const selectedTier     = ref('all');
const selectedCategory = ref('overall'); // 'overall' | 'CORE' | 'FUNCTIONAL' | 'SUPPORT'
const selectedIsland   = ref('all');
const selectedRegion   = ref('all');
const searchTerm       = ref('');
const viewMode         = ref('podium');

const setIsland = (island) => {
    selectedIsland.value = island;
    selectedRegion.value = 'all';
};

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
// tier when the user picks CORE / FUNCTIONAL / SUPPORT instead of Overall.
// If config/ranking.php values change, update these to match.
const BUCKET_TOP_PCT   = 0.20;
const BUCKET_UNDER_PCT = 0.20;
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
// resort globally by total_pct so the table is comparable across tiers — but
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

const mapFilteredScores = computed(() =>
    selectedTier.value === 'all'
        ? mapAllTierRows.value
        : mapAllTierRows.value.filter(r => r.category === selectedTier.value)
);

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
        rows = rows.filter(r => r.island_under === selectedIsland.value);
    }
    if (selectedRegion.value !== 'all') {
        rows = rows.filter(r => r.region_id === selectedRegion.value);
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
    // ordering — unranked rows are appended at the end with null rank/bucket.
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
const tierSelectItems = computed(() => [
    { value: 'all', label: `All Tiers (${tierCounts.value.all ?? 0})` },
    ...tiers.map(t => ({ value: t.value, label: `${t.label} (${tierCounts.value[t.value] ?? 0})` })),
]);

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

// Podium + rest list operate on RANKED rows only so unranked never appears on
// the leader podium. Unranked provinces still show in the table at the bottom.
const rankedOnly       = computed(() => rankedScores.value.filter(isRanked));
const unrankedProvinces = computed(() => rankedScores.value.filter(r => !isRanked(r)));
const top3     = computed(() => rankedOnly.value.slice(0, 3));
const restList = computed(() => rankedOnly.value.slice(3));

// Performer (Top / Average / Low) is bucketed per-tier so it's only meaningful
// when a single tier is selected. In the "All Tiers" view we hide the column
// entirely — mixing buckets across tiers would put a SMALL "Top" below a
// LARGE "Average" on the absolute % axis, which reads as inconsistent.
const tableHeaders = computed(() => {
    const cols = [
        { title: 'Rank',     key: 'rank',     width: '70px',  sortable: false },
        { title: 'Performer', key: 'bucket',  width: '110px', sortable: true  },
        { title: 'Province', key: 'province', sortable: true  },
        { title: 'Director', key: 'director', sortable: false },
        { title: 'Tier',     key: 'category', width: '90px',  align: 'center', sortable: true  },
        { title: 'CORE 60%', key: 'core',     width: '90px',  align: 'center', sortable: false },
        { title: 'FUNC 30%', key: 'functional', width: '90px', align: 'center', sortable: false },
        { title: 'SUPP 10%', key: 'support',  width: '90px',  align: 'center', sortable: false },
        { title: 'Total',    key: 'total_pct', width: '110px', align: 'center', sortable: true  },
    ];
    return selectedTier.value === 'all' ? cols.filter(c => c.key !== 'bucket') : cols;
});

const bucketColor = (b) => ({ Top: '#15803d', Average: '#ca8a04', Low: '#b91c1c' }[b] ?? '#64748b');
const bucketDisplay = (b) => ({ Top: 'Top', Average: 'Average', Low: 'Low' }[b] ?? '—');
const tierColor = (cat) => ({ micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple' }[cat] ?? 'grey');

// Top Performers = anyone with bucket=Top in the current filter (sorted desc).
// Mirrors underData below so both charts always show the matching 20% of each tier.
const top10Data = computed(() => {
    const slice = rankedOnly.value
        .filter(s => s.bucket === 'Top')
        .slice()
        .sort((a, b) => getScore(b) - getScore(a));
    return {
        names:     slice.map(s => s.province),
        scores:    slice.map(s => getScore(s)),
        directors: slice.map(s => s.director || '—'),
        buckets:   slice.map(s => s.bucket),
    };
});

// Low Performers = anyone with bucket=Low in the current filter (sorted desc)
const underData = computed(() => {
    const slice = rankedOnly.value
        .filter(s => s.bucket === 'Low')
        .slice()
        .sort((a, b) => getScore(b) - getScore(a));
    return {
        names:     slice.map(s => s.province),
        scores:    slice.map(s => getScore(s)),
        directors: slice.map(s => s.director || '—'),
        buckets:   slice.map(s => s.bucket),
    };
});

// Average Performers = anyone with bucket=Average in the current filter (sorted desc).
// Same shape as top10Data/underData so the makeHorizOptions helper just works.
const avgData = computed(() => {
    const slice = rankedOnly.value
        .filter(s => s.bucket === 'Average')
        .slice()
        .sort((a, b) => getScore(b) - getScore(a));
    return {
        names:     slice.map(s => s.province),
        scores:    slice.map(s => getScore(s)),
        directors: slice.map(s => s.director || '—'),
        buckets:   slice.map(s => s.bucket),
    };
});

const tooltipHtml = (data, i) => {
    const score = (data.scores[i] ?? 0).toFixed(2);
    const dir   = data.directors[i] ?? '—';
    const name  = data.names[i]     ?? '';
    const bk    = data.buckets[i]   ?? '—';
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

const makeHorizOptions = (data) => {
    const max = Math.max(...data.scores, 0);
    const computedMax = Math.max(10, Math.ceil((max * 1.15) / 5) * 5);
    return {
        chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit',
                 animations: { enabled: true, speed: 700, animateGradually: { enabled: true, delay: 80 } } },
        plotOptions: { bar: { horizontal: true, barHeight: '68%', borderRadius: 3, distributed: true } },
        colors: data.buckets.map(bucketColor),
        legend: { show: false },
        grid: { borderColor: '#f1f5f9',
                xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } },
                padding: { left: 0, right: 12, top: -8, bottom: 0 } },
        dataLabels: { enabled: true, formatter: v => `${v.toFixed(1)}%`,
                      style: { fontSize: '10px', fontFamily: 'inherit', fontWeight: '600', colors: ['#fff'] } },
        xaxis: { categories: data.names, min: 0, max: computedMax,
                 labels: { formatter: v => `${v}%`, style: { fontSize: '10px', fontFamily: 'inherit', colors: '#94a3b8' } },
                 axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { fontSize: '10.5px', fontFamily: 'inherit', colors: '#475569' }, maxWidth: 115 } },
        tooltip: { theme: 'light', custom: ({ dataPointIndex }) => tooltipHtml(data, dataPointIndex) },
    };
};

const top10Options = computed(() => makeHorizOptions(top10Data.value));
const top10Series  = computed(() => [{ name: 'Weighted Score', data: top10Data.value.scores }]);
const underOptions = computed(() => makeHorizOptions(underData.value));
const underSeries  = computed(() => [{ name: 'Weighted Score', data: underData.value.scores }]);
const avgOptions   = computed(() => makeHorizOptions(avgData.value));
const avgSeries    = computed(() => [{ name: 'Weighted Score', data: avgData.value.scores }]);

// ~26px per bar keeps labels legible — Average can have 40+ entries at All Tiers,
// so a fixed 360px would squash bars to a few pixels each.
const avgChartHeight = computed(() => Math.max(360, avgData.value.names.length * 26 + 40));

// ── Trend view: weighted score per province across years ──────────────────
const TREND_LIMIT = 15;

// Region dropdown narrows to the selected island's regions, plus "All".
const regionOptions = computed(() => {
    const regions = selectedIsland.value === 'all'
        ? props.regions
        : props.regions.filter(r => r.island_under === selectedIsland.value);
    return [
        { value: 'all', label: 'All Regions' },
        ...regions.map(r => ({ value: r.id, label: r.name })),
    ];
});

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

                if (selectedIsland.value !== 'all' && r.island_under !== selectedIsland.value) continue;
                if (selectedRegion.value !== 'all' && r.region_id !== selectedRegion.value) continue;

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

// Provinces currently plotted, picked via the dropdown's checkboxes — only
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

// Summary text shown inside the Provinces field — the field's own input is
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
             title: { text: 'Year', style: { fontSize: '11px', fontFamily: 'inherit' } },
             labels: { style: { fontSize: '11px', fontFamily: 'inherit' } } },
    yaxis: { min: 0, max: 100,
             title: { text: 'Weighted Score (%)', style: { fontSize: '11px', fontFamily: 'inherit' } },
             labels: { formatter: v => `${v}%`, style: { fontSize: '11px', fontFamily: 'inherit' } } },
    grid: { borderColor: '#f1f5f9' },
    legend: { position: 'bottom', fontSize: '11px', fontFamily: 'inherit', showForSingleSeries: true,
              markers: { size: 6 }, itemMargin: { horizontal: 8, vertical: 4 } },
    tooltip: { y: { formatter: v => v == null ? '—' : `${v.toFixed(2)}%` } },
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
</script>

<style scoped>
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

.podium-breakdown { display: flex; gap: 20px; margin-top: 18px; padding-top: 16px; border-top: 1px solid rgba(0,0,0,0.06); }
.breakdown-col    { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 5px; }
.breakdown-row    { display: flex; align-items: center; gap: 8px; cursor: default; }
.breakdown-label  { font-size: 13px; font-weight: 600; color: #475569; width: 120px; flex-shrink: 0; white-space: nowrap; }
.breakdown-score  { font-size: 14px; font-weight: 700; width: 64px; text-align: right; flex-shrink: 0; margin-left: auto; }

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

.score-cell { display: flex; align-items: center; gap: 10px; width: 330px; flex-shrink: 0; }
.score-cell .score-track { flex: unset; width: 105px; flex-shrink: 0; }
.score-cell .score-text  { width: 210px; flex-shrink: 0; }
.score-cell .score-pct   { font-size: 15px; }
.score-cell .score-counts { font-size: 12.5px; overflow: hidden; text-overflow: ellipsis; }
.podium-rest-cell { width: 330px; }

/* Tighten the province dropdown's menu rows so more items fit without scrolling */
.trend-province-select :deep(.v-list-item) { min-height: 32px; padding-top: 2px; padding-bottom: 2px; }
.trend-province-select :deep(.v-list-item-title) { font-size: 13.5px; }
.trend-province-select :deep(.v-checkbox-btn) { min-height: unset; }
.trend-province-select :deep(.v-selection-control) { min-height: unset; }

/* ── Elderly-friendly legibility ───────────────────────────────────────────
   Higher-up reviewers need larger, higher-contrast text. These lift Vuetify's
   utility text and dense components inside the dashboard without reflowing the
   layout. Scoped, so they don't leak to other pages. */
.text-caption { font-size: 13px !important; line-height: 1.45 !important; }
.text-body-2  { font-size: 15px !important; line-height: 1.45 !important; }
.leaderboard-table :deep(.v-chip),
:deep(.v-chip--size-x-small) { font-size: 12.5px !important; }
:deep(.v-chip--size-small)   { font-size: 13.5px !important; }
/* Selected value shown in the Region select / Search field */
:deep(.v-field__input) { font-size: 15px; }
:deep(.v-field__input input)::placeholder { font-size: 14px; }
.trend-province-select :deep(.v-selection-control__wrapper) { width: 28px; height: 28px; }

/* Keep the field a fixed single-line height regardless of selection count —
   selections are rendered as a text summary via prepend-inner instead of chips. */
.trend-province-select :deep(.v-field__input) { flex-wrap: nowrap; }

/* Sticky filter bar — pins the ranking's interactive controls just below the
   app navbar (v-toolbar default height = 64px) so they stay reachable while
   the user scrolls through the stat cards, charts, and leaderboard below. */
.dashboard-sticky-filters {
    position: sticky;
    top: 64px;
    z-index: 4;
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.12);
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

/* Compact, single-line dropdown filters. Widths are tuned so the full row
   (Tier · Island · Region · Category · Year · view toggle) fits on one line
   at typical desktop widths, and wraps gracefully on narrow screens. */
.filter-select               { width: 160px; flex-shrink: 0; }
.filter-select--wide         { width: 220px; }
.filter-select--narrow       { width: 110px; }
.filter-select :deep(.v-field__input)       { font-size: 14px; padding-top: 6px; }
.filter-select :deep(.v-field__label)       { font-size: 13px; }
.filter-select :deep(.v-field__append-inner) { padding-top: 8px; }

/* Top / Average / Low performer cards — header + a fixed-height chart slot.
   Pinning the slot to exactly 360px guarantees all three cards match in
   total height regardless of bar count or whether the x-axis is visible. */
.perf-card-body {
    height: 360px;
    overflow: hidden;
    position: relative;
}
.perf-empty {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
/* Average can have 40+ bars; render the chart at its natural height and
   scroll within the 360px slot. */
.avg-chart-scroll {
    height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
}
.avg-chart-scroll::-webkit-scrollbar         { width: 8px; }
.avg-chart-scroll::-webkit-scrollbar-thumb   { background: #cbd5e1; border-radius: 4px; }
.avg-chart-scroll::-webkit-scrollbar-track   { background: transparent; }
</style>
