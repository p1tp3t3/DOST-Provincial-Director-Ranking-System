<script setup>
import { RiFundsLine } from '@remixicon/vue';

defineProps({
    title: { type: String, required: true },
    quantity: { type: Number, required: true },
    icon: { type: Object, default: RiFundsLine },
    color: { type: String, default: 'indigo' }
});
</script>

<template>
    <v-card class="w-full border-0 elevation-1 rounded-md">
        <div :class="[`border-left-thick-${color}`]">
            <v-card-item class="py-5 px-5">
                <div class="d-flex align-center justify-space-between w-100">
                    <div>
                        <v-card-subtitle class="text-caption font-weight-bold text-uppercase tracking-wider text-grey mb-1 text-black">
                            {{ title }}
                        </v-card-subtitle>
                        <v-card-title class="text-h4 font-weight-black text-slate-800">
                            {{ quantity }}
                        </v-card-title>
                    </div>
                    <v-avatar :color="`${color}-lighten-5`" size="48" rounded="lg">
                        <component :is="icon" :class="[`text-${color}-darken-1`, 'w-6 h-6']" />
                    </v-avatar>
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
</style>
