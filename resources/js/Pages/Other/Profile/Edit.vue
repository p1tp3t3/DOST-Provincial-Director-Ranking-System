<template>
    <Head title="Edit Profile" />
    <div class="d-flex flex-column gap-4" style="margin:0 auto;">

        <!-- Header -->
        <div class="d-flex align-center justify-space-between flex-wrap gap-2">
            <div>
                <div class="text-h6 font-weight-bold">Edit Profile</div>
                <div class="text-caption text-medium-emphasis">
                    {{ isRestricted ? 'Update your profile photo and education attainment' : isAdmin ? 'Update your name and profile photo' : 'Update your personal information and photo' }}
                </div>
            </div>
            <v-btn variant="text" size="small" color="medium-emphasis" prepend-icon="mdi-arrow-left"
                @click="goBack">Back to Profile</v-btn>
        </div>

        <!-- Success banner -->
        <v-alert
            v-if="flashSuccess"
            type="success"
            variant="tonal"
            density="compact"
            closable
            @click:close="flashSuccess = false"
        >{{ flashSuccess }}</v-alert>

        <v-row>
            <!-- Left: form -->
            <v-col cols="12" md="8">
                <v-form ref="formRef" @submit.prevent="submit">
                    <div class="d-flex flex-column gap-4">

                        <!-- Personal Information (admins only) -->
                        <v-card v-if="!isRestricted" class="elevation-1 border-0 rounded-md">
                            <div class="pa-5 pb-3 d-flex align-center gap-3">
                                <v-avatar color="indigo-lighten-5" rounded="lg" size="36">
                                    <v-icon color="indigo" size="18">mdi-account-outline</v-icon>
                                </v-avatar>
                                <div>
                                    <div class="text-subtitle-2 font-weight-bold">Personal Information</div>
                                    <div class="text-caption text-medium-emphasis">Your full name and title</div>
                                </div>
                            </div>
                            <v-divider />
                            <div class="pa-5">
                                <v-row dense>
                                    <v-col cols="12" sm="4">
                                        <v-select
                                            v-model="form.prefix"
                                            :items="prefixOptions"
                                            label="Prefix"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            clearable
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="4">
                                        <v-text-field
                                            v-model="form.first_name"
                                            label="First Name"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :rules="[rules.required]"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="4">
                                        <v-text-field
                                            v-model="form.middle_name"
                                            label="Middle Name"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="4">
                                        <v-text-field
                                            v-model="form.last_name"
                                            label="Last Name"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :rules="[rules.required]"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="4">
                                        <v-text-field
                                            v-model="form.suffix"
                                            label="Suffix"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            placeholder="Jr."
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="4">
                                        <v-text-field
                                            v-model="form.length_of_service"
                                            label="Length of Service (years)"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            type="number"
                                            min="0"
                                            placeholder="e.g. 5"
                                        />
                                    </v-col>
                                </v-row>
                            </div>
                        </v-card>

                        <!-- Employment Details (non-employees only) -->
                        <v-card v-if="false" class="elevation-1 border-0 rounded-md">
                            <div class="pa-5 pb-3 d-flex align-center gap-3">
                                <v-avatar color="teal-lighten-5" rounded="lg" size="36">
                                    <v-icon color="teal" size="18">mdi-briefcase-outline</v-icon>
                                </v-avatar>
                                <div>
                                    <div class="text-subtitle-2 font-weight-bold">Employment Details</div>
                                    <div class="text-caption text-medium-emphasis">Position and employment status</div>
                                </div>
                            </div>
                            <v-divider />
                            <div class="pa-5">
                                <v-row dense>
                                    <v-col cols="12" sm="8">
                                        <v-text-field
                                            v-model="form.position"
                                            label="Position / Designation"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="4">
                                        <v-select
                                            v-model="form.status"
                                            :items="statusOptions"
                                            item-title="label"
                                            item-value="value"
                                            label="Employment Status"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            clearable
                                        />
                                    </v-col>
                                </v-row>
                            </div>
                        </v-card>

                        <!-- Education Attainment (employee + director only) -->
                        <v-card v-if="isRestricted" class="elevation-1 border-0 rounded-md">
                            <div class="pa-5 pb-3 d-flex align-center gap-3">
                                <v-avatar color="purple-lighten-5" rounded="lg" size="36">
                                    <v-icon color="purple" size="18">mdi-school-outline</v-icon>
                                </v-avatar>
                                <div>
                                    <div class="text-subtitle-2 font-weight-bold">Education Attainment</div>
                                    <div class="text-caption text-medium-emphasis">Highest degrees obtained</div>
                                </div>
                            </div>
                            <v-divider />
                            <div class="pa-5">
                                <v-row dense>
                                    <v-col cols="12" v-for="(level, i) in educationLevels" :key="level">
                                        <v-text-field
                                            v-model="form.education[i]"
                                            :label="level"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :prepend-inner-icon="eduIcons[i]"
                                            :placeholder="`e.g. Bachelor of Science in ${i === 0 ? 'Computer Science' : i === 1 ? 'Information Technology' : 'Data Science'}`"
                                        />
                                    </v-col>
                                </v-row>
                            </div>
                        </v-card>

                        <!-- Actions -->
                        <div class="d-flex justify-end gap-2">
                            <v-btn variant="text" color="medium-emphasis" size="small" @click="resetForm">Reset</v-btn>
                            <v-btn
                                type="submit"
                                color="primary"
                                variant="tonal"
                                size="small"
                                prepend-icon="mdi-content-save-outline"
                                :loading="saving"
                            >Save Changes</v-btn>
                        </div>

                    </div>
                </v-form>
            </v-col>

            <!-- Right: picture + account info -->
            <v-col cols="12" md="4">
                <div class="d-flex flex-column gap-4">

                    <!-- Profile Picture -->
                    <v-card class="elevation-1 border-0 rounded-md">
                        <div class="px-5 pt-4 pb-3">
                            <div class="text-subtitle-2 font-weight-bold">Profile Photo</div>
                        </div>
                        <v-divider />
                        <div class="pa-5 d-flex flex-column align-center gap-4">
                            <div class="avatar-preview-wrap" :style="{ background: (!previewSrc && !props.profile_picture) ? `rgb(var(--v-theme-${avatarColor}))` : '#000' }">
                                <img
                                    v-if="previewSrc || props.profile_picture"
                                    :src="previewSrc || `/profile-picture?filename=${encodeURIComponent(props.profile_picture)}`"
                                    alt="Profile photo"
                                    class="avatar-preview-img"
                                />
                                <span v-else class="text-h4 font-weight-bold text-white">{{ initials }}</span>
                            </div>
                            <div class="text-center">
                                <v-btn size="small" variant="tonal" color="primary"
                                    prepend-icon="mdi-camera-outline" @click="triggerFileInput">
                                    Change Photo
                                </v-btn>
                                <div class="text-caption text-medium-emphasis mt-2">
                                    JPG, PNG or WebP · Max 3 MB
                                </div>
                            </div>
                            <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp"
                                class="d-none" @change="onFileSelected" />
                        </div>
                    </v-card>

                    <!-- Account Info (read-only) -->
                    <v-card class="elevation-1 border-0 rounded-md">
                        <div class="px-5 pt-4 pb-3">
                            <div class="text-subtitle-2 font-weight-bold">Account Info</div>
                        </div>
                        <v-divider />
                        <div class="pa-4 d-flex flex-column gap-3">
                            <div v-for="item in accountItems" :key="item.label" class="d-flex align-center gap-2">
                                <v-icon size="15" color="medium-emphasis">{{ item.icon }}</v-icon>
                                <div>
                                    <div class="text-caption text-medium-emphasis" style="font-size:10px; text-transform:uppercase; letter-spacing:.04em;">{{ item.label }}</div>
                                    <div class="text-body-2 font-weight-medium">{{ item.value || '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </v-card>

                </div>
            </v-col>
        </v-row>

        <!-- ── Cropper Dialog ───────────────────────────────────── -->
        <v-dialog v-model="cropperOpen" max-width="520" persistent>
            <v-card>
                <div class="pa-4 pb-2 d-flex align-center justify-space-between">
                    <div class="text-subtitle-2 font-weight-bold">Crop Profile Photo</div>
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
                            aspect-ratio="1"
                            initial-coverage="0.8"
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
                <div class="pa-4 d-flex align-center justify-space-between">
                    <div class="text-caption text-medium-emphasis">Drag to reposition · Handles to resize</div>
                    <div class="d-flex gap-2">
                        <v-btn variant="text" color="medium-emphasis" size="small" @click="cancelCrop">Cancel</v-btn>
                        <v-btn color="primary" variant="flat" size="small" :loading="uploading" @click="applyCrop">
                            <v-icon start size="16">mdi-crop</v-icon>
                            Apply & Upload
                        </v-btn>
                    </div>
                </div>
            </v-card>
        </v-dialog>

    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import 'cropperjs';

const props = defineProps({
    role:               { type: String, default: '' },
    username:           { type: String, default: '' },
    email:              { type: String, default: '' },
    province:           { type: String, default: null },
    profile_picture:    { type: String, default: null },
    prefix:             { type: String, default: '' },
    first_name:         { type: String, default: '' },
    middle_name:        { type: String, default: '' },
    last_name:          { type: String, default: '' },
    suffix:             { type: String, default: '' },
    length_of_service:  { type: String, default: '' },
    education:          { type: Array,  default: () => ['', '', ''] },
    position:           { type: String, default: '' },
    status:             { type: String, default: '' },
});

const page       = usePage();
const isRestricted = computed(() => ['employee', 'provincial_director'].includes(props.role));
const isAdmin      = computed(() => ['super_admin', 'sub_admin', 'provincial_admin'].includes(props.role));
const formRef    = ref(null);
const fileInput  = ref(null);
const saving     = ref(false);
const flashSuccess = ref('');

// Watch for Inertia flash
watch(() => page.props.flash, (flash) => {
    if (flash?.success) flashSuccess.value = flash.success;
}, { deep: true, immediate: true });

// ── Form state ────────────────────────────────────────────────
const form = ref({
    prefix:             props.prefix,
    first_name:         props.first_name,
    middle_name:        props.middle_name,
    last_name:          props.last_name,
    suffix:             props.suffix,
    length_of_service:  props.length_of_service,
    education:          [...props.education],
    position:           props.position,
    status:             props.status,
});

const resetForm = () => {
    form.value = {
        prefix:             props.prefix,
        first_name:         props.first_name,
        middle_name:        props.middle_name,
        last_name:          props.last_name,
        suffix:             props.suffix,
        length_of_service:  props.length_of_service,
        education:          [...props.education],
        position:           props.position,
        status:             props.status,
    };
    formRef.value?.resetValidation();
};

// ── Submit ────────────────────────────────────────────────────
const submit = async () => {
    const { valid } = await formRef.value.validate();
    if (!valid) return;
    saving.value = true;
    router.patch(route('profile.update'), form.value, {
        onFinish: () => { saving.value = false; },
    });
};

// ── Cropper state ─────────────────────────────────────────────
const cropperOpen      = ref(false);
const cropSrc          = ref('');
const cropCanvasRef    = ref(null);
const cropImageRef     = ref(null);
const cropSelectionRef = ref(null);
const previewSrc       = ref('');
const uploading        = ref(false);

const triggerFileInput = () => fileInput.value?.click();

// ── Boundary constraint ───────────────────────────────────────
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

        // Clamp within image bounds
        const nx   = Math.max(bx, x);
        const ny   = Math.max(by, y);
        const nw   = Math.min(width,  bx + bw - nx);
        const nh   = Math.min(height, by + bh - ny);
        const side = Math.min(nw, nh);                 // keep 1:1 square
        const fx   = Math.min(nx, bx + bw - side);
        const fy   = Math.min(ny, by + bh - side);

        if (fx !== x || fy !== y || side !== width || side !== height) {
            e.preventDefault();
            _constraining = true;
            sel.$change(fx, fy, side, side);
            _constraining = false;
        }
    };

    sel.addEventListener('change', _boundaryHandler);
};

const detachBoundaryConstraint = () => {
    const sel = cropSelectionRef.value;
    if (sel && _boundaryHandler) {
        sel.removeEventListener('change', _boundaryHandler);
        _boundaryHandler = null;
    }
};

const recenterCropper = async () => {
    await nextTick();
    await new Promise(r => setTimeout(r, 150));
    const img    = cropImageRef.value;
    const canvas = cropCanvasRef.value;
    if (!img || !canvas) return;

    // Show the full image inside the container
    img.$center('contain');

    // Wait for the layout to settle, then read the image's rendered bounds
    await new Promise(r => setTimeout(r, 80));
    const canvasRect = canvas.getBoundingClientRect();
    const imgRect    = img.getBoundingClientRect();

    const bounds = {
        x:      imgRect.left   - canvasRect.left,
        y:      imgRect.top    - canvasRect.top,
        width:  imgRect.width,
        height: imgRect.height,
    };

    attachBoundaryConstraint(bounds);
};

const onFileSelected = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    e.target.value = '';

    const reader = new FileReader();
    reader.onload = (ev) => {
        cropSrc.value     = ev.target.result;
        cropperOpen.value = true;
        recenterCropper();
    };
    reader.readAsDataURL(file);
};

const cancelCrop = () => {
    detachBoundaryConstraint();
    cropperOpen.value = false;
    cropSrc.value     = '';
};

const applyCrop = async () => {
    const selEl = cropSelectionRef.value;
    if (!selEl) return;

    uploading.value = true;
    try {
        const canvas = await selEl.$toCanvas({ width: 300, height: 300 });
        canvas.toBlob((blob) => {
            if (!blob) { uploading.value = false; return; }

            // Show locally immediately
            previewSrc.value = URL.createObjectURL(blob);
            detachBoundaryConstraint();
            cropperOpen.value = false;
            cropSrc.value     = '';

            // Upload to server
            const fd = new FormData();
            fd.append('picture', blob, 'profile.jpg');
            router.post(route('profile.picture.update'), fd, {
                onFinish: () => { uploading.value = false; },
            });
        }, 'image/jpeg', 0.92);
    } catch {
        uploading.value = false;
    }
};

// ── Display helpers ───────────────────────────────────────────
const fullName = computed(() =>
    [props.prefix, props.first_name, props.middle_name, props.last_name, props.suffix]
        .filter(Boolean).join(' ').trim() || props.username
);

const initials = computed(() =>
    (props.first_name?.[0] ?? '') + (props.last_name?.[0] ?? '') || props.username?.[0]?.toUpperCase() || '?'
);

const palette = ['primary','indigo','deep-purple','teal','blue-darken-2','cyan-darken-2'];
const avatarColor = computed(() => {
    const seed = [...(props.username ?? '')].reduce((a, c) => a + c.charCodeAt(0), 0);
    return palette[seed % palette.length];
});

const roleLabels = {
    super_admin: 'Super Admin', sub_admin: 'Sub Admin',
    provincial_admin: 'Provincial Admin',
    provincial_director: 'Provincial Director', employee: 'Employee',
};

const accountItems = computed(() => [
    { label: 'Username', value: props.username,               icon: 'mdi-account-outline'      },
    { label: 'Email',    value: props.email,                  icon: 'mdi-email-outline'         },
    { label: 'Role',     value: roleLabels[props.role] ?? props.role, icon: 'mdi-shield-account-outline' },
    { label: 'Province', value: props.province,               icon: 'mdi-map-marker-outline'    },
]);

const goBack = () => {
    const userId = page.props.auth?.user?.id;
    if (userId) router.visit(`/profile/${userId}`);
    else window.history.back();
};

// ── Options ───────────────────────────────────────────────────
const prefixOptions    = ['Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Engr.', 'Atty.'];
const statusOptions    = [
    { label: 'Permanent', value: 'permanent' },
    { label: 'COS',       value: 'cos'       },
    { label: 'Job Order', value: 'jo'        },
];
const educationLevels  = ['Bachelor\'s Degree', 'Master\'s Degree', 'Doctorate'];
const eduIcons         = ['mdi-school-outline', 'mdi-certificate-outline', 'mdi-star-circle-outline'];
const rules            = { required: (v) => !!v || 'This field is required.' };
</script>

<style scoped>
.avatar-preview-wrap {
    width: 96px;
    height: 96px;
    border-radius: 12px;
    overflow: hidden;
    border: 3px solid rgba(var(--v-theme-primary), 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.avatar-preview-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.cropper-wrap {
    width: 100%;
    height: 360px;
    overflow: hidden;
    background: #111;
}
</style>

<style>
/* Override CropperJS v2 web-component theme — these inherit through shadow DOM */
cropper-selection {
    --theme-color: rgba(255, 255, 255, 0.9);
}
cropper-handle {
    --theme-color: rgba(255, 255, 255, 0.9);
}
cropper-grid {
    --border-color: rgba(255, 255, 255, 0.4);
}
cropper-crosshair {
    --color: rgba(255, 255, 255, 0.6);
}
</style>
