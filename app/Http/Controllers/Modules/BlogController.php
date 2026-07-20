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
            'body'         => ['required', 'array', 'min:1'],
            'body.*'       => ['required', 'string', 'max:5000'],
            'image'        => ['nullable', 'image', 'max:4096'],
            'is_published' => ['boolean'],
        ]);
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
