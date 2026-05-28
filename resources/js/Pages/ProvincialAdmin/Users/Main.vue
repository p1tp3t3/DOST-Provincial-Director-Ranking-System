<template>
    <AuthenticatedLayout>
        <div class="grid gap-4 w-full">

            <!-- Page Header -->
            <div class="d-flex align-center justify-space-between">
                <div>
                    <h2 class="text-h6 font-weight-bold mb-1">User Management</h2>
                </div>
            </div>

            <UserList :list="users" />

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import UserList from '@/Components/Lists/UserList.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    users: { type: Object, required: true },
});
</script>
