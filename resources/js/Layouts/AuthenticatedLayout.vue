<script setup>
import Sidebar from '@/Components/Other/Sidebar.vue';
import AuthHeader from '@/Components/Other/AuthHeader.vue';

</script>

<template>
    <div>
        <div class="min-h-screen flex">
            <Sidebar role="super_admin" />
            <!-- Page Content -->
            <main class="w-full">
                <div class="sticky top-0 z-10">
                    <AuthHeader />
                </div>
                <div class="px-8 py-5">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
