<template>
  <v-toolbar color="primary" elevation="2">

    <v-spacer></v-spacer>

    <NotificationPanel />

    <v-menu min-width="200px" rounded>
      <template #activator="{ props }">
        <v-btn v-bind="props" variant="text" class="text-none px-2 h-auto py-1">
            <div class="d-flex align-center">
                <v-avatar color="secondary" size="small">
                    <v-img :src="`/profile-picture?filename=${authUser?.profile?.profile_picture}`" alt="Admin"></v-img>
                </v-avatar>
                <v-icon size="small" class="ml-1 hidden-sm-and-down">mdi-chevron-down</v-icon>
            </div>
        </v-btn>
      </template>
      <v-list>
        <v-list-item title="John Doe Dodong" :subtitle="getRoleLabel()" @click="router.get(`/profile/${authUser.id}`);">
            <template #prepend>
                <v-avatar color="secondary" size="small">
                    <v-img 
                        :src="`/profile-picture?filename=${authUser?.profile?.profile_picture}`" 
                        alt="Admin"
                    ></v-img>
                </v-avatar>
            </template>
        </v-list-item>
        <v-divider class="my-2"></v-divider>
        <v-list-item prepend-icon="mdi-cog" title="Settings" value="settings"></v-list-item>
        <v-divider class="my-2"></v-divider>
        <v-list-item prepend-icon="mdi-logout" color="error" title="Logout" value="logout" @click="router.post('/logout')"></v-list-item>
      </v-list>
    </v-menu>
  </v-toolbar>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { getAuth } from '@/helper-functions';
import NotificationPanel from '@/Components/Other/NotificationPanel.vue';

const authUser = getAuth();


const emit = defineEmits(['toggle-drawer']);
const searchQuery = ref('');

const toggleDrawer = () => {
  emit('toggle-drawer');
};

const getRoleLabel = () => {
    const label = {
        'super_admin':        'System Administrator',
        'sub_admin':          'Sub Administrator',
        'provincial_admin':   `Provincial Administrator (${authUser?.province?.name})`,
        'provincial_sub_admin':   `Provincial Sub Administrator (${authUser?.province?.name})`,
        'provincial_director':`Provincial Director of ${authUser?.province?.name}`,
        'employee':           `Employee at ${authUser?.province?.name}`,
    };
    return label[authUser?.role] || '';
};
</script>
