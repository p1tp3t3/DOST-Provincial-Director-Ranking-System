<template>
    <Head title="Province" />

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

        <v-form @submit.prevent="askConfirm">
            <v-row dense>

                <!-- Left column: description -->
                <v-col cols="12" md="8">
                    <v-card class="elevation-1 rounded-md pa-5 d-flex flex-column gap-4">

                        <RichTextEditor
                            v-model="form.description"
                            label="Description"
                            simple
                            placeholder="Tell visitors about this province — its focus areas, achievements, or what makes it distinct."
                            :error-messages="form.errors.description"
                            hint="Shown under the province's name on the public landing page"
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
                                v-model="pickedFile"
                                label="Cover Image (optional)"
                                variant="outlined"
                                density="comfortable"
                                accept="image/*"
                                prepend-icon=""
                                prepend-inner-icon="mdi-image-outline"
                                :error-messages="form.errors.image"
                                hint="Max 4 MB — JPG, PNG, GIF, WebP. You can crop it after choosing."
                                persistent-hint
                                clearable
                                @update:model-value="onFileChange"
                            />

                            <!-- Same 2:1 shape as the cover on the landing page -->
                            <div class="cover-preview mt-2 rounded overflow-hidden">
                                <img :src="displayedCover" :alt="showingDefault ? 'Default cover' : 'Cover image'" />
                                <span v-if="showingDefault" class="cover-badge">Default picture</span>
                            </div>

                            <div v-if="form.remove_image" class="text-caption text-warning mt-1">
                                Picture will be removed when you click Save Changes.
                            </div>

                            <div class="d-flex justify-end flex-wrap gap-1 mt-1">
                                <v-btn v-if="previewUrl && cropSource" size="x-small" variant="text" color="primary" prepend-icon="mdi-crop" @click="openCropper">
                                    Adjust crop
                                </v-btn>
                                <v-btn v-if="hasCover" size="x-small" variant="text" color="error" prepend-icon="mdi-trash-can-outline" @click="removePicture">
                                    Remove picture
                                </v-btn>
                                <v-btn v-if="form.remove_image" size="x-small" variant="text" color="medium-emphasis" prepend-icon="mdi-undo" @click="form.remove_image = false">
                                    Undo
                                </v-btn>
                            </div>
                        </div>

                    </v-card>
                </v-col>

            </v-row>
        </v-form>

        <!-- ── Confirm save ────────────────────────────────────── -->
        <v-dialog v-model="confirmOpen" max-width="440">
            <v-card rounded="lg">
                <v-card-title class="pa-5 pb-3 text-h6 font-weight-bold">Save Province Info?</v-card-title>
                <v-card-text class="px-5 pb-2 text-grey-darken-1">
                    These changes will show on the public landing page for {{ province.name }}:
                    <ul class="mt-2 pl-5">
                        <li>Description</li>
                        <li v-if="form.image">New cover picture</li>
                        <li v-else-if="form.remove_image">Cover picture removed (the default picture will show)</li>
                    </ul>
                </v-card-text>
                <v-card-actions class="pa-5 pt-3">
                    <v-spacer />
                    <v-btn variant="text" color="medium-emphasis" :disabled="form.processing" @click="confirmOpen = false">Cancel</v-btn>
                    <v-btn color="primary" variant="flat" :loading="form.processing" @click="submit">Save Changes</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Toast -->
        <v-snackbar v-model="snack.show" :color="snack.color" timeout="3000" location="top right">
            {{ snack.text }}
        </v-snackbar>

        <!-- ── Crop dialog ─────────────────────────────────────── -->
        <v-dialog v-model="cropperOpen" max-width="720" persistent>
            <v-card>
                <div class="pa-4 pb-2 d-flex align-center justify-space-between">
                    <div>
                        <div class="text-subtitle-2 font-weight-bold">Crop Cover Image</div>
                        <div class="text-caption text-medium-emphasis">Wide 2:1 frame — the shape shown on the landing page</div>
                    </div>
                    <v-btn icon size="small" variant="text" color="medium-emphasis" @click="cancelCrop">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </div>
                <v-divider />

                <div class="cropper-wrap">
                    <cropper-canvas
                        ref="cropCanvasRef"
                        v-show="cropSrc"
                        style="display:block; width:100%; height:100%;"
                    >
                        <cropper-image
                            ref="cropImageRef"
                            :src="cropSrc"
                            alt="Crop preview"
                            rotatable scalable skewable translatable
                        />
                        <cropper-shade />
                        <cropper-handle action="select" plain />
                        <cropper-selection
                            ref="cropSelectionRef"
                            :aspect-ratio="COVER_RATIO"
                            initial-coverage="0.9"
                            movable resizable zoomable keyboard
                            outlined
                        >
                            <cropper-grid role="grid" bordered />
                            <cropper-crosshair centered />
                            <cropper-handle action="move"      plain />
                            <cropper-handle action="n-resize"  theme-color="rgba(255,255,255,0.9)" />
                            <cropper-handle action="e-resize"  theme-color="rgba(255,255,255,0.9)" />
                            <cropper-handle action="s-resize"  theme-color="rgba(255,255,255,0.9)" />
                            <cropper-handle action="w-resize"  theme-color="rgba(255,255,255,0.9)" />
                            <cropper-handle action="ne-resize" theme-color="rgba(255,255,255,0.9)" />
                            <cropper-handle action="nw-resize" theme-color="rgba(255,255,255,0.9)" />
                            <cropper-handle action="se-resize" theme-color="rgba(255,255,255,0.9)" />
                            <cropper-handle action="sw-resize" theme-color="rgba(255,255,255,0.9)" />
                        </cropper-selection>
                    </cropper-canvas>
                </div>

                <v-divider />
                <div class="pa-4 d-flex align-center justify-space-between flex-wrap gap-2">
                    <div class="text-caption text-medium-emphasis">Drag to reposition · Handles to resize</div>
                    <div class="d-flex gap-2">
                        <v-btn variant="text" color="medium-emphasis" size="small" @click="cancelCrop">Cancel</v-btn>
                        <v-btn color="primary" variant="flat" size="small" :loading="cropping" @click="applyCrop">
                            <v-icon start size="16">mdi-crop</v-icon>
                            Apply Crop
                        </v-btn>
                    </div>
                </div>
            </v-card>
        </v-dialog>

    </div>
</template>

<script setup>
import { ref, reactive, computed, nextTick, onUnmounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import 'cropperjs';
import RichTextEditor from '@/Components/Other/RichTextEditor/RichTextEditor.vue';

const props = defineProps({
    province: { type: Object, required: true },
});

const form = useForm({
    _method:     'put',
    description:  props.province.description ?? '',
    image:        null,
    remove_image: false, // delete the saved cover → landing page shows the default
});

// The landing page shows the cover in a wide panel (~360×175), so crop to 2:1
// and save at 1200×600.
const COVER_RATIO  = 2;
const COVER_WIDTH  = 1200;
const COVER_HEIGHT = 600;

const pickedFile  = ref(null);  // what the file input shows
const previewUrl  = ref(null);  // the cropped image about to be saved
const cropSource  = ref('');    // original image (data URL), kept for "Adjust crop"
let   cropName    = 'cover';
let   newPick     = false;      // cropper opened for a just-chosen file (vs "Adjust crop")

// Shown when there's no cover (same default as the landing page).
const DEFAULT_COVER = '/assets/province-cover-default.svg';

// What the cover will look like after saving: a new crop, else the saved
// cover (unless it's being removed), else the default.
const displayedCover = computed(() =>
    previewUrl.value
    || (!form.remove_image && props.province.image_url)
    || DEFAULT_COVER
);
const showingDefault = computed(() => displayedCover.value === DEFAULT_COVER);
const hasCover       = computed(() => !showingDefault.value);

// Drop a new (unsaved) picture and mark the saved one for removal.
const dropPendingPick = () => {
    revokePreview();
    pickedFile.value = null;
    cropSource.value = '';
    form.image       = null;
};
const removePicture = () => {
    dropPendingPick();
    if (props.province.image_url) form.remove_image = true;
};

const revokePreview = () => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = null;
};

const onFileChange = (files) => {
    const file = Array.isArray(files) ? files[0] : files;
    if (!(file instanceof File)) {
        // File box cleared: drop the new picture, keep the saved cover.
        dropPendingPick();
        return;
    }

    cropName = file.name.replace(/\.[^.]+$/, '') || 'cover';
    const reader = new FileReader();
    reader.onload = (ev) => {
        cropSource.value = ev.target.result;
        newPick = true;
        openCropper();
    };
    reader.readAsDataURL(file);
};

// ── Cropper (CropperJS v2 web components, as on the profile page) ────────
const cropperOpen      = ref(false);
const cropSrc          = ref('');
const cropping         = ref(false);
const cropCanvasRef    = ref(null);
const cropImageRef     = ref(null);
const cropSelectionRef = ref(null);

// Keep the selection inside the image and at 2:1.
let _constraining    = false;
let _boundaryHandler = null;

const attachBoundaryConstraint = (bounds) => {
    const sel = cropSelectionRef.value;
    if (!sel) return;
    if (_boundaryHandler) sel.removeEventListener('change', _boundaryHandler);

    _boundaryHandler = (e) => {
        if (_constraining) return;
        const { x, y, width, height } = e.detail;
        const { x: bx, y: by, width: bw, height: bh } = bounds;

        const nx = Math.max(bx, x);
        const ny = Math.max(by, y);
        let   w  = Math.min(width,  bx + bw - nx);
        let   h  = Math.min(height, by + bh - ny);
        // Shrink to the largest 2:1 box that fits
        if (w / h > COVER_RATIO) w = h * COVER_RATIO; else h = w / COVER_RATIO;
        const fx = Math.min(nx, bx + bw - w);
        const fy = Math.min(ny, by + bh - h);

        if (fx !== x || fy !== y || w !== width || h !== height) {
            e.preventDefault();
            _constraining = true;
            sel.$change(fx, fy, w, h);
            _constraining = false;
        }
    };
    sel.addEventListener('change', _boundaryHandler);
};

const detachBoundaryConstraint = () => {
    const sel = cropSelectionRef.value;
    if (sel && _boundaryHandler) sel.removeEventListener('change', _boundaryHandler);
    _boundaryHandler = null;
};

const openCropper = async () => {
    if (!cropSource.value) return;
    cropSrc.value     = cropSource.value;
    cropperOpen.value = true;

    await nextTick();
    await new Promise(r => setTimeout(r, 150));
    const img    = cropImageRef.value;
    const canvas = cropCanvasRef.value;
    if (!img || !canvas) return;

    img.$center('contain'); // whole image visible

    await new Promise(r => setTimeout(r, 80));
    const canvasRect = canvas.getBoundingClientRect();
    const imgRect    = img.getBoundingClientRect();
    attachBoundaryConstraint({
        x:      imgRect.left - canvasRect.left,
        y:      imgRect.top  - canvasRect.top,
        width:  imgRect.width,
        height: imgRect.height,
    });
};

const closeCropper = () => {
    detachBoundaryConstraint();
    cropperOpen.value = false;
    cropSrc.value     = '';
};

const cancelCrop = () => {
    closeCropper();
    // Cancelling a just-chosen image drops it (and any earlier crop), so what
    // the file box shows always matches what gets saved. Cancelling
    // "Adjust crop" keeps the current crop.
    if (newPick) dropPendingPick();
    newPick = false;
};

const applyCrop = async () => {
    const sel = cropSelectionRef.value;
    if (!sel) return;

    cropping.value = true;
    try {
        const cropped = await sel.$toCanvas({ width: COVER_WIDTH, height: COVER_HEIGHT });

        // White background so transparent PNGs don't turn black as JPEG.
        const out = document.createElement('canvas');
        out.width  = COVER_WIDTH;
        out.height = COVER_HEIGHT;
        const ctx = out.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, COVER_WIDTH, COVER_HEIGHT);
        ctx.drawImage(cropped, 0, 0);

        const blob = await new Promise(resolve => out.toBlob(resolve, 'image/jpeg', 0.9));
        if (!blob) return;

        revokePreview();
        form.image        = new File([blob], `${cropName}-cover.jpg`, { type: 'image/jpeg' });
        form.remove_image = false; // a new picture replaces the old one
        previewUrl.value  = URL.createObjectURL(blob);
        newPick           = false;
        closeCropper();
        toast('Cover picture ready — click Save Changes to upload it.', 'info');
    } finally {
        cropping.value = false;
    }
};

onUnmounted(() => {
    revokePreview();
    detachBoundaryConstraint();
});

// ── Save: confirm first, then a toast with the result ───────────────────
const confirmOpen = ref(false);
const snack       = reactive({ show: false, text: '', color: 'success' });
const toast       = (text, color = 'success') => Object.assign(snack, { show: true, text, color });

const askConfirm = () => { confirmOpen.value = true; };

const submit = () => {
    // What this save does, for the toast (the form is reset on success).
    const uploaded = !!form.image;
    const removed  = !uploaded && form.remove_image;

    form.post('/province-info', {
        preserveScroll: true,
        // Keep this page instance (Inertia re-creates it by default), so the
        // toast set in onSuccess is the one on screen.
        preserveState:  true,
        onSuccess: () => {
            confirmOpen.value = false;
            // Saved: the page now shows what the server has.
            dropPendingPick();
            form.remove_image = false;
            toast(uploaded ? 'Cover picture uploaded and province info saved.'
                : removed  ? 'Cover picture removed and province info saved.'
                :            'Province info updated.');
        },
        onError: () => {
            confirmOpen.value = false;
            toast('Could not save. Check the fields and try again.', 'error');
        },
    });
};
</script>

<style scoped>
.cover-preview {
    position: relative;
    aspect-ratio: 2 / 1;
    background: #f1f5f9;
}
.cover-badge {
    position: absolute;
    left: 8px;
    bottom: 8px;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    color: #0a3f7d;
    background: rgba(255, 255, 255, 0.85);
}
.cover-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.cropper-wrap {
    width: 100%;
    height: 420px;
    overflow: hidden;
    background: #111;
}
</style>

<style>
/* CropperJS v2 web-component theme (inherits through shadow DOM) */
cropper-selection { --theme-color: rgba(255, 255, 255, 0.9); }
cropper-handle    { --theme-color: rgba(255, 255, 255, 0.9); }
cropper-grid      { --border-color: rgba(255, 255, 255, 0.4); }
cropper-crosshair { --color: rgba(255, 255, 255, 0.6); }
</style>
