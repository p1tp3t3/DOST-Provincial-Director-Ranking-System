<script setup>
import { ref, onBeforeUnmount, onMounted, watch } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { VideoEmbed, toEmbedUrl } from './VideoEmbedExtension.js';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    errorMessages: { type: [String, Array], default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const embedUrlInput = ref('');
const embedMenuOpen = ref(false);
const isExpanded = ref(false);

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
    document.body.style.overflow = isExpanded.value ? 'hidden' : '';
};

const onKeydown = (e) => {
    if (e.key === 'Escape' && isExpanded.value) toggleExpand();
};

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
        }),
        Link.configure({
            openOnClick: false,
            autolink: true,
            HTMLAttributes: { rel: 'noopener noreferrer nofollow', target: '_blank' },
        }),
        Placeholder.configure({
            placeholder: 'Write the article body… paste a YouTube or Google Drive link to embed a video.',
        }),
        VideoEmbed,
    ],
    onUpdate: ({ editor: e }) => emit('update:modelValue', e.getHTML()),
});

watch(
    () => props.modelValue,
    (value) => {
        if (editor.value && value !== editor.value.getHTML()) {
            editor.value.commands.setContent(value || '', { emitUpdate: false });
        }
    }
);

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => {
    editor.value?.destroy();
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});

const isActive = (name, attrs) => editor.value?.isActive(name, attrs) ?? false;

const toggleLink = () => {
    if (isActive('link')) {
        editor.value.chain().focus().unsetLink().run();
        return;
    }
    const url = window.prompt('Link URL');
    if (url) editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
};

const insertEmbed = () => {
    const url = embedUrlInput.value.trim();
    const embedUrl = toEmbedUrl(url);
    if (!embedUrl) {
        window.alert('That doesn\'t look like a YouTube or Google Drive link.');
        return;
    }
    editor.value.chain().focus().setVideoEmbed(embedUrl).run();
    embedUrlInput.value = '';
    embedMenuOpen.value = false;
};

const errorList = () =>
    Array.isArray(props.errorMessages) ? props.errorMessages : [props.errorMessages].filter(Boolean);
</script>

<template>
    <div class="rte-field">
        <div v-if="label" class="rte-label">{{ label }}</div>

        <Teleport to="body" :disabled="!isExpanded">
        <div v-if="isExpanded" class="rte-backdrop" @click="toggleExpand" />
        <div
            class="rte-shell"
            :class="{ 'rte-shell--error': errorList().length, 'rte-shell--expanded': isExpanded }"
        >
            <div v-if="editor" class="rte-toolbar">
                <button type="button" class="rte-btn" :class="{ 'rte-btn--active': isActive('bold') }"
                        title="Bold" @click="editor.chain().focus().toggleBold().run()">
                    <v-icon size="18">mdi-format-bold</v-icon>
                </button>
                <button type="button" class="rte-btn" :class="{ 'rte-btn--active': isActive('italic') }"
                        title="Italic" @click="editor.chain().focus().toggleItalic().run()">
                    <v-icon size="18">mdi-format-italic</v-icon>
                </button>
                <button type="button" class="rte-btn" :class="{ 'rte-btn--active': isActive('strike') }"
                        title="Strikethrough" @click="editor.chain().focus().toggleStrike().run()">
                    <v-icon size="18">mdi-format-strikethrough</v-icon>
                </button>

                <span class="rte-divider" />

                <button type="button" class="rte-btn" :class="{ 'rte-btn--active': isActive('heading', { level: 2 }) }"
                        title="Heading" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">
                    <v-icon size="18">mdi-format-header-2</v-icon>
                </button>
                <button type="button" class="rte-btn" :class="{ 'rte-btn--active': isActive('heading', { level: 3 }) }"
                        title="Subheading" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()">
                    <v-icon size="18">mdi-format-header-3</v-icon>
                </button>

                <span class="rte-divider" />

                <button type="button" class="rte-btn" :class="{ 'rte-btn--active': isActive('bulletList') }"
                        title="Bullet list" @click="editor.chain().focus().toggleBulletList().run()">
                    <v-icon size="18">mdi-format-list-bulleted</v-icon>
                </button>
                <button type="button" class="rte-btn" :class="{ 'rte-btn--active': isActive('orderedList') }"
                        title="Numbered list" @click="editor.chain().focus().toggleOrderedList().run()">
                    <v-icon size="18">mdi-format-list-numbered</v-icon>
                </button>
                <button type="button" class="rte-btn" :class="{ 'rte-btn--active': isActive('blockquote') }"
                        title="Quote" @click="editor.chain().focus().toggleBlockquote().run()">
                    <v-icon size="18">mdi-format-quote-close</v-icon>
                </button>

                <span class="rte-divider" />

                <button type="button" class="rte-btn" :class="{ 'rte-btn--active': isActive('link') }"
                        title="Link" @click="toggleLink">
                    <v-icon size="18">mdi-link-variant</v-icon>
                </button>

                <v-menu v-model="embedMenuOpen" :close-on-content-click="false" location="bottom start">
                    <template #activator="{ props: menuProps }">
                        <button type="button" class="rte-btn" title="Embed YouTube / Drive video" v-bind="menuProps">
                            <v-icon size="18">mdi-video-plus-outline</v-icon>
                        </button>
                    </template>
                    <v-card class="pa-3" min-width="320">
                        <div class="text-caption text-grey-darken-2 mb-2">
                            Paste a YouTube or Google Drive link
                        </div>
                        <v-text-field
                            v-model="embedUrlInput"
                            density="compact"
                            variant="outlined"
                            hide-details
                            placeholder="https://youtube.com/watch?v=…"
                            @keydown.enter.prevent="insertEmbed"
                        />
                        <div class="d-flex justify-end mt-2">
                            <v-btn size="small" color="indigo" variant="flat" @click="insertEmbed">Insert</v-btn>
                        </div>
                    </v-card>
                </v-menu>

                <span class="rte-spacer" />

                <button type="button" class="rte-btn" title="Undo" @click="editor.chain().focus().undo().run()">
                    <v-icon size="18">mdi-undo</v-icon>
                </button>
                <button type="button" class="rte-btn" title="Redo" @click="editor.chain().focus().redo().run()">
                    <v-icon size="18">mdi-redo</v-icon>
                </button>

                <span class="rte-divider" />

                <button
                    type="button"
                    class="rte-btn"
                    :title="isExpanded ? 'Collapse' : 'Expand editor'"
                    @click="toggleExpand"
                >
                    <v-icon size="18">{{ isExpanded ? 'mdi-fullscreen-exit' : 'mdi-arrow-expand' }}</v-icon>
                </button>
            </div>

            <EditorContent :editor="editor" class="rte-content" />

            <div class="rte-hint">
                You can also paste a YouTube or Google Drive link directly into the text — it embeds automatically.
            </div>
        </div>
        </Teleport>

        <div v-if="hint && !errorList().length" class="rte-helper">{{ hint }}</div>
        <div v-for="(err, i) in errorList()" :key="i" class="rte-helper rte-helper--error">{{ err }}</div>
    </div>
</template>

<style scoped>
.rte-field { width: 100%; }

.rte-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: rgba(0, 0, 0, 0.68);
    margin-bottom: 6px;
}

.rte-shell {
    display: flex;
    flex-direction: column;
    border: 1px solid rgba(0, 0, 0, 0.26);
    border-radius: 8px;
    overflow: hidden;
    transition: border-color 0.15s;
    background: #fff;
}
.rte-shell:focus-within {
    border-color: #3f51b5;
    border-width: 1.5px;
}
.rte-shell--error {
    border-color: #b00020;
}

.rte-shell--expanded {
    position: fixed;
    inset: 28px;
    z-index: 2001;
    border-radius: 12px;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.35);
}

.rte-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    z-index: 2000;
}

.rte-toolbar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 2px;
    padding: 6px 8px;
    background: #fafafa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.rte-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: rgba(0, 0, 0, 0.65);
    cursor: pointer;
    transition: background 0.12s, color 0.12s;
}
.rte-btn:hover { background: rgba(0, 0, 0, 0.06); }
.rte-btn--active {
    background: rgba(63, 81, 181, 0.12);
    color: #3f51b5;
}

.rte-divider {
    width: 1px;
    height: 20px;
    background: rgba(0, 0, 0, 0.12);
    margin: 0 4px;
}
.rte-spacer { flex: 1; }

.rte-content {
    padding: 14px 16px;
    min-height: 340px;
    max-height: 640px;
    overflow-y: auto;
    flex: 1;
}

.rte-shell--expanded .rte-content {
    max-height: none;
}

.rte-hint {
    padding: 6px 16px 10px;
    font-size: 0.72rem;
    color: rgba(0, 0, 0, 0.4);
    border-top: 1px dashed rgba(0, 0, 0, 0.08);
}

.rte-helper {
    font-size: 0.75rem;
    color: rgba(0, 0, 0, 0.6);
    margin-top: 4px;
    padding: 0 4px;
}
.rte-helper--error { color: #b00020; }
</style>

<style>
/* Tiptap ProseMirror content — not scoped, since it targets editor-generated markup */
.rte-content .ProseMirror {
    outline: none;
    font-size: 0.925rem;
    line-height: 1.65;
    color: rgba(0, 0, 0, 0.82);
}
.rte-content .ProseMirror p { margin: 0 0 0.85em; }
.rte-content .ProseMirror h2 { font-size: 1.3rem; font-weight: 700; margin: 1em 0 0.5em; }
.rte-content .ProseMirror h3 { font-size: 1.1rem; font-weight: 700; margin: 1em 0 0.5em; }
.rte-content .ProseMirror ul,
.rte-content .ProseMirror ol { padding-left: 1.4em; margin: 0 0 0.85em; }
.rte-content .ProseMirror blockquote {
    border-left: 3px solid #c5cae9;
    margin: 0 0 0.85em;
    padding: 2px 0 2px 14px;
    color: rgba(0, 0, 0, 0.6);
    font-style: italic;
}
.rte-content .ProseMirror a { color: #3f51b5; text-decoration: underline; }
.rte-content .ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: rgba(0, 0, 0, 0.34);
    pointer-events: none;
    height: 0;
}

.rte-content .video-embed {
    position: relative;
    width: 100%;
    padding-top: 56.25%;
    margin: 0 0 1em;
    border-radius: 8px;
    overflow: hidden;
    background: #000;
}
.rte-content .video-embed iframe {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: 0;
}
</style>
