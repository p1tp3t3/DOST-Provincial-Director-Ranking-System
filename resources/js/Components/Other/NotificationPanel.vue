<template>
    <v-menu
        v-model="open"
        :close-on-content-click="false"
        location="bottom end"
        offset="8"
        width="380"
    >
        <template #activator="{ props: menu }">
            <v-btn icon v-bind="menu" class="mr-1">
                <v-badge
                    v-if="unreadCount > 0"
                    color="error"
                    :content="unreadCount > 99 ? '99+' : unreadCount"
                >
                    <v-icon>mdi-bell-outline</v-icon>
                </v-badge>
                <v-icon v-else>mdi-bell-outline</v-icon>
            </v-btn>
        </template>

        <v-card rounded="lg" elevation="8" class="notif-panel">

            <!-- Header -->
            <div class="d-flex align-center justify-space-between px-4 pt-4 pb-2">
                <div class="text-subtitle-1 font-weight-bold">Notifications</div>
                <v-btn
                    v-if="unreadCount > 0"
                    variant="text"
                    size="x-small"
                    color="primary"
                    @click="markAllRead"
                >Mark all as read</v-btn>
            </div>

            <!-- Tabs -->
            <v-tabs v-model="tab" density="compact" color="primary" class="px-2">
                <v-tab value="all" class="text-caption">All</v-tab>
                <v-tab value="unread" class="text-caption">
                    Unread
                    <v-chip v-if="unreadCount > 0" size="x-small" color="error" variant="flat" class="ml-1">
                        {{ unreadCount }}
                    </v-chip>
                </v-tab>
            </v-tabs>
            <v-divider></v-divider>

            <!-- List -->
            <div class="notif-scroll">
                <template v-if="displayed.length > 0">
                    <div
                        v-for="notif in displayed"
                        :key="notif.id"
                        class="notif-item d-flex align-start gap-3 px-4 py-3"
                        :class="{ 'notif-item--unread': !notif.read }"
                        @click="handleClick(notif)"
                    >
                        <!-- Icon avatar -->
                        <div class="flex-shrink-0 notif-avatar-wrap">
                            <v-avatar :color="typeColor(notif.type) + '-lighten-5'" size="42" rounded="lg">
                                <v-icon :color="typeColor(notif.type)" size="20">{{ typeIcon(notif.type) }}</v-icon>
                            </v-avatar>
                            <div
                                v-if="!notif.read"
                                class="notif-dot"
                                :style="{ background: 'rgb(var(--v-theme-primary))' }"
                            ></div>
                        </div>

                        <!-- Content -->
                        <div class="flex-grow-1 min-w-0">
                            <div class="text-body-2 notif-message">{{ notif.message }}</div>
                            <div class="text-caption mt-1" style="font-size: 0.775rem;" :class="notif.read ? 'text-medium-emphasis' : 'text-primary font-weight-medium'">
                                {{ notif.time }}
                            </div>
                        </div>

                        <!-- Unread indicator dot -->
                        <div v-if="!notif.read" class="flex-shrink-0 mt-1">
                            <div class="unread-circle"></div>
                        </div>
                    </div>
                </template>

                <!-- Empty state -->
                <div v-else class="text-center py-10 px-4">
                    <v-icon size="40" color="grey-lighten-2" class="mb-3">mdi-bell-off-outline</v-icon>
                    <div class="text-body-2 text-medium-emphasis">
                        {{ tab === 'unread' ? 'No unread notifications' : 'No notifications yet' }}
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <v-divider></v-divider>
            <div class="pa-3 text-center">
                <v-btn variant="text" size="small" color="primary" block @click="open = false">
                    See all notifications
                </v-btn>
            </div>

        </v-card>
    </v-menu>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const open = ref(false);
const tab  = ref('all');

// Shared with every page (HandleInertiaRequests): { unread, items: [{ id,
// type, message, url, read, time }] }, so it refreshes on each visit.
const page = usePage();
const notifications = computed(() => page.props.notifications?.items ?? []);
const unreadCount   = computed(() => page.props.notifications?.unread ?? 0);

const displayed = computed(() =>
    tab.value === 'unread'
        ? notifications.value.filter(n => !n.read)
        : notifications.value
);

const markAllRead = () => {
    router.post('/notifications/read-all', {}, { preserveScroll: true, preserveState: true });
};

// Mark it read, then go where it points.
const handleClick = (notif) => {
    const go = () => {
        open.value = false;
        if (notif.url) router.visit(notif.url);
    };
    if (notif.read) return go();
    router.post(`/notifications/${notif.id}/read`, {}, {
        preserveScroll: true,
        preserveState: true,
        onFinish: go,
    });
};

const typeColor = (type) => ({
    correction: 'deep-orange',
    kpi:        'indigo',
    account:    'teal',
    report:     'purple',
    system:     'orange',
}[type] ?? 'grey');

const typeIcon = (type) => ({
    correction: 'mdi-file-document-edit-outline',
    kpi:        'mdi-clipboard-check-outline',
    account:    'mdi-account-outline',
    report:     'mdi-file-chart-outline',
    system:     'mdi-cog-outline',
}[type] ?? 'mdi-bell-outline');
</script>

<style scoped>
.notif-panel {
    border: 1px solid rgba(0, 0, 0, 0.08);
}
.notif-scroll {
    max-height: 420px;
    overflow-y: auto;
}
.notif-item {
    cursor: pointer;
    transition: background 0.15s;
    border-radius: 0;
}
.notif-item:hover {
    background: rgba(0, 0, 0, 0.04);
}
.notif-item--unread {
    background: rgba(var(--v-theme-primary), 0.05);
}
.notif-item--unread:hover {
    background: rgba(var(--v-theme-primary), 0.09);
}
.notif-avatar-wrap {
    position: relative;
}
.notif-dot {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #fff;
}
.unread-circle {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgb(var(--v-theme-primary));
}
.notif-message {
    line-height: 1.4;
    font-size: 0.775rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
