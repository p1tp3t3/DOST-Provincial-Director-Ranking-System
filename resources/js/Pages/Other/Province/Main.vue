<template>
    <AuthenticatedLayout>
        <AddProvinceModal v-if="user?.role == 'super_admin'" v-model:open="open" />

        <div>
            <!-- Page Header -->
            <div class="d-flex align-center justify-space-between mb-5">
                <div>
                    <h2 class="text-h6 font-weight-bold mb-1">Province Directories</h2>
                </div>
                <v-btn
                    v-if="user?.role === 'super_admin'"
                    color="primary"
                    variant="flat"
                    size="small"
                    prepend-icon="mdi-plus"
                    @click="open = true"
                >
                    Add Province
                </v-btn>
            </div>

            <ProvinceDirectoryList :list="provinces.data" />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import ProvinceDirectoryList from '@/Components/Lists/ProvinceDirectoryList.vue';
import AddProvinceModal from '@/Components/Modals/Add/AddProvinceModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { getAuth } from '@/helper-functions';

defineProps({
    provinces: { type: Object },
});

const open = ref(false);
const user = getAuth();
</script>
