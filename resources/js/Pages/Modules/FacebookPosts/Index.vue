<template>
    <Head :title="`Facebook Posts - ${director.name}`" />
    <div class="d-flex flex-column gap-3">

        <!-- Breadcrumb -->
        <div class="d-flex align-center gap-2">
            <v-btn icon size="x-small" variant="text" color="medium-emphasis" @click="goBack">
                <v-icon size="18">mdi-arrow-left</v-icon>
            </v-btn>
            <span class="text-caption text-medium-emphasis cursor-pointer" @click="goBack">KPI Editor</span>
            <v-icon size="12" color="medium-emphasis">mdi-chevron-right</v-icon>
            <span class="text-caption font-weight-medium">Facebook Posts ({{ year }})</span>
        </div>

        <!-- Header card -->
        <v-card border elevation="0" rounded="lg" class="overflow-hidden">
            <div class="d-flex flex-wrap align-center">
                <div class="pa-4 d-flex align-center gap-3 flex-grow-1">
                    <v-avatar color="blue" size="44" rounded="lg">
                        <v-icon color="white" size="22">mdi-facebook</v-icon>
                    </v-avatar>
                    <div>
                        <div class="text-h6 font-weight-bold">Facebook Posts</div>
                        <div class="d-flex align-center gap-2 mt-1">
                            <v-chip color="blue" size="x-small" variant="tonal" class="font-weight-medium">
                                Backs: Number of Facebook Posts Submitted
                            </v-chip>
                            <span class="text-caption text-medium-emphasis">·</span>
                            <span class="text-caption text-medium-emphasis">{{ director.name }} · {{ year }}</span>
                        </div>
                    </div>
                </div>

                <v-divider vertical />

                <div class="pa-4 d-flex align-center gap-3">
                    <div class="text-center">
                        <div class="filter-label mb-1">Auto Count</div>
                        <div class="text-h5 font-weight-bold text-blue">{{ posts.length }}</div>
                    </div>
                    <v-divider vertical />
                    <v-btn color="blue" size="small" prepend-icon="mdi-plus" @click="openCreate">
                        Add Post
                    </v-btn>
                </div>
            </div>
            <v-divider />
            <div class="px-4 py-2 d-flex align-center gap-3 flex-wrap" style="background:#eff6ff;">
                <v-icon size="14" color="blue">mdi-shield-check-outline</v-icon>
                <span class="text-caption text-medium-emphasis">
                    The KPI accomplishment is locked. It updates automatically as posts are added or removed here.
                </span>
            </div>
        </v-card>

        <!-- Posts table -->
        <v-card border elevation="0" rounded="lg">
            <v-table density="compact" class="fb-table">
                <thead>
                    <tr>
                        <th class="kpi-th" style="width:36px;">#</th>
                        <th class="kpi-th">Title</th>
                        <th class="kpi-th" style="width:90px; text-align:center;">Type</th>
                        <th class="kpi-th" style="width:120px;">Date Posted</th>
                        <th class="kpi-th">Post URL</th>
                        <th class="kpi-th" style="width:100px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="posts.length === 0">
                        <td colspan="6" class="text-center py-8 text-caption text-medium-emphasis">
                            No Facebook posts recorded for {{ year }} yet. Click <strong>Add Post</strong> to begin.
                        </td>
                    </tr>
                    <tr v-for="(row, idx) in posts" :key="row.id" class="fb-row">
                        <td class="text-caption text-medium-emphasis text-center">{{ idx + 1 }}</td>
                        <td class="text-body-2 font-weight-medium">{{ row.title }}</td>
                        <td class="text-center">
                            <v-chip
                                :color="typeColor(row.post_type)"
                                size="x-small"
                                variant="tonal"
                                class="font-weight-medium"
                            >
                                <v-icon size="12" start>{{ typeIcon(row.post_type) }}</v-icon>
                                {{ row.post_type }}
                            </v-chip>
                        </td>
                        <td class="text-body-2 text-caption">{{ formatDate(row.date_posted) }}</td>
                        <td class="text-body-2 text-caption">
                            <a v-if="row.post_url" :href="row.post_url" target="_blank" rel="noopener" class="text-blue text-decoration-none">
                                <v-icon size="12" class="me-1">mdi-open-in-new</v-icon>
                                {{ truncateUrl(row.post_url) }}
                            </a>
                            <span v-else class="text-medium-emphasis">-</span>
                        </td>
                        <td class="text-right">
                            <v-btn
                                icon
                                size="x-small"
                                variant="text"
                                color="indigo"
                                @click="openEdit(row)"
                            ><v-icon size="16">mdi-pencil</v-icon></v-btn>
                            <v-btn
                                icon
                                size="x-small"
                                variant="text"
                                color="error"
                                @click="confirmDelete(row)"
                            ><v-icon size="16">mdi-delete-outline</v-icon></v-btn>
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>

        <!-- Create / Edit modal -->
        <v-dialog v-model="dialog.open" max-width="540" persistent>
            <v-card rounded="lg">
                <v-card-title class="d-flex align-center gap-2">
                    <v-icon color="blue">mdi-facebook</v-icon>
                    {{ dialog.mode === 'create' ? 'Add Facebook Post' : 'Edit Facebook Post' }}
                </v-card-title>
                <v-divider />
                <v-card-text class="pt-4">
                    <div class="d-flex flex-column gap-3">
                        <v-text-field
                            v-model="form.title"
                            label="Title / Headline *"
                            placeholder="e.g. SETUP success story: AgriTech beneficiary"
                            density="compact"
                            variant="outlined"
                            hide-details="auto"
                            :error-messages="form.errors.title"
                        />
                        <div class="d-flex gap-3">
                            <v-select
                                v-model="form.post_type"
                                :items="['Text', 'Photo', 'Video', 'Link', 'Event']"
                                label="Type *"
                                density="compact"
                                variant="outlined"
                                hide-details="auto"
                                style="max-width:180px;"
                                :error-messages="form.errors.post_type"
                            />
                            <v-text-field
                                v-model="form.date_posted"
                                label="Date Posted *"
                                type="date"
                                density="compact"
                                variant="outlined"
                                hide-details="auto"
                                :error-messages="form.errors.date_posted"
                            />
                        </div>
                        <v-text-field
                            v-model="form.post_url"
                            label="Post URL"
                            placeholder="https://facebook.com/..."
                            density="compact"
                            variant="outlined"
                            hide-details="auto"
                            :error-messages="form.errors.post_url"
                        />
                        <v-textarea
                            v-model="form.caption"
                            label="Caption / Notes"
                            placeholder="Optional copy of the post caption"
                            density="compact"
                            variant="outlined"
                            rows="3"
                            auto-grow
                            hide-details="auto"
                            :error-messages="form.errors.caption"
                        />
                    </div>
                </v-card-text>
                <v-divider />
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" :disabled="form.processing" @click="closeDialog">Cancel</v-btn>
                    <v-btn
                        color="blue"
                        :loading="form.processing"
                        prepend-icon="mdi-content-save"
                        @click="submit"
                    >{{ dialog.mode === 'create' ? 'Add' : 'Save Changes' }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete confirm -->
        <v-dialog v-model="deleteDialog.open" max-width="420">
            <v-card rounded="lg">
                <v-card-title class="d-flex align-center gap-2">
                    <v-icon color="error">mdi-alert-circle-outline</v-icon>
                    Remove post?
                </v-card-title>
                <v-card-text>
                    <div class="text-body-2 mb-2">
                        This will permanently remove the post and decrement the KPI count by 1.
                    </div>
                    <div v-if="deleteDialog.row" class="pa-3" style="background:#f8fafc; border-radius:6px;">
                        <div class="text-body-2 font-weight-medium">{{ deleteDialog.row.title }}</div>
                        <div class="text-caption text-medium-emphasis">{{ deleteDialog.row.post_type }} · {{ formatDate(deleteDialog.row.date_posted) }}</div>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog.open = false">Cancel</v-btn>
                    <v-btn color="error" prepend-icon="mdi-delete" @click="doDelete">Remove</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    director:   { type: Object, required: true },
    year:       { type: Number, required: true },
    posts:      { type: Array,  default: () => [] },
    count:      { type: Number, default: 0 },
    return_url: { type: String, default: '/kpi-data' },
});

const dialog = reactive({ open: false, mode: 'create', editingId: null });
const deleteDialog = reactive({ open: false, row: null });

const form = useForm({
    title: '',
    post_url: '',
    post_type: 'Text',
    date_posted: '',
    caption: '',
});

const openCreate = () => {
    dialog.mode = 'create';
    dialog.editingId = null;
    form.reset();
    form.clearErrors();
    form.post_type = 'Text';
    dialog.open = true;
};

const openEdit = (row) => {
    dialog.mode = 'edit';
    dialog.editingId = row.id;
    form.clearErrors();
    form.title = row.title;
    form.post_url = row.post_url || '';
    form.post_type = row.post_type;
    form.date_posted = row.date_posted;
    form.caption = row.caption || '';
    dialog.open = true;
};

const closeDialog = () => { dialog.open = false; };

const submit = () => {
    const base = `/facebook-posts/${props.director.id}/${props.year}`;
    if (dialog.mode === 'create') {
        form.post(base, {
            preserveScroll: true,
            onSuccess: () => { dialog.open = false; },
        });
    } else {
        form.put(`${base}/${dialog.editingId}`, {
            preserveScroll: true,
            onSuccess: () => { dialog.open = false; },
        });
    }
};

const confirmDelete = (row) => {
    deleteDialog.row = row;
    deleteDialog.open = true;
};

const doDelete = () => {
    if (!deleteDialog.row) return;
    router.delete(`/facebook-posts/${props.director.id}/${props.year}/${deleteDialog.row.id}`, {
        preserveScroll: true,
        onFinish: () => { deleteDialog.open = false; deleteDialog.row = null; },
    });
};

const goBack = () => { router.visit(props.return_url); };

const formatDate = (s) => {
    if (!s) return '-';
    const d = new Date(s);
    if (isNaN(d)) return s;
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

const truncateUrl = (url) => {
    if (!url) return '';
    return url.length > 50 ? url.slice(0, 47) + '...' : url;
};

const typeColor = (t) => ({
    Text: 'blue-grey', Photo: 'teal', Video: 'deep-purple', Link: 'indigo', Event: 'orange',
}[t] || 'blue-grey');

const typeIcon = (t) => ({
    Text: 'mdi-text', Photo: 'mdi-image-outline', Video: 'mdi-play-circle-outline',
    Link: 'mdi-link-variant', Event: 'mdi-calendar-outline',
}[t] || 'mdi-text');
</script>

<style scoped>
.filter-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}
.kpi-th {
    font-size: 0.65rem !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(var(--v-theme-on-surface), 0.5) !important;
    background: rgba(var(--v-theme-surface-variant), 0.4) !important;
    padding: 8px 12px !important;
    white-space: nowrap;
}
.fb-row td {
    padding: 8px 12px !important;
    border-bottom: 1px solid rgba(var(--v-border-color), 0.4) !important;
    vertical-align: middle;
}
</style>
