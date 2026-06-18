<script setup>
import { Head, Link } from '@inertiajs/vue3';
import RegionInfoMap from '@/Components/Map/RegionInfoMap.vue';

defineProps({
    canLogin: { type: Boolean, default: true },
});

const year = new Date().getFullYear();
</script>

<template>
    <Head title="Interactive Map — DOST PSTD Ranking & Information System" />

    <div class="min-h-screen bg-white text-slate-800">
        <!-- ── Navbar ─────────────────────────────────────────────────────── -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-16">
                    <a href="/" class="flex items-center gap-3">
                        <img src="/assets/logo.png" alt="DOST" class="w-10 h-10 rounded-lg object-contain" />
                        <div class="leading-tight">
                            <div class="font-extrabold text-[#082f5f] text-lg tracking-tight">DOST PSTD</div>
                            <div class="text-[11px] text-slate-500 hidden sm:block">Ranking &amp; Information System</div>
                        </div>
                    </a>

                    <nav class="flex items-center gap-1">
                        <a href="/" class="nav-link">Home</a>
                        <a v-if="canLogin" href="/login" class="ml-1 btn-primary">Sign In</a>
                    </nav>
                </div>
            </div>
        </header>

        <!-- ── Map section ────────────────────────────────────────────────── -->
        <section class="py-16 lg:py-24 bg-slate-50 min-h-[calc(100vh-64px)]">
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
                    <RegionInfoMap height="680px" />
                </div>
                <p class="mt-3 text-sm text-slate-400">
                    Geographic data shown for public reference. Performance rankings are available after sign-in.
                </p>
            </div>
        </section>

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
    padding: 8px 12px; border-radius: 8px; font-size: 14px; font-weight: 600;
    color: #475569; transition: all .15s ease;
}
.nav-link:hover { color: #0b57a8; background: #eaf2fb; }
.btn-primary {
    display: inline-block; padding: 9px 20px; border-radius: 10px; font-size: 14px; font-weight: 700;
    color: #fff; background: #0b57a8; box-shadow: 0 2px 8px rgba(11,87,168,.25); transition: background .15s ease;
}
.btn-primary:hover { background: #0a3f7d; }
.eyebrow { font-size: 13px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #0b57a8; }
.section-title { margin-top: 8px; font-size: clamp(1.8rem, 3vw, 2.4rem); font-weight: 800; color: #0f172a; line-height: 1.15; }
.section-lead { margin-top: 16px; font-size: 1.125rem; line-height: 1.7; color: #475569; max-width: 42rem; }
</style>
