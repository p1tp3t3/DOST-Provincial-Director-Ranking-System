// Single front-end source of truth for presenting a KPI category.
//
// The matrix is editable at runtime: an admin can rename a category's KPIs,
// move a KPI between categories, and re-weight it, which shifts the category
// totals (they are the sum of their KPIs' weights). So nothing here hardcodes
// a split like 60/25/15 — every screen derives its labels, column headers and
// subtotal columns from the `kpi_categories` payload the controller sends.
//
// Backed by KPICategory::catalog() on the PHP side; see that method for the
// exact shape. Each entry is:
//   { id, code, name, weight /* 0..1 */, kpis: [...] }

import { computed, toValue } from 'vue';

// Visuals for the three categories the official PSTD matrix ships with. Kept
// as a lookup rather than stored per row so the palette stays consistent
// across screens, and so a category renamed in the DB keeps its colour.
const KNOWN_VISUALS = {
    CORE:      { color: 'indigo',      bg: '#eef2ff', hex: '#0b57a8', icon: 'mdi-rocket-launch-outline' },
    STRATEGIC: { color: 'teal',        bg: '#e0f2f1', hex: '#2079c4', icon: 'mdi-shield-outline' },
    SUPPORT:   { color: 'deep-purple', bg: '#ede7f6', hex: '#3f93e0', icon: 'mdi-cog-outline' },
};

// Cycled by position for any category an admin adds later, so a fourth or
// fifth category still gets a distinct, stable colour instead of falling back
// to the same grey as its neighbours.
const FALLBACK_VISUALS = [
    { color: 'blue',       bg: '#e3f2fd', hex: '#1e6fd9', icon: 'mdi-chart-bar' },
    { color: 'orange',     bg: '#fff3e0', hex: '#d97706', icon: 'mdi-chart-donut' },
    { color: 'pink',       bg: '#fce4ec', hex: '#be185d', icon: 'mdi-chart-arc' },
    { color: 'green',      bg: '#e8f5e9', hex: '#15803d', icon: 'mdi-leaf' },
    { color: 'blue-grey',  bg: '#eceff1', hex: '#64748b', icon: 'mdi-chart-box-outline' },
];

export const categoryVisual = (code, index = 0) =>
    KNOWN_VISUALS[code] ?? FALLBACK_VISUALS[index % FALLBACK_VISUALS.length];

/** 0.6 → '60%'. Keeps one decimal only when the weight actually needs it (0.075 → '7.5%'). */
export const formatWeight = (weight) => {
    const pct = (Number(weight) || 0) * 100;
    return `${Number.isInteger(pct) ? pct : Math.round(pct * 10) / 10}%`;
};

/**
 * Normalises the `kpi_categories` prop into everything a screen needs to label
 * a category: display name, uppercase code, weight as a percent string, and
 * the shared colours/icon.
 *
 * Accepts an array, a ref, or a getter (e.g. `() => props.kpi_categories`) so
 * it stays reactive when Inertia swaps the page props after a catalog edit.
 */
export const useKpiCategories = (source) => computed(() => {
    const list = toValue(source) ?? [];
    return list.map((cat, i) => {
        const visual = categoryVisual(cat.code, i);
        return {
            id:          cat.id,
            code:        cat.code,
            name:        cat.name,
            label:       cat.name,
            weight:      Number(cat.weight) || 0,
            weightLabel: formatWeight(cat.weight),
            // "CORE 60%" — the form used for table headers and legends.
            headerLabel: `${cat.code} ${formatWeight(cat.weight)}`,
            // Data-table column key for this category's subtotal. The `cat_`
            // prefix keeps it from colliding with a real field on a score row,
            // and it doubles as the `#[item.<key>]` slot name — held as a plain
            // property so the dynamic slot argument stays a simple expression.
            columnKey:   `cat_${cat.code}`,
            slotName:    `item.cat_${cat.code}`,
            kpiCount:    cat.kpis?.length ?? 0,
            ...visual,
        };
    });
});

/** Category codes in matrix order — the canonical order for subtotal columns. */
export const useCategoryCodes = (source) =>
    computed(() => (toValue(source) ?? []).map(c => c.code));

/**
 * Category filter options for a dashboard's "Overall / <category>" selector.
 * `withOverall` prepends the unfiltered option the dashboards default to.
 */
export const useCategoryOptions = (source, { withOverall = false } = {}) => {
    const cats = useKpiCategories(source);
    return computed(() => {
        const opts = cats.value.map(c => ({
            value:  c.code,
            label:  c.code,
            name:   c.name,
            weight: c.weightLabel,
        }));
        return withOverall
            ? [{ value: 'overall', label: 'Overall', name: 'Overall', weight: '100%' }, ...opts]
            : opts;
    });
};

/** A province row's subtotal for one category, as a percent. 0 when absent. */
export const subtotalPct = (row, code) => Number(row?.subtotals_pct?.[code] ?? 0);
