<template>
    <ModalWrapper
        :open="open"
        title="Add Admin"
        subtitle="Register a new province in the system"
        icon="mdi-map-plus"
        @update:open="$emit('update:open', $event)"
    >
        <!-- Body -->
        <v-form ref="formRef" @submit.prevent="submit">
            <v-row dense>
                <v-col cols="12">
                    <v-select
                        v-model="form.category"
                        :items="categoryOptions"
                        item-title="label"
                        item-value="value"
                        label="Role"
                        placeholder="Select user role"
                        variant="outlined"
                        density="compact"
                        clearable
                    />
                </v-col>
                <v-col cols="12">
                    <v-text-field
                        v-model="form.admin_username"
                        label="Username"
                        placeholder="e.g. padmin_davao"
                        variant="outlined"
                        density="compact"
                        :rules="[v => !!v || 'Username is required']"
                    />
                </v-col>
                <v-col cols="12">
                    <v-text-field
                        v-model="form.admin_email"
                        label="Email"
                        placeholder="e.g. admin@dost.gov.ph"
                        variant="outlined"
                        density="compact"
                        :rules="[
                            v => !!v || 'Email is required',
                            v => /.+@.+\..+/.test(v) || 'Email must be valid',
                        ]"
                    />
                </v-col>
                <v-col cols="12">
                    <v-text-field
                        v-model="form.admin_password"
                        label="Password"
                        :type="showPassword ? 'text' : 'password'"
                        variant="outlined"
                        density="compact"
                        :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                        @click:append-inner="showPassword = !showPassword"
                        :rules="[
                            v => !!v || 'Password is required',
                            v => v.length >= 8 || 'Minimum 8 characters',
                        ]"
                    />
                </v-col>
                <v-col cols="12">
                    <v-text-field
                        v-model="form.confirm_admin_password"
                        label="Confirm Password"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        variant="outlined"
                        density="compact"
                        :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                        @click:append-inner="showConfirmPassword = !showConfirmPassword"
                        :rules="[
                            v => !!v || 'Please confirm your password',
                            v => v === form.admin_password || 'Passwords do not match',
                        ]"
                    />
                </v-col>
            </v-row>

        </v-form>

        <!-- Footer Actions -->
        <template #actions>
            <v-btn variant="text" size="small" @click="close">Cancel</v-btn>
            <v-btn variant="flat" color="primary" size="small" :loading="submitting" @click="submit">
                Save Admin
            </v-btn>
        </template>
    </ModalWrapper>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ModalWrapper from '../ModalWrapper.vue';

defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['update:open']);

const formRef             = ref(null);
const submitting          = ref(false);
const showPassword        = ref(false);
const showConfirmPassword = ref(false);

const defaultForm = () => ({
    category:               null,
    admin_username:         '',
    admin_email:            '',
    admin_password:         '',
    confirm_admin_password: '',
});

const form = ref(defaultForm());

const categoryOptions = [
    { label: 'Super Admin',  value: 'super_admin'  },
    { label: 'Sub Admin',  value: 'sub_admin'  },
];

const close = () => {
    form.value                = defaultForm();
    showPassword.value        = false;
    showConfirmPassword.value = false;
    emit('update:open', false);
};

const submit = async () => {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    submitting.value = true;
    router.post('/province-directories/add', form.value, {
        onSuccess: () => close(),
        onFinish:  () => { submitting.value = false; },
    });
};
</script>

<style scoped>
.section-label {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: rgba(0, 0, 0, 0.45);
}
</style>
