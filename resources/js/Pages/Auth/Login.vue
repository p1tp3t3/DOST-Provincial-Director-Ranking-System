<script setup>
import { Head } from '@inertiajs/vue3';
import LoginForm from '@/Components/Form/LoginForm.vue';

defineProps({
    canResetPassword: { type: Boolean },
    status:           { type: String  },
});

const stats = [
    { value: '81',  label: 'Provinces'  },
    { value: '100+', label: 'Directors' },
    { value: '500+', label: 'Employees' },
];

const features = [
    { icon: 'mdi-chart-bar',           label: 'KPI Tracking'         },
    { icon: 'mdi-podium',              label: 'Province Ranking'      },
    { icon: 'mdi-file-chart-outline',  label: 'Report Generation'     },
    { icon: 'mdi-account-group',       label: 'Staff Management'      },
];
</script>

<template>
    <Head title="Sign In - PRISM" />

    <div class="login-root">

        <!-- ── Left panel ───────────────────────────────────────── -->
        <div class="left-panel d-none d-md-flex flex-column">

            <!-- Decorative blobs -->
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>

            <!-- Top: Logo bar -->
            <div class="d-flex align-center gap-3 left-section" style="position:relative;">
                <div class="logo-box">
                    <img src="/assets/logo.png" alt="DOST" class="logo-img" />
                </div>
                <div>
                    <div class="text-white font-weight-black" style="font-size:1.05rem; line-height:1.2; letter-spacing:0.02em;">Department of Science and Technology</div>
                    <div class="text-white" style="font-size:0.65rem; opacity:0.65; line-height:1.5; text-transform:uppercase; letter-spacing:0.06em;">
                        OneDOST4U - Solutions and Opportunities for All
                    </div>
                </div>
            </div>

            <!-- Center: Main content -->
            <div class="left-section flex-grow-1 d-flex flex-column justify-center" style="position:relative;">

                <!-- PRISM system brand -->
                <div class="prism-badge mb-4">
                    <img src="/assets/logo.png" alt="PRISM" class="prism-badge-logo" />
                    <span class="prism-badge-text">PRISM</span>
                </div>

                <!-- PRISM full name -->
                <div class="prism-fullname mb-7">
                    Provincial Director Ranking and Information System for Management
                </div>

                <!-- Headline -->
                <div class="text-white font-weight-black mb-4" style="font-size:2.6rem; line-height:1.1; letter-spacing:-0.02em;">
                    Excellence<br>in Leadership.
                </div>
                <div class="text-white mb-10" style="opacity:0.65; font-size:0.9rem; line-height:1.8; max-width:340px;">
                    Empowering provincial directors through transparent, evidence-based performance evaluation and recognition.
                </div>

                <!-- Features - 2 column grid -->
                <div class="feat-grid">
                    <div
                        v-for="feat in features"
                        :key="feat.label"
                        class="feat-card"
                    >
                        <div class="feat-icon-wrap mb-2">
                            <v-icon size="18" color="white">{{ feat.icon }}</v-icon>
                        </div>
                        <div class="text-white font-weight-medium" style="font-size:0.8rem;">{{ feat.label }}</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Right panel ──────────────────────────────────────── -->
        <div class="right-panel d-flex flex-column">

            <!-- Top bar -->
            <!-- Centered form area -->
            <div class="right-body d-flex align-center justify-center flex-grow-1">
                <div class="form-box">

                    <!-- Mobile logo -->
                    <div class="d-flex d-md-none align-center justify-center gap-3 mb-8">
                        <img src="/assets/logo.png" alt="DOST" style="width:44px;height:44px;object-fit:contain;" />
                        <div>
                            <div class="text-h6 font-weight-black" style="color:#0047ab;">PRISM</div>
                            <div class="text-caption text-medium-emphasis">Provincial Director Ranking IS</div>
                        </div>
                    </div>

                    <!-- Heading -->
                    <div class="mb-8">
                        <div class="form-title mb-1">Sign in to your account</div>
                        <div class="text-body-2 text-medium-emphasis">Enter your credentials to access the dashboard</div>
                    </div>

                    <!-- Status alert -->
                    <v-alert
                        v-if="status"
                        type="success"
                        variant="tonal"
                        density="compact"
                        rounded="lg"
                        class="mb-6"
                    >{{ status }}</v-alert>

                    <!-- Form -->
                    <div class="form-card pa-7 mb-7">
                        <LoginForm :can-reset-password="canResetPassword" :status="status" />
                    </div>

                    <!-- Footer text -->
                    <div class="text-center">
                        <div class="d-flex align-center justify-center gap-1 mb-1">
                            <v-icon size="12" color="grey">mdi-shield-check-outline</v-icon>
                            <span class="text-caption text-medium-emphasis">Protected by enterprise-grade security</span>
                        </div>
                        <div class="text-caption text-medium-emphasis">
                            © {{ new Date().getFullYear() }} PRISM · Department of Science and Technology
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
/* ── Root ───────────────────────────────────── */
.login-root {
    min-height: 100vh;
    display: flex;
}

/* ── Left panel ─────────────────────────────── */
.left-panel {
    width: 50%;
    background: linear-gradient(160deg, #002d72 0%, #0047ab 50%, #1565c0 100%);
    position: relative;
    overflow: hidden;
}

.left-section {
    padding: 20px 52px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
}
.left-section:last-child {
    border-bottom: none;
}

/* Decorative blobs */
.blob {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}
.blob-1 {
    width: 400px; height: 400px;
    background: rgba(255,255,255,0.03);
    top: -120px; right: -120px;
}
.blob-2 {
    width: 250px; height: 250px;
    background: rgba(255,255,255,0.025);
    bottom: 60px; left: -80px;
}
.blob-3 {
    width: 180px; height: 180px;
    background: rgba(100,181,246,0.15);
    top: 45%; right: -40px;
}

/* Logo */
.logo-box {
    width: 52px; height: 52px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.logo-img {
    width: 36px; height: 36px;
    object-fit: contain;
}

/* DOST badge pill */
.dost-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.14);
    border-radius: 20px;
    padding: 5px 12px;
}
.dost-badge-logo {
    width: 18px;
    height: 18px;
    object-fit: contain;
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.25));
}

/* PRISM brand lockup (center) */
.prism-badge {
    display: inline-flex;
    align-items: center;
    gap: 11px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 999px;
    padding: 9px 22px 9px 12px;
}
.prism-badge-logo {
    width: 30px;
    height: 30px;
    object-fit: contain;
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.25));
}
.prism-badge-text {
    color: #fff;
    font-size: 1.3rem;
    font-weight: 900;
    letter-spacing: 0.22em;
    line-height: 1;
}
.prism-fullname {
    color: #fff;
    font-size: 1.2rem;
    font-weight: 600;
    line-height: 1.45;
    letter-spacing: 0.005em;
    max-width: 440px;
    opacity: 0.92;
}

/* Feature grid */
.feat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.feat-card {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 16px;
    transition: background 0.2s;
}
.feat-card:hover {
    background: rgba(255,255,255,0.1);
}
.feat-icon-wrap {
    width: 34px; height: 34px;
    background: rgba(255,255,255,0.12);
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
}

/* Stats */
.stat-divider {
    height: 1px;
    background: rgba(255,255,255,0.12);
}
.stat-row {
    display: flex;
    gap: 40px;
}
.stat-item { text-align: left; }

/* ── Right panel ────────────────────────────── */
.right-panel {
    width: 50%;
    flex: none;
    background: #f8fafc;
}

.right-topbar {
    padding: 14px 40px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    background: #fff;
}

.right-body {
    padding: 40px 24px;
}

.form-box {
    width: 100%;
    max-width: 460px;
}

.greeting-tag {
    display: inline-flex;
    align-items: center;
    background: rgba(0, 71, 171, 0.07);
    border: 1px solid rgba(0, 71, 171, 0.15);
    border-radius: 20px;
    padding: 5px 12px;
}

.form-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: #0d1b3e;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.form-card {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.07);
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.05);
}

/* ── Responsive ─────────────────────────────── */

/* Tablet landscape: shrink left section padding */
@media (max-width: 1280px) {
    .left-section { padding: 32px 36px; }
    .feat-grid { gap: 10px; }
    .feat-card { padding: 12px; }
    .form-box { max-width: 400px; }
}

/* Tablet portrait: collapse to single column */
@media (max-width: 959px) {
    .login-root {
        flex-direction: column;
    }

    /* Left panel becomes a compact branded header */
    .left-panel {
        width: 100%;
        flex-direction: row !important;
        align-items: center;
        justify-content: space-between;
        padding: 0;
        min-height: unset;
    }
    .left-section {
        padding: 20px 24px;
        border-bottom: none;
        border-right: 1px solid rgba(255,255,255,0.1);
        flex-grow: 0;
    }
    .left-section:last-child {
        border-right: none;
    }
    /* Hide center & bottom sections on tablet - show only logo bar */
    .left-section.flex-grow-1,
    .left-section:not(:first-child) {
        display: none !important;
    }
    .left-panel .d-flex.align-center.gap-3.left-section {
        display: flex !important;
        width: 100%;
        border-right: none;
        padding: 18px 24px;
    }
    .blob { display: none; }

    /* Right panel fills the rest */
    .right-panel {
        width: 100%;
        flex: 1;
        min-height: calc(100vh - 74px);
    }
    .right-body { padding: 32px 20px; }
}

/* Mobile: tighten form card padding */
@media (max-width: 599px) {
    .right-body { padding: 24px 16px; }
    .form-card  { padding: 20px !important; }
    .form-title { font-size: 1.4rem; }
    .greeting-tag { margin-bottom: 14px; }
}
</style>
