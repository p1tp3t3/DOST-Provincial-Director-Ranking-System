<script setup>
import { onMounted, onUnmounted } from 'vue';
import Sidebar from '@/Components/Other/Sidebar.vue';
import AuthHeader from '@/Components/Other/AuthHeader.vue';
import ActionModals from '@/Components/Modals/ActionModals.vue';
import { registerActionModals } from '@/Composables/useActionModals';
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
            <!-- Page Content: takes the width left beside the sidebar (w-full would
                 add the sidebar's width on top and push the page past the window),
                 and may shrink so wide tables scroll inside their own box. -->
            <main class="flex-1 min-w-0">
                <div class="sticky top-0 z-10">
                    <AuthHeader />
                </div>
                <div class="px-8 py-5">
                    <slot />
                </div>
            </main>
        </div>

        <!-- App-wide confirmation + success/error dialogs (useActionModals) -->
        <ActionModals :ref="registerActionModals" />
    </div>
</template>
