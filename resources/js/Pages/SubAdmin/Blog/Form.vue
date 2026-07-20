<template>
    <Head :title="post ? 'Edit Post' : 'New Post'" />

    <div class="grid gap-4 w-full">

        <!-- Header -->
        <v-card class="border-0 elevation-1 bg-white rounded-md">
            <div class="d-flex align-center justify-space-between py-3 px-5 flex-wrap gap-2">
                <div>
                    <div class="text-subtitle-1 font-weight-bold text-grey-darken-3">
                        {{ post ? 'Edit Post' : 'New Post' }}
                    </div>
                    <div class="text-caption text-grey">
                        {{ post ? 'Update the article details below' : 'Fill in the details to create a new article' }}
                    </div>
                </div>
                <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.visit('/blogs')">
                    Back to Posts
                </v-btn>
            </div>
        </v-card>

        <!-- Form body -->
        <v-form @submit.prevent="submit">
            <v-row dense>

                <!-- Left column: content -->
                <v-col cols="12" md="8">
                    <v-card class="elevation-1 rounded-md pa-5 d-flex flex-column gap-4">

                        <!-- Title -->
                        <v-text-field
                            v-model="form.title"
                            label="Title *"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.title"
                            counter="255"
                            maxlength="255"
                        />

                        <!-- Excerpt -->
                        <v-textarea
                            v-model="form.excerpt"
                            label="Excerpt *"
                            variant="outlined"
                            density="comfortable"
                            rows="3"
                            auto-grow
                            counter="600"
                            maxlength="600"
                            :error-messages="form.errors.excerpt"
                            hint="Shown on the landing page card and at the top of the article"
                        />

                        <!-- Body paragraphs -->
                        <div>
                            <div class="text-caption font-weight-bold text-grey-darken-2 mb-2">
                                Body Paragraphs *
                                <span class="text-grey font-weight-regular">(each block becomes a paragraph)</span>
                            </div>
                            <div v-for="(_, i) in form.body" :key="i" class="d-flex gap-2 mb-2 align-start">
                                <v-textarea
                                    v-model="form.body[i]"
                                    :label="`Paragraph ${i + 1}`"
                                    variant="outlined"
                                    density="comfortable"
                                    rows="4"
                                    auto-grow
                                    hide-details
                                    :error-messages="form.errors[`body.${i}`]"
                                />
                                <v-btn
                                    v-if="form.body.length > 1"
                                    icon="mdi-minus-circle-outline"
                                    variant="text"
                                    color="error"
                                    size="small"
                                    class="mt-1 flex-shrink-0"
                                    @click="removePara(i)"
                                />
                            </div>
                            <v-btn
                                variant="tonal"
                                color="indigo"
                                size="small"
                                prepend-icon="mdi-plus"
                                class="mt-1"
                                @click="addPara"
                            >
                                Add Paragraph
                            </v-btn>
                            <div v-if="form.errors.body" class="text-caption text-error mt-1">
                                {{ form.errors.body }}
                            </div>
                        </div>

                    </v-card>
                </v-col>

                <!-- Right column: meta -->
                <v-col cols="12" md="4">
                    <v-card class="elevation-1 rounded-md pa-5 d-flex flex-column gap-4">

                        <!-- Publish toggle -->
                        <v-switch
                            v-model="form.is_published"
                            :label="form.is_published ? 'Published' : 'Draft'"
                            color="success"
                            hide-details
                            density="comfortable"
                        />

                        <!-- Tag -->
                        <v-select
                            v-model="form.tag"
                            :items="TAGS"
                            label="Tag *"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.tag"
                        />

                        <!-- Image upload -->
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

                            <!-- Preview -->
                            <div v-if="previewUrl" class="mt-2 rounded overflow-hidden" style="height:140px;">
                                <img
                                    :src="previewUrl"
                                    alt="Preview"
                                    style="width:100%;height:100%;object-fit:cover;"
                                />
                            </div>
                            <div v-else-if="post?.image_url && !fileCleared" class="mt-2 rounded overflow-hidden" style="height:140px;">
                                <img
                                    :src="post.image_url"
                                    alt="Current image"
                                    style="width:100%;height:100%;object-fit:cover;"
                                />
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex flex-column gap-2 mt-2">
                            <v-btn
                                color="indigo"
                                variant="flat"
                                type="submit"
                                :loading="form.processing"
                                block
                            >
                                {{ post ? 'Save Changes' : 'Create Post' }}
                            </v-btn>
                            <v-btn
                                variant="text"
                                block
                                @click="router.visit('/blogs')"
                            >
                                Cancel
                            </v-btn>
                        </div>

                    </v-card>
                </v-col>

            </v-row>
        </v-form>

    </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    post: { type: Object, default: null },
});

const TAGS = ['Announcement', 'Updates', 'Events', 'Other'];

const form = useForm({
    _method:      props.post ? 'put' : undefined,
    title:        props.post?.title        ?? '',
    tag:          props.post?.tag          ?? 'Announcement',
    excerpt:      props.post?.excerpt      ?? '',
    body:         props.post?.body?.length ? [...props.post.body] : [''],
    is_published: props.post?.is_published ?? true,
    image:        null,
});

// ── Image preview ─────────────────────────────────────────────────────────────
const previewUrl  = ref(null);
const fileCleared = ref(false);

const onFileChange = (files) => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }

    const file = Array.isArray(files) ? files[0] : files;
    if (file instanceof File) {
        form.image    = file;
        previewUrl.value = URL.createObjectURL(file);
        fileCleared.value = false;
    } else {
        form.image    = null;
        fileCleared.value = true;
    }
};

onUnmounted(() => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
});

// ── Body paragraph helpers ────────────────────────────────────────────────────
const addPara    = () => form.body.push('');
const removePara = (i) => form.body.splice(i, 1);

// ── Submit ────────────────────────────────────────────────────────────────────
const submit = () => {
    const url = props.post ? `/blogs/${props.post.id}` : '/blogs';
    form.post(url);
};
</script>
