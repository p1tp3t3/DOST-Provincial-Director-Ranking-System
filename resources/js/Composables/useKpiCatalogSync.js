// Keeps an open screen in step with the shared KPI matrix.
//
// The catalog is editable at runtime on /kpi-catalog: a KPI can be renamed,
// moved between categories, re-weighted or removed, and every one of those
// changes shifts the category weights and the province scores derived from
// them. A screen that was already open when that happened would otherwise sit
// on stale labels and stale numbers until the user navigated away and back.
//
// KPICatalogController broadcasts KpiCatalogUpdated on every change; this
// subscribes to it and asks Inertia to re-fetch just the props that depend on
// the matrix. Authorization for the channel lives in routes/channels.php.

import { router } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';

/**
 * @param {string[]} only   Props to re-fetch. Always include the *scores* as
 *                          well as the categories — a weight change alters both,
 *                          and refreshing only the labels would leave the page
 *                          showing new percentages against old totals.
 * @param {object}  options
 * @param {() => boolean} options.canReload  Return false to skip a refresh, e.g.
 *                          while the screen holds unsaved edits that a reload
 *                          would discard.
 *
 * Returns useEcho()'s handle. No manual teardown needed — useEcho unsubscribes
 * on unmount and reference-counts the channel, so several screens sharing it
 * (or a page transition) can't pull it out from under each other.
 */
export const useKpiCatalogSync = (only, { canReload = () => true } = {}) =>
    useEcho('kpi-catalog', 'KpiCatalogUpdated', () => {
        if (!canReload()) return;
        router.reload({ only, preserveScroll: true, preserveState: true });
    });
