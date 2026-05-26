<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3'; // 💡 1. Imported router to drive button click navigation safely

import {
    RiDashboard2Fill,
    RiSettings2Fill,
    RiUser2Fill,
    RiTeamFill,
    RiFileChartFill,
    RiFileList3Fill,
    RiGlobalFill,
    RiMenuFoldLine,
    RiMenuUnfoldLine,
    RiLogoutBoxRLine,
    RiFolder2Fill
} from '@remixicon/vue';

const props = defineProps({
    role: {
        type: String,
        required: true,
    },
});

const isOpen = ref(true);
const page = usePage();
const authUser = computed(() => page.props.auth.user);
const currentUrl = computed(() => page.url);

const getRoleColor = () => {
    const colors = {
        'super_admin': 'bg-red-100 text-red-800',
        'sub_admin': 'bg-orange-100 text-orange-800',
        'provincial_admin': 'bg-blue-100 text-blue-800',
        'provincial_director': 'bg-blue-100 text-blue-800',
        'employee': 'bg-green-100 text-green-800',
    };
    return colors[authUser.value?.role];
};

const getRoleLabel = () => {
    const label = {
        'super_admin': 'System Administrator',
        'sub_admin': 'Sub Administrator',
        'provincial_admin': 'Provincial Administrator',
        'provincial_director': 'Provincial Director',
        'employee': 'Employee',
    };
    return label[authUser.value?.role] || '';
};

const tabs = computed(() => {
    if (!authUser.value) return [];
    
    switch(authUser.value.role) {
        case 'super_admin':
            return [
                { name: 'Dashboard', href: '/dashboard', icon: RiDashboard2Fill },
                { name: 'Users', href: '/users', icon: RiUser2Fill },
                { name: 'Settings', href: '/settings', icon: RiSettings2Fill },
            ];
        case 'sub_admin':
            return [
                { name: 'Dashboard', href: '/dashboard', icon: RiDashboard2Fill },
                { name: 'Province Directories', href: '/province-directories', icon: RiFolder2Fill },
                { name: 'Provincial Directors', href: '/provincial-directors', icon: RiTeamFill },
                { name: 'Employees', href: '/employees', icon: RiTeamFill },
                { name: 'Reports', href: '/reports', icon: RiFileChartFill },
            ];
        case 'provincial_admin':
            return [
                { name: 'Dashboard', href: '/dashboard', icon: RiDashboard2Fill },
                { name: 'Employees', href: '/employees', icon: RiTeamFill },
                { name: 'Provincial Directors', href: '/provincial-directors', icon: RiUser2Fill },
                { name: 'Reports', href: '/report', icon: RiFileList3Fill },
            ];
        case 'provincial_director':
            return [
                { name: 'Dashboard', href: '/dashboard', icon: RiDashboard2Fill },
                { name: 'My Profile', href:'/profile', icon: RiUser2Fill },
                { name: 'Reports', href: '/report', icon: RiFileList3Fill },
            ];
        case 'regional_director':
            return [
                { name: 'Dashboard', href: '/dashboard', icon: RiDashboard2Fill },
                { name: 'My Profile', href: '/profile', icon: RiUser2Fill },
                { name: 'Regional Reports', href: '/regional-reports', icon: RiGlobalFill },
            ];
        default:
            return [];
    }
});

const isActive = (href) => {
    return currentUrl.value === href;
};

// 💡 2. Navigation Handler to intercept button clicks and maintain SPA routing rules
const navigateTo = (href) => {
    router.visit(href);
};

// 💡 3. Logout action handler using explicit routing methods
const handleLogout = () => {
    router.post('/logout');
};

const defAvatar = 'https://scontent.fcgy1-3.fna.fbcdn.net/v/t39.30808-1/569409499_2926389544213362_5572906559510250325_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=107&ccb=1-7&_nc_sid=1d2534&_nc_eui2=AeHxMS2Jaxqdlz7XjktrvQNCkuMFs6-OJrWS4wWzr44mtaR_gFGX3XynJKcVctLnDQznMva1uf7y4DJ9zvqkENur&_nc_ohc=t1KgQyv8YI4Q7kNvwFZDK9s&_nc_oc=AdovjHXEhGImiLI-b4UzqvAlKfytDYJV4eb0rG9Z9EgUyAyg_EF3UGX2mFLadgn20tFa5hK9DE54diCLrTUm3qlo&_nc_zt=24&_nc_ht=scontent.fcgy1-3.fna&_nc_gid=AYiNPwYYm5mm809vxgPhoA&_nc_ss=782a8&oh=00_Af5BeBLxqbptd619Z7yoL_PoCiaDpG1OQR9LWJj9XiJMMw&oe=6A13B822'
</script>

<template>
    <aside :class="['sidebar sticky flex-shrink-0 left-0 top-0 h-screen text-white shadow-xl transition-all duration-300 flex flex-column', isOpen ? 'w-64' : 'w-16']">

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
        <nav class="flex-1 overflow-y-auto px-2 py-3 space-y-1">
            <p v-if="isOpen" class="nav-section-label">Menu</p>

            <v-tooltip
                v-for="tab in tabs"
                :key="tab.href"
                :disabled="isOpen"
                location="right"
                :text="tab.name"
            >
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
}

.logo-box {
    background: rgba(255,255,255,0.12);
    border-radius: 8px;
    padding: 5px;
    display: grid;
    place-items: center;
}

.toggle-btn {
    display: grid;
    place-items: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    color: rgba(255,255,255,0.6);
    transition: background 0.15s, color 0.15s;
    flex-shrink: 0;
}
.toggle-btn:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
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
</style>
