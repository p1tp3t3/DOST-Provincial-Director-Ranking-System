<template>
    <AuthenticatedLayout>
        <div class="">

            <!-- Page Header -->
            <div class="d-flex align-center justify-space-between mb-5">
                <div>
                    <h2 class="text-h6 font-weight-bold mb-1">Province Directories</h2>
                </div>
            </div>

            <ProvinceDirectoryList :list="provinces.data" />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import ProvinceDirectoryList from '@/Components/Lists/ProvinceDirectoryList.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    provinces: {
        type: Object
    }
})
</script>
