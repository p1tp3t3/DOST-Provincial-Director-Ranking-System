<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

// Horizontal score bars, one row per province, on a fixed 0..max scale. The
// value is printed in dark text just past the end of each bar - never inside
// it - so short or light-coloured bars stay readable. Lists given the same
// `max` share one scale, so bars placed side by side compare directly.
const props = defineProps({
    // [{ key?, name, value, color, light?, director?, performer? }]
    // `light` turns the fill into a color -> light gradient.
    items:      { type: Array,  default: () => [] },
    max:        { type: Number, default: 100 },
    valueLabel: { type: String, default: 'weighted score' },
    nameWidth:  { type: Number, default: 150 },
    // The smallest row. The list fills its parent's height: a short list
    // stretches its rows and bars to fill it, a long one keeps this height and
    // scrolls.
    rowHeight:  { type: Number, default: 30 },
});

const PLOT_PAD = 8;    // .sb-plot's top + bottom padding
const MAX_BAR  = 80;   // a list of one or two still reads as bars, not blocks

// Grow the bars in from zero once, like the charts these replaced.
const grown = ref(false);
const scrollEl   = ref(null);
const viewHeight = ref(0);
let observer = null;
onMounted(() => {
    requestAnimationFrame(() => { grown.value = true; });
    if (typeof ResizeObserver !== 'undefined' && scrollEl.value) {
        observer = new ResizeObserver(([entry]) => { viewHeight.value = entry.contentRect.height; });
        observer.observe(scrollEl.value);
    }
});
onUnmounted(() => observer?.disconnect());

const rowH = computed(() => {
    const n = props.items.length;
    if (!n || !viewHeight.value) return props.rowHeight;
    return Math.max(props.rowHeight, (viewHeight.value - PLOT_PAD) / n);
});
const sizing = computed(() => ({
    '--name-w':     `${props.nameWidth}px`,
    '--row-h':      `${rowH.value}px`,
    '--bar-h':      `${Math.min(MAX_BAR, Math.round(rowH.value * 0.56))}px`,   // 18px at 32, 54px at 96
    '--value-size': rowH.value > 48 ? '13px' : '12px',
}));

const scale = computed(() => (props.max > 0 ? props.max : 100));

// 3-5 evenly spaced ticks on round numbers: 100 -> 0/25/50/75/100, 25 -> 0/5/../25.
const ticks = computed(() => {
    const max  = scale.value;
    const nice = (step) => Math.abs(step * 10 - Math.round(step * 10)) < 1e-9;
    const n    = [4, 5, 3].find(k => nice(max / k)) ?? 4;
    return Array.from({ length: n + 1 }, (_, i) => ({ value: (max / n) * i, pct: (i / n) * 100 }));
});

const pct       = (v) => Math.max(0, Math.min(100, ((Number(v) || 0) / scale.value) * 100));
const fill      = (item) => (item.light ? `linear-gradient(90deg, ${item.color}, ${item.light})` : item.color);
const tickLabel = (v) => `${Number.isInteger(v) ? v : Number(v.toFixed(1))}%`;
</script>

<template>
    <div class="sb" :style="sizing">
        <div ref="scrollEl" class="sb-scroll">
            <div class="sb-plot">
                <!-- Gridlines sit behind the rows and run the full height -->
                <div class="sb-grid" aria-hidden="true">
                    <span v-for="t in ticks" :key="t.pct" class="sb-gridline" :class="{ 'sb-gridline--base': t.pct === 0 }" :style="{ left: `${t.pct}%` }" />
                </div>

                <v-tooltip v-for="item in items" :key="item.key ?? item.name" location="top" :open-delay="60">
                    <template #activator="{ props: tip }">
                        <div v-bind="tip" class="sb-row">
                            <span class="sb-name">{{ item.name }}</span>
                            <div class="sb-track">
                                <div class="sb-bar" :style="{ width: `${grown ? pct(item.value) : 0}%`, background: fill(item) }" />
                                <span class="sb-value" :style="{ left: `${grown ? pct(item.value) : 0}%` }">{{ (Number(item.value) || 0).toFixed(1) }}%</span>
                            </div>
                        </div>
                    </template>
                    <div class="sb-tip">
                        <div class="sb-tip-name">{{ item.name }}</div>
                        <div v-if="item.director" class="sb-tip-meta">Director: {{ item.director }}</div>
                        <div v-if="item.performer" class="sb-tip-meta">Performer: {{ item.performer }}</div>
                        <div class="sb-tip-score">
                            <span class="sb-tip-dot" :style="{ background: item.color }" />
                            <strong>{{ (Number(item.value) || 0).toFixed(2) }}%</strong>&nbsp;{{ valueLabel }}
                        </div>
                    </div>
                </v-tooltip>
            </div>
        </div>

        <div class="sb-axis" aria-hidden="true">
            <span></span>
            <div class="sb-axis-track">
                <span v-for="t in ticks" :key="t.pct" class="sb-tick" :style="{ left: `${t.pct}%` }">{{ tickLabel(t.value) }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Room past the 100% mark for the value label of a full-length bar. */
.sb { --gap: 10px; --label-pad: 52px; display: flex; flex-direction: column; flex: 1 1 auto; height: 100%; min-height: 0; }

/* Scroll area and axis reserve the same scrollbar gutter, so the bars, the
   gridlines and the tick labels stay lined up whether or not a list scrolls. */
.sb-scroll {
    flex: 1 1 auto; min-height: 0;
    display: flex; flex-direction: column;
    overflow-y: auto; overflow-x: hidden;
    scrollbar-gutter: stable; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;
}
.sb-plot { position: relative; flex: 1 0 auto; padding: 2px 0 6px; }

.sb-grid {
    position: absolute; top: 0; bottom: 0;
    left: calc(var(--name-w) + var(--gap)); right: var(--label-pad);
    pointer-events: none;
}
.sb-gridline { position: absolute; top: 0; bottom: 0; width: 1px; background: #eef1f5; transform: translateX(-0.5px); }
.sb-gridline--base { background: #cbd5e1; }

.sb-row {
    position: relative;
    display: grid; grid-template-columns: var(--name-w) minmax(0, 1fr); column-gap: var(--gap); align-items: center;
    height: var(--row-h); padding-right: var(--label-pad);
    border-radius: 6px; cursor: default;
}
.sb-row:hover { background: rgba(99, 102, 241, 0.06); }
.sb-row:hover .sb-name { color: #0f172a; }

.sb-name {
    font-size: 13px; color: #475569; text-align: right;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.sb-track { position: relative; height: 100%; }
.sb-bar {
    position: absolute; left: 0; top: 50%; transform: translateY(-50%);
    height: var(--bar-h); min-width: 2px; border-radius: 0 4px 4px 0;
    transition: width .6s cubic-bezier(.2, .7, .3, 1), height .2s ease;
}
.sb-value {
    position: absolute; top: 50%; transform: translate(6px, -50%);
    font-size: var(--value-size, 12px); font-weight: 600; color: #1e293b; white-space: nowrap;
    font-variant-numeric: tabular-nums;
    transition: left .6s cubic-bezier(.2, .7, .3, 1);
}

.sb-axis {
    flex: 0 0 auto;
    display: grid; grid-template-columns: var(--name-w) minmax(0, 1fr); column-gap: var(--gap);
    padding-right: var(--label-pad);
    overflow: hidden; scrollbar-gutter: stable; scrollbar-width: thin;
    border-top: 1px solid #f1f5f9;
}
.sb-axis-track { position: relative; height: 22px; }
.sb-tick {
    position: absolute; top: 4px; transform: translateX(-50%);
    font-size: 11px; color: #94a3b8; white-space: nowrap; font-variant-numeric: tabular-nums;
}

.sb-tip { font-size: 12px; line-height: 1.5; min-width: 180px; }
.sb-tip-name { font-weight: 700; margin-bottom: 2px; }
.sb-tip-meta { opacity: .8; }
.sb-tip-score { display: flex; align-items: center; gap: 6px; margin-top: 4px; }
.sb-tip-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 0 1.5px rgba(255, 255, 255, .85); }
</style>
