<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { RiCalendarLine, RiArrowLeftLine, RiPriceTag3Line } from '@remixicon/vue';

const props = defineProps({
    slug:     { type: String, required: true },
    canLogin: { type: Boolean, default: true },
});

const allPosts = [
    {
        slug:    'new-performance-evaluation-framework',
        image:   '/assets/hero/pic1.png',
        tag:     'Announcement',
        date:    'June 10, 2025',
        title:   'DOST Launches New Performance Evaluation Framework for Provincial S&T Directors',
        excerpt: 'The Department of Science and Technology introduces an enhanced evaluation framework incorporating 37 key performance indicators to better assess the effectiveness of provincial S&T leadership.',
        body: [
            'The Department of Science and Technology (DOST) has officially launched a comprehensive new performance evaluation framework for Provincial Science and Technology Directors (PSTDs) across the Philippines. The framework introduces 37 structured key performance indicators (KPIs) designed to provide a more holistic, evidence-based assessment of each director\'s contributions to science and technology in their respective provinces.',
            'The 37 indicators are organized into three categories: Core (weighted at 60%), which covers the primary S&T mandate of each provincial office; Strategic (30%), which assesses administrative and coordination effectiveness; and Support (10%), which evaluates auxiliary activities that contribute to the overall performance of the provincial S&T office.',
            'DOST Secretary emphasized that the new framework reflects the agency\'s commitment to transparency and accountability. "We want every Provincial S&T Director to know exactly how they are being measured, and we want the public to trust that our evaluations are fair, consistent, and grounded in real data," the Secretary said during the launch event.',
            'The framework will be implemented starting Fiscal Year 2025 and will feed directly into the PRISM, which provides real-time rankings and dashboards accessible to authorized DOST personnel. Provincial directors will receive detailed scorecards at the end of each evaluation period to guide their improvement efforts.',
            'Training sessions for regional directors and evaluation officers are scheduled throughout the third quarter of 2025 to ensure a smooth rollout of the new framework nationwide.',
        ],
    },
    {
        slug:    'fy-2024-pstd-rankings-available',
        image:   '/assets/hero/pic2.png',
        tag:     'Updates',
        date:    'May 28, 2025',
        title:   'FY 2024 PSTD Rankings Now Available on the Information System',
        excerpt: 'The FY 2024 annual rankings of Provincial Science and Technology Directors are now published, reflecting performance across core, strategic, and support categories.',
        body: [
            'DOST is pleased to announce that the Fiscal Year 2024 rankings of all Provincial Science and Technology Directors (PSTDs) are now available on the PRISM. The rankings cover all 83 provinces and clusters across the three island groups of the Philippines - Luzon, Visayas, and Mindanao.',
            'This year\'s rankings reflect performance data collected and validated throughout FY 2024, covering all 37 performance indicators across Core, Strategic, and Support categories. Provinces are evaluated both individually and within their classification tier - whether large, medium, or small - to ensure fair comparisons across different contexts.',
            'The FY 2024 results show notable improvements in several regions, particularly in the Visayas group, where a number of provincial offices posted their highest scores in three years. DOST Regional Directors credit the improvement to increased collaboration, better data reporting practices, and targeted capacity-building programs conducted during the year.',
            'Authorized DOST personnel can access the full rankings dashboard, which includes podium views, sortable data tables, year-over-year trend charts, and a color-coded performance map. Individual province scorecards are also available for download.',
            'The FY 2024 rankings will serve as the baseline for the new performance evaluation framework launching in FY 2025. Provincial directors are encouraged to review their scorecards and coordinate with their regional offices to identify priority areas for improvement.',
        ],
    },
    {
        slug:    'regional-st-directors-summit-2025',
        image:   '/assets/hero/pic3.png',
        tag:     'Events',
        date:    'May 15, 2025',
        title:   'Regional S&T Directors Summit: Highlights and Key Takeaways',
        excerpt: 'Officials from all 17 DOST regional offices gathered to discuss strategies for improving S&T service delivery and strengthening provincial S&T directorates.',
        body: [
            'The Department of Science and Technology recently convened the 2025 Regional S&T Directors Summit, bringing together officials from all 16 regional offices, provincial directors, and central office representatives. The summit, held over two days, focused on strategies for improving science and technology service delivery at the provincial level and strengthening the capacity of provincial S&T directorates.',
            'A key agenda item was the rollout plan for the updated PSTD performance evaluation framework. Regional directors engaged in breakout sessions to discuss implementation challenges, data collection protocols, and best practices for supporting provincial offices in meeting the new KPI standards.',
            'The summit also featured presentations from top-performing provincial directors, who shared the strategies that contributed to their high rankings in FY 2023. Themes included proactive stakeholder engagement, improved data management systems, and stronger collaboration with local government units and academic institutions.',
            'Participants also reviewed the capabilities of the PRISM, with a live demonstration of the new dashboard features including the interactive performance map, trend analysis charts, and the province directory module.',
            'The event concluded with a commitment from all regional directors to cascade the summit\'s outcomes to their provincial offices and to submit quarterly progress reports through the information system. The next summit is tentatively scheduled for the fourth quarter of 2025.',
        ],
    },
];

const post = computed(() => allPosts.find(p => p.slug === props.slug) ?? null);
const related = computed(() => allPosts.filter(p => p.slug !== props.slug));
const year = new Date().getFullYear();
</script>

<template>
    <Head :title="post ? post.title : 'Blog'" />

    <div class="min-h-screen bg-white text-slate-800">

        <!-- ── Navbar ──────────────────────────────────────────────────────── -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-16">
                    <a href="/" class="flex items-center gap-3">
                        <img src="/assets/logo.png" alt="DOST" class="w-10 h-10 rounded-lg object-contain" />
                        <div class="leading-tight">
                            <div class="font-extrabold text-[#082f5f] text-lg tracking-tight">DOST PRISM</div>
                            <div class="text-[11px] text-slate-500 hidden sm:block">Ranking &amp; Information System</div>
                        </div>
                    </a>
                    <nav class="flex items-center gap-2">
                        <a href="/#news" class="nav-link">
                            <RiArrowLeftLine class="w-4 h-4" /> Back to News
                        </a>
                        <a v-if="canLogin" href="/login" class="ml-1 btn-primary">Sign In</a>
                    </nav>
                </div>
            </div>
        </header>

        <!-- ── 404 fallback ───────────────────────────────────────────────── -->
        <div v-if="!post" class="max-w-2xl mx-auto px-4 py-32 text-center">
            <div class="text-5xl font-extrabold text-slate-200 mb-4">404</div>
            <h1 class="text-2xl font-bold text-slate-800">Post not found</h1>
            <p class="mt-2 text-slate-500">This article doesn't exist or may have been moved.</p>
            <a href="/" class="mt-6 inline-flex items-center gap-2 btn-primary">
                <RiArrowLeftLine class="w-4 h-4" /> Go Home
            </a>
        </div>

        <template v-else>
            <!-- ── Hero image ─────────────────────────────────────────────── -->
            <div class="blog-hero">
                <img :src="post.image" :alt="post.title" />
                <div class="blog-hero-veil"></div>
            </div>

            <!-- ── Article ────────────────────────────────────────────────── -->
            <main class="max-w-3xl mx-auto px-4 sm:px-6 py-14">
                <div class="flex items-center gap-3 mb-6">
                    <span class="blog-tag">
                        <RiPriceTag3Line class="w-3.5 h-3.5" /> {{ post.tag }}
                    </span>
                    <span class="flex items-center gap-1.5 text-sm text-slate-400">
                        <RiCalendarLine class="w-4 h-4" /> {{ post.date }}
                    </span>
                </div>

                <h1 class="text-3xl lg:text-4xl font-extrabold text-slate-900 leading-tight">
                    {{ post.title }}
                </h1>

                <p class="mt-5 text-lg text-slate-500 leading-relaxed border-l-4 border-blue-200 pl-4 italic">
                    {{ post.excerpt }}
                </p>

                <div class="mt-10 space-y-5">
                    <p v-for="(para, i) in post.body" :key="i" class="text-slate-700 leading-relaxed text-[17px]">
                        {{ para }}
                    </p>
                </div>

                <div class="mt-12 pt-8 border-t border-slate-100">
                    <a href="/#news" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-900">
                        <RiArrowLeftLine class="w-4 h-4" /> Back to all news
                    </a>
                </div>
            </main>

            <!-- ── Related posts ──────────────────────────────────────────── -->
            <section class="bg-slate-50 py-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-8">More from DOST PRISM</h2>
                    <div class="grid sm:grid-cols-2 gap-6">
                        <a v-for="r in related" :key="r.slug" :href="`/blog/${r.slug}`" class="related-card">
                            <div class="related-img">
                                <img :src="r.image" :alt="r.title" />
                            </div>
                            <div class="related-body">
                                <span class="blog-tag text-xs">{{ r.tag }}</span>
                                <p class="mt-2 font-semibold text-slate-900 leading-snug line-clamp-2">{{ r.title }}</p>
                                <p class="mt-1 text-xs text-slate-400 flex items-center gap-1">
                                    <RiCalendarLine class="w-3 h-3" /> {{ r.date }}
                                </p>
                            </div>
                        </a>
                    </div>
                </div>
            </section>
        </template>

        <!-- ── Footer ─────────────────────────────────────────────────────── -->
        <footer class="bg-[#082f5f] text-slate-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 text-center text-sm" style="color:#7e97b8">
                &copy; {{ year }} Department of Science and Technology. All rights reserved.
            </div>
        </footer>
    </div>
</template>

<style scoped>
.nav-link {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 12px; border-radius: 8px; font-size: 14px; font-weight: 600;
    color: #475569; transition: all .15s ease;
}
.nav-link:hover { color: #0b57a8; background: #eaf2fb; }
.btn-primary {
    display: inline-block; padding: 9px 20px; border-radius: 10px; font-size: 14px; font-weight: 700;
    color: #fff; background: #0b57a8; transition: background .15s ease;
}
.btn-primary:hover { background: #0a3f7d; }

.blog-hero {
    position: relative; height: 420px; overflow: hidden;
}
.blog-hero img {
    width: 100%; height: 100%; object-fit: cover;
}
.blog-hero-veil {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,.25) 0%, rgba(0,0,0,.05) 60%, rgba(255,255,255,1) 100%);
}

.blog-tag {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    color: #0b57a8; background: #eaf2fb; border: 1px solid #d6e4f5;
    padding: 4px 10px; border-radius: 999px;
}

.related-card {
    display: flex; gap: 16px; background: #fff; border: 1px solid #e7edf4;
    border-radius: 16px; overflow: hidden; transition: box-shadow .15s ease, transform .15s ease;
}
.related-card:hover { box-shadow: 0 10px 28px rgba(11,87,168,.10); transform: translateY(-2px); }
.related-img { width: 120px; height: 100%; min-height: 100px; flex-shrink: 0; overflow: hidden; }
.related-img img { width: 100%; height: 100%; object-fit: cover; }
.related-body { padding: 16px 16px 16px 0; }
</style>
