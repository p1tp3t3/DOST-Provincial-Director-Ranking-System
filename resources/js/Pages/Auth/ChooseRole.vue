<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    roles: { type: Array, default: () => [] },
});

const switching = ref(null);

const roleMeta = {
    super_admin:         { label: 'Super Admin',         icon: 'mdi-shield-crown-outline' },
    sub_admin:            { label: 'Sub Admin',            icon: 'mdi-shield-account-outline' },
    regional_director:    { label: 'Regional Director',    icon: 'mdi-map-marker-radius-outline' },
    provincial_admin:     { label: 'Provincial Admin',     icon: 'mdi-account-cog-outline' },
    provincial_director:  { label: 'Provincial Director',  icon: 'mdi-account-tie-outline' },
    employee:             { label: 'Employee',             icon: 'mdi-account-outline' },
};

const meta = (name) => roleMeta[name] ?? { label: name, icon: 'mdi-account-outline' };

const choose = (role) => {
    switching.value = role.name;
    router.patch('/switch-role', { role: role.name }, {
        onFinish: () => { switching.value = null; },
    });
};
</script>

<template>
    <Head title="Choose Role" />

    <div class="choose-root d-flex align-center justify-center">
        <v-card max-width="440" width="100%" rounded="lg" elevation="2" class="pa-2">
            <v-card-item class="pt-6 px-6">
                <div class="text-h6 font-weight-bold">Choose your role</div>
                <div class="text-caption text-medium-emphasis">
                    Your account holds more than one role. Pick which one you're using right now — you can switch later.
                </div>
            </v-card-item>
            <v-card-text class="px-6 pb-6">
                <div class="d-flex flex-column gap-2">
                    <v-btn
                        v-for="role in roles"
                        :key="role.id"
                        size="large"
                        variant="outlined"
                        color="primary"
                        class="justify-start text-none"
                        :loading="switching === role.name"
                        :disabled="switching !== null && switching !== role.name"
                        :prepend-icon="meta(role.name).icon"
                        @click="choose(role)"
                    >{{ meta(role.name).label }}</v-btn>
                </div>
            </v-card-text>
        </v-card>
    </div>
</template>

<style scoped>
.choose-root {
    min-height: 100vh;
    padding: 16px;
    background: #f8fafc;
}
</style>
