<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';
import { RiTargetLine } from '@remixicon/vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

</script>

<template>
    <Head title="Welcome - PDRIS" />

    <GuestLayout>
        <div class="w-full">
            <!-- Hero Section -->
            <div class="grid md:grid-cols-2 gap-12 mb-16 items-center">
                <div>
                    <div class="inline-block bg-indigo-100 text-indigo-900 px-4 py-1 rounded-full text-sm font-semibold mb-4">
                        Provincial Initiative
                    </div>
                    <h1 class="text-5xl font-bold mb-6 text-gray-900 leading-tight">
                        Provincial Director Ranking Information System
                    </h1>
                    <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                        An official repository of the Provincial Department of Science and Technology showcasing the achievements, rankings, and contributions of directors from our province.
                    </p>
                    <div class="flex gap-4">
                        <a 
                            v-if="canRegister"
                            href="/register" 
                            class="px-8 py-3 bg-indigo-900 text-white rounded-lg font-semibold hover:bg-indigo-800 transition shadow-md"
                        >
                            Register
                        </a>
                        <a 
                            v-if="canLogin"
                            href="/login" 
                            class="px-8 py-3 border-2 border-indigo-900 text-indigo-900 rounded-lg font-semibold hover:bg-indigo-50 transition"
                        >
                            Sign In
                        </a>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg h-96 flex items-center justify-center shadow-lg">
                    <div class="text-center text-white">
                        <svg class="w-32 h-32 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v4h8v-4zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                        </svg>
                        <p class="text-2xl font-bold">Provincial Directors Network</p>
                    </div>
                </div>
            </div>

            <!-- Mission Statement -->
            <div class="bg-indigo-50 border-l-4 border-indigo-900 p-8 rounded-lg mb-16">
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 text-indigo-900 flex-shrink-0 mt-1">
                        <RiTargetLine />
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-indigo-900 mb-2">Our Mission</h3>
                        <p class="text-gray-700 text-lg">
                            To provide comprehensive, transparent, and science-based rankings of provincial directors, promoting excellence in leadership and driving innovation within the Department of Science and Technology.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Key Sections -->
            <div class="grid md:grid-cols-3 gap-8 mb-16">
                <div class="bg-white p-8 rounded-lg border border-gray-200 hover:shadow-lg transition">
                    <div class="bg-indigo-100 rounded-full w-14 h-14 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-indigo-900" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.5 1.5H5.75A2.25 2.25 0 003.5 3.75v12.5A2.25 2.25 0 005.75 18.5h8.5a2.25 2.25 0 002.25-2.25V6.5m-11-5v3.75A2.25 2.25 0 007 7h6m0-5.5v3.75a.75.75 0 001.5 0V1.5m-4.5 4.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Comprehensive Database</h3>
                    <p class="text-gray-600">
                        Access detailed profiles, certifications, and career histories of directors recognized by the Provincial Department of Science and Technology.
                    </p>
                </div>

                <div class="bg-white p-8 rounded-lg border border-gray-200 hover:shadow-lg transition">
                    <div class="bg-indigo-100 rounded-full w-14 h-14 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-indigo-900" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Official Rankings</h3>
                    <p class="text-gray-600">
                        View scientifically-based rankings and performance metrics developed through transparent, evidence-based evaluation processes.
                    </p>
                </div>

                <div class="bg-white p-8 rounded-lg border border-gray-200 hover:shadow-lg transition">
                    <div class="bg-indigo-100 rounded-full w-14 h-14 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-indigo-900" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 100 4h3V5a2 2 0 00-2-2 1 1 0 00-1 1v2H4zm11 0a2 2 0 012 2v2h1a1 1 0 110 2h-1a2 2 0 100-4h-3V5a2 2 0 00-2-2 1 1 0 000 2h1a1 1 0 100-2h-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Data Insights</h3>
                    <p class="text-gray-600">
                        Analyze trends, performance patterns, and success factors that contribute to directors' achievements within the province.
                    </p>
                </div>
            </div>

            <!-- How to Use -->
            <div class="mb-16 bg-gradient-to-r from-indigo-50 to-purple-50 py-12 px-8 rounded-lg border border-indigo-200">
                <h2 class="text-4xl font-bold mb-12 text-gray-900">How to Use PDRIS</h2>
                <div class="grid md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="bg-indigo-900 text-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4 text-lg font-bold">1</div>
                        <h3 class="font-semibold text-gray-900 mb-2">Create Account</h3>
                        <p class="text-gray-600 text-sm">Register to access full database and personalized features</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-indigo-900 text-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4 text-lg font-bold">2</div>
                        <h3 class="font-semibold text-gray-900 mb-2">Browse Profiles</h3>
                        <p class="text-gray-600 text-sm">Explore detailed director profiles and achievements</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-indigo-900 text-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4 text-lg font-bold">3</div>
                        <h3 class="font-semibold text-gray-900 mb-2">Review Rankings</h3>
                        <p class="text-gray-600 text-sm">Check official rankings and comparative analysis</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-indigo-900 text-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4 text-lg font-bold">4</div>
                        <h3 class="font-semibold text-gray-900 mb-2">Track Updates</h3>
                        <p class="text-gray-600 text-sm">Stay informed about new rankings and director promotions</p>
                    </div>
                </div>
            </div>

            <!-- Benefits -->
            <div class="mb-16">
                <h2 class="text-4xl font-bold mb-12 text-gray-900">System Benefits</h2>
                <div class="grid md:grid-cols-2 gap-8">
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="bg-green-100 rounded-full w-12 h-12 flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Transparency</h3>
                            <p class="text-gray-600">Open access to ranking methodologies and evaluation criteria ensures accountability and trust</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="bg-green-100 rounded-full w-12 h-12 flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Evidence-Based</h3>
                            <p class="text-gray-600">Rankings based on scientific methodology and quantifiable performance indicators</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="bg-green-100 rounded-full w-12 h-12 flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Real-Time Updates</h3>
                            <p class="text-gray-600">Current information reflecting the latest achievements and career developments</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="bg-green-100 rounded-full w-12 h-12 flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Professional Network</h3>
                            <p class="text-gray-600">Connect with provincial directors and access collaborative opportunities</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="bg-gradient-to-r from-indigo-900 to-indigo-800 text-white py-16 px-8 rounded-lg text-center mb-12">
                <h2 class="text-4xl font-bold mb-4">Get Started Today</h2>
                <p class="text-xl mb-8 text-indigo-100">
                    Join our provincial directors' community and access comprehensive ranking and profiling information
                </p>
                <div class="flex justify-center gap-4 flex-wrap">
                    <a 
                        v-if="canRegister"
                        href="/register" 
                        class="px-8 py-3 bg-white text-indigo-900 rounded-lg font-semibold hover:bg-gray-100 transition"
                    >
                        Create Account
                    </a>
                    <a 
                        v-if="canLogin"
                        href="/login" 
                        class="px-8 py-3 border-2 border-white text-white rounded-lg font-semibold hover:bg-indigo-700 transition"
                    >
                        Sign In
                    </a>
                </div>
            </div>

            <!-- Department Information -->
            <div class="bg-gray-100 py-12 px-8 rounded-lg border border-gray-300">
                <div class="grid md:grid-cols-2 gap-12">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">About PDRIS</h3>
                        <p class="text-gray-700 mb-4">
                            The Provincial Director Ranking Information System is an initiative of the Provincial Department of Science and Technology, designed to promote excellence, transparency, and evidence-based evaluation in director leadership.
                        </p>
                        <p class="text-gray-700">
                            Our platform supports the department's mission to foster innovation and scientific excellence at the provincial level.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Contact & Support</h3>
                        <ul class="text-gray-700 space-y-3">
                            <li class="flex gap-3">
                                <svg class="w-6 h-6 text-indigo-900 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                </svg>
                                <span>support@pdris.gov</span>
                            </li>
                            <li class="flex gap-3">
                                <svg class="w-6 h-6 text-indigo-900 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773c.346.854 1.261 2.081 2.515 3.334.254.254.587.601.906.915l1.536-1.536a1 1 0 011.06-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2.57a7 7 0 01-6.992-6.992V5a1 1 0 01-1-1H3z"></path>
                                </svg>
                                <span>+1 (555) 123-4567</span>
                            </li>
                            <li class="flex gap-3">
                                <svg class="w-6 h-6 text-indigo-900 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Provincial Science & Technology Center</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>