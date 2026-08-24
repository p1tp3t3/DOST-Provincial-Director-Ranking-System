<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $posts = BlogPost::orderByDesc('created_at')
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'title'        => $p->title,
                'slug'         => $p->slug,
                'tag'          => $p->tag,
                'excerpt'      => $p->excerpt,
                'image_url'    => $p->image_url,
                'is_published' => $p->is_published,
                'published_at' => $p->published_at?->format('M d, Y'),
                'created_at'   => $p->created_at->format('M d, Y'),
                'creator'      => 'Admin',
            ]);

        return inertia('SubAdmin/Blog/Main', ['posts' => $posts]);
    }

    public function create()
    {
        return inertia('SubAdmin/Blog/Form', ['post' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['body']         = $this->sanitizeBody($data['body']);
        $data['image_url']    = $this->storeImage($request);
        $data['created_by']   = Auth::id();
        $data['slug']         = BlogPost::generateSlug($data['title']);
        $data['published_at'] = $data['is_published'] ? now() : null;
        unset($data['image']);

        BlogPost::create($data);

        return redirect('/blogs')->with('success', 'Blog post created.');
    }

    public function edit(int $id)
    {
        $post = BlogPost::findOrFail($id);

        return inertia('SubAdmin/Blog/Form', [
            'post' => [
                'id'           => $post->id,
                'title'        => $post->title,
                'tag'          => $post->tag,
                'excerpt'      => $post->excerpt,
                'body'         => $post->body,
                'image_url'    => $post->image_url,
                'is_published' => $post->is_published,
            ],
        ]);
    }

    public function update(Request $request, int $id)
    {
        $post = BlogPost::findOrFail($id);
        $data = $this->validated($request);
        $data['body'] = $this->sanitizeBody($data['body']);

        if ($request->hasFile('image')) {
            $this->deleteImageFile($post->image_url);
            $data['image_url'] = $this->storeImage($request);
        }
        unset($data['image']);

        if ($data['is_published'] && !$post->is_published) {
            $data['published_at'] = now();
        } elseif (!$data['is_published']) {
            $data['published_at'] = null;
        }

        $post->update($data);

        return redirect('/blogs')->with('success', 'Blog post updated.');
    }

    public function destroy(int $id)
    {
        $post = BlogPost::findOrFail($id);
        $this->deleteImageFile($post->image_url);
        $post->delete();

        return back()->with('success', 'Blog post deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'tag'          => ['required', 'in:Announcement,Updates,Events,Other'],
            'excerpt'      => ['required', 'string', 'max:600'],
            'body'         => ['required', 'string', 'max:50000'],
            'image'        => ['nullable', 'image', 'max:4096'],
            'is_published' => ['boolean'],
        ]);
    }

    /**
     * Strip the post body down to the tag/attribute allow-list produced by the
     * rich text editor. The editor's own schema already constrains this, but a
     * request can be crafted by hand, and this HTML is later rendered with
     * v-html on the public blog page, so the server re-checks it independently.
     */
    private function sanitizeBody(string $html): string
    {
        $allowedTags = [
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

        $clean = function (\DOMNode $node) use (&$clean, $dom, $allowedTags, $allowedIframeHosts) {
            foreach (iterator_to_array($node->childNodes) as $child) {
                if ($child instanceof \DOMElement) {
                    $tag = strtolower($child->tagName);

                    if (!array_key_exists($tag, $allowedTags)) {
                        if (in_array($tag, ['script', 'style'], true)) {
                            // Drop entirely — their text content is code, not prose.
                            $node->removeChild($child);
                            continue;
                        }
                        // Unwrap: keep the children/text, drop the tag itself.
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

    private function storeImage(Request $request): ?string
    {
        if (!$request->hasFile('image')) return null;
        $path = $request->file('image')->store('blog-images', 'public');
        return '/storage/' . $path;
    }

    private function deleteImageFile(?string $imageUrl): void
    {
        if ($imageUrl && Str::startsWith($imageUrl, '/storage/')) {
            Storage::disk('public')->delete(Str::after($imageUrl, '/storage/'));
        }
    }
}
