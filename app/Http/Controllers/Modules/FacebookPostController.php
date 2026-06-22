<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\FacebookPost;
use App\Models\KPI;
use App\Models\ProvincialDirectorKPI;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Evidence-based KPI module backing the `supp_facebook_posts` KPI. Same pattern
// as LinkageController: the live record count is mirrored into
// provincial_director_kpis.accomplished so RankingService keeps working.
class FacebookPostController extends Controller
{
    public function index(int $directorId, int $year)
    {
        $director = $this->authorizeDirector($directorId);

        $posts = FacebookPost::where('provincial_director_id', $director->id)
            ->where('year', $year)
            ->orderByDesc('date_posted')
            ->orderByDesc('id')
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'title'       => $r->title,
                'post_url'    => $r->post_url,
                'post_type'   => $r->post_type,
                'date_posted' => optional($r->date_posted)->format('Y-m-d'),
                'caption'     => $r->caption,
            ])->values();

        $profile      = $director->profile;
        $directorName = $profile
            ? trim(($profile->first_name ?? '') . ' ' . ($profile->middle_name ?? '') . ' ' . ($profile->last_name ?? ''))
            : '—';

        $returnUrl = $this->returnUrlFor($director->id, $year);

        return inertia('Modules/FacebookPosts/Index', [
            'director'   => ['id' => $director->id, 'name' => $directorName ?: '—'],
            'year'       => $year,
            'posts'      => $posts,
            'count'      => $posts->count(),
            'return_url' => $returnUrl,
        ]);
    }

    public function store(Request $request, int $directorId, int $year)
    {
        $director = $this->authorizeDirector($directorId);
        $data     = $this->validated($request);

        FacebookPost::create(array_merge($data, [
            'provincial_director_id' => $director->id,
            'year'                   => $year,
        ]));

        $this->syncAccomplishedCount($director->id, $year);

        return back()->with('success', 'Facebook post added.');
    }

    public function update(Request $request, int $directorId, int $year, int $postId)
    {
        $director = $this->authorizeDirector($directorId);
        $post     = FacebookPost::where('provincial_director_id', $director->id)
            ->where('year', $year)
            ->findOrFail($postId);

        $post->update($this->validated($request));

        return back()->with('success', 'Facebook post updated.');
    }

    public function destroy(int $directorId, int $year, int $postId)
    {
        $director = $this->authorizeDirector($directorId);

        FacebookPost::where('provincial_director_id', $director->id)
            ->where('year', $year)
            ->where('id', $postId)
            ->delete();

        $this->syncAccomplishedCount($director->id, $year);

        return back()->with('success', 'Facebook post removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'post_url'    => ['nullable', 'string', 'max:500'],
            'post_type'   => ['required', 'in:Text,Photo,Video,Link,Event'],
            'date_posted' => ['required', 'date'],
            'caption'     => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function authorizeDirector(int $directorId): User
    {
        $user     = Auth::user();
        $director = User::where('id', $directorId)
            ->where('role', 'provincial_director')
            ->with('profile')
            ->firstOrFail();

        if ($user->role === 'provincial_sub_admin' && $director->province_id !== $user->province_id) {
            abort(403);
        }

        return $director;
    }

    private function syncAccomplishedCount(int $directorId, int $year): void
    {
        $kpi = KPI::where('code', 'supp_facebook_posts')->first();
        if (!$kpi) return;

        $count = FacebookPost::where('provincial_director_id', $directorId)
            ->where('year', $year)
            ->count();

        $existing = ProvincialDirectorKPI::where('provincial_director_id', $directorId)
            ->where('kpi_id', $kpi->id)
            ->where('year', $year)
            ->first();

        if ($count === 0) {
            if ($existing && ($existing->target === null || $existing->target === '')) {
                $existing->delete();
            } elseif ($existing) {
                $existing->update(['accomplished' => null]);
            }
            return;
        }

        ProvincialDirectorKPI::updateOrCreate(
            [
                'provincial_director_id' => $directorId,
                'kpi_id'                 => $kpi->id,
                'year'                   => $year,
            ],
            ['accomplished' => (string) $count]
        );
    }

    private function returnUrlFor(int $directorId, int $year): string
    {
        $user = Auth::user();
        if ($user->role === 'provincial_sub_admin') {
            return "/provincial-kpi/{$year}";
        }
        $director = User::find($directorId);
        if ($director && $director->province_id) {
            $encryptedProvince = \Illuminate\Support\Facades\Crypt::encrypt($director->province_id);
            return "/kpi-data/{$encryptedProvince}/{$year}";
        }
        return '/kpi-data';
    }
}
