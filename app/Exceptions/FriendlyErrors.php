<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Turns errors from saves into sentences people can act on, never SQL or PHP
 * text. It covers JSON requests (axios / fetch) and Inertia form submissions;
 * page loads keep Laravel's own error pages. The real error is still written
 * to the log as usual, so nothing is lost for whoever fixes it.
 *
 *   SQLSTATE[23000]: … Column 'length_of_service' cannot be null …
 *   → "Length of service can't be left blank. Please fill it in and try again."
 */
final class FriendlyErrors
{
    public const GENERIC = 'Something went wrong on our end, so this could not be completed. Please try again. If it keeps happening, contact the system administrator.';

    // Column names that don't read well when spelled out.
    private const LABELS = [
        'dost_employee_id'        => 'Employee No.',
        'num_plantilla_employees' => 'Number of plantilla employees',
        'num_municipalities'      => 'Number of municipalities',
        'num_cities'              => 'Number of cities',
        'kpi_id'                  => 'KPI',
        'category_id'             => 'KPI category',
        'provincial_director_id'  => 'Provincial director',
        'created_by'              => 'Author',
        'post_url'                => 'Post link',
        'image_url'               => 'Image',
    ];

    // Laravel's built-in wording for HTTP errors, replaced with plainer text.
    // A message written on purpose (abort(403, 'You can only …')) is kept.
    private const DEFAULT_HTTP_MESSAGES = [
        '', 'This action is unauthorized.', 'CSRF token mismatch.', 'Too Many Attempts.',
        'Forbidden', 'Not Found', 'Unauthorized', 'Server Error', 'Service Unavailable',
    ];

    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (Throwable $e, Request $request) {
            $inertiaSave = $request->hasHeader('X-Inertia') && !$request->isMethod('GET');
            if (!$request->expectsJson() && !$inertiaSave) {
                return null;
            }

            $friendly = self::describe($e, $request);
            if ($friendly === null) {
                return null; // already readable (validation errors, sign-in redirects, …)
            }
            [$status, $message, $field] = $friendly;

            // Inertia saves report errors through the page's onError, which
            // opens the same error dialog as a field that failed validation.
            if ($inertiaSave) {
                return back()->withErrors([$field ?? 'message' => $message]);
            }

            $body = ['message' => $message];
            if ($field) {
                $body['errors'] = [$field => [$message]];
            }
            if (config('app.debug')) {
                $body['debug'] = class_basename($e) . ': ' . $e->getMessage(); // developers only, never shown
            }

            return response()->json($body, $status);
        });
    }

    /**
     * [status, message, field] to send instead of the raw error, or null when
     * Laravel's own response is already meant for people.
     *
     * @return array{0:int,1:string,2:?string}|null
     */
    public static function describe(Throwable $e, Request $request): ?array
    {
        if ($e instanceof QueryException) {
            return self::fromQuery($e, $request);
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            if ($e->getPrevious() instanceof ModelNotFoundException) {
                return [404, 'This record no longer exists. It may have been removed by someone else. Refresh the page and try again.', null];
            }
            if (!in_array(trim($e->getMessage()), self::DEFAULT_HTTP_MESSAGES, true)
                && !str_starts_with($e->getMessage(), 'The route ')
                && !str_contains($e->getMessage(), 'method is not supported')) {
                return null;
            }

            return [$status, match (true) {
                $status === 401 => 'Your session has ended. Please sign in again.',
                $status === 403 => "You don't have permission to do this.",
                $status === 404 => "The page or record you're looking for couldn't be found.",
                $status === 405 => "This action isn't available here. Refresh the page and try again.",
                $status === 413 => 'The file is too large to upload. Please choose a smaller file.',
                $status === 419 => 'This page has expired. Refresh the page and try again.',
                $status === 429 => 'Too many attempts. Please wait a minute and try again.',
                $status === 503 => 'The system is temporarily unavailable. Please try again in a few minutes.',
                $status >= 500  => self::GENERIC,
                default         => 'This request could not be completed. Refresh the page and try again.',
            }, null];
        }

        // Validation errors, sign-in redirects and prepared responses are
        // already written for people.
        if ($e instanceof \Illuminate\Validation\ValidationException
            || $e instanceof \Illuminate\Auth\AuthenticationException
            || $e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
            return null;
        }

        return [500, self::GENERIC, null];
    }

    /** @return array{0:int,1:string,2:?string} */
    private static function fromQuery(QueryException $e, Request $request): array
    {
        $code  = (int) ($e->errorInfo[1] ?? 0);
        $text  = (string) ($e->errorInfo[2] ?? $e->getMessage());
        $table = preg_match('/\b(?:into|update|from)\s+[`"]?(\w+)[`"]?/i', $e->getSql(), $m) ? $m[1] : null;
        $find  = fn (string $pattern) => preg_match($pattern, $text, $m) ? $m[1] : null;

        // A required value is missing.
        if (in_array($code, [1048, 1364], true) || str_contains($text, 'NOT NULL constraint failed')) {
            $column = $find("/Column '(\\w+)'/") ?? $find("/Field '(\\w+)'/") ?? $find('/NOT NULL constraint failed: \\w+\\.(\\w+)/');
            return [422, $column
                ? self::label($column) . " can't be left blank. Please fill it in and try again."
                : 'A required field is empty. Please fill in every required field and try again.', $column];
        }

        // The same value is already used by another record.
        if ($code === 1062 || str_contains($text, 'UNIQUE constraint failed')) {
            $column = $find('/UNIQUE constraint failed: \\w+\\.(\\w+)$/') ?? self::uniqueColumn($find("/for key '([^']+)'/"), $table);
            return [422, $column
                ? self::label($column) . ' is already in use. Please enter a different one.'
                : 'This record already exists, so it was not added again.', $column];
        }

        // A value doesn't fit its column.
        $column = self::columnIn($text);
        if ($code === 1406) {
            return [422, self::label($column ?? 'value') . ' is too long. Please shorten it and try again.', $column];
        }
        if ($code === 1264) {
            return [422, self::label($column ?? 'value') . ' is too large or too small. Please check it and try again.', $column];
        }
        if (in_array($code, [1265, 1292, 1366, 1367], true)) {
            return [422, self::label($column ?? 'value') . ' has an invalid value. Please check it and try again.', $column];
        }

        // Records that point at each other.
        $foreignKey = str_contains($text, 'FOREIGN KEY constraint failed');
        if ($code === 1451 || ($foreignKey && $request->isMethod('DELETE'))) {
            return [409, "This can't be deleted because other records still use it. Remove or reassign those records first.", null];
        }
        if ($code === 1452 || $foreignKey) {
            $column = $find('/FOREIGN KEY \\(`(\\w+)`\\)/');
            return [422, $column
                ? 'The selected ' . Str::lower(self::label($column)) . ' no longer exists. Refresh the page and try again.'
                : 'Something you selected no longer exists. Refresh the page and try again.', $column];
        }

        // The database is busy or unreachable.
        if (in_array($code, [1205, 1213], true) || str_contains($text, 'database is locked')) {
            return [503, 'The system is busy right now. Please wait a moment and try again.', null];
        }
        if (in_array($code, [1045, 1049, 2002, 2006, 2013], true)
            || preg_match('/\[(2002|2006|1045|1049)\]|Connection refused|server has gone away/i', $e->getMessage())) {
            return [503, "The database can't be reached right now. Please try again in a few minutes. If it keeps happening, contact the system administrator.", null];
        }

        return [500, self::GENERIC, null];
    }

    // "length_of_service" → "Length of service"; "province_id" → "Province".
    private static function label(string $column): string
    {
        return self::LABELS[$column]
            ?? Str::ucfirst(str_replace('_', ' ', preg_replace('/_id$/', '', $column)));
    }

    // "… for column 'num_cities' at row 1" (or `db`.`table`.`num_cities`) → "num_cities".
    private static function columnIn(string $text): ?string
    {
        if (!preg_match('/for column\s+(\S+)/i', $text, $m)) {
            return null;
        }
        $parts = explode('.', str_replace(['`', "'", '"'], '', $m[1]));

        return end($parts) ?: null;
    }

    // MySQL names the unique index, not the column: "users_email_unique" → "email".
    private static function uniqueColumn(?string $key, ?string $table): ?string
    {
        if (!$key || !$table) {
            return null;
        }
        $key    = Str::afterLast($key, '.');
        $column = Str::of($key)->after($table . '_')->beforeLast('_unique')->toString();

        try {
            return $column !== $key && Schema::hasColumn($table, $column) ? $column : null;
        } catch (Throwable) {
            return null;
        }
    }
}
