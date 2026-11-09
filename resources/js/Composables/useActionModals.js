// App-wide confirmation + success/error dialogs for submission forms.
//
// One <ActionModals> lives in AuthenticatedLayout (a persistent layout), so a
// result dialog opened in a save's onSuccess survives the page being re-created
// by the Inertia visit. Pages just do:
//
//   const modals = useActionModals();
//   if (!(await modals.confirm({ title: 'Save changes?', message: '…' }))) return;
//   router.put(url, data, {
//       onSuccess: () => modals.success('Saved', '…'),
//       onError:   (errors) => modals.error('Could not save', errors),
//   });
//   // axios/fetch: catch (e) { modals.errorFrom('Could not save', e); }
//
// Outside the layout (no dialogs mounted) it falls back to the browser's
// confirm/alert so nothing silently breaks.

import { shallowRef } from 'vue';

const instance = shallowRef(null);

// Toasts asked for while no layout is mounted (e.g. "Welcome back" from the
// login page) wait here and show as soon as the layout's dialogs register.
const pendingToasts = [];

// Used as a function ref in AuthenticatedLayout.
export const registerActionModals = (component) => {
    instance.value = component ?? null;
    if (instance.value) {
        while (pendingToasts.length) instance.value.toast(...pendingToasts.shift());
    }
};

export function useActionModals() {
    const flatten = (errors) =>
        !errors ? [] : typeof errors === 'string' ? [errors]
            : Array.isArray(errors) ? errors.flat() : Object.values(errors).flat();

    return {
        confirm: (opts = {}) =>
            instance.value
                ? instance.value.confirm(opts)
                : Promise.resolve(window.confirm([opts.title, opts.message].filter(Boolean).join('\n\n'))),
        success: (title, message = '') =>
            instance.value ? instance.value.success(title, message) : window.alert([title, message].filter(Boolean).join('\n')),
        error: (title, errors = null, message = '') =>
            instance.value ? instance.value.error(title, errors, message)
                : window.alert([title, message, ...flatten(errors)].filter(Boolean).join('\n')),
        // Short non-blocking notice; queued until the layout is mounted.
        toast: (text, color = 'success', timeout = 3500) =>
            instance.value ? instance.value.toast(text, color, timeout) : pendingToasts.push([text, color, timeout]),
        errorFrom: (title, err, fallback) =>
            instance.value ? instance.value.errorFrom(title, err, fallback)
                : window.alert([title, err?.response?.data?.message ?? fallback].filter(Boolean).join('\n')),
    };
}
