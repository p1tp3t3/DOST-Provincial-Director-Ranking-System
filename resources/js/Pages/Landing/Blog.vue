<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { RiCalendarLine, RiArrowLeftLine, RiPriceTag3Line } from '@remixicon/vue';

defineProps({
    slug:     { type: String,  required: true },
    post:     { type: Object,  default: null },
    related:  { type: Array,   default: () => [] },
    canLogin: { type: Boolean, default: true },
});

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
