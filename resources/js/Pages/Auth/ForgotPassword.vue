<template>
    <div class="auth-root">
        <div class="auth-bg"></div>
        <div class="particle p1"></div>
        <div class="particle p2"></div>
        <div class="particle p3"></div>

        <div class="auth-center">

            <!-- Brand -->
            <div class="text-center mb-7">
                <div class="d-flex align-center justify-center gap-3 mb-1">
                    <img src="/assets/logo.png" alt="DOST" height="48"
                        style="filter:drop-shadow(0 2px 6px rgba(0,0,0,.25));" />
                    <div class="text-left">
                        <div class="text-h6 font-weight-bold text-white" style="line-height:1.2;">PDRIS</div>
                        <div class="text-caption text-white" style="opacity:.75;letter-spacing:.04em;">DOST Philippines</div>
                    </div>
                </div>
            </div>

            <!-- Card -->
            <v-card rounded="xl" elevation="0" class="auth-card">

                <!-- ── Sent state ── -->
                <div v-if="sent" class="pa-8 text-center d-flex flex-column align-center gap-4">
                    <v-avatar color="success-lighten-5" size="72">
                        <v-icon color="success" size="38">mdi-email-check-outline</v-icon>
                    </v-avatar>

                    <div>
                        <div class="text-h6 font-weight-bold mb-1">Check your inbox</div>
                        <div class="text-body-2 text-medium-emphasis">
                            If an account matches your input, a password reset link has been sent to the registered email address.
                        </div>
                    </div>

                    <v-alert type="info" variant="tonal" density="compact" icon="mdi-clock-outline" class="text-caption text-left w-100">
                        The link expires in <strong>1 hour</strong>. Check your spam folder if you don't see it.
                    </v-alert>

                    <v-btn
                        variant="tonal"
                        color="primary"
                        size="small"
                        prepend-icon="mdi-refresh"
                        @click="trySent = false"
                    >Try another account</v-btn>

                    <v-btn
                        variant="text"
                        color="medium-emphasis"
                        size="small"
                        prepend-icon="mdi-arrow-left"
                        :href="route('login')"
                    >Back to Login</v-btn>
                </div>

                <!-- ── Form state ── -->
                <template v-else>
                    <div class="pa-6 pb-4">
                        <div class="d-flex align-center gap-3 mb-1">
                            <v-avatar color="primary-lighten-5" rounded="lg" size="40">
                                <v-icon color="primary" size="22">mdi-lock-question</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-subtitle-1 font-weight-bold">Forgot Password?</div>
                                <div class="text-caption text-medium-emphasis">We'll send a reset link to your email</div>
                            </div>
                        </div>
                    </div>

                    <v-divider></v-divider>

                    <div class="pa-6">
                        <v-form @submit.prevent="submit">
                            <div class="d-flex flex-column gap-4">

                                <v-text-field
                                    v-model="form.identifier"
                                    label="Email, Username, or DOST Employee ID"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-account-search-outline"
                                    :error-messages="form.errors.identifier"
                                    placeholder="Enter any one"
                                    autofocus
                                    autocomplete="off"
                                    hide-details="auto"
                                />

                                <!-- Accepted inputs hint -->
                                <div class="d-flex gap-2 flex-wrap">
                                    <v-chip size="x-small" variant="tonal" color="primary" prepend-icon="mdi-email-outline">Email</v-chip>
                                    <v-chip size="x-small" variant="tonal" color="primary" prepend-icon="mdi-account-outline">Username</v-chip>
                                    <v-chip size="x-small" variant="tonal" color="primary" prepend-icon="mdi-card-account-details-outline">DOST ID</v-chip>
                                </div>

                                <v-btn
                                    type="submit"
                                    color="primary"
                                    variant="flat"
                                    size="large"
                                    block
                                    rounded="lg"
                                    :loading="form.processing"
                                    :disabled="!form.identifier.trim()"
                                >
                                    <v-icon start>mdi-send-outline</v-icon>
                                    Send Reset Link
                                </v-btn>

                                <div class="text-center">
                                    <v-btn
                                        variant="text"
                                        size="small"
                                        color="primary"
                                        prepend-icon="mdi-arrow-left"
                                        :href="route('login')"
                                    >Back to Login</v-btn>
                                </div>

                            </div>
                        </v-form>
                    </div>
                </template>

            </v-card>

        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: { type: String, default: null },
});

const trySent = ref(false);
const sent    = computed(() => !!props.status || trySent.value);

const form = useForm({ identifier: '' });

const submit = () => {
    form.post(route('password.email'), {
        onSuccess: () => { trySent.value = true; },
    });
};
</script>

<style scoped>
.auth-root {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    padding: 24px 16px;
}

.auth-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(145deg, #001f5b 0%, #0047ab 55%, #1565c0 100%);
    z-index: 0;
}

.particle {
    position: absolute;
    border-radius: 50%;
    opacity: 0.07;
    background: #fff;
    z-index: 0;
}
.p1 { width: 320px; height: 320px; top: -80px;  right: -80px; }
.p2 { width: 200px; height: 200px; bottom: -60px; left: -60px; }
.p3 { width: 140px; height: 140px; top: 50%; left: 10%; }

.auth-center {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 440px;
}

.auth-card {
    background: #fff;
    border: 1px solid rgba(0,0,0,.06);
    overflow: hidden;
}
</style>
