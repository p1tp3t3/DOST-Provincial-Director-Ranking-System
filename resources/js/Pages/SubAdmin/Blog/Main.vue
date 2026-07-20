<template>
    <Head title="Blog Posts" />
    <div class="grid gap-4 w-full">

        <!-- Header -->
        <v-card class="border-0 elevation-1 bg-white rounded-md">
            <div class="d-flex align-center justify-space-between py-3 px-5 flex-wrap gap-2">
                <div>
                    <div class="text-subtitle-1 font-weight-bold text-grey-darken-3">Blog Posts</div>
                    <div class="text-caption text-grey">Manage news &amp; updates shown on the landing page</div>
                </div>
                <v-btn color="indigo" prepend-icon="mdi-plus" @click="router.visit('/blogs/create')">
                    New Post
                </v-btn>
            </div>
        </v-card>

        <!-- Summary chips -->
        <div class="d-flex gap-3 flex-wrap">
            <v-chip color="indigo" variant="tonal" label>
                <v-icon start>mdi-newspaper-variant-outline</v-icon>
                {{ posts.length }} total
            </v-chip>
            <v-chip color="success" variant="tonal" label>
                <v-icon start>mdi-eye-outline</v-icon>
                {{ posts.filter(p => p.is_published).length }} published
            </v-chip>
            <v-chip color="warning" variant="tonal" label>
                <v-icon start>mdi-eye-off-outline</v-icon>
                {{ posts.filter(p => !p.is_published).length }} drafts
            </v-chip>
        </div>

        <!-- Posts table -->
        <v-card class="elevation-1 rounded-md">
            <v-table density="comfortable" hover>
                <thead>
                    <tr>
                        <th class="text-left font-weight-bold text-grey-darken-2" style="width:40%">Title</th>
                        <th class="text-left font-weight-bold text-grey-darken-2">Tag</th>
                        <th class="text-left font-weight-bold text-grey-darken-2">Status</th>
                        <th class="text-left font-weight-bold text-grey-darken-2">Date</th>
                        <th class="text-left font-weight-bold text-grey-darken-2">Created by</th>
                        <th class="text-right font-weight-bold text-grey-darken-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="posts.length === 0">
                        <td colspan="6" class="text-center text-grey py-8">
                            No blog posts yet. Click <strong>New Post</strong> to add the first one.
                        </td>
                    </tr>
                    <tr v-for="post in posts" :key="post.id">
                        <td>
                            <div class="font-weight-medium text-grey-darken-3 py-1" style="max-width:340px;white-space:normal;line-height:1.3;">
                                {{ post.title }}
                            </div>
                            <div class="text-caption text-grey text-truncate" style="max-width:340px;">
                                {{ post.excerpt }}
                            </div>
                        </td>
                        <td>
                            <v-chip :color="tagColor(post.tag)" size="small" label variant="tonal">
                                {{ post.tag }}
                            </v-chip>
                        </td>
                        <td>
                            <v-chip
                                :color="post.is_published ? 'success' : 'warning'"
                                size="small" label variant="tonal"
                            >
                                {{ post.is_published ? 'Published' : 'Draft' }}
                            </v-chip>
                        </td>
                        <td class="text-caption text-grey">{{ post.published_at || post.created_at }}</td>
                        <td class="text-caption text-grey">{{ post.creator }}</td>
                        <td class="text-right">
                            <v-btn
                                icon="mdi-pencil-outline"
                                size="small"
                                variant="text"
                                color="indigo"
                                @click="router.visit(`/blogs/${post.id}/edit`)"
                            />
                            <v-btn
                                icon="mdi-delete-outline"
                                size="small"
                                variant="text"
                                color="error"
                                @click="confirmDelete(post)"
                            />
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>

        <!-- Delete confirmation -->
        <v-dialog v-model="deleteDialog" max-width="420">
            <v-card rounded="lg">
                <v-card-title class="pa-5 pb-3 text-h6 font-weight-bold">Delete Post?</v-card-title>
                <v-card-text class="px-5 pb-2 text-grey-darken-1">
                    "<strong>{{ deleteTarget?.title }}</strong>" will be permanently removed from the landing page.
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" variant="flat" :loading="deleting" @click="doDelete">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snack.show" :color="snack.color" timeout="3000" location="top right">
            {{ snack.text }}
        </v-snackbar>

    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';

defineProps({
    posts: { type: Array, default: () => [] },
});

const TAGS = ['Announcement', 'Updates', 'Events', 'Other'];

const tagColor = (tag) => ({
    Announcement: 'indigo',
    Updates:      'teal',
    Events:       'purple',
    Other:        'grey',
}[tag] ?? 'grey');

const snack = reactive({ show: false, text: '', color: 'success' });
const showSnack = (text, color = 'success') => {
    snack.text = text; snack.color = color; snack.show = true;
};

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteDialog = ref(false);
const deleteTarget = ref(null);
const deleting     = ref(false);

const confirmDelete = (post) => { deleteTarget.value = post; deleteDialog.value = true; };

const doDelete = () => {
    deleting.value = true;
    useForm({}).delete(`/blogs/${deleteTarget.value.id}`, {
        onSuccess: () => {
            deleting.value = false;
            deleteDialog.value = false;
            showSnack('Post deleted.');
        },
        onError: () => {
            deleting.value = false;
            showSnack('Failed to delete.', 'error');
        },
    });
};
</script>
