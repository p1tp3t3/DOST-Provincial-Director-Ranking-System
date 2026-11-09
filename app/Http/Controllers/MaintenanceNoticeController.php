<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

// The public "under maintenance" page. Its ?return= is the page the visitor
// was on, encrypted by CheckMaintenanceMode; it's decrypted here and handed to
// the page as return_to, where the live listener (resources/js/echo.js) sends
// the visitor back once maintenance ends.
class MaintenanceNoticeController extends Controller
{
    public function __invoke(Request $request)
    {
        return inertia('Other/Maintenance/Main', [
            'return_to' => self::returnPath($request->query('return')),
        ]);
    }

    // The decrypted page, if it's valid and points within this site.
    private static function returnPath(mixed $encrypted): ?string
    {
        if (!is_string($encrypted) || $encrypted === '') return null;

        try {
            $path = Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null; // tampered with, or not ours
        }

        $sameSite = str_starts_with($path, '/') && !str_starts_with($path, '//') && !str_starts_with($path, '/\\');

        return $sameSite && !str_starts_with($path, '/maintenance-notice') ? $path : null;
    }
}
