<template>
    <!-- Ask once on load; the browser's own permission prompt needs a click to open -->
    <v-dialog v-model="askPush" max-width="420" persistent>
        <v-card rounded="lg" class="pa-2">
            <v-card-title class="d-flex align-center gap-2 text-subtitle-1 font-weight-bold">
                <v-icon color="primary">mdi-bell-ring-outline</v-icon>
                Turn on notifications?
            </v-card-title>
            <v-card-text class="text-body-2 text-medium-emphasis">
                <template v-if="getsReminders">
                    Get browser notifications 3 days and 1 day before a KPI submission deadline,
                    plus a weekly reminder about missing accomplishments.
                </template>
                <template v-else>
                    Get PRISM updates and reminders as browser notifications.
                </template>
                <div v-if="push.error.value" class="text-error mt-2">{{ push.error.value }}</div>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn variant="text" @click="dismissPush">Not now</v-btn>
                <v-btn color="primary" variant="flat" :loading="push.busy.value" @click="acceptPush">Turn on</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

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

            <!-- Browser push notifications -->
            <div v-if="loggedIn" class="push-row d-flex align-center gap-3 mx-3 mb-2 px-3 py-2">
                <v-icon size="20" :color="push.subscribed.value ? 'success' : 'grey'">
                    {{ push.subscribed.value ? 'mdi-bell-ring-outline' : 'mdi-bell-plus-outline' }}
                </v-icon>
                <div class="flex-grow-1 min-w-0 text-caption">
                    <template v-if="!push.available.value">
                        Browser notifications need a secure (HTTPS) connection.
                    </template>
                    <template v-else-if="push.permission.value === 'denied'">
                        Notifications are blocked — allow them in your browser's site settings.
                    </template>
                    <template v-else-if="push.subscribed.value">
                        Browser notifications are on.
                    </template>
                    <template v-else>
                        Get PRISM reminders as browser notifications.
                    </template>
                    <div v-if="push.error.value" class="text-error">{{ push.error.value }}</div>
                </div>
                <template v-if="push.available.value && push.permission.value !== 'denied'">
                    <v-btn
                        v-if="push.subscribed.value"
                        size="x-small" variant="text" color="grey-darken-1"
                        :loading="push.busy.value" @click="push.disable"
                    >Turn off</v-btn>
                    <v-btn
                        v-else
                        size="x-small" variant="flat" color="primary"
                        :loading="push.busy.value" @click="push.enable"
                    >Turn on</v-btn>
                </template>
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
                            <v-avatar :color="typeColor(notif.category) + '-lighten-5'" size="42" rounded="lg">
                                <v-icon :color="typeColor(notif.category)" size="20">{{ typeIcon(notif.category) }}</v-icon>
                            </v-avatar>
                            <div
                                v-if="!notif.read"
                                class="notif-dot"
                                :style="{ background: 'rgb(var(--v-theme-primary))' }"
                            ></div>
                        </div>

                        <!-- Content -->
                        <div class="flex-grow-1 min-w-0">
                            <div class="text-body-2 font-weight-medium">{{ notif.title }}</div>
                            <div class="text-body-2 notif-message text-medium-emphasis">{{ notif.body }}</div>
                            <div class="text-caption mt-1" style="font-size: 0.775rem;" :class="notif.read ? 'text-medium-emphasis' : 'text-primary font-weight-medium'">
                                {{ notif.sender || 'System' }} · {{ timeAgo(notif.created_at) }}
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
import { ref, computed, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import axios from 'axios';
import { usePushNotifications } from '@/Composables/usePushNotifications';

const page = usePage();
const push = usePushNotifications();

// Roles the KPI reminder jobs notify (any locked role the user holds) — they
// get the reminder-specific wording in the prompt.
const REMINDER_ROLES = ['regional_director', 'provincial_admin', 'provincial_director'];
const loggedIn      = computed(() => !!page.props.auth?.user);
const getsReminders = computed(() =>
    (page.props.auth?.user?.roles ?? []).some(r => (r.pivot?.locked ?? r.locked) && REMINDER_ROLES.includes(r.name))
);

// On load inside the system, ask for notification permission — unless it's
// already on, the browser blocks it, or "Not now" was picked in the last 7 days.
// Chrome/Edge show their own prompt straight away; Firefox/Safari only allow it
// after a click, so they get the in-app dialog whose button opens the prompt.
const ASK_KEY     = 'prism-push-ask-dismissed-at';
const ASK_COOLOFF = 7 * 24 * 60 * 60 * 1000;
const askPush     = ref(false);

const recentlyDismissed = () => {
    try {
        return Date.now() - Number(localStorage.getItem(ASK_KEY) || 0) < ASK_COOLOFF;
    } catch {
        return false;
    }
};

const dismissPush = () => {
    try { localStorage.setItem(ASK_KEY, String(Date.now())); } catch { /* storage blocked */ }
    askPush.value = false;
};

const acceptPush = async () => {
    await push.enable();
    // Close unless enabling failed; a "Block" answer also closes it.
    if (!push.error.value) askPush.value = false;
};

onMounted(async () => {
    if (!loggedIn.value) return;
    await push.refresh();
    const shouldAsk = push.available.value
        && push.permission.value === 'default'
        && !push.subscribed.value
        && !recentlyDismissed();
    if (!shouldAsk) return;

    await push.enable(); // opens the browser prompt where allowed without a click
    if (push.permission.value === 'default') askPush.value = true; // ignored or closed
});

const open = ref(false);
const tab  = ref('all');

// Notifications sent with NotificationHelper::notify(): loaded from
// /notifications, and new ones arrive live over Reverb on the user's private
// channel (event "system-notification").
const notifications = ref([]);
const unreadCount   = ref(0);

const load = async () => {
    try {
        const { data } = await axios.get('/notifications');
        notifications.value = data.notifications;
        unreadCount.value   = data.unread;
    } catch {
        // Bell stays as it was; the next page load retries.
    }
};

const userId = page.props.auth?.user?.id;
if (userId) {
    useEcho(`App.Models.User.${userId}`, '.system-notification', (notif) => {
        if (notifications.value.some(n => n.id === notif.id)) return;
        notifications.value = [notif, ...notifications.value].slice(0, 30);
        if (!notif.read) unreadCount.value++;
    });
}

onMounted(() => { if (userId) load(); });

const displayed = computed(() =>
    tab.value === 'unread'
        ? notifications.value.filter(n => !n.read)
        : notifications.value
);

const markAllRead = async () => {
    notifications.value = notifications.value.map(n => ({ ...n, read: true }));
    unreadCount.value   = 0;
    try { await axios.post('/notifications/read-all'); } catch { load(); }
};

const handleClick = async (notif) => {
    if (!notif.read) {
        notif.read = true;
        unreadCount.value = Math.max(0, unreadCount.value - 1);
        try { await axios.post(`/notifications/${notif.id}/read`); } catch { /* shown read locally */ }
    }
    if (notif.url) {
        open.value = false;
        router.visit(notif.url);
    }
};

const timeAgo = (iso) => {
    if (!iso) return '';
    const secs = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
    if (secs < 60)     return 'Just now';
    if (secs < 3600)   return `${Math.floor(secs / 60)} min ago`;
    if (secs < 86400)  return `${Math.floor(secs / 3600)} hr ago`;
    if (secs < 172800) return 'Yesterday';
    if (secs < 604800) return `${Math.floor(secs / 86400)} days ago`;
    return new Date(iso).toLocaleDateString();
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
.push-row {
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 8px;
    background: rgba(0, 0, 0, 0.02);
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
