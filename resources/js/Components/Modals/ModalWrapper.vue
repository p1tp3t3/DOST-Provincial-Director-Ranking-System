<template>
    <v-dialog
        :model-value="open"
        @update:model-value="$emit('update:open', $event)"
        :max-width="maxWidth"
        persistent
        scrollable
    >
        <v-card rounded="lg">

            <!-- Header -->
            <v-card-title class="px-5 pt-5 pb-3">
                <div class="d-flex align-center justify-space-between">
                    <div class="d-flex align-center gap-3">
                        <v-avatar color="primary-lighten-5" size="40" rounded="lg">
                            <v-icon color="primary" size="20">{{ icon }}</v-icon>
                        </v-avatar>
                        <div>
                            <div class="text-subtitle-2 font-weight-bold">{{ title }}</div>
                        </div>
                    </div>
                    <v-btn icon size="x-small" variant="text" color="medium-emphasis" @click="$emit('update:open', false)">
                        <v-icon size="18">mdi-close</v-icon>
                    </v-btn>
                </div>
            </v-card-title>

            <v-divider></v-divider>

            <!-- Body -->
            <v-card-text class="px-5 py-4" style="max-height: 70vh; overflow-y: auto;">
                <slot />
            </v-card-text>

            <v-divider></v-divider>

            <!-- Footer -->
            <v-card-actions class="px-5 py-3 gap-2 justify-end">
                <slot name="actions" />
            </v-card-actions>

        </v-card>
    </v-dialog>
</template>

<script setup>
defineProps({
    open:     { type: Boolean, default: false },
    title:    { type: String,  required: true  },
    subtitle: { type: String,  default: ''     },
    icon:     { type: String,  default: 'mdi-information-outline' },
    maxWidth: { type: [String, Number], default: 500 },
});

defineEmits(['update:open']);
</script>
