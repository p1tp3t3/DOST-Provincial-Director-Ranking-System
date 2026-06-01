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

const open = ref(false);
const tab  = ref('all');

const notifications = ref([
    { id: 1,  type: 'kpi',      message: 'Your KPI record for Q2 2026 has been updated by the Provincial Admin.',          time: 'Just now',      read: false },
    { id: 2,  type: 'account',  message: 'A new employee account was generated for Davao del Sur (32 accounts created).',  time: '5 minutes ago', read: false },
    { id: 3,  type: 'report',   message: 'Activity logs report for May 2026 was exported successfully.',                   time: '1 hour ago',    read: false },
    { id: 4,  type: 'system',   message: 'System maintenance is scheduled on June 5, 2026 at 12:00 AM.',                   time: '3 hours ago',   read: false },
    { id: 5,  type: 'account',  message: 'Your password was changed successfully.',                                        time: 'Yesterday',     read: true  },
    { id: 6,  type: 'kpi',      message: 'KPI outcomes for Bukidnon province have been submitted for review.',             time: 'Yesterday',     read: true  },
    { id: 7,  type: 'report',   message: 'Ranking report for Region XI has been published.',                               time: '2 days ago',    read: true  },
    { id: 8,  type: 'system',   message: 'Database backup completed successfully (14.2 MB).',                              time: '3 days ago',    read: true  },
    { id: 9,  type: 'account',  message: 'New provincial admin account was created for Surigao del Norte.',                time: '4 days ago',    read: true  },
    { id: 10, type: 'kpi',      message: 'Q1 2026 KPI scores have been finalized and are now available for review.',       time: '1 week ago',    read: true  },
]);

const unreadCount = computed(() => notifications.value.filter(n => !n.read).length);

const displayed = computed(() =>
    tab.value === 'unread'
        ? notifications.value.filter(n => !n.read)
        : notifications.value
);

const markAllRead = () => {
    notifications.value = notifications.value.map(n => ({ ...n, read: true }));
};

const handleClick = (notif) => {
    if (!notif.read) {
        const idx = notifications.value.findIndex(n => n.id === notif.id);
        if (idx !== -1) notifications.value[idx].read = true;
    }
};

const typeColor = (type) => ({
    kpi:     'indigo',
    account: 'teal',
    report:  'purple',
    system:  'orange',
}[type] ?? 'grey');

const typeIcon = (type) => ({
    kpi:     'mdi-clipboard-check-outline',
    account: 'mdi-account-outline',
    report:  'mdi-file-chart-outline',
    system:  'mdi-cog-outline',
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
