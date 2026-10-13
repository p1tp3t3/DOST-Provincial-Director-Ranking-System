<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\FacebookPost;
use App\Models\User;
use App\Services\KpiEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

// Evidence-based KPI module backing the `supp_facebook_posts` KPI. Same pattern
// as LinkageController: records belong to the province, each quarter's count
// comes from the posting dates and is rolled up into the annual figure.
class FacebookPostController extends Controller
{
    private const KPI_CODE = 'supp_facebook_posts';

    public function __construct(private KpiEntryService $entries) {}

    public function index(int $directorId, int $year)
    {
        $director = $this->authorizeDirector($directorId);

        $posts = FacebookPost::where('province_id', $director->province_id)
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

        return inertia('Modules/FacebookPosts/Index', [
            'director'   => ['id' => $director->id, 'name' => $directorName ?: '—'],
            'year'       => $year,
            'posts'      => $posts,
            'count'      => $posts->count(),
            'return_url' => $this->returnUrlFor($director, $year),
        ]);
    }

    public function store(Request $request, int $directorId, int $year)
    {
        $director = $this->authorizeDirector($directorId);
        $data     = $this->validated($request);
        $this->entries->assertRecordQuarterOpen($director->province_id, $year, $data['date_posted']);

        FacebookPost::create([
            ...$data,
            'province_id'            => $director->province_id,
            'provincial_director_id' => $director->id,
            'year'                   => $year,
        ]);

        $this->entries->syncRecordKpi(self::KPI_CODE, $director->province_id, $year, Auth::id());

        return back()->with('success', 'Facebook post added.');
    }

    public function update(Request $request, int $directorId, int $year, int $postId)
    {
        $director = $this->authorizeDirector($directorId);
        $post     = FacebookPost::where('province_id', $director->province_id)
            ->where('year', $year)
            ->findOrFail($postId);

        $data = $this->validated($request);
        $this->entries->assertRecordQuarterOpen($director->province_id, $year, $post->date_posted);
        $this->entries->assertRecordQuarterOpen($director->province_id, $year, $data['date_posted']);

        $post->update($data);

        // A new posting date can move the record to another quarter.
        $this->entries->syncRecordKpi(self::KPI_CODE, $director->province_id, $year, Auth::id());

        return back()->with('success', 'Facebook post updated.');
    }

    public function destroy(int $directorId, int $year, int $postId)
    {
        $director = $this->authorizeDirector($directorId);
        $post     = FacebookPost::where('province_id', $director->province_id)
            ->where('year', $year)
            ->findOrFail($postId);

        $this->entries->assertRecordQuarterOpen($director->province_id, $year, $post->date_posted);
        $post->delete();

        $this->entries->syncRecordKpi(self::KPI_CODE, $director->province_id, $year, Auth::id());

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
            ->whereRole('provincial_director')
            ->with('profile')
            ->firstOrFail();

        if ($user->role === 'provincial_admin' && $director->province_id !== $user->province_id) {
            abort(403);
        }

        return $director;
    }

    private function returnUrlFor(User $director, int $year): string
    {
        if (Auth::user()->role === 'provincial_admin') {
            return "/provincial-kpi/{$year}";
        }
        return $director->province_id
            ? '/kpi-data/' . Crypt::encrypt($director->province_id) . "/{$year}"
            : '/kpi-data';
    }
}
