<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import RegionInfoMap from '@/Components/Map/RegionInfoMap.vue';
import { ref, reactive, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';

// DOST brand blue, for Vuetify color props.
const DOST = '#0b57a8';

const navLinks = [
    { href: '#about',    label: 'About' },
    { href: '#system',   label: 'Platform' },
    { href: '#features', label: 'Features' },
    { href: '#map',      label: 'Map' },
    { href: '#news',     label: 'News' },
];

// ── Scroll spy: highlight the nav item of the section being viewed ─────────
const HEADER_OFFSET = 80; // sticky header (64px) + a little breathing room
const activeSection = ref('');
let spyLockedUntil  = 0;   // pause while a clicked link smooth-scrolls past other sections
let spyFrame        = 0;

const updateActiveSection = () => {
    spyFrame = 0;
    if (Date.now() < spyLockedUntil) return;

    // The last section whose top has reached the header is the current one.
    let current = '';
    for (const l of navLinks) {
        const el = document.querySelector(l.href);
        if (el && el.getBoundingClientRect().top - HEADER_OFFSET <= 1) current = l.href;
    }
    // At the very bottom the last section may never reach the header.
    const atBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;
    if (atBottom) current = navLinks[navLinks.length - 1].href;

    activeSection.value = current;
};
const onScroll = () => { if (!spyFrame) spyFrame = requestAnimationFrame(updateActiveSection); };

// Sliding underline: follows the active link's label (button minus padding).
const navBtns   = {};
const setNavBtn = (href, c) => { if (c) navBtns[href] = c.$el ?? c; else delete navBtns[href]; };
const indicator = reactive({ left: 0, width: 0, visible: false });
const indicatorStyle = computed(() => ({
    transform: `translateX(${indicator.left}px)`,
    width:     `${indicator.width}px`,
    opacity:   indicator.visible ? 1 : 0,
}));
const INDICATOR_INSET = 14; // keep the line under the text, not the button edges
const positionIndicator = () => {
    const el = navBtns[activeSection.value];
    if (!el || !el.offsetWidth) { indicator.visible = false; return; }
    indicator.left    = el.offsetLeft + INDICATOR_INSET;
    indicator.width   = Math.max(el.offsetWidth - INDICATOR_INSET * 2, 12);
    indicator.visible = true;
};
watch(activeSection, () => nextTick(positionIndicator));

const goToSection = (href) => {
    activeSection.value = href;
    spyLockedUntil = Date.now() + 900; // ~ smooth-scroll duration
    setTimeout(updateActiveSection, 950);
    mobileOpen.value = false;
};

onMounted(() => {
    updateActiveSection();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    window.addEventListener('resize', positionIndicator);
    // Fonts can change the link widths after the first paint.
    document.fonts?.ready?.then(positionIndicator);
});
onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('resize', onScroll);
    window.removeEventListener('resize', positionIndicator);
    if (spyFrame) cancelAnimationFrame(spyFrame);
});
import {
    RiTrophyLine, RiMapPin2Line, RiBuilding2Line, RiBarChart2Line,
    RiLineChartLine, RiGroupLine, RiDashboard3Line,
    RiArrowRightLine, RiGlobalLine, RiMicroscopeLine,
    RiLeafLine, RiLightbulbLine, RiTeamLine,
    RiCalendarLine, RiArrowRightUpLine, RiCloseLine,
    RiMapPin2Fill, RiLockLine, RiUserFill, RiEyeLine, RiFocus3Line, RiAuctionLine
} from '@remixicon/vue';

const props = defineProps({
    canLogin:    { type: Boolean, default: true },
    blogs:       { type: Array,   default: () => [] },
    provinces:   { type: Array,   default: () => [] },
    // Live scoring framework: { categories: [{code, name, weight, kpi_count}], kpi_count }
    // The public copy below reads from this so it can't drift from what the
    // system actually scores after an admin edits the KPI matrix.
    kpi_framework: { type: Object, default: () => ({ categories: [], kpi_count: 0 }) },
});

// Colours match the scoring bar's blue ramp: heaviest category darkest.
const SCORING_RAMP = ['#0b57a8', '#2079c4', '#3f93e0', '#68aeeb', '#93c8f3'];

const scoringCategories = computed(() =>
    (props.kpi_framework.categories ?? []).map((c, i) => {
        const pct = (Number(c.weight) || 0) * 100;
        return {
            code:   c.code,
            name:   c.name,
            pct,
            label:  `${Number.isInteger(pct) ? pct : Math.round(pct * 10) / 10}%`,
            color:  SCORING_RAMP[i % SCORING_RAMP.length],
        };
    })
);

const kpiCount      = computed(() => props.kpi_framework.kpi_count ?? 0);
const categoryCount = computed(() => scoringCategories.value.length);

// "Core, Strategic, and Support" — reads naturally however many there are.
const categoryNameList = computed(() => {
    const names = scoringCategories.value.map(c => c.name);
    if (names.length <= 1) return names[0] ?? '';
    if (names.length === 2) return names.join(' and ');
    return `${names.slice(0, -1).join(', ')}, and ${names[names.length - 1]}`;
});

// "Core (60%), Strategic (25%), and Support (15%)" for the feature blurb.
const categoryWeightList = computed(() => {
    const parts = scoringCategories.value.map(c => `${c.name} (${c.label})`);
    if (parts.length <= 1) return parts[0] ?? '';
    if (parts.length === 2) return parts.join(' and ');
    return `${parts.slice(0, -1).join(', ')}, and ${parts[parts.length - 1]}`;
});

const provinceByName = computed(() => {
    const map = new Map();
    for (const p of props.provinces) map.set(p.name, p);
    return map;
});

const stats = computed(() => [
    { value: '17',  label: 'Regions Covered' },
    { value: '83',  label: 'Provinces & Clusters' },
    { value: String(categoryCount.value), label: 'Categories' },
    { value: String(kpiCount.value),      label: 'Performance KPIs' },
]);

const pillars = [
    { icon: RiMicroscopeLine, title: 'Research & Development',
      text: 'Advancing scientific knowledge through cutting-edge R&D initiatives that address national development priorities.' },
    { icon: RiLightbulbLine, title: 'Technology Transfer',
      text: 'Bridging the gap between science and industry by transferring proven technologies to communities and enterprises.' },
    { icon: RiLeafLine, title: 'S&T Services',
      text: 'Delivering science and technology services - from metrology to S&T scholarships - to every corner of the Philippines.' },
    { icon: RiTeamLine, title: 'Human Capital',
      text: 'Nurturing the next generation of Filipino scientists, engineers, and innovators through grants and training programs.' },
];

const features = computed(() => [
    { icon: RiDashboard3Line, title: 'Performance Dashboard',
      text: 'See PSTO rankings four ways - an awards podium, a sortable table, year-over-year trends, and a live map - all filterable by tier, region, island, and category.' },
    { icon: RiMapPin2Line, title: 'Interactive Maps',
      text: 'Explore the country province-by-province. Drill into a region or island and read each area at a glance through clear color coding.' },
    { icon: RiBuilding2Line, title: 'Province Directories',
      text: 'Browse every province\'s profile - its provincial S&T director, staff, classification tier, and region - in one organized place.' },
    { icon: RiTrophyLine, title: 'PRISM Ranking Matrix',
      text: `A transparent, weighted scoring of ${kpiCount.value} indicators across ${categoryWeightList.value} categories, with adjective ratings.` },
    { icon: RiBarChart2Line, title: 'Regional Analytics',
      text: 'Compare performance across regions, islands, and size tiers, with top and low performers surfaced automatically.' },
    { icon: RiLineChartLine, title: 'Trends Over Time',
      text: 'Track how each PSTO performs across reporting years to spot momentum, plateaus, and turnarounds.' },
]);

const year = new Date().getFullYear();
const mobileOpen = ref(false);
const rootEl = ref(null);

// Reveal-on-scroll. .reveal-on is added only after JS confirms it can run, so
// without scripting the content stays fully visible (graceful degradation).
onMounted(() => {
    if (typeof IntersectionObserver === 'undefined' || !rootEl.value) return;
    rootEl.value.classList.add('reveal-on');
    const io = new IntersectionObserver((entries) => {
        for (const e of entries) {
            if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); }
        }
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    rootEl.value.querySelectorAll('.reveal').forEach((el) => io.observe(el));
});

const selectedProvince = ref(null);

const islandBg = {
    Luzon:    '/assets/hero/pic1.png',
    Visayas:  '/assets/hero/pic2.png',
    Mindanao: '/assets/hero/pic3.png',
};

// Shown when a province has no cover uploaded (or it was removed) — the same
// default the provincial admin sees on the Province Info page.
const DEFAULT_PROVINCE_COVER = '/assets/province-cover-default.svg';
const placeholderBg = () => DEFAULT_PROVINCE_COVER;

const onProvinceSelected = (province) => {
    if (!province) {
        selectedProvince.value = null;
        return;
    }
    const record = provinceByName.value.get(province.name);
    selectedProvince.value = {
        ...province,
        category:    record?.category ?? null,
        description: record?.description ?? null,
        image:       record?.image_url || placeholderBg(province),
    };
};

const SAMPLE_DIRECTOR_POOL = [
    { name: 'Dr. Maria T. Santos',      years: 8  },
    { name: 'Engr. Juan C. dela Cruz',  years: 5  },
    { name: 'Dr. Ricardo A. Flores',    years: 12 },
    { name: 'Dr. Ana L. Reyes',         years: 6  },
    { name: 'Engr. Jose B. Bautista',   years: 3  },
    { name: 'Dr. Lina M. Mendoza',      years: 9  },
    { name: 'Dr. Pedro S. Garcia',      years: 7  },
    { name: 'Engr. Carmen O. Torres',   years: 4  },
    { name: 'Dr. Roberto N. Cruz',      years: 11 },
    { name: 'Dr. Gloria P. Villanueva', years: 10 },
];

const AVATAR_COLORS = [
    '#1e3a8a','#065f46','#7c2d12','#4c1d95',
    '#1e40af','#164e63','#6b21a8','#9d174d',
    '#0f4c81','#3b1f6e',
];

const strHash = (s) => s.split('').reduce((a, c) => a + c.charCodeAt(0), 0);

const sampleDirector = computed(() => {
    if (!selectedProvince.value) return null;
    const h    = strHash(selectedProvince.value.name);
    const dir  = SAMPLE_DIRECTOR_POOL[h % SAMPLE_DIRECTOR_POOL.length];
    const bare = dir.name.replace(/^(Dr\.|Engr\.|Prof\.)\s*/, '');
    const initials = bare.split(' ').filter(p => /^[A-Z]/.test(p)).slice(0, 2).map(p => p[0]).join('');
    return {
        name:     dir.name,
        years:    dir.years,
        initials: initials || bare.slice(0, 2).toUpperCase(),
        color:    AVATAR_COLORS[h % AVATAR_COLORS.length],
    };
});
</script>

<template>
    <Head title="Welcome" />

    <div ref="rootEl" class="landing min-h-screen bg-white text-slate-800">

        <!-- ── Navbar ──────────────────────────────────────────────────────── -->
        <header style="z-index: 100;" class="sticky top-0 bg-white/95 backdrop-blur border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-16">
                    <a href="/" class="flex items-center gap-3" style="text-decoration:none">
                        <img src="/assets/logo.png" alt="DOST" class="w-10 h-10 rounded-lg object-contain" />
                        <div class="leading-tight">
                            <div class="font-extrabold dost-ink text-lg tracking-tight">DOST PRISM</div>
                            <div class="text-[11px] text-slate-500 hidden sm:block">Developed by Region IX</div>
                        </div>
                    </a>

                    <nav class="hidden md:flex items-center gap-1 relative">
                        <v-btn
                            v-for="l in navLinks" :key="l.href" :href="l.href"
                            :ref="(c) => setNavBtn(l.href, c)"
                            variant="text" rounded="lg" class="nav-btn"
                            :class="{ 'nav-btn--current': activeSection === l.href }"
                            :aria-current="activeSection === l.href ? 'location' : undefined"
                            @click="goToSection(l.href)"
                        >{{ l.label }}</v-btn>
                        <!-- One underline that slides to the selected section's link -->
                        <span class="nav-indicator" :style="indicatorStyle" aria-hidden="true"></span>
                        <v-btn v-if="canLogin" href="/login" :color="DOST" variant="flat" rounded="lg" class="ml-2 dost-shadow">Sign In</v-btn>
                    </nav>

                    <div class="md:hidden">
                        <v-btn icon variant="text" size="small" color="#475569" aria-label="Menu" @click="mobileOpen = !mobileOpen">
                            <v-icon>{{ mobileOpen ? 'mdi-close' : 'mdi-menu' }}</v-icon>
                        </v-btn>
                    </div>
                </div>

                <div v-if="mobileOpen" class="md:hidden pb-4 flex flex-col gap-1">
                    <v-btn
                        v-for="l in navLinks" :key="l.href" :href="l.href"
                        variant="text" rounded="lg" block class="nav-btn justify-start"
                        :class="{ 'nav-btn--current nav-btn--underlined': activeSection === l.href }"
                        :aria-current="activeSection === l.href ? 'location' : undefined"
                        @click="goToSection(l.href)"
                    >{{ l.label }}</v-btn>
                    <v-btn v-if="canLogin" href="/login" :color="DOST" variant="flat" rounded="lg" block class="mt-1" @click="mobileOpen = false">Sign In</v-btn>
                </div>
            </div>
        </header>

        <!-- ── Hero ───────────────────────────────────────────────────────── -->
        <section class="hero">
            <video
                class="hero-video"
                src="/assets/hero/hero.mp4"
                poster="/assets/hero/pic1.png"
                autoplay
                loop
                muted
                playsinline
            ></video>
            <div class="hero-anim"></div>
            <div class="hero-veil"></div>

            <div class="hero-inner">
                <div class="hero-content px-4 sm:px-6 py-8 text-white">
                    <div class="max-w-7xl mx-auto w-full">
                        <div class="hero-copy">
                            <div class="hero-badge">
                                <img src="/assets/logo.png" alt="DOST" class="hero-badge-logo" />
                                Department of Science and Technology
                                <span class="hero-badge-sep">·</span>
                                Developed by Region IX
                            </div>
                            <div class="hero-tagline">
                                <span class="hero-tagline-mark">OneDOST4U</span>
                                <span class="hero-tagline-sep">-</span>
                                <span class="hero-tagline-sub">Solutions and Opportunities for All</span>
                            </div>
                            <!-- PRISM is the headline; the full name below spells it out with the
                                 five source letters (P·R·I·S·M) picked out in the accent color.
                                 Keep each line on one source line: Vue drops whitespace-only text
                                 between tags when it contains a newline, which would glue words. -->
                            <h1 class="hero-title">
                                <span class="hero-acronym">PRISM</span>
                                <span class="hero-fullname">
                                    <span class="hero-fullname-line"><span class="hero-initial">P</span>rovincial <span class="hero-fullname-minor">Science and Technology Office</span></span>
                                    <span class="hero-fullname-line"><span class="hero-initial">R</span>anking <span class="hero-fullname-conn">&amp;</span> <span class="hero-initial">I</span>nformation <span class="hero-initial">S</span>ystem <span class="hero-fullname-conn">for</span> <span class="hero-initial">M</span>anagement</span>
                                </span>
                            </h1>
                            <div class="mt-10 flex flex-wrap gap-3">
                                <v-btn href="#about" size="x-large" color="white" variant="flat" rounded="lg" class="hero-btn hero-btn--light">
                                    Learn More
                                    <template #append><RiArrowRightLine class="w-5 h-5" /></template>
                                </v-btn>
                                <v-btn href="/map" size="x-large" color="white" variant="outlined" rounded="lg" class="hero-btn hero-btn--ghost">
                                    <template #prepend><RiGlobalLine class="w-5 h-5" /></template>
                                    Explore the Map
                                </v-btn>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats strip -->
                <div class="hero-stats">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div v-for="s in stats" :key="s.label" class="hero-stat">
                            <div class="hero-stat-num">{{ s.value }}</div>
                            <div class="hero-stat-label">{{ s.label }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── About DOST ─────────────────────────────────────────────────── -->
        <section id="about" class="py-20 lg:py-28">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <div class="eyebrow">About the Agency</div>
                        <h2 class="section-title">Department of Science and Technology</h2>
                        <p class="section-lead">
                            The Department of Science and Technology (DOST) is the primary government agency in the Philippines
                            responsible for the coordination and implementation of science, technology, and innovation (STI)
                            to support national development.
                        </p>
                        <p class="mt-4 text-lg text-slate-600 leading-relaxed">
                            Founded in 1958 and reorganized under Republic Act No. 2067, DOST drives the country's
                            scientific progress through research and development, technology transfer, and science
                            education. With offices in every region, DOST brings science closer to every Filipino.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-x-10 gap-y-4">
                            <div class="about-fact">
                                <span class="about-fact-num">1958</span>
                                <span class="about-fact-label">Established</span>
                            </div>
                            <div class="about-fact">
                                <span class="about-fact-num">17</span>
                                <span class="about-fact-label">Regional Offices</span>
                            </div>
                            <div class="about-fact">
                                <span class="about-fact-num">RA 2067</span>
                                <span class="about-fact-label">Science Act of 1958</span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <v-card v-for="p in pillars" :key="p.title" variant="flat" class="pillar-card reveal">
                            <v-avatar size="44" rounded="lg" class="pillar-icon">
                                <component :is="p.icon" class="w-6 h-6" />
                            </v-avatar>
                            <h4 class="mt-4 font-bold text-slate-900 text-base">{{ p.title }}</h4>
                            <p class="mt-1 text-sm text-slate-500 leading-relaxed">{{ p.text }}</p>
                        </v-card>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── About the System ───────────────────────────────────────────── -->
        <!-- ── Mandate · Mission · Vision ─────────────────────────────────── -->
        <section class="mvm">
            <div class="mvm-glow" aria-hidden="true"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 relative">
                <div class="text-center max-w-2xl mx-auto reveal">
                    <div class="eyebrow eyebrow--on-dark">Our Foundation</div>
                    <h2 class="mvm-title">Mandate, Mission &amp; Vision</h2>
                    <p class="mvm-sub">The guiding principles of the Department of Science and Technology.</p>
                </div>
                <div class="mvm-grid">
                    <v-card tag="article" variant="flat" class="mvm-card reveal reveal--d1">
                        <div class="mvm-icon"><RiAuctionLine class="w-6 h-6" /></div>
                        <h3 class="mvm-label">Mandate</h3>
                        <p class="mvm-text">Provide central direction, leadership and coordination of scientific and technological efforts and ensure that the results therefrom are geared and utilized in areas of maximum economic and social benefits for the people.</p>
                    </v-card>
                    <v-card tag="article" variant="flat" class="mvm-card mvm-card--accent reveal reveal--d2">
                        <div class="mvm-icon"><RiFocus3Line class="w-6 h-6" /></div>
                        <h3 class="mvm-label">Mission</h3>
                        <p class="mvm-text">To direct, lead, and coordinate the country's scientific, technological, and innovative efforts geared towards maximum economic and social benefits for the people.</p>
                    </v-card>
                    <v-card tag="article" variant="flat" class="mvm-card reveal reveal--d3">
                        <div class="mvm-icon"><RiEyeLine class="w-6 h-6" /></div>
                        <h3 class="mvm-label">Vision</h3>
                        <p class="mvm-text">DOST as the leading enabler and provider of science, technology, and innovation (STI) explicit solutions towards national development.</p>
                    </v-card>
                </div>
            </div>
        </section>

        <section id="system" class="py-24 lg:py-32 bg-slate-50">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
                <div class="eyebrow">The Platform</div>
                <h2 class="section-title">PRISM</h2>
                <p class="section-lead mx-auto">
                    PRISM - the Provincial Science and Technology Office Ranking &amp; Information System
                    for Management - is DOST's centralized platform for monitoring, evaluating, and ranking
                    Provincial Science and Technology Offices (PSTOs) across the Philippines.
                </p>
                <p class="mt-5 text-lg text-slate-600 leading-relaxed">
                    Covering all 83 provinces and clusters across 17 regions, the system uses a structured
                    scoring framework with {{ kpiCount }} performance indicators grouped into
                    {{ categoryCount }} categories - {{ categoryNameList }} - to produce transparent,
                    evidence-based rankings.
                </p>
                <p class="mt-5 text-lg text-slate-600 leading-relaxed">
                    Designed to promote excellence and accountability in provincial S&amp;T service delivery,
                    the system empowers DOST management to identify top-performing offices, surface areas
                    for improvement, and track progress over time.
                </p>

                <div class="scoring-card reveal">
                    <div class="scoring-head">
                        <span class="scoring-head-title">Scoring Framework</span>
                        <span class="scoring-head-sub">{{ kpiCount }} indicators · {{ categoryCount }} categories</span>
                    </div>
                    <div class="scoring-keys">
                        <v-chip v-for="cat in scoringCategories" :key="cat.code" variant="outlined" class="scoring-key">
                            <span class="scoring-dot mr-2" :style="{ background: cat.color }"></span>{{ cat.name }}&nbsp;<b>{{ cat.label }}</b>
                        </v-chip>
                    </div>
                    <div class="scoring-stack">
                        <div
                            v-for="cat in scoringCategories"
                            :key="cat.code"
                            class="scoring-seg"
                            :style="{ width: `${cat.pct}%`, background: cat.color }"
                        ><span>{{ cat.label }}</span></div>
                    </div>
                </div>

                <div class="mt-10 flex justify-center">
                    <v-btn v-if="canLogin" href="/login" size="x-large" :color="DOST" variant="flat" rounded="lg" class="dost-shadow lift">
                        Access the System
                        <template #append><RiArrowRightLine class="w-5 h-5" /></template>
                    </v-btn>
                </div>
            </div>
        </section>

        <!-- ── Features ───────────────────────────────────────────────────── -->
        <section id="features" class="py-20 lg:py-28">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="max-w-2xl mx-auto text-center">
                    <div class="eyebrow">What's Inside</div>
                    <h2 class="section-title">Everything the system offers</h2>
                    <p class="section-lead mx-auto">
                        From a high-level national overview down to a single province's KPI scorecard.
                    </p>
                </div>

                <div class="mt-14 grid md:grid-cols-2 gap-x-10 gap-y-6">
                    <v-card v-for="f in features" :key="f.title" variant="flat" class="feature-row reveal">
                        <v-avatar size="58" rounded="lg" class="feature-row-icon"><component :is="f.icon" class="w-7 h-7" /></v-avatar>
                        <div class="feature-row-body">
                            <h3 class="feature-row-title">{{ f.title }}</h3>
                            <p class="feature-row-text">{{ f.text }}</p>
                        </div>
                    </v-card>
                </div>
            </div>
        </section>

        <!-- ── Interactive Map ───────────────────────────────────────────────── -->
        <section id="map" class="py-20 lg:py-28 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="max-w-2xl">
                    <div class="eyebrow">Interactive Map</div>
                    <h2 class="section-title">Explore the Philippines by region</h2>
                    <p class="section-lead">
                        Every province is color-coded by its region across the three island groups -
                        Luzon, Visayas, and Mindanao. Click any province to see its details.
                    </p>
                </div>

                <!-- Map wrapper -->
                <div class="mt-8 map-wrapper rounded-2xl bg-white border border-slate-200 shadow-xl p-3 sm:p-4">
                    <RegionInfoMap height="620px" :hide-panel="true" :panel-width="360" @province-selected="onProvinceSelected">
                        <!-- Panel lives inside RegionInfoMap's root so it's visible in fullscreen -->
                        <template #default>
                            <Transition name="prov-slide">
                                <div v-if="selectedProvince" class="prov-panel" :key="selectedProvince.name">

                                    <!-- Photo header -->
                                    <div class="prov-photo">
                                        <img :src="selectedProvince.image" :alt="selectedProvince.name" />
                                        <div class="prov-photo-gradient"></div>
                                        <v-btn icon size="x-small" variant="flat" class="prov-close" aria-label="Close" @click="selectedProvince = null">
                                            <RiCloseLine class="w-4 h-4" />
                                        </v-btn>
                                    </div>

                                    <!-- White content body -->
                                    <div class="prov-body">

                                        <!-- Province header -->
                                        <h3 class="prov-title">{{ selectedProvince.name }}</h3>
                                        <div class="prov-region-row">
                                            <span class="prov-swatch" :style="{ background: selectedProvince.color }"></span>
                                            {{ selectedProvince.region }}
                                        </div>
                                        <v-chip size="small" variant="tonal" :color="DOST" class="prov-chip font-weight-bold text-uppercase">
                                            <template #prepend><RiMapPin2Fill class="w-3 h-3 mr-1" /></template>
                                            {{ selectedProvince.island }}
                                        </v-chip>

                                        <div class="prov-rows">
                                            <div class="prov-row">
                                                <RiGroupLine class="prov-row-icon" />
                                                <span>{{ selectedProvince.siblings.length + 1 }} provinces in this region</span>
                                            </div>
                                            <div v-if="selectedProvince.category" class="prov-row">
                                                <RiBuilding2Line class="prov-row-icon" />
                                                <span class="text-capitalize">{{ selectedProvince.category }} classification</span>
                                            </div>
                                            <div class="prov-row">
                                                <RiBarChart2Line class="prov-row-icon" />
                                                <span>Evaluated annually via PRISM</span>
                                            </div>
                                        </div>

                                        <!-- Description -->
                                        <!-- Rich text from Province Info, sanitized server-side on save -->
                                        <div v-if="selectedProvince.description" class="prov-desc prov-desc-html" v-html="selectedProvince.description"></div>
                                        <p v-else class="prov-desc">
                                            {{ `${selectedProvince.name} is a province under ${selectedProvince.region}, located in the ${selectedProvince.island} island group of the Philippines. It is served by a DOST Provincial Science and Technology Office (PSTO).` }}
                                        </p>

                                        <!-- Sibling provinces -->
                                        <div v-if="selectedProvince.siblings.length">
                                            <div class="prov-section-label">Other provinces in this region</div>
                                            <div class="prov-pills">
                                                <v-chip v-for="s in selectedProvince.siblings.slice(0, 6)" :key="s" size="small" variant="outlined" color="#475569">{{ s }}</v-chip>
                                                <v-chip v-if="selectedProvince.siblings.length > 6" size="small" variant="tonal" :color="DOST">+{{ selectedProvince.siblings.length - 6 }} more</v-chip>
                                            </div>
                                        </div>

                                        <v-divider class="my-3" />

                                        <!-- Director -->
                                        <div v-if="sampleDirector">
                                            <div class="prov-dir-label">Provincial S&amp;T Director</div>
                                            <div class="prov-dir-card">
                                                <v-avatar size="48" image="/assets/default-profile.avif" class="prov-dir-avatar" />
                                                <div class="prov-dir-info">
                                                    <div class="prov-dir-name">{{ sampleDirector.name }}</div>
                                                    <div class="prov-dir-service">{{ sampleDirector.years }} yrs of service</div>
                                                </div>
                                                <v-chip size="x-small" variant="tonal" color="#64748b" class="font-weight-bold text-uppercase">Sample</v-chip>
                                            </div>
                                        </div>

                                        <v-divider class="my-3" />

                                        <!-- PRISM scoring teaser -->
                                        <div class="prov-prism">
                                            <div class="prov-prism-head">PRISM Scoring Framework</div>
                                            <div class="prov-prism-bar">
                                                <!-- Narrow slices drop the name and show the percent alone,
                                                     so the label still fits whatever the split happens to be. -->
                                                <div
                                                    v-for="cat in scoringCategories"
                                                    :key="cat.code"
                                                    class="prov-prism-seg"
                                                    :style="{ width: `${cat.pct}%`, background: cat.color, fontSize: cat.pct < 15 ? '9px' : null }"
                                                >{{ cat.pct < 15 ? cat.label : `${cat.name} ${cat.label}` }}</div>
                                            </div>
                                            <div class="prov-prism-labels">{{ kpiCount }} indicators · {{ categoryCount }} categories · evaluated annually</div>
                                        </div>

                                        <v-divider class="my-3" />

                                        <!-- Sign-in teaser -->
                                        <div v-if="canLogin" class="prov-signin">
                                            <div class="prov-signin-head">
                                                <RiLockLine class="w-3.5 h-3.5" />
                                                <span>PSTO Rankings &amp; Profile</span>
                                            </div>
                                            <p class="prov-signin-text">
                                                The PSTO's KPI scores, performance tier, and office profile for
                                                <strong>{{ selectedProvince.name }}</strong> are available after sign-in.
                                            </p>
                                            <v-btn href="/login" size="small" :color="DOST" variant="flat" rounded="lg">
                                                Sign In to View
                                                <template #append><RiArrowRightLine class="w-3.5 h-3.5" /></template>
                                            </v-btn>
                                        </div>

                                    </div>
                                </div>
                            </Transition>
                        </template>
                    </RegionInfoMap>
                </div>

                <p class="mt-3 text-base text-slate-400">
                    Geographic data shown for public reference. Performance rankings are available after sign-in.
                </p>
            </div>
        </section>

        <!-- ── News & Updates ─────────────────────────────────────────────── -->
        <section id="news" class="py-20 lg:py-28">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="flex items-end justify-between mb-12">
                    <div>
                        <div class="eyebrow">News &amp; Updates</div>
                        <h2 class="section-title">Latest from DOST PRISM</h2>
                    </div>
                </div>

                <div v-if="!blogs.length" class="text-center py-16 text-slate-400">
                    <p class="text-lg font-medium">No news yet.</p>
                    <p class="text-base mt-1">Check back soon for updates from DOST PRISM.</p>
                </div>

                <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <v-card v-for="b in blogs" :key="b.title" tag="article" variant="flat" class="blog-card reveal" @click="router.visit(`/blog/${b.slug}`)">
                        <v-img :src="b.image" :alt="b.title" height="200" cover class="blog-card-img" />
                        <div class="blog-card-body">
                            <div class="flex items-center gap-2 mb-3">
                                <v-chip size="small" variant="tonal" :color="DOST" class="font-weight-bold text-uppercase">{{ b.tag }}</v-chip>
                                <span class="flex items-center gap-1 text-sm text-slate-400">
                                    <RiCalendarLine class="w-3.5 h-3.5" /> {{ b.date }}
                                </span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 leading-snug">{{ b.title }}</h3>
                            <p class="mt-2 text-base text-slate-500 leading-relaxed line-clamp-3">{{ b.excerpt }}</p>
                        </div>
                        <div class="blog-card-footer">
                            <v-btn variant="text" size="small" :color="DOST" class="px-0 blog-read-more">
                                Read more
                                <template #append><RiArrowRightUpLine class="w-4 h-4" /></template>
                            </v-btn>
                        </div>
                    </v-card>
                </div>
            </div>
        </section>

        <!-- ── CTA ────────────────────────────────────────────────────────── -->
        <section class="py-16 lg:py-20">
            <div class="max-w-5xl mx-auto px-4 sm:px-6">
                <div class="cta">
                    <RiGroupLine class="w-12 h-12 mx-auto" style="color:#bcd6f2" />
                    <h2 class="mt-4 text-4xl lg:text-5xl font-extrabold text-white">Ready to dive into the rankings?</h2>
                    <p class="mt-3 text-xl max-w-2xl mx-auto" style="color:#cfe0f5">
                        Authorized DOST personnel can sign in to access the full performance dashboard,
                        province directories, and reports.
                    </p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <v-btn v-if="canLogin" href="/login" size="x-large" color="white" variant="flat" rounded="lg" class="hero-btn hero-btn--light">Sign In</v-btn>
                        <v-btn href="#map" size="x-large" color="white" variant="outlined" rounded="lg" class="hero-btn hero-btn--ghost">
                            <template #prepend><RiGlobalLine class="w-5 h-5" /></template>
                            Explore the Map
                        </v-btn>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Footer ─────────────────────────────────────────────────────── -->
        <footer class="footer text-slate-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-14">
                <div class="grid md:grid-cols-4 gap-10">
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-3">
                            <img src="/assets/logo.png" alt="DOST" class="w-9 h-9 rounded-lg object-contain bg-white p-0.5" />
                            <span class="font-bold text-white text-lg">DOST PRISM</span>
                        </div>
                        <p class="mt-4 text-base max-w-md leading-relaxed" style="color:#9fb6d4">
                            An initiative of the Department of Science and Technology to promote excellence,
                            transparency, and evidence-based evaluation of Provincial Science and Technology
                            Offices across the Philippines.
                        </p>
                        <p class="mt-2 text-sm font-semibold" style="color:#7e97b8">Developed by Region IX</p>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-white mb-4">Navigate</h4>
                        <ul class="space-y-2">
                            <li><a href="#about"    class="foot-link">About DOST</a></li>
                            <li><a href="#system"   class="foot-link">Platform</a></li>
                            <li><a href="#features" class="foot-link">Features</a></li>
                            <li><a href="#map"      class="foot-link">Interactive Map</a></li>
                            <li><a href="#news"     class="foot-link">News &amp; Updates</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-white mb-4">Access</h4>
                        <ul class="space-y-2">
                            <li><Link href="/login" class="foot-link">Sign In</Link></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-12 pt-6 border-t text-center text-base" style="border-color:#123a66;color:#7e97b8">
                    &copy; {{ year }} Department of Science and Technology. All rights reserved.
                </div>
            </div>
        </footer>

    </div>
</template>

<style scoped>
/* ── Vuetify components in the landing's look ───────────────────────────── */
.landing .v-btn { text-transform: none; letter-spacing: normal; font-weight: 700; }
.nav-btn { color: #475569 !important; font-size: 16px; }
.nav-btn:hover { color: var(--dost) !important; }
.nav-btn--current { color: var(--dost) !important; }
/* Just the underline — no Vuetify hover/focus box behind the links */
.nav-btn :deep(.v-btn__overlay) { display: none; }
.nav-btn--underlined :deep(.v-btn__content) {
    text-decoration: underline; text-decoration-thickness: 2px; text-underline-offset: 6px;
}
.nav-indicator {
    position: absolute; left: 0; bottom: 2px; height: 2.5px;
    border-radius: 999px; background: var(--dost); pointer-events: none;
    transition: transform .35s cubic-bezier(.4, 0, .2, 1), width .35s cubic-bezier(.4, 0, .2, 1), opacity .25s ease;
}
/* Land sections below the sticky header when a nav link is clicked */
.landing section[id] { scroll-margin-top: 64px; }
.dost-shadow { box-shadow: 0 4px 14px rgba(11,87,168,.30); }
.lift { transition: transform .15s ease; }
.lift:hover { transform: translateY(-1px); }
.hero-btn { transition: transform .15s ease, background .15s ease; }
.hero-btn--light { color: var(--dost-700) !important; box-shadow: 0 8px 24px rgba(0,0,0,.18); }
.hero-btn--light:hover { transform: translateY(-1px); }
.hero-btn--ghost { border-color: rgba(255,255,255,.45) !important; }
.blog-card { cursor: pointer; }
.pillar-icon { background: var(--dost-tint); color: var(--dost); }
.feature-row-icon { color: #fff; }
.prov-chip { letter-spacing: .05em; }

/* ── Province description (rich text) ─────────────────────────────────────── */
.prov-desc-html :deep(p) { margin: 0 0 0.6em; }
.prov-desc-html :deep(p:last-child) { margin-bottom: 0; }
.prov-desc-html :deep(h2) { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0.8em 0 0.4em; }
.prov-desc-html :deep(h3) { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0.8em 0 0.4em; }
.prov-desc-html :deep(ul),
.prov-desc-html :deep(ol) { padding-left: 1.3em; margin: 0 0 0.6em; }
.prov-desc-html :deep(ul) { list-style: disc; }
.prov-desc-html :deep(ol) { list-style: decimal; }
.prov-desc-html :deep(blockquote) { border-left: 3px solid #cbd5e1; padding-left: 0.8em; color: #64748b; margin: 0 0 0.6em; }
.prov-desc-html :deep(a) { color: #1d4ed8; text-decoration: underline; }
.prov-desc-html :deep(.video-embed) { position: relative; padding-top: 56.25%; margin: 0 0 0.6em; }
.prov-desc-html :deep(.video-embed iframe) { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; border-radius: 8px; }

/* ── DOST palette ─────────────────────────────────────────────────────────── */
.landing {
    --dost:        #0b57a8;
    --dost-600:    #0a4d96;
    --dost-700:    #0a3f7d;
    --dost-navy:   #082f5f;
    --dost-navy-2: #061f40;
    --dost-tint:   #eaf2fb;
    --dost-line:   #d6e4f5;
}

/* ── Nav ─────────────────────────────────────────────────────────────────── */
.dost-ink { color: var(--dost-navy); }

/* ── Hero ────────────────────────────────────────────────────────────────── */
.hero {
    position: relative; overflow: hidden;
    min-height: calc(100vh - 4rem);   /* fill the viewport below the sticky navbar */
    display: flex; flex-direction: column;
    background: linear-gradient(135deg, var(--dost-navy) 0%, var(--dost) 65%, var(--dost-navy) 100%);
}
.hero-video {
    position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;
    z-index: 0;
}
.hero-anim {
    position: absolute; inset: -20%; z-index: 1; opacity: .25; pointer-events: none;
    background-image:
        radial-gradient(circle at 20% 25%, rgba(86,179,245,.40) 0, transparent 30%),
        radial-gradient(circle at 82% 12%, rgba(124,196,255,.35) 0, transparent 28%),
        radial-gradient(circle at 70% 85%, rgba(20,86,168,.45) 0, transparent 34%);
    animation: heroDrift 18s ease-in-out infinite alternate;
}
@keyframes heroDrift {
    from { transform: translate3d(0,0,0) scale(1); }
    to   { transform: translate3d(2.5%, -2%, 0) scale(1.06); }
}
.hero-veil {
    position: absolute; inset: 0; z-index: 2; pointer-events: none;
    background:
        /* dark behind the left-aligned copy, fading right so the imagery shows */
        linear-gradient(90deg, rgba(5,21,48,.95) 0%, rgba(5,21,48,.84) 34%, rgba(5,21,48,.45) 62%, rgba(5,21,48,.08) 100%),
        /* subtle top (nav) + solid base into the stats strip */
        linear-gradient(180deg, rgba(8,47,95,.45) 0%, rgba(8,47,95,.12) 40%, rgba(6,24,52,.80) 100%);
}
.hero-inner { position: relative; z-index: 3; flex: 1; display: flex; flex-direction: column; }
.hero-content { flex: 1 1 auto; display: flex; flex-direction: column; justify-content: center; min-height: 0; }
.hero-copy { max-width: 760px; text-align: left; }

.hero-badge {
    display: inline-flex; align-items: center; gap: 8px; margin-bottom: 14px;
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.22);
    padding: 6px 16px; border-radius: 999px; font-size: 14px; font-weight: 600; letter-spacing: .02em; color: #eaf2fb;
}
.hero-badge-logo    { width: 18px; height: 18px; object-fit: contain; filter: drop-shadow(0 1px 2px rgba(0,0,0,.25)); }
.hero-badge-sep     { opacity: .45; margin: 0 4px; }
.hero-tagline       { color: #eaf2fb; font-size: 17px; letter-spacing: .01em; margin-bottom: 22px; }
.hero-tagline-mark  { font-weight: 800; }
.hero-tagline-sep   { margin: 0 6px; opacity: .55; }
.hero-tagline-sub   { opacity: .85; font-weight: 500; }
.hero-title { color: #fff; font-weight: 800; }

/* The acronym — the headline. Gradient fill, so the shadow comes from a filter
   (a text-shadow would paint over a background-clipped fill). */
.hero-acronym {
    display: inline-block;
    font-size: clamp(4rem, 9vw, 7.25rem); line-height: .95; letter-spacing: .04em;
    background: linear-gradient(100deg, #ffffff 0%, #d6ebff 45%, #6ec1ff 100%);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent; color: transparent;
    filter: drop-shadow(0 4px 22px rgba(2,12,30,.55));
}

/* What PRISM stands for — a caption under the acronym, tied to it by the rule on the left. */
.hero-fullname {
    display: block; margin-top: 18px; padding-left: 18px;
    border-left: 3px solid rgba(110,193,255,.8);
    font-size: clamp(1.4rem, 2.3vw, 1.85rem); font-weight: 700; line-height: 1.3;
    color: #eaf2fb; text-shadow: 0 2px 14px rgba(2,12,30,.55);
}
.hero-fullname-line { display: block; text-wrap: balance; }
.hero-fullname-line + .hero-fullname-line { margin-top: 2px; }
.hero-fullname-minor { font-size: .64em; font-weight: 600; color: rgba(234,242,251,.72); letter-spacing: .01em; }
.hero-fullname-conn  { font-weight: 500; color: rgba(234,242,251,.6); }
.hero-initial        { color: #6ec1ff; font-weight: 800; }

.hero-stats { position: relative; z-index: 3; border-top: 1px solid rgba(255,255,255,.14); background: rgba(255,255,255,.05); }

/* ── Shared ──────────────────────────────────────────────────────────────── */
.eyebrow {
    display: inline-block; font-size: 13px; font-weight: 800; letter-spacing: .08em;
    text-transform: uppercase; color: var(--dost);
    background: var(--dost-tint); border: 1px solid var(--dost-line);
    padding: 6px 14px; border-radius: 999px;
}
.section-title { margin-top: 8px; font-size: clamp(2rem, 3vw, 2.6rem); font-weight: 800; color: #0f172a; line-height: 1.15; }
.section-lead { margin-top: 16px; font-size: 1.25rem; line-height: 1.75; color: #475569; max-width: 44rem; }

/* ── About cards ─────────────────────────────────────────────────────────── */
.about-card {
    border: 1px solid var(--dost-line); border-radius: 14px; padding: 18px 20px;
    background: var(--dost-tint);
}
.about-card-label { font-size: 13px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--dost); }

/* ── Pillars ─────────────────────────────────────────────────────────────── */
.pillar-card {
    border: 1px solid #e7edf4; border-radius: 16px; padding: 22px; background: #fff;
    transition: box-shadow .15s ease, transform .15s ease;
}
.pillar-card:hover { box-shadow: 0 10px 28px rgba(11,87,168,.10); transform: translateY(-2px); }
.pillar-icon {
    width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center;
    background: var(--dost-tint); color: var(--dost);
}

/* ── System preview ──────────────────────────────────────────────────────── */
.system-preview {
    background: #fff; border: 1px solid #e2ecf7; border-radius: 20px;
    box-shadow: 0 20px 50px rgba(11,87,168,.12); overflow: hidden;
}
.sp-header {
    display: flex; align-items: center; gap: 8px; padding: 18px 22px;
    border-bottom: 1px solid #eef3fa; background: #fafcff;
}
.sp-body { padding: 18px 22px; }
.sp-weights {
    display: grid; grid-template-columns: repeat(3,1fr); gap: 8px;
    padding: 0 22px; margin-bottom: 16px;
}
.sp-wc { border-radius: 10px; padding: 10px 6px; text-align: center; }
.sp-wc-1 { background: #e7f0fb; }
.sp-wc-2 { background: #eef4fc; }
.sp-wc-3 { background: #f4f8fd; }
.sp-wl { font-size: 10px; font-weight: 700; color: var(--dost-600); letter-spacing: .04em; }
.sp-wv { font-weight: 800; color: var(--dost-navy); font-size: 15px; }
.sp-footer {
    display: flex; flex-wrap: wrap; gap: 6px; padding: 14px 22px;
    border-top: 1px solid #eef3fa; background: #fafcff;
}
.sp-badge {
    font-size: 11px; font-weight: 700; color: var(--dost); background: var(--dost-tint);
    border: 1px solid var(--dost-line); padding: 4px 10px; border-radius: 999px;
}


/* ── Features ────────────────────────────────────────────────────────────── */
.feature-row {
    display: flex; align-items: flex-start; gap: 18px;
    padding: 22px 20px; border-radius: 16px;
    transition: background .18s ease;
}
.feature-row:hover { background: var(--dost-tint); }
.feature-row-icon {
    width: 58px; height: 58px; flex-shrink: 0; border-radius: 16px;
    display: grid; place-items: center; color: #fff;
    background: linear-gradient(135deg, var(--dost) 0%, #56b3f5 100%);
    box-shadow: 0 8px 20px rgba(11,87,168,.30);
}
.feature-row-title { font-size: 1.25rem; font-weight: 700; color: #0f172a; }
.feature-row-text  { margin-top: 7px; font-size: 1.05rem; line-height: 1.65; color: #475569; }

/* ── Blog cards ──────────────────────────────────────────────────────────── */
.blog-card-img :deep(.v-img__img) { transition: transform .4s ease; }
.blog-card:hover .blog-card-img :deep(.v-img__img) { transform: scale(1.05); }
.blog-card {
    background: #fff; border: 1px solid #e7edf4; border-radius: 18px; overflow: hidden;
    display: flex; flex-direction: column;
    transition: box-shadow .15s ease, transform .15s ease;
}
.blog-card:hover { box-shadow: 0 12px 30px rgba(11,87,168,.10); transform: translateY(-2px); }
.blog-card-body { padding: 24px; flex: 1; }
.blog-card-footer {
    padding: 14px 24px; border-top: 1px solid #f1f5f9;
}

/* ── CTA ─────────────────────────────────────────────────────────────────── */
.cta {
    position: relative; overflow: hidden; border-radius: 28px; padding: 56px 32px; text-align: center;
    background: linear-gradient(120deg, var(--dost-navy) 0%, var(--dost) 100%);
    box-shadow: 0 24px 60px rgba(8,47,95,.30);
}

/* ── Footer ──────────────────────────────────────────────────────────────── */
.footer { background: var(--dost-navy); }
.foot-link { color: #b9cce6; transition: color .15s ease; font-size: 16px; }
.foot-link:hover { color: #fff; }

/* ── Map wrapper ─────────────────────────────────────────────────────────── */
.map-wrapper { position: relative; }

/* ── Smooth in-page scrolling ────────────────────────────────────────────── */
:global(html) { scroll-behavior: smooth; }

/* ── Reveal on scroll ────────────────────────────────────────────────────── */
.reveal-on .reveal {
    opacity: 0; transform: translateY(26px);
    transition: opacity .7s cubic-bezier(.22,1,.36,1), transform .7s cubic-bezier(.22,1,.36,1);
    will-change: opacity, transform;
}
.reveal-on .reveal.is-visible { opacity: 1; transform: none; }
.reveal--d1 { transition-delay: .08s; }
.reveal--d2 { transition-delay: .18s; }
.reveal--d3 { transition-delay: .28s; }
@media (prefers-reduced-motion: reduce) {
    .reveal-on .reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
}

/* ── About key facts ─────────────────────────────────────────────────────── */
.about-fact { display: flex; flex-direction: column; }
.about-fact-num { font-size: 1.65rem; font-weight: 800; color: var(--dost-navy); line-height: 1; }
.about-fact-label { margin-top: 5px; font-size: .9rem; font-weight: 600; color: #64748b; }

/* ── Mandate · Mission · Vision ──────────────────────────────────────────── */
.mvm {
    position: relative; overflow: hidden; padding: 88px 0 96px;
    background: linear-gradient(160deg, var(--dost-navy) 0%, var(--dost-700) 55%, var(--dost-navy-2) 100%);
}
.mvm-glow {
    position: absolute; inset: -25% -10% auto -10%; height: 75%; pointer-events: none;
    background:
        radial-gradient(55% 60% at 18% 0%, rgba(86,179,245,.20), transparent 70%),
        radial-gradient(45% 55% at 85% 8%, rgba(124,196,255,.16), transparent 70%);
}
.eyebrow--on-dark { color: #cfe6ff; background: rgba(255,255,255,.10); border-color: rgba(255,255,255,.22); }
.mvm-title { margin-top: 8px; font-size: clamp(2rem, 3vw, 2.6rem); font-weight: 800; color: #fff; line-height: 1.15; }
.mvm-sub   { margin-top: 14px; font-size: 1.2rem; line-height: 1.7; color: #c6dbf3; }
.mvm-grid  { margin-top: 48px; display: grid; gap: 22px; grid-template-columns: repeat(3, 1fr); }
@media (max-width: 900px) { .mvm-grid { grid-template-columns: 1fr; } }
.mvm-card {
    position: relative; border-radius: 20px; padding: 30px 28px 32px;
    background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.16);
    backdrop-filter: blur(6px); box-shadow: 0 20px 50px rgba(3,17,38,.35);
    transition: transform .25s ease, box-shadow .25s ease, background .25s ease, border-color .25s ease;
}
.mvm-card:hover {
    transform: translateY(-6px); background: rgba(255,255,255,.11);
    border-color: rgba(142,197,255,.5); box-shadow: 0 28px 64px rgba(3,17,38,.5);
}
.mvm-card--accent { background: rgba(142,197,255,.12); border-color: rgba(142,197,255,.4); }
.mvm-icon {
    width: 54px; height: 54px; border-radius: 16px; display: grid; place-items: center;
    color: #fff; background: linear-gradient(135deg, var(--dost) 0%, #56b3f5 100%);
    box-shadow: 0 8px 20px rgba(11,87,168,.45);
}
.mvm-label { margin-top: 18px; font-size: 13px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #8ec5ff; }
.mvm-text  { margin-top: 8px; font-size: 1.02rem; line-height: 1.7; color: #e7eefb; }

/* ── Hero entrance + stat cards ──────────────────────────────────────────── */
.hero-badge, .hero-tagline, .hero-title {
    animation: heroIn .85s cubic-bezier(.22,1,.36,1) both;
}
.hero-tagline { animation-delay: .08s; }
.hero-title   { animation-delay: .16s; }
@keyframes heroIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: none; } }
@media (prefers-reduced-motion: reduce) {
    .hero-badge, .hero-tagline, .hero-title { animation: none; }
}
.hero-stat {
    text-align: center; padding: 16px 8px; border-radius: 14px;
    background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12);
    transition: background .2s ease, transform .2s ease, border-color .2s ease;
}
.hero-stat:hover { background: rgba(255,255,255,.12); border-color: rgba(142,197,255,.45); transform: translateY(-3px); }
.hero-stat-num   { font-size: 2.3rem; font-weight: 800; color: #fff; line-height: 1; }
.hero-stat-label { margin-top: 6px; font-size: .95rem; font-weight: 500; color: #bcd6f2; }

/* ── Scoring framework card ──────────────────────────────────────────────── */
.scoring-card {
    margin-top: 44px; text-align: left; background: #fff;
    border: 1px solid #e2ecf7; border-radius: 20px; padding: 26px 28px;
    box-shadow: 0 20px 50px rgba(11,87,168,.10);
}
.scoring-head { display: flex; align-items: baseline; justify-content: space-between; gap: 6px; flex-wrap: wrap; margin-bottom: 20px; }
.scoring-head-title { font-size: 1.12rem; font-weight: 800; color: var(--dost-navy); }
.scoring-head-sub   { font-size: .92rem; font-weight: 600; color: #64748b; }
.scoring-keys { display: flex; flex-wrap: wrap; gap: 18px; margin-bottom: 12px; }
.scoring-key  { gap: 7px; font-size: .98rem; font-weight: 600; color: #334155 !important; border-color: #e2e8f0 !important; }
.scoring-key b { color: var(--dost-navy); font-weight: 800; margin-left: 2px; }
.scoring-dot  { width: 12px; height: 12px; border-radius: 4px; flex-shrink: 0; }
.scoring-stack {
    display: flex; height: 46px; border-radius: 12px; overflow: hidden;
    box-shadow: 0 6px 16px rgba(11,87,168,.18);
}
.scoring-seg {
    display: flex; align-items: center; justify-content: center; min-width: 0;
    color: #fff; font-weight: 800; font-size: .98rem; text-shadow: 0 1px 2px rgba(0,0,0,.28);
    transition: filter .15s ease;
}
.scoring-seg + .scoring-seg { box-shadow: inset 1px 0 0 rgba(255,255,255,.3); }
.scoring-seg:hover { filter: brightness(1.07); }

</style>
