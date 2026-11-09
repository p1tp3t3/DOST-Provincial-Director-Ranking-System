<template>
    <!-- Confirmation -->
    <v-dialog v-model="confirmState.open" max-width="440" persistent>
        <v-card rounded="lg">
            <div class="pa-5 pb-3 d-flex align-start gap-3">
                <v-avatar :color="`${confirmState.color}-lighten-5`" size="40" rounded="lg" class="flex-shrink-0">
                    <v-icon :color="confirmState.color" size="22">{{ confirmState.icon }}</v-icon>
                </v-avatar>
                <div class="min-w-0">
                    <div class="text-subtitle-1 font-weight-bold">{{ confirmState.title }}</div>
                    <div class="text-body-2 text-medium-emphasis mt-1" style="white-space: pre-line;">{{ confirmState.message }}</div>
                </div>
            </div>
            <v-card-actions class="px-5 pb-4 pt-2">
                <v-spacer />
                <v-btn variant="text" color="medium-emphasis" @click="answer(false)">{{ confirmState.cancelText }}</v-btn>
                <v-btn :color="confirmState.color" variant="flat" @click="answer(true)">{{ confirmState.confirmText }}</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Toast (short, non-blocking notices) -->
    <v-snackbar v-model="toastState.show" :color="toastState.color" :timeout="toastState.timeout" location="top right">
        <div class="d-flex align-center gap-2">
            <v-icon size="20">{{ toastState.color === 'error' ? 'mdi-alert-circle-outline' : 'mdi-check-circle-outline' }}</v-icon>
            {{ toastState.text }}
        </div>
    </v-snackbar>

    <!-- Success / error result -->
    <v-dialog v-model="resultState.open" max-width="420">
        <v-card rounded="lg" class="text-center pa-6">
            <v-avatar :color="resultState.ok ? 'success-lighten-5' : 'error-lighten-5'" size="64" class="mx-auto">
                <v-icon :color="resultState.ok ? 'success' : 'error'" size="36">
                    {{ resultState.ok ? 'mdi-check-circle-outline' : 'mdi-alert-circle-outline' }}
                </v-icon>
            </v-avatar>
            <div class="text-h6 font-weight-bold mt-4">{{ resultState.title }}</div>
            <div v-if="resultState.message" class="text-body-2 text-medium-emphasis mt-2">{{ resultState.message }}</div>
            <ul v-if="resultState.details.length" class="text-body-2 text-error text-left mt-3 pl-5">
                <li v-for="(d, i) in resultState.details" :key="i">{{ d }}</li>
            </ul>
            <v-btn :color="resultState.ok ? 'success' : 'error'" variant="flat" class="mt-5" min-width="120" @click="resultState.open = false">OK</v-btn>
        </v-card>
    </v-dialog>
</template>

<script setup>
// Confirmation + success/error dialogs for save actions. Use through a ref:
//   const modals = ref(null);
//   if (!(await modals.value.confirm({ title, message }))) return;
//   modals.value.success('Saved', '…') / modals.value.error('Could not save', errors)
import { reactive } from 'vue';

const confirmState = reactive({
    open: false, title: '', message: '', confirmText: 'Confirm', cancelText: 'Cancel',
    color: 'primary', icon: 'mdi-help-circle-outline',
});
const resultState = reactive({ open: false, ok: true, title: '', message: '', details: [] });
const toastState  = reactive({ show: false, text: '', color: 'success', timeout: 3500 });

const toast = (text, color = 'success', timeout = 3500) => Object.assign(toastState, { show: true, text, color, timeout });

let resolver = null;

const confirm = (opts = {}) => {
    resolver?.(false); // a confirm left open is treated as cancelled
    Object.assign(confirmState, {
        title:       opts.title ?? 'Are you sure?',
        message:     opts.message ?? '',
        confirmText: opts.confirmText ?? 'Confirm',
        cancelText:  opts.cancelText ?? 'Cancel',
        color:       opts.color ?? 'primary',
        icon:        opts.icon ?? 'mdi-help-circle-outline',
        open:        true,
    });
    return new Promise((resolve) => { resolver = resolve; });
};

const answer = (yes) => {
    confirmState.open = false;
    resolver?.(yes);
    resolver = null;
};

const success = (title, message = '') =>
    Object.assign(resultState, { open: true, ok: true, title, message, details: [] });

// `errors` may be Inertia's validation errors object, an array or a string.
const error = (title, errors = null, message = '') => {
    const details = !errors ? []
        : typeof errors === 'string' ? [errors]
        : Array.isArray(errors) ? errors.flat()
        : Object.values(errors).flat();
    Object.assign(resultState, { open: true, ok: false, title, message, details: details.filter(Boolean) });
};

// Error dialog from a failed axios/fetch JSON response: field errors first,
// then the server's reason (code_error), then its general message.
const errorFrom = (title, err, fallback = 'Something went wrong. Please try again.') => {
    const data   = err?.response?.data ?? err?.data ?? {};
    const errors = data.errors ?? {};
    const fields = Object.entries(errors)
        .filter(([k]) => !['message', 'code_error'].includes(k))
        .flatMap(([, v]) => v);
    const details = fields.length ? fields
        : errors.code_error?.length ? errors.code_error
        : [data.message ?? (err?.message && !err?.response ? 'Network error — check your connection.' : fallback)];
    error(title, details);
};

defineExpose({ confirm, success, error, errorFrom, toast });
</script>
