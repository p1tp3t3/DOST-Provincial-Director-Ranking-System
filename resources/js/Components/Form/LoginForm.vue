<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import { ref, reactive } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const form = useForm({
    identifier: '',
    password:   '',
    remember:   false,
});

const required = (v) => !!v || 'Password is required';

// Sign in → success toast → after 2 seconds, go to the dashboard (or role picker).
//
// Posted with axios rather than an Inertia visit so the page can pause on the
// toast. The browser still follows the server's redirect; where it ends up
// tells the outcome. A failed sign-in redirects back to /login and stays
// silent — no message — per the security rule in LoginRequest::authenticate().
const REDIRECT_DELAY = 2000;
const success = reactive({ open: false, name: '', target: '/dashboard', choosingRole: false });
const signingIn = ref(false);

// The redirected-to page's Inertia data (who signed in), from its HTML.
const pageDataFrom = (html) => {
    try {
        const el = new DOMParser().parseFromString(html, 'text/html').getElementById('app');
        return JSON.parse(el?.dataset.page ?? 'null');
    } catch {
        return null;
    }
};

const submit = async () => {
    if (signingIn.value) return;
    signingIn.value = true;
    try {
        const res = await axios.post(route('login'), {
            identifier: form.identifier,
            password:   form.password,
            remember:   form.remember,
        }, { headers: { Accept: 'text/html' } });

        const finalUrl = new URL(res.request?.responseURL || window.location.href);
        if (finalUrl.pathname === new URL(route('login')).pathname) {
            // Failed: silent, like a page reload.
            form.reset('password');
            signingIn.value = false;
            return;
        }

        const page = pageDataFrom(res.data);
        const user = page?.props?.auth?.user;
        Object.assign(success, {
            open:         true,
            name:         user?.profile?.first_name || '',
            target:       finalUrl.pathname + finalUrl.search,
            choosingRole: page?.component === 'Auth/ChooseRole',
        });
        setTimeout(() => router.visit(success.target), REDIRECT_DELAY);
    } catch {
        // Network/server error: stay silent as for any failed sign-in.
        form.reset('password');
        signingIn.value = false;
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div>
            <v-text-field
                id="identifier"
                label="Employee No. or Email"
                v-model="form.identifier"
                variant="outlined"
                clearable
                autofocus
                autocomplete="username"
                placeholder="Enter your employee no. or email"
                prepend-inner-icon="mdi-account-search-outline"
            />
        </div>

        <div>
            <v-text-field
                id="password"
                type="password"
                label="Password"
                variant="outlined"
                v-model="form.password"
                :rules="[required]"
                clearable
                autocomplete="current-password"
                placeholder="*********"
            />
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center">
                <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-gray-300 text-indigo-900" />
                <span class="ms-3 text-sm text-gray-600 font-medium">Remember me</span>
            </label>
        </div>

        <div v-if="route('password.request')" class="text-right">
            <Link
                :href="route('password.request')"
                class="text-sm text-indigo-900 font-semibold hover:text-indigo-700 transition"
            >
                Forgot your password?
            </Link>
        </div>

         <v-btn
            :disabled="signingIn"
            :loading="signingIn"
            color="primary"
            size="large"
            type="submit"
            variant="elevated"
            block
        >
            Sign In
        </v-btn>
    </form>

    <!-- Signed in: this toast shows for 2 seconds, then the dashboard opens -->
    <v-snackbar v-model="success.open" color="success" :timeout="-1" location="top right">
        <div class="d-flex align-center gap-2">
            <v-icon size="20">mdi-check-circle-outline</v-icon>
            <div>
                <div class="font-weight-bold">Signed in successfully</div>
                <div class="text-caption">
                    {{ success.name ? `Welcome back, ${success.name}!` : 'Welcome back!' }}
                    {{ success.choosingRole ? 'Taking you to choose your role…' : 'Taking you to your dashboard…' }}
                </div>
            </div>
        </div>
    </v-snackbar>
</template>
