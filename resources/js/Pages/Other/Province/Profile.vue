<template>
    <AuthenticatedLayout>
        <div>
            profile page of province 
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    province_profile: {
        type: Object
    }
})
</script>