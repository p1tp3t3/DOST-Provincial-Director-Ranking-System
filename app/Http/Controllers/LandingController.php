<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Support\Facades\Route;

class LandingController extends Controller
{
    public function home()
    {
        $blogs = BlogPost::where('is_published', true)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get()
            ->map(fn ($p) => [
                'slug'    => $p->slug,
                'image'   => $p->image_url ?: '/assets/placeholder.png',
                'tag'     => $p->tag,
                'date'    => ($p->published_at ?? $p->created_at)->format('F d, Y'),
                'title'   => $p->title,
                'excerpt' => $p->excerpt,
            ]);

        return inertia('Landing/Welcome', [
            'canLogin'    => Route::has('login'),
            'canRegister' => Route::has('register'),
            'blogs'       => $blogs,
        ]);
    }

    public function blogShow(string $slug)
    {
        $record = BlogPost::where('slug', $slug)
            ->where('is_published', true)
            ->first();

        $post    = null;
        $related = [];

        if ($record) {
            $post = [
                'slug'    => $record->slug,
                'image'   => $record->image_url ?: '/assets/placeholder.png',
                'tag'     => $record->tag,
                'date'    => ($record->published_at ?? $record->created_at)->format('F d, Y'),
                'title'   => $record->title,
                'excerpt' => $record->excerpt,
                'body'    => $record->body,
            ];

            $related = BlogPost::where('is_published', true)
                ->where('slug', '!=', $slug)
                ->orderByDesc('published_at')
                ->limit(2)
                ->get()
                ->map(fn ($p) => [
                    'slug'    => $p->slug,
                    'image'   => $p->image_url ?: '/assets/placeholder.png',
                    'tag'     => $p->tag,
                    'date'    => ($p->published_at ?? $p->created_at)->format('F d, Y'),
                    'title'   => $p->title,
                    'excerpt' => $p->excerpt,
                ])->values();
        }

        return inertia('Landing/Blog', [
            'slug'     => $slug,
            'post'     => $post,
            'related'  => $related,
            'canLogin' => Route::has('login'),
        ]);
    }
}
