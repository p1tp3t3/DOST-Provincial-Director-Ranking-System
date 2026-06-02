<script setup>
import { ref, computed, watchEffect } from 'vue';import { usePage, router } from '@inertiajs/vue3';

import {
    RiDashboard2Fill,
    RiSettings2Fill,
    RiUser2Fill,
    RiTeamFill,
    RiFileChartFill,
    RiFileList3Fill,
    RiGlobalFill,
    RiLogoutBoxRLine,
    RiFolder2Fill,
    RiUserAddLine,
    RiListCheck3,
    RiAdminFill,
    RiListView,
    RiFile2Fill,
    RiBarChart2Fill,
    RiRoadMapFill,
    RiFolder2Line,
} from '@remixicon/vue';

const props = defineProps({
    role: { type: String, required: true },
});

const isOpen = ref(false);
const openGroups = ref([]);
const page = usePage();
const authUser  = computed(() => page.props.auth.user);
const currentUrl = computed(() => page.url);

const getRoleLabel = () => {
    const label = {
        'super_admin':        'System Administrator',
        'sub_admin':          'Sub Administrator',
        'provincial_admin':   `Provincial Administrator (${authUser.value?.province?.name})`,
        'provincial_sub_admin':   `Provincial Sub Administrator (${authUser.value?.province?.name})`,
        'provincial_director':`Provincial Director of ${authUser.value?.province?.name}`,
        'employee':           'Employee',
    };
    return label[authUser.value?.role] || '';
};


const tabs = computed(() => {
    if (!authUser.value) return [];

    switch (authUser.value.role) {
        case 'super_admin':
            return [
                { name: 'Dashboard', href: '/dashboard', icon: RiDashboard2Fill },
                { name: 'Performance Map', href: '/performance-map', icon: RiRoadMapFill },
                { name: 'Province Directories', href: '/province-directories', icon: RiFolder2Line },
                {
                    name: 'User Management', icon: RiUser2Fill,
                    children: [
                        { name: 'User List',         href: '/users',          icon: RiTeamFill    },
                        { name: 'User Registration', href: '/users/create',   icon: RiUserAddLine },
                        { name: 'Admin List',         href: '/admins',          icon: RiAdminFill    },
                    ]
                },
                { name: 'Maintenance', href: '/maintenance', icon: RiSettings2Fill },
                { name: 'Activity Logs', href: '/activity-logs', icon: RiListView },
                { name: 'Reports', href: '/super-admin-report', icon: RiBarChart2Fill },
            ];
        case 'sub_admin':
            return [
                { name: 'Dashboard',            href: '/dashboard',            icon: RiDashboard2Fill },
                { name: 'Performance Map', href: '/performance-map', icon: RiRoadMapFill },
                { name: 'Province Directories', href: '/province-directories', icon: RiFolder2Fill    },
                { name: 'Provincial Directors', href: '/provincial-directors', icon: RiTeamFill       },
                { name: 'Employees',            href: '/employees',            icon: RiTeamFill       },
                { name: 'Reports',              href: '/sub-admin-report',              icon: RiFileChartFill  },
            ];
        case 'provincial_admin':
            return [
                { name: 'Dashboard', href: '/dashboard', icon: RiDashboard2Fill },
                {
                    name: 'User Management', icon: RiUser2Fill,
                    children: [
                        { name: 'User List',         href: '/users',          icon: RiTeamFill    },
                        { name: 'User Registration', href: '/users/create',   icon: RiUserAddLine },
                        { name: 'Auto User Generator', href: '/users/auto-generator',   icon: RiFile2Fill },
                    ]
                },
                { name: 'Activity Logs', href: '/activity-logs', icon: RiListView },
                { name: 'Reports', href: '/provincial-admin-report', icon: RiBarChart2Fill },
            ];
        case 'provincial_sub_admin':
            return [
                { name: 'Dashboard', href: '/dashboard', icon: RiDashboard2Fill },
                { name: 'Employees', href: '/employees', icon: RiTeamFill       },
                {
                    name: 'KPI Management', icon: RiListCheck3,
                    children: [
                        { name: 'KPI Overview',    href: '/kpi',        icon: RiFileList3Fill },
                        { name: 'KPI Reports',     href: '/kpi/reports',icon: RiFileChartFill },
                    ]
                },
                { name: 'Reports',   href: '/provincial-sub-admin-report',    icon: RiFileList3Fill  },
            ];
        case 'provincial_director':
            return [
                { name: 'Dashboard', href: '/dashboard', icon: RiDashboard2Fill },
                { name: 'My Profile',href: '/profile',   icon: RiUser2Fill      },
                { name: 'Employees', href: '/employees', icon: RiTeamFill       },
                { name: 'Reports',   href: '/report',    icon: RiFileList3Fill  },
            ];
        case 'regional_director':
            return [
                { name: 'Dashboard',       href: '/dashboard',        icon: RiDashboard2Fill },
                { name: 'My Profile',      href: '/profile',          icon: RiUser2Fill      },
                { name: 'Regional Reports',href: '/regional-reports', icon: RiGlobalFill     },
            ];
        case 'employee':
            return [
                { name: 'Dashboard', href: '/dashboard', icon: RiDashboard2Fill },
                { name: 'My Profile',href: '/profile',   icon: RiUser2Fill      },
                { name: 'Provincial Directors', href: '/provincial-directors', icon: RiTeamFill       },
                { name: 'Reports',   href: '/report',    icon: RiFileList3Fill  },
            ];
        default:
            return [];
    }
});

const isActive = (href) => currentUrl.value === href;

const isGroupActive = (tab) =>
    tab.children?.some(child => currentUrl.value === child.href);

const toggleGroup = (name) => {
    if (openGroups.value.includes(name)) {
        openGroups.value = openGroups.value.filter(g => g !== name);
    } else {
        openGroups.value.push(name);
    }
};

const isGroupOpen = (name) => openGroups.value.includes(name);

// Auto-open groups that have an active child on load or URL change
watchEffect(() => {
    tabs.value.forEach(tab => {
        if (tab.children?.some(child => currentUrl.value === child.href)) {
            if (!openGroups.value.includes(tab.name)) {
                openGroups.value.push(tab.name);
            }
        }
    });
});

const navigateTo = (href) => router.visit(href);
const handleLogout = () => router.post('/logout');

const onSubNavEnter = (el) => {
    el.style.height = '0';
    el.style.opacity = '0';
    el.offsetHeight; // force reflow
    el.style.transition = 'height 0.25s ease, opacity 0.2s ease';
    el.style.height = el.scrollHeight + 'px';
    el.style.opacity = '1';
};
const onSubNavAfterEnter = (el) => {
    el.style.height = 'auto';
    el.style.transition = '';
};
const onSubNavLeave = (el) => {
    el.style.height = el.scrollHeight + 'px';
    el.style.opacity = '1';
    el.offsetHeight; // force reflow
    el.style.transition = 'height 0.25s ease, opacity 0.2s ease';
    el.style.height = '0';
    el.style.opacity = '0';
};

const defAvatar = '/profile-picture?filename=profile-pic-2026-06-01-202414.png';
</script>

<template>
    <aside
        :class="['sidebar sticky flex-shrink-0 left-0 top-0 h-screen text-white shadow-xl flex flex-column', isOpen ? 'w-64' : 'w-16']"
        @mouseenter="isOpen = true"
        @mouseleave="isOpen = false"
    >

        <!-- Header -->
        <div class="sidebar-header flex items-center px-3 h-16 border-b border-white/10 flex-shrink-0"
             :class="isOpen ? 'justify-between' : 'justify-center'">
            <div v-if="isOpen" class="flex items-center gap-3 overflow-hidden">
                <div class="logo-box flex-shrink-0">
                    <img src="/assets/logo.png" alt="PDRIS Logo" class="w-8 h-8">
                </div>
                <div class="leading-tight overflow-hidden">
                    <div class="font-bold text-sm whitespace-nowrap">PDRIS</div>
                    <div class="text-xs text-blue-300 whitespace-nowrap">Admin Panel</div>
                </div>
            </div>
            <div v-else class="logo-box">
                <img src="/assets/logo.png" alt="PDRIS Logo" class="w-8 h-8">
            </div>
        </div>

        <!-- User Section -->
        <div class="px-3 py-4 border-b border-white/10 flex-shrink-0"
             :class="isOpen ? '' : 'flex justify-center'">
            <div v-if="isOpen" class="flex items-center gap-3">
                <v-avatar size="36" class="flex-shrink-0 ring-2 ring-white/20">
                    <v-img :src="defAvatar" cover></v-img>
                </v-avatar>
                <div class="overflow-hidden">
                    <p class="text-sm font-semibold mb-0 truncate">{{ authUser?.username || 'User' }}</p>
                    <span class="role-badge">{{ getRoleLabel() }}</span>
                </div>
            </div>
            <v-tooltip v-else location="right" :text="getRoleLabel()">
                <template #activator="{ props: tp }">
                    <v-avatar v-bind="tp" size="36" class="ring-2 ring-white/20">
                        <v-img :src="defAvatar" cover></v-img>
                    </v-avatar>
                </template>
            </v-tooltip>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-2 py-3">
            <p v-if="isOpen" class="nav-section-label">Menu</p>

            <template v-for="tab in tabs" :key="tab.name">

                <!-- Parent with children -->
                <template v-if="tab.children">
                    <v-tooltip :disabled="isOpen" location="right" :text="tab.name">
                        <template #activator="{ props: tp }">
                            <button
                                v-bind="tp"
                                :class="['nav-item', isGroupActive(tab) ? 'nav-item--group-active' : '', !isOpen ? 'nav-item--collapsed' : '']"
                                @click="isOpen ? toggleGroup(tab.name) : null"
                            >
                                <component :is="tab.icon" class="nav-icon" />
                                <span v-if="isOpen" class="nav-label">{{ tab.name }}</span>
                                <v-icon
                                    v-if="isOpen"
                                    size="14"
                                    class="chevron"
                                    :class="{ 'chevron--open': isGroupOpen(tab.name) }"
                                >
                                    mdi-chevron-down
                                </v-icon>
                            </button>
                        </template>
                    </v-tooltip>

                    <!-- Sub-nav items -->
                    <Transition
                        @enter="onSubNavEnter"
                        @after-enter="onSubNavAfterEnter"
                        @leave="onSubNavLeave"
                    >
                        <div v-if="isOpen && isGroupOpen(tab.name)" class="sub-nav">
                            <button
                                v-for="child in tab.children"
                                :key="child.href"
                                :class="['sub-nav-item', isActive(child.href) ? 'sub-nav-item--active' : '']"
                                @click="navigateTo(child.href)"
                            >
                                <component :is="child.icon" class="sub-nav-icon" />
                                <span class="nav-label">{{ child.name }}</span>
                                <span v-if="isActive(child.href)" class="active-dot"></span>
                            </button>
                        </div>
                    </Transition>
                </template>

                <!-- Regular nav item -->
                <v-tooltip v-else :disabled="isOpen" location="right" :text="tab.name">
                    <template #activator="{ props: tp }">
                        <button
                            v-bind="tp"
                            :class="['nav-item', isActive(tab.href) ? 'nav-item--active' : '', !isOpen ? 'nav-item--collapsed' : '']"
                            @click="navigateTo(tab.href)"
                        >
                            <component :is="tab.icon" class="nav-icon" />
                            <span v-if="isOpen" class="nav-label">{{ tab.name }}</span>
                            <span v-if="isOpen && isActive(tab.href)" class="active-dot"></span>
                        </button>
                    </template>
                </v-tooltip>

            </template>
        </nav>

        <!-- Logout -->
        <div class="px-2 py-3 border-t border-white/10 flex-shrink-0">
            <v-tooltip :disabled="isOpen" location="right" text="Logout">
                <template #activator="{ props: tp }">
                    <button
                        v-bind="tp"
                        :class="['nav-item nav-item--logout', !isOpen ? 'nav-item--collapsed' : '']"
                        @click="handleLogout"
                    >
                        <RiLogoutBoxRLine class="nav-icon" />
                        <span v-if="isOpen" class="nav-label">Logout</span>
                    </button>
                </template>
            </v-tooltip>
        </div>

    </aside>
</template>

<style scoped>
.sidebar {
    background-color: #0f2044;
    display: flex;
    flex-direction: column;
    transition: width 0.22s cubic-bezier(.4,0,.2,1);
    overflow: hidden;
}

.logo-box {
    background: rgba(255,255,255,0.12);
    border-radius: 8px;
    padding: 5px;
    display: grid;
    place-items: center;
}

.role-badge {
    display: inline-block;
    font-size: 0.65rem;
    font-weight: 600;
    color: #93c5fd;
    line-height: 1;
}

.nav-section-label {
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.35);
    padding: 0 10px;
    margin-bottom: 4px;
}

.nav-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 9px 10px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    color: rgba(255,255,255,0.65);
    transition: background 0.15s, color 0.15s;
    text-align: left;
    border: none;
    background: none;
    cursor: pointer;
}
.nav-item:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
}
.nav-item--active {
    background: rgba(99,102,241,0.25);
    color: #fff;
    padding-left: 7px;
}
.nav-item--group-active {
    color: #fff;
}
.nav-item--collapsed {
    justify-content: center;
    padding: 9px 0;
}
.nav-item--logout {
    color: rgba(248,113,113,0.8);
}
.nav-item--logout:hover {
    background: rgba(248,113,113,0.1);
    color: #fca5a5;
}

.nav-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}
.nav-label {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.active-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #818cf8;
    flex-shrink: 0;
}

.chevron {
    transition: transform 0.2s;
    color: rgba(255,255,255,0.4);
    flex-shrink: 0;
}
.chevron--open {
    transform: rotate(180deg);
}

/* Sub-nav */
.sub-nav {
    margin: 2px 0 4px 0;
    padding-left: 16px;
    border-left: 1px solid rgba(255,255,255,0.1);
    margin-left: 18px;
}
.sub-nav-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 7px 10px;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 400;
    color: rgba(255,255,255,0.55);
    transition: background 0.15s, color 0.15s;
    text-align: left;
    border: none;
    background: none;
    cursor: pointer;
}
.sub-nav-item:hover {
    background: rgba(255,255,255,0.07);
    color: #fff;
}
.sub-nav-item--active {
    background: rgba(99, 102, 241, 0.2);
    color: #c7d2fe;
    font-weight: 600;
    padding-left: 8px;
}
.sub-nav-icon {
    width: 14px;
    height: 14px;
}

.nav-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}
.nav-label {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.active-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #818cf8;
    flex-shrink: 0;
}

/* Sub-nav slide transition */
.subnav-enter-active,
.subnav-leave-active {
    transition: max-height 0.25s ease, opacity 0.2s ease;
    overflow: hidden;
    max-height: 200px;
}
.subnav-enter-from,
.subnav-leave-to {
    max-height: 0;
    opacity: 0;
}
</style>
