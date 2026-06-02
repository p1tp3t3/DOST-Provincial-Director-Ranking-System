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
            <AdminList :list="props.admins" />

        </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import AdminList from '@/Components/Lists/AdminList.vue';
import AddAdminModal from '@/Components/Modals/Add/AddAdminModal.vue';

const props = defineProps({
    admins: { type: Object, default: null },
});

const listData = computed(() => props.admins);
const open = ref(false);

</script>
