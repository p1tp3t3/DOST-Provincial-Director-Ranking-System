<template>
        <AddAdminModal v-model:open="open" />

        <div class="grid gap-4 w-full">

            <!-- Header -->
            <div class="d-flex align-center justify-space-between">
                <div>
                    <div class="text-h6 font-weight-bold">Admin Management</div>
                    <div class="text-caption text-medium-emphasis">Manage sub admins and provincial admins</div>
                </div>
                <v-btn 
                    color="primary" 
                    variant="flat" 
                    size="small" 
                    prepend-icon="mdi-plus"
                    @click="open = true"
                >
                    Add Admin
                </v-btn>
            </div>

            <!-- List -->
            <AdminList :list="props.admins" :initial-search="search" :initial-role="role" />

        </div>
</template>

<script setup>
import { ref } from 'vue';
import AdminList from '@/Components/Lists/AdminList.vue';
import AddAdminModal from '@/Components/Modals/Add/AddAdminModal.vue';

const props = defineProps({
    admins: { type: Object, default: null },
    search: { type: String, default: '' },
    role:   { type: String, default: '' },
});

const open = ref(false);

</script>
