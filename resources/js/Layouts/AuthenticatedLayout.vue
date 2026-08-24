<script setup>
import { onMounted, onUnmounted } from 'vue';
import Sidebar from '@/Components/Other/Sidebar.vue';
import AuthHeader from '@/Components/Other/AuthHeader.vue';
import { receiveBroadcast, getAuth } from '@/helper-functions.js';

const user = getAuth();

onMounted(() => {
    if (user) {
        receiveBroadcast(`App.Models.User.${user.id}`, 'private', '.test.broadcast', (event) => {
            console.log('[broadcast] test.broadcast received:', event);
        });
    }
});

onUnmounted(() => {
    if (user) {
        window.Echo.leave(`App.Models.User.${user.id}`);
    }
});
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
