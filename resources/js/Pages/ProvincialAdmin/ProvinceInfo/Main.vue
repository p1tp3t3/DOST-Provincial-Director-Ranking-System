<template>
    <Head title="Province Info" />

    <div class="grid gap-4 w-full">

        <!-- Header -->
        <v-card class="border-0 elevation-1 bg-white rounded-md">
            <div class="d-flex align-center justify-space-between py-3 px-5 flex-wrap gap-2">
                <div>
                    <div class="text-subtitle-1 font-weight-bold text-grey-darken-3">Province Info</div>
                    <div class="text-caption text-grey">
                        What visitors see about {{ province.name }} on the public landing page
                    </div>
                </div>
            </div>
        </v-card>

        <v-form @submit.prevent="submit">
            <v-row dense>

                <!-- Left column: description -->
                <v-col cols="12" md="8">
                    <v-card class="elevation-1 rounded-md pa-5 d-flex flex-column gap-4">

                        <v-textarea
                            v-model="form.description"
                            label="Description"
                            variant="outlined"
                            density="comfortable"
                            rows="6"
                            auto-grow
                            counter="2000"
                            maxlength="2000"
                            :error-messages="form.errors.description"
                            hint="Shown under the province's name on the public landing page"
                            persistent-hint
                            placeholder="Tell visitors about this province — its focus areas, achievements, or what makes it distinct."
                        />

                        <div class="d-flex justify-end">
                            <v-btn
                                color="primary"
                                variant="flat"
                                type="submit"
                                :loading="form.processing"
                            >Save Changes</v-btn>
                        </div>

                    </v-card>
                </v-col>

                <!-- Right column: identity + image -->
                <v-col cols="12" md="4">
                    <v-card class="elevation-1 rounded-md pa-5 d-flex flex-column gap-4">

                        <div>
                            <div class="text-caption text-grey-darken-1 mb-1">Province</div>
                            <div class="text-subtitle-2 font-weight-bold">{{ province.name }}</div>
                            <div class="text-caption text-grey">{{ province.region }} · {{ province.category }} classification</div>
                        </div>

                        <v-divider />

                        <div>
                            <v-file-input
                                ref="fileInput"
                                label="Cover Image (optional)"
                                variant="outlined"
                                density="comfortable"
                                accept="image/*"
                                prepend-icon=""
                                prepend-inner-icon="mdi-image-outline"
                                :error-messages="form.errors.image"
                                hint="Max 4 MB — JPG, PNG, GIF, WebP"
                                persistent-hint
                                clearable
                                @update:model-value="onFileChange"
                            />

                            <div v-if="previewUrl" class="mt-2 rounded overflow-hidden" style="height:140px;">
                                <img :src="previewUrl" alt="Preview" style="width:100%;height:100%;object-fit:cover;" />
                            </div>
                            <div v-else-if="province.image_url && !fileCleared" class="mt-2 rounded overflow-hidden" style="height:140px;">
                                <img :src="province.image_url" alt="Current image" style="width:100%;height:100%;object-fit:cover;" />
                            </div>
                        </div>

                    </v-card>
                </v-col>

            </v-row>
        </v-form>

    </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    province: { type: Object, required: true },
});

const form = useForm({
    _method:     'put',
    description: props.province.description ?? '',
    image:       null,
});

const previewUrl  = ref(null);
const fileCleared = ref(false);

const onFileChange = (files) => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }

    const file = Array.isArray(files) ? files[0] : files;
    if (file instanceof File) {
        form.image = file;
        previewUrl.value = URL.createObjectURL(file);
        fileCleared.value = false;
    } else {
        form.image = null;
        fileCleared.value = true;
    }
};

onUnmounted(() => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
});

const submit = () => {
    form.post('/province-info');
};
</script>
