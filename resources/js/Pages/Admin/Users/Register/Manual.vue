<template>
        <div class="grid gap-4 w-full">

            <!-- Header -->
            <div class="d-flex align-center justify-space-between">
                <div>
                    <div class="text-h6 font-weight-bold">Manual Registration</div>
                    <div class="text-caption text-medium-emphasis">Register a new employee or provincial director</div>
                </div>
            </div>

            <v-form ref="formRef" @submit.prevent="submit">
                <v-row>

                    <!-- Left: Main Form -->
                    <v-col cols="12" md="8">
                        <div class="d-flex flex-column gap-4">

                            <!-- Role Selection -->
                            <v-card class="elevation-1 border-0 rounded-md">
                                <div class="pa-5 pb-3 d-flex align-center gap-3">
                                    <v-avatar color="indigo-lighten-5" rounded="lg" size="36">
                                        <v-icon color="indigo" size="18">mdi-shield-account-outline</v-icon>
                                    </v-avatar>
                                    <div>
                                        <div class="text-subtitle-2 font-weight-bold">Role</div>
                                        <div class="text-caption text-medium-emphasis">Select the account type to register</div>
                                    </div>
                                </div>
                                <v-divider></v-divider>
                                <div class="pa-5">
                                    <div class="d-flex gap-3">
                                        <v-card
                                            v-for="r in roleOptions"
                                            :key="r.value"
                                            :variant="form.role === r.value ? 'tonal' : 'outlined'"
                                            :color="form.role === r.value ? 'indigo' : undefined"
                                            class="role-card flex-grow-1 pa-4"
                                            rounded="lg"
                                            @click="form.role = r.value"
                                        >
                                            <div class="d-flex align-center gap-3">
                                                <v-icon
                                                    :color="form.role === r.value ? 'indigo' : 'medium-emphasis'"
                                                    size="22"
                                                >{{ r.icon }}</v-icon>
                                                <div>
                                                    <div class="text-body-2 font-weight-bold">{{ r.label }}</div>
                                                    <div class="text-caption text-medium-emphasis">{{ r.hint }}</div>
                                                </div>
                                            </div>
                                        </v-card>
                                    </div>
                                </div>
                            </v-card>

                            <!-- Personal Information -->
                            <v-card class="elevation-1 border-0 rounded-md">
                                <div class="pa-5 pb-3 d-flex align-center gap-3">
                                    <v-avatar color="indigo-lighten-5" rounded="lg" size="36">
                                        <v-icon color="indigo" size="18">mdi-account-outline</v-icon>
                                    </v-avatar>
                                    <div>
                                        <div class="text-subtitle-2 font-weight-bold">Personal Information</div>
                                        <div class="text-caption text-medium-emphasis">Enter the registrant's full name and details</div>
                                    </div>
                                </div>
                                <v-divider></v-divider>
                                <div class="pa-5">
                                    <v-row dense>
                                        <v-col cols="12" sm="2">
                                            <v-select
                                                v-model="form.prefix"
                                                :items="prefixOptions"
                                                label="Prefix"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                :rules="[r.required]"
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="3">
                                            <v-text-field
                                                v-model="form.first_name"
                                                label="First Name"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                :rules="[r.required]"
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="4">
                                            <v-text-field
                                                v-model="form.middle_name"
                                                label="Middle Name"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                :rules="[r.required]"
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="2">
                                            <v-text-field
                                                v-model="form.last_name"
                                                label="Last Name"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                :rules="[r.required]"
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="1">
                                            <v-text-field
                                                v-model="form.suffix"
                                                label="Suffix"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                placeholder="Jr."
                                            />
                                        </v-col>
                                    </v-row>
                                </div>
                            </v-card>

                            <!-- Assignment Details (both roles) -->
                            <v-card class="elevation-1 border-0 rounded-md">
                                <div class="pa-5 pb-3 d-flex align-center gap-3">
                                    <v-avatar color="indigo-lighten-5" rounded="lg" size="36">
                                        <v-icon color="indigo" size="18">mdi-map-marker-outline</v-icon>
                                    </v-avatar>
                                    <div>
                                        <div class="text-subtitle-2 font-weight-bold">Assignment Details</div>
                                        <div class="text-caption text-medium-emphasis">Province assignment and length of service</div>
                                    </div>
                                </div>
                                <v-divider></v-divider>
                                <div class="pa-5">
                                    <v-row dense>
                                        <v-col cols="12" sm="8">
                                            <v-autocomplete
                                                v-model="form.province"
                                                :items="provinces"
                                                item-title="name"
                                                item-value="id"
                                                label="Province"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                clearable
                                                :rules="[r.required]"
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="4">
                                            <v-text-field
                                                v-model="form.length_of_service"
                                                label="Length of Service (years)"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                min="0"
                                                placeholder="e.g. 5"
                                            />
                                        </v-col>
                                    </v-row>
                                </div>
                            </v-card>

                            <!-- Employee-only fields -->
                            <v-card v-if="form.role === 'employee'" class="elevation-1 border-0 rounded-md">
                                <div class="pa-5 pb-3 d-flex align-center gap-3">
                                    <v-avatar color="teal-lighten-5" rounded="lg" size="36">
                                        <v-icon color="teal" size="18">mdi-briefcase-outline</v-icon>
                                    </v-avatar>
                                    <div>
                                        <div class="text-subtitle-2 font-weight-bold">Employment Details</div>
                                        <div class="text-caption text-medium-emphasis">Position and employment status</div>
                                    </div>
                                </div>
                                <v-divider></v-divider>
                                <div class="pa-5">
                                    <v-row dense>
                                        <v-col cols="12" sm="8">
                                            <v-text-field
                                                v-model="form.position"
                                                label="Position / Designation"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                :rules="[r.required]"
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="4">
                                            <v-select
                                                v-model="form.status"
                                                :items="statusOptions"
                                                item-title="label"
                                                item-value="value"
                                                label="Employment Status"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                :rules="[r.required]"
                                            />
                                        </v-col>
                                    </v-row>
                                </div>
                            </v-card>

                            <!-- Account Credentials -->
                            <v-card class="elevation-1 border-0 rounded-md">
                                <div class="pa-5 pb-3 d-flex align-center gap-3">
                                    <v-avatar color="indigo-lighten-5" rounded="lg" size="36">
                                        <v-icon color="indigo" size="18">mdi-lock-outline</v-icon>
                                    </v-avatar>
                                    <div>
                                        <div class="text-subtitle-2 font-weight-bold">Account Credentials</div>
                                        <div class="text-caption text-medium-emphasis">Login information for the new account</div>
                                    </div>
                                </div>
                                <v-divider></v-divider>
                                <div class="pa-5">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <v-text-field
                                                v-model="form.dost_employee_id"
                                                label="DOST Employee ID"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                :rules="[r.required]"
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <v-text-field
                                                v-model="form.username"
                                                label="Username"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                :rules="[r.required]"
                                            />
                                        </v-col>
                                        <v-col cols="12">
                                            <v-text-field
                                                v-model="form.email"
                                                label="Email Address"
                                                type="email"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                :rules="[r.required, r.email]"
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <v-text-field
                                                v-model="form.password"
                                                label="Password"
                                                :type="showPassword ? 'text' : 'password'"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                :rules="[r.required, r.minLength(8)]"
                                                :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                                @click:append-inner="showPassword = !showPassword"
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <v-text-field
                                                v-model="form.password_confirmation"
                                                label="Confirm Password"
                                                :type="showConfirm ? 'text' : 'password'"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                :rules="[r.required, r.match(form.password)]"
                                                :append-inner-icon="showConfirm ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                                @click:append-inner="showConfirm = !showConfirm"
                                            />
                                        </v-col>
                                    </v-row>
                                </div>
                            </v-card>

                            <!-- Actions -->
                            <div class="d-flex justify-end gap-2">
                                <v-btn variant="text" color="medium-emphasis" size="small" @click="resetForm">Clear</v-btn>
                                <v-btn
                                    type="submit"
                                    color="primary"
                                    variant="tonal"
                                    size="small"
                                    prepend-icon="mdi-account-plus-outline"
                                    :loading="submitting"
                                >Register Account</v-btn>
                            </div>

                        </div>
                    </v-col>

                    <!-- Right: Summary -->
                    <v-col cols="12" md="4">
                        <div class="d-flex flex-column gap-4">

                            <!-- Preview card -->
                            <v-card class="elevation-1 border-0 rounded-md">
                                <div class="px-5 pt-4 pb-3">
                                    <div class="text-subtitle-2 font-weight-bold">Account Preview</div>
                                </div>
                                <v-divider></v-divider>
                                <div class="pa-4 d-flex flex-column gap-3">
                                    <div class="d-flex align-center gap-3">
                                        <v-avatar
                                            :color="form.role === 'provincial_director' ? 'blue-lighten-5' : 'teal-lighten-5'"
                                            rounded="lg"
                                            size="44"
                                        >
                                            <v-icon
                                                :color="form.role === 'provincial_director' ? 'blue' : 'teal'"
                                                size="22"
                                            >{{ form.role === 'provincial_director' ? 'mdi-account-tie-outline' : 'mdi-account-outline' }}</v-icon>
                                        </v-avatar>
                                        <div>
                                            <div class="text-body-2 font-weight-bold">
                                                {{ fullName || '- No name yet -' }}
                                            </div>
                                            <div class="text-caption text-medium-emphasis">
                                                {{ form.username || 'username' }}
                                            </div>
                                        </div>
                                    </div>

                                    <v-divider></v-divider>

                                    <div v-for="item in previewItems" :key="item.label" class="d-flex align-center justify-space-between">
                                        <span class="text-caption text-medium-emphasis">{{ item.label }}</span>
                                        <span class="text-caption font-weight-medium" :class="item.value ? '' : 'text-medium-emphasis'">
                                            {{ item.value || '-' }}
                                        </span>
                                    </div>
                                </div>
                            </v-card>

                            <!-- Checklist -->
                            <v-card class="elevation-1 border-0 rounded-md">
                                <div class="px-5 pt-4 pb-3">
                                    <div class="text-subtitle-2 font-weight-bold">Required Fields</div>
                                </div>
                                <v-divider></v-divider>
                                <div class="pa-4 d-flex flex-column gap-2">
                                    <div v-for="check in checklist" :key="check.label" class="d-flex align-center gap-2">
                                        <v-icon size="14" :color="check.done ? 'success' : 'grey-lighten-2'">
                                            {{ check.done ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                        </v-icon>
                                        <span class="text-caption" :class="check.done ? 'text-success' : 'text-medium-emphasis'">
                                            {{ check.label }}
                                        </span>
                                    </div>
                                </div>
                            </v-card>

                        </div>
                    </v-col>

                </v-row>
            </v-form>

        </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    provinces: { type: Array, default: () => [] },
});

const formRef    = ref(null);
const submitting = ref(false);
const showPassword = ref(false);
const showConfirm  = ref(false);

const defaultForm = () => ({
    registration_type:    'manual',
    role:                 'employee',
    province:             null,
    length_of_service:    '',
    prefix:               '',
    first_name:           '',
    middle_name:          '',
    last_name:            '',
    suffix:               '',
    position:             '',
    status:               '',
    dost_employee_id:     '',
    username:             '',
    email:                '',
    password:             '',
    password_confirmation:'',
});

const form = ref(defaultForm());

// ── Validation rules ──────────────────────────────────────────
const r = {
    required:            (v) => !!v || 'This field is required.',
    email:               (v) => /.+@.+\..+/.test(v) || 'Invalid email address.',
    minLength: (n) =>    (v) => (v && v.length >= n) || `Minimum ${n} characters.`,
    match:     (other) => (v) => v === other || 'Passwords do not match.',
};

// ── Options ───────────────────────────────────────────────────
const roleOptions = [
    { value: 'employee',            label: 'Employee',           hint: 'Provincial office staff',  icon: 'mdi-account-outline'     },
    { value: 'provincial_director', label: 'Provincial Director', hint: 'Director of the province', icon: 'mdi-account-tie-outline'  },
];

const prefixOptions = ['Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Engr.', 'Atty.'];

const statusOptions = [
    { label: 'Permanent', value: 'permanent' },
    { label: 'COS',       value: 'cos'       },
    { label: 'Job Order', value: 'jo'        },
];

// ── Computed ──────────────────────────────────────────────────
const fullName = computed(() => {
    const { prefix, first_name, middle_name, last_name, suffix } = form.value;
    return [prefix, first_name, middle_name, last_name, suffix].filter(Boolean).join(' ').trim();
});

const selectedProvinceName = computed(() =>
    props.provinces.find(p => p.id === form.value.province)?.name ?? null
);

const previewItems = computed(() => {
    const items = [
        { label: 'Role',             value: form.value.role === 'employee' ? 'Employee' : 'Provincial Director' },
        { label: 'Province',         value: selectedProvinceName.value },
        { label: 'Length of Service',value: form.value.length_of_service ? `${form.value.length_of_service} yr(s)` : null },
        { label: 'DOST ID',          value: form.value.dost_employee_id },
        { label: 'Email',            value: form.value.email },
    ];
    if (form.value.role === 'employee') {
        items.push({ label: 'Position', value: form.value.position });
        items.push({ label: 'Status',   value: form.value.status   });
    }
    return items;
});

const checklist = computed(() => {
    const base = [
        { label: 'Role selected',      done: !!form.value.role },
        { label: 'Province selected',  done: !!form.value.province },
        { label: 'First name filled',  done: !!form.value.first_name },
        { label: 'Last name filled',   done: !!form.value.last_name },
        { label: 'DOST ID filled',     done: !!form.value.dost_employee_id },
        { label: 'Username filled',    done: !!form.value.username },
        { label: 'Email filled',       done: !!form.value.email },
        { label: 'Password set',       done: form.value.password.length >= 8 },
        { label: 'Passwords match',    done: !!form.value.password && form.value.password === form.value.password_confirmation },
    ];
    if (form.value.role === 'employee') {
        base.push({ label: 'Position filled', done: !!form.value.position });
        base.push({ label: 'Status selected', done: !!form.value.status   });
    }
    return base;
});

// ── Submit ────────────────────────────────────────────────────
const submit = async () => {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    submitting.value = true;
    router.post('/users/store', form.value, {
        onSuccess: () => resetForm(),
        onError:   (errors) => console.error(errors),
        onFinish:  () => { submitting.value = false; },
    });
};

const resetForm = () => {
    form.value = defaultForm();
    formRef.value?.reset();
    showPassword.value = false;
    showConfirm.value  = false;
};
</script>

<style scoped>
.role-card {
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
}
</style>
