<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import RegionInfoMap from '@/Components/Map/RegionInfoMap.vue';
import {
    RiTrophyLine, RiMapPin2Line, RiBuilding2Line, RiBarChart2Line,
    RiLineChartLine, RiGroupLine, RiShieldCheckLine, RiDashboard3Line,
    RiArrowRightLine, RiGlobalLine,
} from '@remixicon/vue';

defineProps({
    canLogin:    { type: Boolean, default: true },
    canRegister: { type: Boolean, default: false },
});

const stats = [
    { value: '16', label: 'Regions' },
    { value: '83', label: 'Provinces & Clusters' },
    { value: '3',  label: 'Island Groups' },
    { value: '37', label: 'Performance KPIs' },
];

const features = [
    { icon: RiDashboard3Line, title: 'Performance Dashboard',
      text: 'See PSTD rankings four ways — an awards podium, a sortable table, year-over-year trends, and a live map — all filterable by tier, region, island, and category.' },
    { icon: RiMapPin2Line, title: 'Interactive Maps',
      text: 'Explore the country province-by-province. Drill into a region or island and read each area at a glance through clear color coding.' },
    { icon: RiBuilding2Line, title: 'Province Directories',
      text: 'Browse every province’s profile — its provincial S&T director, staff, classification tier, and region — in one organized place.' },
    { icon: RiTrophyLine, title: 'PSTD Ranking Matrix',
      text: 'A transparent, weighted scoring of 37 indicators across Core (60%), Functional (30%), and Support (10%) functions, with adjective ratings.' },
    { icon: RiBarChart2Line, title: 'Regional Analytics',
      text: 'Compare performance across regions, islands, and size tiers, with top and low performers surfaced automatically.' },
    { icon: RiLineChartLine, title: 'Trends Over Time',
      text: 'Track how each province and director performs across reporting years to spot momentum, plateaus, and turnarounds.' },
];

const steps = [
    { n: '01', title: 'Explore the map', text: 'Start on the public map to understand how provinces are grouped into regions and islands.' },
    { n: '02', title: 'Sign in', text: 'Authorized DOST staff sign in to unlock the full ranking dashboard and directories.' },
    { n: '03', title: 'Review rankings', text: 'Open the dashboard to see podium, table, trend, and performance-map views.' },
    { n: '04', title: 'Dive into details', text: 'Open any province to view its director, KPI scores, and history.' },
];

const year = new Date().getFullYear();
const mobileOpen = ref(false);
</script>

<template>
    <Head title="Welcome — DOST PSTD Ranking & Information System" />

    <div class="landing min-h-screen bg-white text-slate-800">
        <!-- ── Navbar ─────────────────────────────────────────────────────── -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-16">
                    <a href="/" class="flex items-center gap-3">
                        <img src="/assets/logo.png" alt="DOST" class="w-10 h-10 rounded-lg object-contain" />
                        <div class="leading-tight">
                            <div class="font-extrabold dost-ink text-lg tracking-tight">DOST PSTD</div>
                            <div class="text-[11px] text-slate-500 hidden sm:block">Ranking &amp; Information System</div>
                        </div>
                    </a>

                    <nav class="hidden md:flex items-center gap-1">
                        <a href="#overview" class="nav-link">Overview</a>
                        <a href="#map" class="nav-link">Map</a>
                        <a href="#features" class="nav-link">Features</a>
                        <a v-if="canRegister" href="/register" class="ml-2 btn-outline">Register</a>
                        <a v-if="canLogin" href="/login" class="ml-1 btn-primary">Sign In</a>
                    </nav>

                    <button class="md:hidden p-2 text-slate-600" @click="mobileOpen = !mobileOpen" aria-label="Menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
                <div v-if="mobileOpen" class="md:hidden pb-4 flex flex-col gap-1">
                    <a href="#overview" class="nav-link" @click="mobileOpen=false">Overview</a>
                    <a href="#map" class="nav-link" @click="mobileOpen=false">Map</a>
                    <a href="#features" class="nav-link" @click="mobileOpen=false">Features</a>
                    <a v-if="canLogin" href="/login" class="btn-primary text-center mt-1" @click="mobileOpen=false">Sign In</a>
                </div>
            </div>
        </header>

        <!-- ── Hero (video background) ─────────────────────────────────────── -->
        <section id="overview" class="hero">
            <!-- Drop a DOST clip at public/assets/hero.mp4 to enable the video.
                 Until then, the animated DOST-blue background below shows. -->
            <video class="hero-video" autoplay muted loop playsinline poster="/assets/hero-poster.jpg">
                <source src="/assets/hero.mp4" type="video/mp4" />
            </video>
            <div class="hero-anim"></div>
            <div class="hero-veil"></div>

            <div class="hero-inner">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 py-20 lg:py-28">
                    <div class="grid lg:grid-cols-2 gap-12 items-center">
                        <div class="text-white">
                            <div class="hero-badge">
                                <RiShieldCheckLine class="w-4 h-4" /> Department of Science and Technology
                            </div>
                            <h1 class="hero-title">
                                Provincial S&amp;T Director
                                <span class="hero-title-accent">Ranking &amp; Information System</span>
                            </h1>
                            <p class="hero-sub">
                                A transparent, evidence-based platform that scores and ranks Provincial
                                Science &amp; Technology Directors across the Philippines — and maps the
                                performance of every region and province in one place.
                            </p>
                            <div class="mt-8 flex flex-wrap gap-3">
                                <a href="#map" class="btn-hero-light"><RiGlobalLine class="w-5 h-5" /> Explore the Map</a>
                                <a v-if="canLogin" href="/login" class="btn-hero-ghost">Sign In <RiArrowRightLine class="w-5 h-5" /></a>
                            </div>
                        </div>

                        <!-- Hero preview card -->
                        <div class="hero-card">
                            <div class="flex items-center gap-2 mb-4">
                                <RiTrophyLine class="w-5 h-5" style="color:#c79a1e" />
                                <span class="font-bold text-slate-800">Top Performers</span>
                                <span class="ml-auto text-xs font-semibold text-slate-400">Sample</span>
                            </div>
                            <div class="space-y-3">
                                <div v-for="(p, i) in [
                                    { rank:'1st', name:'Province A', region:'Region A', pct:'57.5', c:'#c79a1e' },
                                    { rank:'2nd', name:'Province B', region:'Region B', pct:'35.8', c:'#8a97a8' },
                                    { rank:'3rd', name:'Province C', region:'Region C', pct:'33.6', c:'#b06b34' },
                                ]" :key="i" class="flex items-center gap-3">
                                    <span class="w-9 h-9 grid place-items-center rounded-lg text-white text-xs font-bold" :style="{ background: p.c }">{{ p.rank }}</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold text-slate-800 text-sm truncate">{{ p.name }}</div>
                                        <div class="text-xs text-slate-400">{{ p.region }}</div>
                                    </div>
                                    <div class="font-extrabold text-slate-800">{{ p.pct }}%</div>
                                </div>
                            </div>
                            <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                                <div class="wcell wcell-1"><div class="wlabel">CORE</div><div class="wval">60%</div></div>
                                <div class="wcell wcell-2"><div class="wlabel">FUNCTIONAL</div><div class="wval">30%</div></div>
                                <div class="wcell wcell-3"><div class="wlabel">SUPPORT</div><div class="wval">10%</div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats strip -->
                <div class="hero-stats">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div v-for="s in stats" :key="s.label" class="text-center">
                            <div class="text-3xl font-extrabold text-white">{{ s.value }}</div>
                            <div class="text-sm font-medium mt-1" style="color:#bcd6f2">{{ s.label }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Map section ────────────────────────────────────────────────── -->
        <section id="map" class="py-16 lg:py-24 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="max-w-2xl">
                    <div class="eyebrow">Interactive Map</div>
                    <h2 class="section-title">Explore the Philippines by region</h2>
                    <p class="section-lead">
                        Every province is color-coded by its region across the three island groups —
                        Luzon, Visayas, and Mindanao. Hover to identify a province, click to see the
                        other provinces in its region, or use the legend to jump straight to a region.
                    </p>
                </div>

                <div class="mt-8 rounded-2xl bg-white border border-slate-200 shadow-xl p-3 sm:p-4">
                    <RegionInfoMap height="620px" />
                </div>
                <p class="mt-3 text-sm text-slate-400">
                    Geographic data shown for public reference. Performance rankings are available after sign-in.
                </p>
            </div>
        </section>

        <!-- ── Features ───────────────────────────────────────────────────── -->
        <section id="features" class="py-16 lg:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="max-w-2xl mx-auto text-center">
                    <div class="eyebrow">What you can see</div>
                    <h2 class="section-title">Everything the system offers</h2>
                    <p class="section-lead mx-auto">From a high-level national overview down to a single province’s KPI scorecard.</p>
                </div>

                <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="f in features" :key="f.title" class="feature">
                        <div class="feat-icon"><component :is="f.icon" class="w-6 h-6" /></div>
                        <h3 class="mt-5 text-lg font-bold text-slate-900">{{ f.title }}</h3>
                        <p class="mt-2 text-slate-600 leading-relaxed">{{ f.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── How it works ───────────────────────────────────────────────── -->
        <section class="py-16 lg:py-24 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="max-w-2xl">
                    <div class="eyebrow">How it works</div>
                    <h2 class="section-title">From overview to detail in four steps</h2>
                </div>
                <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="s in steps" :key="s.n" class="rounded-2xl bg-white border border-slate-200 p-7">
                        <div class="step-num">{{ s.n }}</div>
                        <h3 class="mt-3 text-lg font-bold text-slate-900">{{ s.title }}</h3>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ s.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── CTA ────────────────────────────────────────────────────────── -->
        <section class="py-16 lg:py-20">
            <div class="max-w-5xl mx-auto px-4 sm:px-6">
                <div class="cta">
                    <RiGroupLine class="w-12 h-12 mx-auto" style="color:#bcd6f2" />
                    <h2 class="mt-4 text-3xl lg:text-4xl font-extrabold text-white">Ready to dive into the rankings?</h2>
                    <p class="mt-3 text-lg max-w-2xl mx-auto" style="color:#cfe0f5">
                        Authorized DOST personnel can sign in to access the full performance dashboard,
                        province directories, and reports.
                    </p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <a v-if="canLogin" href="/login" class="btn-hero-light">Sign In</a>
                        <a href="#map" class="btn-hero-ghost">Back to the map</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Footer ─────────────────────────────────────────────────────── -->
        <footer class="footer text-slate-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
                <div class="grid md:grid-cols-4 gap-8">
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-3">
                            <img src="/assets/logo.png" alt="DOST" class="w-9 h-9 rounded-lg object-contain bg-white p-0.5" />
                            <span class="font-bold text-white">DOST PSTD Ranking &amp; Information System</span>
                        </div>
                        <p class="mt-4 text-sm max-w-md leading-relaxed" style="color:#9fb6d4">
                            An initiative of the Department of Science and Technology to promote excellence,
                            transparency, and evidence-based evaluation of provincial S&amp;T leadership.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white mb-3">Explore</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#overview" class="foot-link">Overview</a></li>
                            <li><a href="#map" class="foot-link">Regional Map</a></li>
                            <li><a href="#features" class="foot-link">Features</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white mb-3">Access</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link href="/login" class="foot-link">Sign In</Link></li>
                            <li v-if="canRegister"><Link href="/register" class="foot-link">Register</Link></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-10 pt-6 border-t text-center text-sm" style="border-color:#123a66;color:#7e97b8">
                    &copy; {{ year }} Department of Science and Technology. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* ── DOST palette (logo blue) ────────────────────────────────────────────── */
.landing {
    --dost:        #0b57a8;  /* primary DOST blue */
    --dost-600:    #0a4d96;
    --dost-700:    #0a3f7d;
    --dost-navy:   #082f5f;  /* deep navy */
    --dost-navy-2: #061f40;
    --dost-tint:   #eaf2fb;  /* pale blue surface */
    --dost-line:   #d6e4f5;
}

/* ── Nav ─────────────────────────────────────────────────────────────────── */
.dost-ink { color: var(--dost-navy); }
.nav-link {
    padding: 8px 12px; border-radius: 8px; font-size: 14px; font-weight: 600;
    color: #475569; transition: all .15s ease;
}
.nav-link:hover { color: var(--dost); background: var(--dost-tint); }
.btn-primary {
    display: inline-block; padding: 9px 20px; border-radius: 10px; font-size: 14px; font-weight: 700;
    color: #fff; background: var(--dost); box-shadow: 0 2px 8px rgba(11,87,168,.25); transition: background .15s ease;
}
.btn-primary:hover { background: var(--dost-700); }
.btn-outline {
    display: inline-block; padding: 9px 18px; border-radius: 10px; font-size: 14px; font-weight: 700;
    color: var(--dost); border: 1.5px solid var(--dost-line); transition: all .15s ease;
}
.btn-outline:hover { background: var(--dost-tint); border-color: var(--dost); }

/* ── Hero ────────────────────────────────────────────────────────────────── */
.hero {
    position: relative; overflow: hidden;
    background: linear-gradient(135deg, var(--dost-navy) 0%, var(--dost) 65%, var(--dost-navy) 100%);
}
.hero-video {
    position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;
    z-index: 0; opacity: .9;
}
/* Animated science/tech texture — also the graceful fallback when no video is set */
.hero-anim {
    position: absolute; inset: -20%; z-index: 1; opacity: .5; pointer-events: none;
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
/* Dark DOST-blue veil guarantees white-text contrast over any video */
.hero-veil {
    position: absolute; inset: 0; z-index: 2; pointer-events: none;
    background: linear-gradient(118deg, rgba(6,31,64,.92) 0%, rgba(10,63,125,.80) 52%, rgba(6,31,64,.90) 100%);
}
.hero-inner { position: relative; z-index: 3; }

.hero-badge {
    display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px;
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.22);
    padding: 6px 14px; border-radius: 999px; font-size: 12.5px; font-weight: 600; letter-spacing: .02em; color: #eaf2fb;
}
.hero-title {
    font-size: clamp(2.4rem, 4.5vw, 3.4rem); font-weight: 800; line-height: 1.08; letter-spacing: -.01em;
    color: #ffffff; text-shadow: 0 2px 20px rgba(0,0,0,.25);
}
.hero-title-accent { display: block; color: #8ec5ff; margin-top: 2px; }
.hero-sub {
    margin-top: 20px; max-width: 36rem; font-size: 1.125rem; line-height: 1.7;
    color: rgba(255,255,255,.9);
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

/* Hero preview card */
.hero-card {
    background: rgba(255,255,255,.97); backdrop-filter: blur(6px);
    border-radius: 18px; padding: 22px; box-shadow: 0 24px 60px rgba(3,20,46,.45);
    border: 1px solid rgba(255,255,255,.5);
}
.wcell { border-radius: 10px; padding: 8px 4px; }
.wcell-1 { background: #e7f0fb; }
.wcell-2 { background: #eef4fc; }
.wcell-3 { background: #f4f8fd; }
.wlabel { font-size: 11px; font-weight: 700; color: var(--dost-600); }
.wcell-2 .wlabel { color: #3f7fc4; }
.wcell-3 .wlabel { color: #6b9fd6; }
.wval { font-weight: 800; color: var(--dost-navy); }

.hero-stats { position: relative; z-index: 3; border-top: 1px solid rgba(255,255,255,.14); background: rgba(255,255,255,.05); }

/* ── Shared section bits ─────────────────────────────────────────────────── */
.eyebrow { font-size: 13px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--dost); }
.section-title { margin-top: 8px; font-size: clamp(1.8rem, 3vw, 2.4rem); font-weight: 800; color: #0f172a; line-height: 1.15; }
.section-lead { margin-top: 16px; font-size: 1.125rem; line-height: 1.7; color: #475569; max-width: 42rem; }

.feature { border: 1px solid #e7edf4; border-radius: 18px; padding: 28px; background: #fff; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
.feature:hover { border-color: var(--dost-line); box-shadow: 0 12px 30px rgba(11,87,168,.10); transform: translateY(-2px); }
.feat-icon { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; background: var(--dost-tint); color: var(--dost); transition: background .15s ease, color .15s ease; }
.feature:hover .feat-icon { background: var(--dost); color: #fff; }

.step-num { font-size: 2.5rem; font-weight: 900; color: #cfe0f5; line-height: 1; }

/* ── CTA ─────────────────────────────────────────────────────────────────── */
.cta {
    position: relative; overflow: hidden; border-radius: 28px; padding: 56px 32px; text-align: center;
    background: linear-gradient(120deg, var(--dost-navy) 0%, var(--dost) 100%);
    box-shadow: 0 24px 60px rgba(8,47,95,.30);
}

/* ── Footer ──────────────────────────────────────────────────────────────── */
.footer { background: var(--dost-navy); }
.foot-link { color: #b9cce6; transition: color .15s ease; }
.foot-link:hover { color: #fff; }
</style>
