<template>
  <v-toolbar color="primary" elevation="2">

    <v-spacer></v-spacer>

    <v-btn icon class="mr-2">
      <v-badge color="error" content="4" dot>
        <v-icon>mdi-bell-outline</v-icon>
      </v-badge>
    </v-btn>

    <v-menu min-width="200px" rounded>
      <template #activator="{ props }">
        <v-btn v-bind="props" variant="text" class="text-none px-2 h-auto py-1">
            <div class="d-flex align-center">
                <v-avatar color="secondary" size="small">
                    <v-img src="https://scontent.fmnl4-4.fna.fbcdn.net/v/t39.30808-6/671994014_1446782747463752_7026258672873290755_n.jpg?_nc_cat=102&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeFymkb_IP6WtMqq3S4CPWPSv6A_pkIXupO_oD-mQhe6kxbjMMnObW1bgbTcWqJ114GlY8_528NJYA2bl2U52kok&_nc_ohc=6T09lQAVF5wQ7kNvwEhuhWk&_nc_oc=AdososSS_TSidvNKq42wciPjmEBXkoonJGgCLZGzCylswf6Xn1hmoSeByPIpnTlqCLo&_nc_zt=23&_nc_ht=scontent.fmnl4-4.fna&_nc_gid=C2Ihbu5CeshFQvMw189giw&_nc_ss=792a8&oh=00_Af5DJBbjqC_K-_q0q1s8-cMA_WxIu1vvuor-CvNRaNj_Zw&oe=6A131335" alt="Admin"></v-img>
                </v-avatar>
                
                <v-icon size="small" class="ml-1 hidden-sm-and-down">mdi-chevron-down</v-icon>
            </div>
        </v-btn>
      </template>


      <v-list>
        <div v-if="show">
            <v-list-item title="John Doe Dodong" subtitle="Super Admin">
                <template #prepend>
                    <v-avatar color="secondary" size="small">
                        <v-img 
                            src="https://scontent.fmnl4-4.fna.fbcdn.net/v/t39.30808-6/671994014_1446782747463752_7026258672873290755_n.jpg?_nc_cat=102&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeFymkb_IP6WtMqq3S4CPWPSv6A_pkIXupO_oD-mQhe6kxbjMMnObW1bgbTcWqJ114GlY8_528NJYA2bl2U52kok&_nc_ohc=6T09lQAVF5wQ7kNvwEhuhWk&_nc_oc=AdososSS_TSidvNKq42wciPjmEBXkoonJGgCLZGzCylswf6Xn1hmoSeByPIpnTlqCLo&_nc_zt=23&_nc_ht=scontent.fmnl4-4.fna&_nc_gid=C2Ihbu5CeshFQvMw189giw&_nc_ss=792a8&oh=00_Af5DJBbjqC_K-_q0q1s8-cMA_WxIu1vvuor-CvNRaNj_Zw&oe=6A131335" 
                            alt="Admin"
                        ></v-img>
                    </v-avatar>
                </template>
            </v-list-item>
            <v-divider class="my-2"></v-divider>
            <v-list-item prepend-icon="mdi-account" title="My Profile" value="profile"></v-list-item>
        </div>
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

const show = (getAuth().role == 'provincial_director' && getAuth().role != 'employee')

const emit = defineEmits(['toggle-drawer']);
const searchQuery = ref('');

const toggleDrawer = () => {
  emit('toggle-drawer');
};
</script>
