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
                    <img src="/images/dost-logo.png" alt="DOST" height="48"
                        style="filter:drop-shadow(0 2px 6px rgba(0,0,0,.25));" />
                    <div class="text-left">
                        <div class="text-h6 font-weight-bold text-white" style="line-height:1.2;">PDRIS</div>
                        <div class="text-caption text-white" style="opacity:.75;letter-spacing:.04em;">DOST Philippines</div>
                    </div>
                </div>
            </div>

            <!-- Card -->
            <v-card rounded="xl" elevation="0" class="auth-card">

                <!-- ── Invalid / expired token ── -->
                <div v-if="!valid" class="pa-8 text-center d-flex flex-column align-center gap-4">
                    <v-avatar color="error-lighten-5" size="72">
                        <v-icon color="error" size="38">mdi-link-variant-off</v-icon>
                    </v-avatar>

                    <div>
                        <div class="text-h6 font-weight-bold mb-1">Link Expired or Invalid</div>
                        <div class="text-body-2 text-medium-emphasis">
                            This password reset link has expired or has already been used. Please request a new one.
                        </div>
                    </div>

                    <v-btn
                        color="primary"
                        variant="flat"
                        rounded="lg"
                        prepend-icon="mdi-lock-question"
                        :href="route('password.request')"
                    >Request New Link</v-btn>

                    <v-btn variant="text" color="medium-emphasis" size="small" prepend-icon="mdi-arrow-left" :href="route('login')">
                        Back to Login
                    </v-btn>
                </div>

                <!-- ── Reset form ── -->
                <template v-else-if="!done">
                    <div class="pa-6 pb-4">
                        <div class="d-flex align-center gap-3 mb-1">
                            <v-avatar color="success-lighten-5" rounded="lg" size="40">
                                <v-icon color="success" size="22">mdi-lock-reset</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-subtitle-1 font-weight-bold">Set New Password</div>
                                <div v-if="email" class="text-caption text-medium-emphasis">
                                    Resetting for <strong>{{ email }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <v-divider></v-divider>

                    <div class="pa-6">
                        <v-form @submit.prevent="submit">
                            <div class="d-flex flex-column gap-4">

                                <v-alert
                                    v-if="form.errors.token"
                                    type="error"
                                    variant="tonal"
                                    density="compact"
                                    icon="mdi-alert-circle-outline"
                                    class="text-caption"
                                >{{ form.errors.token }}</v-alert>

                                <v-text-field
                                    v-model="form.password"
                                    label="New Password"
                                    :type="showPassword ? 'text' : 'password'"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-lock-outline"
                                    :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                    @click:append-inner="showPassword = !showPassword"
                                    :error-messages="form.errors.password"
                                    autocomplete="new-password"
                                    autofocus
                                    hide-details="auto"
                                />

                                <v-text-field
                                    v-model="form.password_confirmation"
                                    label="Confirm New Password"
                                    :type="showConfirm ? 'text' : 'password'"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-lock-check-outline"
                                    :append-inner-icon="showConfirm ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                    @click:append-inner="showConfirm = !showConfirm"
                                    :error-messages="form.errors.password_confirmation"
                                    autocomplete="new-password"
                                    hide-details="auto"
                                />

                                <!-- Strength hints -->
                                <div class="d-flex flex-column gap-1 px-1">
                                    <div v-for="hint in hints" :key="hint.label" class="d-flex align-center gap-2">
                                        <v-icon :color="hint.ok ? 'success' : 'grey-lighten-1'" size="14">
                                            {{ hint.ok ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                        </v-icon>
                                        <span class="text-caption" :style="{ color: hint.ok ? 'rgb(var(--v-theme-success))' : '#9e9e9e' }">
                                            {{ hint.label }}
                                        </span>
                                    </div>
                                </div>

                                <v-btn
                                    type="submit"
                                    color="success"
                                    variant="flat"
                                    size="large"
                                    block
                                    rounded="lg"
                                    :loading="form.processing"
                                    :disabled="!canSubmit"
                                >
                                    <v-icon start>mdi-lock-reset</v-icon>
                                    Reset Password
                                </v-btn>

                                <div class="text-center">
                                    <v-btn variant="text" size="small" color="medium-emphasis" prepend-icon="mdi-arrow-left" :href="route('login')">
                                        Back to Login
                                    </v-btn>
                                </div>

                            </div>
                        </v-form>
                    </div>
                </template>

                <!-- ── Success state ── -->
                <div v-else class="pa-8 text-center d-flex flex-column align-center gap-4">
                    <v-avatar color="success-lighten-5" size="72">
                        <v-icon color="success" size="38">mdi-shield-check-outline</v-icon>
                    </v-avatar>

                    <div>
                        <div class="text-h6 font-weight-bold mb-1">Password Reset!</div>
                        <div class="text-body-2 text-medium-emphasis">
                            Your password has been updated successfully. You can now log in with your new password.
                        </div>
                    </div>

                    <v-btn color="primary" variant="flat" rounded="lg" prepend-icon="mdi-login" :href="route('login')">
                        Go to Login
                    </v-btn>
                </div>

            </v-card>

        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    valid: { type: Boolean, default: false },
    token: { type: String, required: true },
    email: { type: String, default: '' },
});

const done         = ref(false);
const showPassword = ref(false);
const showConfirm  = ref(false);

const form = useForm({
    password:              '',
    password_confirmation: '',
});

const hints = computed(() => [
    { label: 'At least 8 characters',  ok: form.password.length >= 8 },
    { label: 'Contains a number',       ok: /\d/.test(form.password) },
    { label: 'Passwords match',         ok: form.password.length > 0 && form.password === form.password_confirmation },
]);

const canSubmit = computed(() => hints.value.every(h => h.ok));

const submit = () => {
    form.post(route('password.recovery.reset', { token: props.token }), {
        onSuccess: () => { done.value = true; },
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
