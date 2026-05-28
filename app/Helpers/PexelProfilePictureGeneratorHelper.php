<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PexelProfilePictureGeneratorHelper
{
    /**
     * Create a new class instance.
     */
    public static function generate($query = 'random content', $pages = 10)
    {
        $key = env('PEXELS_API_KEY');

        if (!$key) {
            return null;
        }

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => $key
            ])
            ->timeout(30)
            ->get('https://api.pexels.com/v1/search', [
                'query' => $query,
                'per_page' => $pages
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (empty($data['photos']) || !is_array($data['photos'])) {
                return null;
            }
            
            $photo = $data['photos'][array_rand($data['photos'])];

            return self::generateProfilePicture($photo);

        } catch (\Throwable $e) {
            Log::error($e);
            return null;
        }
    }

    private static function generateProfilePicture($photo): ?string
    {

        if (!$photo || empty($photo['src']['original'])) {
            return null;
        }

        $folderPath = storage_path('app/public/profile-pictures');

        // Ensure directory exists
        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0755, true);
        }

        $imageUrl = $photo['src']['original'];
        $fileName = 'profile_' . uniqid() . '.jpg';
        $fullPath = rtrim($folderPath, '/') . '/' . $fileName;

        try {
            $response = Http::withoutVerifying()->timeout(15)->get($imageUrl);

            if (!$response->successful() || empty($response->body())) {
                return null;
            }

            File::put($fullPath, $response->body());

            return $fileName;

        } catch (\Throwable $e) {
            Log::error($e);
            return null;
        }
    }
}
