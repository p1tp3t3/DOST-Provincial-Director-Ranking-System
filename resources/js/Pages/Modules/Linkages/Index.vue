<template>
    <Head :title="`Linkages — ${director.name}`" />
    <div class="d-flex flex-column gap-3">

        <!-- Breadcrumb -->
        <div class="d-flex align-center gap-2">
            <v-btn icon size="x-small" variant="text" color="medium-emphasis" @click="goBack">
                <v-icon size="18">mdi-arrow-left</v-icon>
            </v-btn>
            <span class="text-caption text-medium-emphasis cursor-pointer" @click="goBack">KPI Editor</span>
            <v-icon size="12" color="medium-emphasis">mdi-chevron-right</v-icon>
            <span class="text-caption font-weight-medium">Linkages ({{ year }})</span>
        </div>

        <!-- Header card -->
        <v-card border elevation="0" rounded="lg" class="overflow-hidden">
            <div class="d-flex flex-wrap align-center">
                <div class="pa-4 d-flex align-center gap-3 flex-grow-1">
                    <v-avatar color="teal" size="44" rounded="lg">
                        <v-icon color="white" size="22">mdi-handshake-outline</v-icon>
                    </v-avatar>
                    <div>
                        <div class="text-h6 font-weight-bold">Linkages (MOA / MOU)</div>
                        <div class="d-flex align-center gap-2 mt-1">
                            <v-chip color="teal" size="x-small" variant="tonal" class="font-weight-medium">
                                Backs: Number of Linkages established
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
                        <div class="text-h5 font-weight-bold text-teal">{{ linkages.length }}</div>
                    </div>
                    <v-divider vertical />
                    <v-btn color="teal" size="small" prepend-icon="mdi-plus" @click="openCreate">
                        Add Linkage
                    </v-btn>
                </div>
            </div>
            <v-divider />
            <div class="px-4 py-2 d-flex align-center gap-3 flex-wrap" style="background:#f0fdfa;">
                <v-icon size="14" color="teal">mdi-shield-check-outline</v-icon>
                <span class="text-caption text-medium-emphasis">
                    The KPI accomplishment is locked. It updates automatically as records are added or removed here.
                </span>
            </div>
        </v-card>

        <!-- Linkages table -->
        <v-card border elevation="0" rounded="lg">
            <v-table density="compact" class="link-table">
                <thead>
                    <tr>
                        <th class="kpi-th" style="width:36px;">#</th>
                        <th class="kpi-th">Partner Organization</th>
                        <th class="kpi-th">Title</th>
                        <th class="kpi-th" style="width:80px; text-align:center;">Type</th>
                        <th class="kpi-th" style="width:120px;">Date Signed</th>
                        <th class="kpi-th">Signatories</th>
                        <th class="kpi-th" style="width:100px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="linkages.length === 0">
                        <td colspan="7" class="text-center py-8 text-caption text-medium-emphasis">
                            No linkages recorded for {{ year }} yet. Click <strong>Add Linkage</strong> to begin.
                        </td>
                    </tr>
                    <tr v-for="(row, idx) in linkages" :key="row.id" class="link-row">
                        <td class="text-caption text-medium-emphasis text-center">{{ idx + 1 }}</td>
                        <td class="text-body-2 font-weight-medium">{{ row.partner_organization }}</td>
                        <td class="text-body-2">{{ row.title }}</td>
                        <td class="text-center">
                            <v-chip
                                :color="row.type === 'MOA' ? 'indigo' : 'teal'"
                                size="x-small"
                                variant="tonal"
                                class="font-weight-medium"
                            >{{ row.type }}</v-chip>
                        </td>
                        <td class="text-body-2 text-caption">{{ formatDate(row.date_signed) }}</td>
                        <td class="text-body-2 text-caption text-medium-emphasis">{{ row.signatories || '—' }}</td>
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
                    <v-icon color="teal">mdi-handshake-outline</v-icon>
                    {{ dialog.mode === 'create' ? 'Add Linkage' : 'Edit Linkage' }}
                </v-card-title>
                <v-divider />
                <v-card-text class="pt-4">
                    <div class="d-flex flex-column gap-3">
                        <v-text-field
                            v-model="form.partner_organization"
                            label="Partner Organization *"
                            placeholder="e.g. DepEd Region IV-A"
                            density="compact"
                            variant="outlined"
                            hide-details="auto"
                            :error-messages="form.errors.partner_organization"
                        />
                        <v-text-field
                            v-model="form.title"
                            label="MOA / MOU Title *"
                            placeholder="e.g. Joint Implementation of S&T Scholarship Program"
                            density="compact"
                            variant="outlined"
                            hide-details="auto"
                            :error-messages="form.errors.title"
                        />
                        <div class="d-flex gap-3">
                            <v-select
                                v-model="form.type"
                                :items="['MOA', 'MOU']"
                                label="Type *"
                                density="compact"
                                variant="outlined"
                                hide-details="auto"
                                style="max-width:140px;"
                                :error-messages="form.errors.type"
                            />
                            <v-text-field
                                v-model="form.date_signed"
                                label="Date Signed *"
                                type="date"
                                density="compact"
                                variant="outlined"
                                hide-details="auto"
                                :error-messages="form.errors.date_signed"
                            />
                        </div>
                        <v-textarea
                            v-model="form.signatories"
                            label="Signatories"
                            placeholder="Names of signatories, one per line"
                            density="compact"
                            variant="outlined"
                            rows="2"
                            auto-grow
                            hide-details="auto"
                            :error-messages="form.errors.signatories"
                        />
                        <v-textarea
                            v-model="form.remarks"
                            label="Remarks / Notes"
                            placeholder="Optional context about the linkage"
                            density="compact"
                            variant="outlined"
                            rows="2"
                            auto-grow
                            hide-details="auto"
                            :error-messages="form.errors.remarks"
                        />
                    </div>
                </v-card-text>
                <v-divider />
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" :disabled="form.processing" @click="closeDialog">Cancel</v-btn>
                    <v-btn
                        color="teal"
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
                    Remove linkage?
                </v-card-title>
                <v-card-text>
                    <div class="text-body-2 mb-2">
                        This will permanently remove the linkage and decrement the KPI count by 1.
                    </div>
                    <div v-if="deleteDialog.row" class="pa-3" style="background:#f8fafc; border-radius:6px;">
                        <div class="text-body-2 font-weight-medium">{{ deleteDialog.row.partner_organization }}</div>
                        <div class="text-caption text-medium-emphasis">{{ deleteDialog.row.title }}</div>
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
import { ref, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    director:   { type: Object, required: true },
    year:       { type: Number, required: true },
    linkages:   { type: Array,  default: () => [] },
    count:      { type: Number, default: 0 },
    return_url: { type: String, default: '/kpi-data' },
});

const dialog = reactive({ open: false, mode: 'create', editingId: null });
const deleteDialog = reactive({ open: false, row: null });

const form = useForm({
    partner_organization: '',
    title: '',
    type: 'MOA',
    date_signed: '',
    signatories: '',
    remarks: '',
});

const openCreate = () => {
    dialog.mode = 'create';
    dialog.editingId = null;
    form.reset();
    form.clearErrors();
    form.type = 'MOA';
    dialog.open = true;
};

const openEdit = (row) => {
    dialog.mode = 'edit';
    dialog.editingId = row.id;
    form.clearErrors();
    form.partner_organization = row.partner_organization;
    form.title = row.title;
    form.type = row.type;
    form.date_signed = row.date_signed;
    form.signatories = row.signatories || '';
    form.remarks = row.remarks || '';
    dialog.open = true;
};

const closeDialog = () => {
    dialog.open = false;
};

const submit = () => {
    const base = `/linkages/${props.director.id}/${props.year}`;
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
    router.delete(`/linkages/${props.director.id}/${props.year}/${deleteDialog.row.id}`, {
        preserveScroll: true,
        onFinish: () => { deleteDialog.open = false; deleteDialog.row = null; },
    });
};

const goBack = () => {
    router.visit(props.return_url);
};

const formatDate = (s) => {
    if (!s) return '—';
    const d = new Date(s);
    if (isNaN(d)) return s;
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};
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
.link-row td {
    padding: 8px 12px !important;
    border-bottom: 1px solid rgba(var(--v-border-color), 0.4) !important;
    vertical-align: middle;
}
</style>
