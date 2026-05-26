<template>
    <AuthenticatedLayout>
        <div class="grid gap-4 w-full">

            <!-- Page Header -->
            <div class="d-flex align-center justify-space-between">
                <div>
                    <h2 class="text-h6 font-weight-bold mb-1">User Management</h2>
                    <p class="text-body-2 text-medium-emphasis mb-0">Manage all system accounts and their roles.</p>
                </div>
                <v-btn color="primary" variant="flat" size="small" prepend-icon="mdi-plus">
                    Add User
                </v-btn>
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
