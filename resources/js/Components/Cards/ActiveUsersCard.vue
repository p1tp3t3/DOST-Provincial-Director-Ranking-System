<template>
    <v-card class="elevation-1 border-0 rounded-md h-100">
        <div class="d-flex align-center justify-space-between px-5 pt-4 pb-3">
            <div>
                <div class="text-subtitle-2 font-weight-bold">Active Users</div>
                <div class="text-caption text-medium-emphasis">Currently online in the system</div>
            </div>
            <v-chip size="small" variant="tonal" color="success">{{ users.length }} Online</v-chip>
        </div>
        <v-divider></v-divider>

        <v-list lines="one" class="pa-0">
            <v-list-item
                v-for="(user, i) in users"
                :key="i"
                :border="i < users.length - 1 ? 'b' : false"
                class="px-5 py-2"
            >
                <template #prepend>
                    <div class="position-relative mr-3">
                        <v-avatar size="34">
                            <v-img :src="defPic" cover></v-img>
                        </v-avatar>
                        <span class="online-dot"></span>
                    </div>
                </template>
                <template #title>
                    <span class="text-body-2 font-weight-medium">{{ user.name }}</span>
                </template>
                <template #subtitle>
                    <span class="text-caption text-medium-emphasis">Active {{ user.last_active }}</span>
                </template>
                <template #append>
                    <v-chip
                        size="x-small"
                        variant="tonal"
                        :color="roleColor(user.role)"
                        class="text-capitalize"
                    >
                        {{ user.role }}
                    </v-chip>
                </template>
            </v-list-item>

            <div v-if="users.length === 0" class="text-center py-10">
                <v-icon size="36" color="grey-lighten-2" class="mb-2">mdi-account-off-outline</v-icon>
                <div class="text-caption text-medium-emphasis">No active users right now</div>
            </div>
        </v-list>
    </v-card>
</template>

<script setup>
const defPic = 'https://scontent.fcgy1-3.fna.fbcdn.net/v/t39.30808-1/569409499_2926389544213362_5572906559510250325_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=107&ccb=1-7&_nc_sid=1d2534&_nc_eui2=AeHxMS2Jaxqdlz7XjktrvQNCkuMFs6-OJrWS4wWzr44mtaR_gFGX3XynJKcVctLnDQznMva1uf7y4DJ9zvqkENur&_nc_ohc=t1KgQyv8YI4Q7kNvwFZDK9s&_nc_oc=AdovjHXEhGImiLI-b4UzqvAlKfytDYJV4eb0rG9Z9EgUyAyg_EF3UGX2mFLadgn20tFa5hK9DE54diCLrTUm3qlo&_nc_zt=24&_nc_ht=scontent.fcgy1-3.fna&_nc_gid=AYiNPwYYm5mm809vxgPhoA&_nc_ss=782a8&oh=00_Af5BeBLxqbptd619Z7yoL_PoCiaDpG1OQR9LWJj9XiJMMw&oe=6A13B822';

defineProps({
    users: { type: Array, default: () => [] },
});

const roleColor = (role) => {
    const map = { admin: 'indigo', 'sub-admin': 'purple', director: 'blue', employee: 'teal' };
    return map[role?.toLowerCase()] ?? 'grey';
};
</script>

<style scoped>
.online-dot {
    position: absolute;
    bottom: 1px;
    right: 1px;
    width: 9px;
    height: 9px;
    background-color: #4caf50;
    border-radius: 50%;
    border: 2px solid white;
}
</style>
