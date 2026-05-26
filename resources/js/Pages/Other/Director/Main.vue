<script setup>
import ProvincialDirectorList from '@/Components/Lists/ProvincialDirectorList.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    directors: {
        type: Object
    }
})
</script>

<template>
    <AuthenticatedLayout>
        <div class="">
            <!-- Page Header -->
            <div class="d-flex align-center justify-space-between mb-5">
                <div>
                    <h2 class="text-h6 font-weight-bold mb-1">Provincial Directors</h2>
                </div>
            </div>
            <ProvincialDirectorList :list="directors" />
        </div>
    </AuthenticatedLayout>
</template>
