import { Node, mergeAttributes } from '@tiptap/core';
import { Plugin, PluginKey } from '@tiptap/pm/state';

const YOUTUBE_REGEX = /(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/i;
const DRIVE_REGEX = /drive\.google\.com\/file\/d\/([\w-]+)/i;

export function toEmbedUrl(url) {
    if (typeof url !== 'string') return null;

    const youtube = url.match(YOUTUBE_REGEX);
    if (youtube) return `https://www.youtube.com/embed/${youtube[1]}`;

    const drive = url.match(DRIVE_REGEX);
    if (drive) return `https://drive.google.com/file/d/${drive[1]}/preview`;

    return null;
}

export const VideoEmbed = Node.create({
    name: 'videoEmbed',
    group: 'block',
    atom: true,
    draggable: true,

    addAttributes() {
        return {
            src: { default: null },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'div[data-video-embed]',
                getAttrs: (el) => ({ src: el.querySelector('iframe')?.getAttribute('src') ?? null }),
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            { 'data-video-embed': '', class: 'video-embed' },
            [
                'iframe',
                mergeAttributes({
                    src: HTMLAttributes.src,
                    frameborder: '0',
                    allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture',
                    allowfullscreen: 'true',
                    title: 'Embedded video',
                }),
            ],
        ];
    },

    addCommands() {
        return {
            setVideoEmbed:
                (src) =>
                ({ commands }) =>
                    commands.insertContent({ type: this.name, attrs: { src } }),
        };
    },

    addProseMirrorPlugins() {
        return [
            new Plugin({
                key: new PluginKey('videoEmbedPastePlugin'),
                props: {
                    handlePaste(view, event) {
                        const text = event.clipboardData?.getData('text/plain')?.trim();
                        if (!text) return false;

                        const embedUrl = toEmbedUrl(text);
                        if (!embedUrl) return false;

                        const { state, dispatch } = view;
                        const node = state.schema.nodes.videoEmbed.create({ src: embedUrl });
                        dispatch(state.tr.replaceSelectionWith(node));
                        return true;
                    },
                },
            }),
        ];
    },
});
