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

                <!-- Header -->
                <div class="pa-6 pb-4">
                    <div class="d-flex align-center gap-3">
                        <v-avatar color="primary-lighten-5" rounded="lg" size="44">
                            <v-icon color="primary" size="24">mdi-shield-crown-outline</v-icon>
                        </v-avatar>
                        <div>
                            <div class="text-subtitle-1 font-weight-bold">Super Admin Console</div>
                            <div class="text-caption text-medium-emphasis">Restricted access — authorized personnel only</div>
                        </div>
                    </div>
                </div>

                <v-divider></v-divider>

                <!-- Maintenance banner -->
                <v-alert
                    type="warning"
                    variant="tonal"
                    density="compact"
                    icon="mdi-wrench-clock"
                    rounded="0"
                    class="text-caption"
                >
                    The system is currently in <strong>maintenance mode</strong>.
                    Only super admin accounts are allowed to log in.
                </v-alert>

                <!-- Form -->
                <div class="pa-6">
                    <v-form @submit.prevent="submit">
                        <div class="d-flex flex-column gap-4">

                            <v-text-field
                                v-model="form.identifier"
                                label="Email or Username"
                                variant="outlined"
                                density="comfortable"
                                prepend-inner-icon="mdi-account-search-outline"
                                :error-messages="form.errors.identifier"
                                autocomplete="username"
                                autofocus
                                hide-details="auto"
                            />

                            <v-text-field
                                v-model="form.password"
                                label="Password"
                                :type="showPassword ? 'text' : 'password'"
                                variant="outlined"
                                density="comfortable"
                                prepend-inner-icon="mdi-lock-outline"
                                :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                @click:append-inner="showPassword = !showPassword"
                                :error-messages="form.errors.password"
                                autocomplete="current-password"
                                hide-details="auto"
                            />

                            <v-checkbox
                                v-model="form.remember"
                                label="Keep me logged in"
                                density="compact"
                                hide-details
                                color="primary"
                                class="mt-n1"
                            />

                            <v-btn
                                type="submit"
                                color="primary"
                                variant="flat"
                                size="large"
                                block
                                rounded="lg"
                                :loading="form.processing"
                                :disabled="!form.identifier.trim() || !form.password"
                            >
                                <v-icon start>mdi-login</v-icon>
                                Sign In as Super Admin
                            </v-btn>

                            <div class="text-center">
                                <v-btn
                                    variant="text"
                                    size="small"
                                    color="medium-emphasis"
                                    prepend-icon="mdi-arrow-left"
                                    :href="route('maintenance-notice')"
                                >Back to Notice</v-btn>
                            </div>

                        </div>
                    </v-form>
                </div>

            </v-card>

            <div class="text-center mt-5">
                <div class="text-caption text-white" style="opacity:.5;">
                    This page is not publicly listed. Unauthorized access is prohibited.
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const showPassword = ref(false);

const form = useForm({
    identifier: '',
    password:   '',
    remember:   false,
});

const submit = () => {
    form.post(route('console.authenticate'), {
        onFinish: () => form.reset('password'),
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
