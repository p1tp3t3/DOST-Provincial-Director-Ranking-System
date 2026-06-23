<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import RegionInfoMap from '@/Components/Map/RegionInfoMap.vue';
import { ref, computed, onMounted } from 'vue';
import {
    RiTrophyLine, RiMapPin2Line, RiBuilding2Line, RiBarChart2Line,
    RiLineChartLine, RiGroupLine, RiDashboard3Line,
    RiArrowRightLine, RiGlobalLine, RiMicroscopeLine,
    RiLeafLine, RiLightbulbLine, RiTeamLine,
    RiCalendarLine, RiArrowRightUpLine, RiCloseLine,
    RiMapPin2Fill, RiLockLine, RiUserFill,
} from '@remixicon/vue';

defineProps({
    canLogin: { type: Boolean, default: true },
});

const stats = [
    { value: '17',  label: 'Regions Covered' },
    { value: '83',  label: 'Provinces & Clusters' },
    { value: '3',   label: 'Categories' },
    { value: '37',  label: 'Performance KPIs' },
];

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

const features = [
    { icon: RiDashboard3Line, title: 'Performance Dashboard',
      text: 'See PSTD rankings four ways - an awards podium, a sortable table, year-over-year trends, and a live map - all filterable by tier, region, island, and category.' },
    { icon: RiMapPin2Line, title: 'Interactive Maps',
      text: 'Explore the country province-by-province. Drill into a region or island and read each area at a glance through clear color coding.' },
    { icon: RiBuilding2Line, title: 'Province Directories',
      text: 'Browse every province\'s profile - its provincial S&T director, staff, classification tier, and region - in one organized place.' },
    { icon: RiTrophyLine, title: 'PRISM Ranking Matrix',
      text: 'A transparent, weighted scoring of 37 indicators across Core (60%), Functional (30%), and Support (10%) functions, with adjective ratings.' },
    { icon: RiBarChart2Line, title: 'Regional Analytics',
      text: 'Compare performance across regions, islands, and size tiers, with top and low performers surfaced automatically.' },
    { icon: RiLineChartLine, title: 'Trends Over Time',
      text: 'Track how each province and director performs across reporting years to spot momentum, plateaus, and turnarounds.' },
];

const blogs = [
    {
        slug:   'new-performance-evaluation-framework',
        image:  '/assets/hero/pic1.png',
        tag:    'Announcement',
        date:   'June 10, 2025',
        title:  'DOST Launches New Performance Evaluation Framework for Provincial S&T Directors',
        excerpt:'The Department of Science and Technology introduces an enhanced evaluation framework incorporating 37 key performance indicators to better assess the effectiveness of provincial S&T leadership.',
    },
    {
        slug:   'fy-2024-pstd-rankings-available',
        image:  '/assets/hero/pic2.png',
        tag:    'Updates',
        date:   'May 28, 2025',
        title:  'FY 2024 PSTD Rankings Now Available on the Information System',
        excerpt:'The FY 2024 annual rankings of Provincial Science and Technology Directors are now published, reflecting performance across core, functional, and support functions.',
    },
    {
        slug:   'regional-st-directors-summit-2025',
        image:  '/assets/hero/pic3.png',
        tag:    'Events',
        date:   'May 15, 2025',
        title:  'Regional S&T Directors Summit: Highlights and Key Takeaways',
        excerpt:'Officials from all 16 DOST regional offices gathered to discuss strategies for improving S&T service delivery and strengthening provincial S&T directorates.',
    },
];

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

const provinceBg = (province) => {
    const seed = encodeURIComponent((province?.name ?? 'province').toLowerCase().replace(/\s+/g, '-'));
    return `https://picsum.photos/seed/${seed}/800/300`;
};

const onProvinceSelected = (province) => {
    selectedProvince.value = province;
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
                            <div class="text-[13px] text-slate-500 hidden sm:block">Ranking &amp; Information System</div>
                            <div class="text-[11px] text-slate-400 hidden sm:block">Developed by Region IX</div>
                        </div>
                    </a>

                    <nav class="hidden md:flex items-center gap-1">
                        <a href="#about"    class="nav-link">About</a>
                        <a href="#system"   class="nav-link">The System</a>
                        <a href="#features" class="nav-link">Features</a>
                        <a href="#map"      class="nav-link">Map</a>
                        <a href="#news"     class="nav-link">News</a>
                        <a v-if="canLogin" href="/login" class="ml-2 btn-primary">Sign In</a>
                    </nav>

                    <button class="md:hidden p-2 text-slate-600" @click="mobileOpen = !mobileOpen" aria-label="Menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>

                <div v-if="mobileOpen" class="md:hidden pb-4 flex flex-col gap-1">
                    <a href="#about"    class="nav-link" @click="mobileOpen=false">About</a>
                    <a href="#system"   class="nav-link" @click="mobileOpen=false">The System</a>
                    <a href="#features" class="nav-link" @click="mobileOpen=false">Features</a>
                    <a href="#map"      class="nav-link" @click="mobileOpen=false">Map</a>
                    <a href="#news"     class="nav-link" @click="mobileOpen=false">News</a>
                    <a v-if="canLogin" href="/login" class="btn-primary text-center mt-1" @click="mobileOpen=false">Sign In</a>
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
                <div class="hero-content max-w-4xl mx-auto px-4 sm:px-6 py-8 text-center text-white">
                    <div class="hero-badge mx-auto">
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
                    <h1 class="hero-title">
                        Provincial Director
                        <span class="hero-title-accent">Ranking &amp; Information System for Management</span>
                    </h1>
                    <p class="hero-sub mx-auto">
                        A transparent, evidence-based platform that scores and ranks Provincial Science &amp; Technology
                        Directors across the Philippines - driving excellence in regional S&amp;T leadership.
                    </p>
                    <div class="mt-10 flex flex-wrap justify-center gap-3">
                        <a href="#about"  class="btn-hero-light">Learn More <RiArrowRightLine class="w-5 h-5" /></a>
                        <a href="/map"    class="btn-hero-ghost"><RiGlobalLine class="w-5 h-5" /> Explore the Map</a>
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
                        <div v-for="p in pillars" :key="p.title" class="pillar-card reveal">
                            <div class="pillar-icon">
                                <component :is="p.icon" class="w-6 h-6" />
                            </div>
                            <h4 class="mt-4 font-bold text-slate-900 text-base">{{ p.title }}</h4>
                            <p class="mt-1 text-sm text-slate-500 leading-relaxed">{{ p.text }}</p>
                        </div>
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
                    <article class="mvm-card reveal reveal--d1">
                        <div class="mvm-icon"><RiBuilding2Line class="w-6 h-6" /></div>
                        <h3 class="mvm-label">Mandate</h3>
                        <p class="mvm-text">Provide central direction, leadership and coordination of scientific and technological efforts and ensure that the results therefrom are geared and utilized in areas of maximum economic and social benefits for the people.</p>
                    </article>
                    <article class="mvm-card mvm-card--accent reveal reveal--d2">
                        <div class="mvm-icon"><RiLightbulbLine class="w-6 h-6" /></div>
                        <h3 class="mvm-label">Mission</h3>
                        <p class="mvm-text">To direct, lead, and coordinate the country's scientific, technological, and innovative efforts geared towards maximum economic and social benefits for the people.</p>
                    </article>
                    <article class="mvm-card reveal reveal--d3">
                        <div class="mvm-icon"><RiGlobalLine class="w-6 h-6" /></div>
                        <h3 class="mvm-label">Vision</h3>
                        <p class="mvm-text">DOST as the leading enabler and provider of science, technology, and innovation (STI) explicit solutions towards national development.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="system" class="py-24 lg:py-32 bg-slate-50">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
                <div class="eyebrow">The System</div>
                <h2 class="section-title">PRISM</h2>
                <p class="section-lead mx-auto">
                    The PRISM is DOST's centralized platform for monitoring,
                    evaluating, and ranking Provincial Science &amp; Technology Directors across the Philippines.
                </p>
                <p class="mt-5 text-lg text-slate-600 leading-relaxed">
                    Covering all 83 provinces and clusters across 17 regions, the system uses a structured
                    scoring framework with 37 performance indicators grouped into three function areas -
                    Core, Functional, and Support - to produce transparent, evidence-based rankings.
                </p>
                <p class="mt-5 text-lg text-slate-600 leading-relaxed">
                    Designed to promote excellence and accountability in regional S&amp;T leadership,
                    the system empowers DOST management to identify top performers, surface areas
                    for improvement, and track progress over time.
                </p>

                <div class="scoring-card reveal">
                    <div class="scoring-head">
                        <span class="scoring-head-title">Scoring Framework</span>
                        <span class="scoring-head-sub">37 indicators · 3 functions · 83 provinces</span>
                    </div>
                    <div class="scoring-keys">
                        <span class="scoring-key"><span class="scoring-dot" style="background:#0b57a8;"></span>Core Functions <b>60%</b></span>
                        <span class="scoring-key"><span class="scoring-dot" style="background:#2079c4;"></span>Functional Functions <b>30%</b></span>
                        <span class="scoring-key"><span class="scoring-dot" style="background:#3f93e0;"></span>Support Functions <b>10%</b></span>
                    </div>
                    <div class="scoring-stack">
                        <div class="scoring-seg" style="width:60%; background:#0b57a8;"><span>60%</span></div>
                        <div class="scoring-seg" style="width:30%; background:#2079c4;"><span>30%</span></div>
                        <div class="scoring-seg" style="width:10%; background:#3f93e0;"><span>10%</span></div>
                    </div>
                </div>

                <div class="mt-10 flex justify-center">
                    <a v-if="canLogin" href="/login" class="inline-flex items-center gap-2 btn-primary-lg">
                        Access the System <RiArrowRightLine class="w-5 h-5" />
                    </a>
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
                    <div v-for="f in features" :key="f.title" class="feature-row reveal">
                        <div class="feature-row-icon"><component :is="f.icon" class="w-7 h-7" /></div>
                        <div class="feature-row-body">
                            <h3 class="feature-row-title">{{ f.title }}</h3>
                            <p class="feature-row-text">{{ f.text }}</p>
                        </div>
                    </div>
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
                                        <img :src="provinceBg(selectedProvince)" :alt="selectedProvince.name" />
                                        <div class="prov-photo-gradient"></div>
                                        <button class="prov-close" @click="selectedProvince = null">
                                            <RiCloseLine class="w-4 h-4" />
                                        </button>
                                    </div>

                                    <!-- White content body -->
                                    <div class="prov-body">

                                        <!-- Province header -->
                                        <h3 class="prov-title">{{ selectedProvince.name }}</h3>
                                        <div class="prov-region-row">
                                            <span class="prov-swatch" :style="{ background: selectedProvince.color }"></span>
                                            {{ selectedProvince.region }}
                                        </div>
                                        <div class="prov-island-chip">
                                            <RiMapPin2Fill class="w-3 h-3" />
                                            {{ selectedProvince.island }}
                                        </div>

                                        <div class="prov-rows">
                                            <div class="prov-row">
                                                <RiGroupLine class="prov-row-icon" />
                                                <span>{{ selectedProvince.siblings.length + 1 }} provinces in this region</span>
                                            </div>
                                        </div>

                                        <!-- Description -->
                                        <p class="prov-desc">
                                            {{ selectedProvince.name }} is a province under {{ selectedProvince.region }},
                                            located in the {{ selectedProvince.island }} island group of the Philippines.
                                            It is part of DOST's provincial S&amp;T director network, which evaluates
                                            performance across Core, Functional, and Support functions.
                                        </p>

                                        <hr class="prov-sep" />

                                        <!-- Director -->
                                        <div v-if="sampleDirector">
                                            <div class="prov-dir-label">Provincial S&amp;T Director</div>
                                            <div class="prov-dir-card">
                                                <div class="prov-dir-avatar">
                                                    <img src="/assets/default-profile.avif" class="w-full h-full object-cover" alt="Director" />
                                                </div>
                                                <div class="prov-dir-info">
                                                    <div class="prov-dir-name">{{ sampleDirector.name }}</div>
                                                    <div class="prov-dir-service">{{ sampleDirector.years }} yrs of service</div>
                                                </div>
                                                <span class="prov-dir-badge">Sample</span>
                                            </div>
                                        </div>

                                        <hr class="prov-sep" />

                                        <!-- Sign-in teaser -->
                                        <div v-if="canLogin" class="prov-signin">
                                            <div class="prov-signin-head">
                                                <RiLockLine class="w-3.5 h-3.5" />
                                                <span>Rankings &amp; Director Profile</span>
                                            </div>
                                            <p class="prov-signin-text">
                                                KPI scores, performance tier, and director info for
                                                <strong>{{ selectedProvince.name }}</strong> are available after sign-in.
                                            </p>
                                            <a href="/login" class="prov-signin-btn">
                                                Sign In to View <RiArrowRightLine class="w-3.5 h-3.5" />
                                            </a>
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

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <article v-for="b in blogs" :key="b.title" class="blog-card reveal" @click="router.visit(`/blog/${b.slug}`)" style="cursor:pointer">
                        <div class="blog-card-img">
                            <img :src="b.image" :alt="b.title" />
                        </div>
                        <div class="blog-card-body">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="blog-tag">{{ b.tag }}</span>
                                <span class="flex items-center gap-1 text-sm text-slate-400">
                                    <RiCalendarLine class="w-3.5 h-3.5" /> {{ b.date }}
                                </span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 leading-snug">{{ b.title }}</h3>
                            <p class="mt-2 text-base text-slate-500 leading-relaxed line-clamp-3">{{ b.excerpt }}</p>
                        </div>
                        <div class="blog-card-footer">
                            <span class="blog-read-more">Read more <RiArrowRightUpLine class="w-4 h-4" /></span>
                        </div>
                    </article>
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
                        <a v-if="canLogin" href="/login" class="btn-hero-light">Sign In</a>
                        <a href="#map" class="btn-hero-ghost"><RiGlobalLine class="w-5 h-5" /> Explore the Map</a>
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
                            transparency, and evidence-based evaluation of provincial S&amp;T leadership
                            across the Philippines.
                        </p>
                        <p class="mt-2 text-sm font-semibold" style="color:#7e97b8">Developed by Region IX</p>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-white mb-4">Navigate</h4>
                        <ul class="space-y-2">
                            <li><a href="#about"    class="foot-link">About DOST</a></li>
                            <li><a href="#system"   class="foot-link">The System</a></li>
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
.nav-link {
    padding: 8px 14px; border-radius: 8px; font-size: 16px; font-weight: 600;
    color: #475569; transition: all .15s ease; text-decoration: none !important;
}
.nav-link:hover { color: var(--dost); background: var(--dost-tint); }
.btn-primary {
    display: inline-block; padding: 10px 22px; border-radius: 10px; font-size: 16px; font-weight: 700;
    color: #fff; background: var(--dost); box-shadow: 0 2px 8px rgba(11,87,168,.25); transition: background .15s ease;
    text-decoration: none !important;
}
.btn-primary:hover { background: var(--dost-700); }

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
    background: linear-gradient(180deg, rgba(8,47,95,.86) 0%, rgba(8,47,95,.46) 40%, rgba(8,47,95,.54) 68%, rgba(6,31,64,.92) 100%);
}
.hero-inner { position: relative; z-index: 3; flex: 1; display: flex; flex-direction: column; }
.hero-content { flex: 1 1 auto; display: flex; flex-direction: column; justify-content: center; min-height: 0; }

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
.hero-title {
    font-size: clamp(2.4rem, 4.5vw, 3.4rem); font-weight: 800; line-height: 1.08;
    color: #fff; text-shadow: 0 2px 20px rgba(0,0,0,.25);
}
.hero-title-accent { display: block; color: #8ec5ff; margin-top: 4px; }
.hero-sub {
    margin-top: 20px; max-width: 40rem; font-size: 1.25rem; line-height: 1.7;
    color: rgba(255,255,255,.88);
}
.btn-hero-light {
    display: inline-flex; align-items: center; gap: 8px; padding: 13px 24px; border-radius: 12px;
    background: #fff; color: var(--dost-700); font-weight: 700; box-shadow: 0 8px 24px rgba(0,0,0,.18);
    transition: transform .15s ease, background .15s ease;
}
.btn-hero-light:hover { background: #eef5ff; transform: translateY(-1px); }
.btn-hero-ghost {
    display: inline-flex; align-items: center; gap: 8px; padding: 13px 22px; border-radius: 12px;
    color: #fff; font-weight: 700; border: 1.5px solid rgba(255,255,255,.45); transition: background .15s ease;
}
.btn-hero-ghost:hover { background: rgba(255,255,255,.12); }

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

.btn-primary-lg {
    display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px; border-radius: 12px;
    background: var(--dost); color: #fff; font-weight: 700; font-size: 17px;
    box-shadow: 0 4px 14px rgba(11,87,168,.30); transition: background .15s ease, transform .15s ease;
}
.btn-primary-lg:hover { background: var(--dost-700); transform: translateY(-1px); }

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
.blog-card-img {
    width: 100%; height: 200px; overflow: hidden;
}
.blog-card-img img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform .4s ease;
}
.blog-card:hover .blog-card-img img { transform: scale(1.05); }
.blog-card {
    background: #fff; border: 1px solid #e7edf4; border-radius: 18px; overflow: hidden;
    display: flex; flex-direction: column;
    transition: box-shadow .15s ease, transform .15s ease;
}
.blog-card:hover { box-shadow: 0 12px 30px rgba(11,87,168,.10); transform: translateY(-2px); }
.blog-card-body { padding: 24px; flex: 1; }
.blog-tag {
    display: inline-block; font-size: 13px; font-weight: 700; letter-spacing: .04em;
    text-transform: uppercase; color: var(--dost); background: var(--dost-tint);
    border: 1px solid var(--dost-line); padding: 4px 12px; border-radius: 999px;
}
.blog-card-footer {
    padding: 14px 24px; border-top: 1px solid #f1f5f9;
}
.blog-read-more {
    display: inline-flex; align-items: center; gap: 4px; font-size: 15px;
    font-weight: 700; color: var(--dost); transition: gap .15s ease;
}
.blog-card:hover .blog-read-more { gap: 8px; }

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
.hero-badge, .hero-tagline, .hero-title, .hero-sub {
    animation: heroIn .85s cubic-bezier(.22,1,.36,1) both;
}
.hero-tagline { animation-delay: .08s; }
.hero-title   { animation-delay: .16s; }
.hero-sub     { animation-delay: .26s; }
@keyframes heroIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: none; } }
@media (prefers-reduced-motion: reduce) {
    .hero-badge, .hero-tagline, .hero-title, .hero-sub { animation: none; }
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
.scoring-key  { display: inline-flex; align-items: center; gap: 7px; font-size: .98rem; font-weight: 600; color: #334155; }
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
