<script setup>
import { Head, Link } from '@inertiajs/vue3';
import LoginForm from '@/Components/Form/LoginForm.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Sign In - PDRIS" />

    <div class="min-h-screen flex bg-gray-50">
        <!-- Left Side - DOST Branding -->
        <div class="hidden md:flex md:w-1/2 bg-indigo flex-col justify-between p-12">
            <!-- Top Logo/Branding -->
            <div class="flex gap-5 item-center">
                <div class="bg-white rounded-lg p-4 w-20 h-20 flex items-center justify-center mb-8 shadow-lg">
                    <svg class="w-12 h-12 text-indigo-900" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 100 4h3V5a2 2 0 00-2-2 1 1 0 00-1 1v2H4zm11 0a2 2 0 012 2v2h1a1 1 0 110 2h-1a2 2 0 100-4h-3V5a2 2 0 00-2-2 1 1 0 000 2h1a1 1 0 100-2h-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-white text-4xl font-bold mb-2">PDRIS</h1>
                    <p class="text-indigo-200 text-sm font-semibold">Provincial Director Ranking</p>
                    <p class="text-indigo-200 text-sm font-semibold">Information System</p>
                </div>
            </div>

            <!-- Center Content -->
            <div class="text-center text-white">
                <div class="mb-8">
                    <svg class="w-32 h-32 mx-auto mb-8 text-indigo-200 opacity-80" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v4h8v-4zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold mb-4">Excellence in Leadership</h2>
                <p class="text-indigo-200 text-lg leading-relaxed">
                    Empowering provincial directors through transparent, evidence-based evaluation and recognition
                </p>
            </div>

            <!-- Bottom Info -->
            <div class="border-t border-indigo-700 pt-8">
                <p class="text-indigo-200 text-sm mb-2">
                    <span class="font-semibold">Department of Science and Technology</span>
                </p>
                <p class="text-indigo-300 text-xs">
                    Advancing provincial development through science and technology leadership
                </p>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full md:w-1/2 flex flex-col justify-center px-8 py-12 md:px-16">
            <div class="max-w-md mx-auto w-full">
                <!-- Mobile Header -->
                <div class="md:hidden mb-8 text-center">
                    <div class="bg-indigo-100 rounded-lg p-3 w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-indigo-900" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 100 4h3V5a2 2 0 00-2-2 1 1 0 00-1 1v2H4zm11 0a2 2 0 012 2v2h1a1 1 0 110 2h-1a2 2 0 100-4h-3V5a2 2 0 00-2-2 1 1 0 000 2h1a1 1 0 100-2h-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-indigo-900">PDRIS</h1>
                    <p class="text-gray-600 text-sm">Provincial Director Ranking Information System</p>
                </div>

                <!-- Login Header -->
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h2>
                    <p class="text-gray-600">Sign in to your account to continue</p>

                    <!-- Status Message -->
                    <div v-if="status" class="mt-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
                        {{ status }}
                    </div>
                </div>

                <!-- Login Form -->
                <LoginForm :can-reset-password="canResetPassword" :status="status" />

                <!-- Register Link -->
                <div class="mt-8 text-center border-t border-gray-200 pt-6">
                    <p class="text-gray-600">Don't have an account? 
                        <Link 
                            :href="route('register')"
                            class="font-semibold text-indigo-900 hover:text-indigo-700 transition"
                        >
                            Create one
                        </Link>
                    </p>
                </div>

                <!-- Footer -->
                <div class="mt-8 text-center text-xs text-gray-500">
                    <p>Secure Login • Provincial Department of Science and Technology</p>
                    <p class="mt-2">© 2026 PDRIS. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
</template>
