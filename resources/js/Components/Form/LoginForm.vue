<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

const form = useForm({
    identifier: '',
    password:   '',
    remember:   false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div>
            <v-text-field
                id="identifier"
                label="Email, Username, or Employee ID"
                v-model="form.identifier"
                variant="outlined"
                clearable
                autofocus
                autocomplete="username"
                placeholder="Enter your email, username, or DOST ID"
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
            :disabled="form.processing"
            :loading="form.processing"
            color="primary"
            size="large"
            type="submit"
            variant="elevated"
            block
        >
            Sign In
        </v-btn>
    </form>
</template>