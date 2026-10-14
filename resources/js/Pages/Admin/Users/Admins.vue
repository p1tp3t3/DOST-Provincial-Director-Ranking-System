<template>
        <Head title="Admins" />

        <div class="grid gap-4 w-full">

            <!-- Header -->
            <!-- Admins are added through User Registration (optional admin role)
                 or a user's Roles in the User List — not from here. -->
            <div class="d-flex align-center justify-space-between">
                <div>
                    <div class="text-h6 font-weight-bold">Admin Management</div>
                    <div class="text-caption text-medium-emphasis">Manage sub admins and provincial admins</div>
                </div>
            </div>

            <!-- List -->
            <AdminList :list="props.admins" :initial-search="search" :initial-role="role" />

        </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AdminList from '@/Components/Lists/AdminList.vue';

const props = defineProps({
    admins: { type: Object, default: null },
    search: { type: String, default: '' },
    role:   { type: String, default: '' },
});
</script>
