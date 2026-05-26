<template>
    <v-card class="elevation-1 border-0 rounded-md h-100">
        <div class="d-flex align-center justify-space-between px-5 pt-4 pb-3">
            <div>
                <div class="text-subtitle-2 font-weight-bold">Recent Activity</div>
                <div class="text-caption text-medium-emphasis">Latest system actions across all users</div>
            </div>
        </div>
        <v-divider></v-divider>
        <v-table density="comfortable" hover>
            <thead>
                <tr>
                    <th class="text-caption text-medium-emphasis">User</th>
                    <th class="text-caption text-medium-emphasis">Action</th>
                    <th class="text-caption text-medium-emphasis" width="90">Module</th>
                    <th class="text-caption text-medium-emphasis" width="130">Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(log, i) in logs" :key="i">
                    <td>
                        <div class="d-flex align-center gap-2 py-1">
                            <v-avatar size="28">
                                <v-img :src="defPic" cover></v-img>
                            </v-avatar>
                            <div>
                                <div class="text-body-2 font-weight-medium">{{ log.user }}</div>
                                <v-chip size="x-small" variant="tonal" :color="roleColor(log.role)" class="text-capitalize">{{ log.role }}</v-chip>
                            </div>
                        </div>
                    </td>
                    <td class="text-body-2">{{ log.action }}</td>
                    <td>
                        <v-chip size="x-small" variant="outlined" color="grey">{{ log.module }}</v-chip>
                    </td>
                    <td class="text-caption text-medium-emphasis">{{ log.datetime }}</td>
                </tr>
                <tr v-if="logs.length === 0">
                    <td colspan="4">
                        <div class="text-center py-10">
                            <v-icon size="36" color="grey-lighten-2" class="mb-2">mdi-clipboard-text-off-outline</v-icon>
                            <div class="text-caption text-medium-emphasis">No activity recorded</div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </v-table>
    </v-card>
</template>

<script setup>
const defPic = 'https://scontent.fcgy1-3.fna.fbcdn.net/v/t39.30808-1/569409499_2926389544213362_5572906559510250325_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=107&ccb=1-7&_nc_sid=1d2534&_nc_eui2=AeHxMS2Jaxqdlz7XjktrvQNCkuMFs6-OJrWS4wWzr44mtaR_gFGX3XynJKcVctLnDQznMva1uf7y4DJ9zvqkENur&_nc_ohc=t1KgQyv8YI4Q7kNvwFZDK9s&_nc_oc=AdovjHXEhGImiLI-b4UzqvAlKfytDYJV4eb0rG9Z9EgUyAyg_EF3UGX2mFLadgn20tFa5hK9DE54diCLrTUm3qlo&_nc_zt=24&_nc_ht=scontent.fcgy1-3.fna&_nc_gid=AYiNPwYYm5mm809vxgPhoA&_nc_ss=782a8&oh=00_Af5BeBLxqbptd619Z7yoL_PoCiaDpG1OQR9LWJj9XiJMMw&oe=6A13B822';

defineProps({
    logs: { type: Array, default: () => [] },
});

const roleColor = (role) => {
    const map = { admin: 'indigo', 'sub-admin': 'purple', director: 'blue', employee: 'teal' };
    return map[role?.toLowerCase()] ?? 'grey';
};
</script>
