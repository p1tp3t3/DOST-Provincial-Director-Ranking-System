<?php

namespace App\Helpers;

class RichTextSanitizer
{
    /** Formatting only — no links, images or embeds (used by province descriptions). */
    public const TEXT_ONLY = [
        'p' => [], 'br' => [], 'strong' => [], 'em' => [], 's' => [],
        'h2' => [], 'h3' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [],
    ];

    /**
     * Strip HTML down to the tag/attribute allow-list produced by the rich text
     * editor. The editor's own schema already constrains this, but a request can
     * be crafted by hand, and this HTML is later rendered with v-html on public
     * pages, so the server re-checks it independently.
     */
    public static function clean(string $html, ?array $allowedTags = null): string
    {
        $allowedTags ??= [
            'p' => [], 'br' => [], 'strong' => [], 'em' => [], 's' => [],
            'h2' => [], 'h3' => [],
            'ul' => [], 'ol' => [], 'li' => [],
            'blockquote' => [],
            'a' => ['href', 'target', 'rel'],
            'div' => ['data-video-embed', 'class'],
            'iframe' => ['src', 'width', 'height', 'frameborder', 'allow', 'allowfullscreen', 'title'],
        ];
        $allowedIframeHosts = ['www.youtube.com', 'youtube.com', 'youtube-nocookie.com', 'drive.google.com'];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div id="__root__">' . $html . '</div>',
            LIBXML_NOENT | LIBXML_NOWARNING | LIBXML_NOERROR
        );
        libxml_clear_errors();

        $root = $dom->getElementById('__root__');
        if (!$root) {
            return '';
        }

        $clean = function (\DOMNode $node) use (&$clean, $allowedTags, $allowedIframeHosts) {
            foreach (iterator_to_array($node->childNodes) as $child) {
                if ($child instanceof \DOMElement) {
                    $tag = strtolower($child->tagName);

                    if (!array_key_exists($tag, $allowedTags)) {
                        if (in_array($tag, ['script', 'style'], true)) {
                            // Drop entirely — their text content is code, not prose.
                            $node->removeChild($child);
                            continue;
                        }
                        // Unwrap: keep the children/text, drop the tag itself. Clean the
                        // subtree first — the hoisted children aren't revisited by this loop.
                        $clean($child);
                        while ($child->firstChild) {
                            $node->insertBefore($child->firstChild, $child);
                        }
                        $node->removeChild($child);
                        continue;
                    }

                    foreach (iterator_to_array($child->attributes) as $attr) {
                        if (!in_array($attr->nodeName, $allowedTags[$tag], true)) {
                            $child->removeAttribute($attr->nodeName);
                            continue;
                        }
                        if ($attr->nodeName === 'href' && !preg_match('/^https?:\/\//i', $attr->nodeValue)) {
                            $child->removeAttribute('href');
                        }
                        if ($attr->nodeName === 'src' && $tag === 'iframe') {
                            $host = parse_url($attr->nodeValue, PHP_URL_HOST) ?: '';
                            $allowed = collect($allowedIframeHosts)
                                ->contains(fn ($h) => $host === $h || str_ends_with($host, ".{$h}"));
                            if (!$allowed) {
                                $child->removeAttribute('src');
                            }
                        }
                    }

                    if ($tag === 'a') {
                        $child->setAttribute('target', '_blank');
                        $child->setAttribute('rel', 'noopener noreferrer nofollow');
                    }

                    $clean($child);
                }
            }
        };

        $clean($root);

        $inner = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $inner .= $dom->saveHTML($child);
        }

        return $inner;
    }

    /**
     * True when the HTML has no visible text and no embed — e.g. the editor's
     * empty state "<p></p>".
     */
    public static function isBlank(?string $html): bool
    {
        if ($html === null) {
            return true;
        }
        return trim(html_entity_decode(strip_tags($html))) === '' && !str_contains($html, '<iframe');
    }
}
