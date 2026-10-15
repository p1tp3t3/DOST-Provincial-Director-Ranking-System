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
    <Head title="Blog" />

    <div class="min-h-screen bg-white text-slate-800">

        <!-- ── Navbar ──────────────────────────────────────────────────────── -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-16">
                    <a href="/" class="flex items-center gap-3" style="text-decoration:none">
                        <img src="/assets/logo.png" alt="DOST" class="w-10 h-10 rounded-lg object-contain" />
                        <div class="leading-tight">
                            <div class="font-extrabold text-[#082f5f] text-lg tracking-tight">DOST PRISM</div>
                            <div class="text-[11px] text-slate-500 hidden sm:block">Ranking &amp; Information System</div>
                        </div>
                    </a>
                    <nav class="flex items-center gap-2">
                        <v-btn href="/#news" variant="text" rounded="lg" class="nav-btn">
                            <template #prepend><RiArrowLeftLine class="w-4 h-4" /></template>
                            Back to News
                        </v-btn>
                        <v-btn v-if="canLogin" href="/login" color="#0b57a8" variant="flat" rounded="lg" class="ml-1 dost-shadow">Sign In</v-btn>
                    </nav>
                </div>
            </div>
        </header>

        <!-- ── 404 fallback ───────────────────────────────────────────────── -->
        <div v-if="!post" class="max-w-2xl mx-auto px-4 py-32 text-center">
            <div class="text-5xl font-extrabold text-slate-200 mb-4">404</div>
            <h1 class="text-2xl font-bold text-slate-800">Post not found</h1>
            <p class="mt-2 text-slate-500">This article doesn't exist or may have been moved.</p>
            <v-btn href="/" color="#0b57a8" variant="flat" rounded="lg" class="mt-6 dost-shadow">
                <template #prepend><RiArrowLeftLine class="w-4 h-4" /></template>
                Go Home
            </v-btn>
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
                    <v-chip size="small" variant="tonal" color="#0b57a8" class="font-weight-bold text-uppercase">
                        <template #prepend><RiPriceTag3Line class="w-3.5 h-3.5 mr-1" /></template>
                        {{ post.tag }}
                    </v-chip>
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

                <div class="mt-10 blog-content" v-html="post.body"></div>

                <div class="mt-12 pt-8 border-t border-slate-100">
                    <v-btn href="/#news" variant="text" size="small" color="#0b57a8" class="px-0">
                        <template #prepend><RiArrowLeftLine class="w-4 h-4" /></template>
                        Back to all news
                    </v-btn>
                </div>
            </main>

            <!-- ── Related posts ──────────────────────────────────────────── -->
            <section class="bg-slate-50 py-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-8">More from DOST PRISM</h2>
                    <div class="grid sm:grid-cols-2 gap-6">
                        <v-card v-for="r in related" :key="r.slug" :href="`/blog/${r.slug}`" variant="flat" class="related-card">
                            <v-img :src="r.image" :alt="r.title" cover width="120" min-height="100" class="related-img" />
                            <div class="related-body">
                                <v-chip size="x-small" variant="tonal" color="#0b57a8" class="font-weight-bold text-uppercase">{{ r.tag }}</v-chip>
                                <p class="mt-2 font-semibold text-slate-900 leading-snug line-clamp-2">{{ r.title }}</p>
                                <p class="mt-1 text-xs text-slate-400 flex items-center gap-1">
                                    <RiCalendarLine class="w-3 h-3" /> {{ r.date }}
                                </p>
                            </div>
                        </v-card>
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
/* ── Vuetify components in the landing's look ───────────────────────────── */
.v-btn { text-transform: none; letter-spacing: normal; font-weight: 700; }
.nav-btn { color: #475569 !important; font-size: 16px; }
.nav-btn:hover { color: #0b57a8 !important; }
.dost-shadow { box-shadow: 0 2px 8px rgba(11,87,168,.25); }

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


.related-card {
    display: flex; gap: 16px; background: #fff; border: 1px solid #e7edf4;
    border-radius: 16px; overflow: hidden; transition: box-shadow .15s ease, transform .15s ease;
}
.related-card:hover { box-shadow: 0 10px 28px rgba(11,87,168,.10); transform: translateY(-2px); }
.related-img { width: 120px; height: 100%; min-height: 100px; flex-shrink: 0; overflow: hidden; }
.related-body { padding: 16px 16px 16px 0; }

.blog-content { color: #334155; line-height: 1.75; font-size: 17px; }
.blog-content :deep(p) { margin: 0 0 1.25em; }
.blog-content :deep(h2) { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 1.4em 0 0.6em; }
.blog-content :deep(h3) { font-size: 1.2rem; font-weight: 700; color: #0f172a; margin: 1.2em 0 0.5em; }
.blog-content :deep(ul),
.blog-content :deep(ol) { padding-left: 1.4em; margin: 0 0 1.25em; }
.blog-content :deep(li) { margin-bottom: 0.4em; }
.blog-content :deep(blockquote) {
    border-left: 4px solid #bfdbfe;
    padding: 4px 0 4px 18px;
    margin: 0 0 1.25em;
    color: #64748b;
    font-style: italic;
}
.blog-content :deep(a) { color: #1d4ed8; text-decoration: underline; }
.blog-content :deep(.video-embed) {
    position: relative;
    width: 100%;
    padding-top: 56.25%;
    margin: 0 0 1.5em;
    border-radius: 12px;
    overflow: hidden;
    background: #000;
}
.blog-content :deep(.video-embed) iframe {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: 0;
}
</style>
