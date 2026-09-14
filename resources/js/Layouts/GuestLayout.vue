<script setup>
import GuestHeader from '@/Components/Other/GuestHeader.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex flex-col min-h-screen bg-gray-50">
        <GuestHeader />
        <main class="flex-grow">
            <div class="max-w-7xl mx-auto px-4 md:px-6 py-12">
                <slot />
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="bg-indigo-900 text-white mt-20">
            <div class="max-w-7xl mx-auto px-4 md:px-6 py-12">
                <div class="grid md:grid-cols-4 gap-8 mb-8">
                    <div>
                        <h4 class="font-bold mb-4">PRISM</h4>
                        <p class="text-sm text-indigo-200">PSTO Ranking and Information System for Management - Department of Science and Technology</p>
                    </div>
                    <div>
                        <h4 class="font-bold mb-4">Quick Links</h4>
                        <ul class="text-sm space-y-2 text-indigo-200">
                            <li><Link href="/" class="hover:text-white transition">Home</Link></li>
                            <li><Link href="#" class="hover:text-white transition">Directors</Link></li>
                            <li><Link href="#" class="hover:text-white transition">Rankings</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold mb-4">Support</h4>
                        <ul class="text-sm space-y-2 text-indigo-200">
                            <li><Link href="#" class="hover:text-white transition">Contact Us</Link></li>
                            <li><Link href="#" class="hover:text-white transition">FAQs</Link></li>
                            <li><Link href="#" class="hover:text-white transition">Help</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold mb-4">Official</h4>
                        <ul class="text-sm space-y-2 text-indigo-200">
                            <li><Link href="#" class="hover:text-white transition">Privacy Policy</Link></li>
                            <li><Link href="#" class="hover:text-white transition">Terms of Use</Link></li>
                            <li><Link href="#" class="hover:text-white transition">Accessibility</Link></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-indigo-800 pt-8 text-center text-sm text-indigo-200">
                    <p>&copy; 2026 Provincial Department of Science and Technology. All rights reserved.</p>
                    <p class="mt-2 text-xs">\"Excellence in Science and Technology Leadership\"</p>
                </div>
            </div>
        </footer>
    </div>
</template>
