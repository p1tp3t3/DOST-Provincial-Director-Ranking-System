<script setup>
import { computed } from 'vue';
import { RiFundsLine } from '@remixicon/vue';

const props = defineProps({
    title: { type: String, required: true },
    quantity: { type: [Number, String], required: true },
    subtitle: { type: String, default: '' },
    icon: { type: Object, default: RiFundsLine },
    color: { type: String, default: 'indigo' },
    trend: { type: String, default: 'neutral' }, // 'up' | 'down' | 'neutral'
});

// Value text always stays neutral dark - the trend color belongs on the
// subtitle (where the ▲/▼ delta lives), not on the name itself. This way
// all four KPI cards present their headline name in the same visual register.
const valueClass = computed(() => 'text-slate-800');

// Only shrink to text-h5 for very long strings - most card values (Bukidnon,
// Medium, 63 of 81, Region IV-A) should render at the bigger text-h4 size so
// the name reads as the visual focus of the card.
const isLongString = computed(() =>
    typeof props.quantity === 'string' && props.quantity.length > 12
);

// Pill background uses the card's accent color regardless of trend, so the
// four KPI cards share a consistent indigo highlight (no green/red on the
// name itself).
const highlightStyle = computed(() => {
    const bgByColor = {
        indigo:  'rgba(63, 81, 181, 0.18)',
        success: 'rgba(76, 175, 80, 0.22)',
        danger:  'rgba(244, 67, 54, 0.22)',
    };
    return { background: bgByColor[props.color] ?? bgByColor.indigo };
});

// Trend color is applied ONLY to the leading ▲/▼ arrow in the subtitle -
// not the whole "▲ 49.2% vs. 2024" line. Split it out so the rest stays
// dark / default and only the arrow renders green (up) or red (down).
const subtitleArrow = computed(() => {
    const s = props.subtitle ?? '';
    if (s.startsWith('▲')) return '▲';
    if (s.startsWith('▼')) return '▼';
    return '';
});
const subtitleRest = computed(() =>
    subtitleArrow.value ? props.subtitle.slice(subtitleArrow.value.length) : props.subtitle
);
const arrowClass = computed(() => {
    if (props.trend === 'up')   return 'text-success';
    if (props.trend === 'down') return 'text-error';
    return '';
});
</script>

<template>
    <v-card class="w-full border-0 elevation-1 rounded-md">
        <div :class="[`border-left-thick-${color}`]">
            <v-card-item class="py-4 px-4">
                <div class="d-flex align-center ga-2 mb-2">
                    <v-avatar :color="`${color}-lighten-5`" size="36" rounded="lg" class="flex-shrink-0">
                        <component :is="icon" :class="[`text-${color}-darken-1`, 'w-5 h-5']" />
                    </v-avatar>
                    <div class="text-caption font-weight-bold text-uppercase tracking-wider text-grey-darken-1 qc-title">
                        {{ title }}
                    </div>
                </div>
                <!-- Value (left) and subtitle (right), pushed apart left-to-right.
                     flex-wrap lets the subtitle drop to a new line if the value
                     is too wide for the sm=3 column (e.g. long region names). -->
                <div class="d-flex align-baseline justify-space-between flex-wrap ga-2 qc-row">
                    <div
                        class="font-weight-black qc-value qc-highlight"
                        :class="[valueClass, isLongString ? 'text-h5' : 'text-h4']"
                        :style="highlightStyle"
                    >
                        {{ quantity }}
                    </div>
                    <div v-if="subtitle" class="text-caption font-weight-bold qc-subtitle">
                        <span v-if="subtitleArrow" :class="arrowClass">{{ subtitleArrow }}</span>{{ subtitleRest }}
                    </div>
                </div>
            </v-card-item>
        </div>
    </v-card>
</template>

<style scoped>
.border-left-thick-indigo { border-left: 5px solid #3f51b5 !important; }
.border-left-thick-success { border-left: 5px solid #4caf50 !important; }
.border-left-thick-danger { border-left: 5px solid #f44336 !important; }
.text-slate-800 { color: #1e293b !important; }
.qc-title { line-height: 1.2; }
.qc-row { width: 100%; row-gap: 4px; }
.qc-value { line-height: 1.2; word-break: break-word; }
.qc-highlight {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
}
.qc-subtitle { line-height: 1.2; }
</style>
