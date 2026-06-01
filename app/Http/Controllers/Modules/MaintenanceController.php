<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    public function index()
    {
        return inertia('Admin/Maintenance/Main', [
            'backups'      => $this->get_backups(),
            'system_info'  => $this->get_system_info(),
            'storage_info' => $this->get_storage_info(),
        ]);
    }

    // ── Backup ─────────────────────────────────────────────────────

    public function create_backup()
    {
        $filename = 'backup_' . now()->format('Y-m-d_H-i') . '.sql';
        $path     = storage_path("app/backups/{$filename}");

        if (!is_dir(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }

        $db   = config('database.connections.mysql');
        $host = $db['host'];
        $port = $db['port'];
        $name = $db['database'];
        $user = $db['username'];
        $pass = $db['password'];

        $command = "mysqldump --host={$host} --port={$port} --user={$user} --password={$pass} {$name} > \"{$path}\" 2>&1";
        exec($command, $output, $code);

        if ($code !== 0 || !file_exists($path)) {
            return back()->with('error', 'Backup failed. Check server mysqldump availability.');
        }

        return back()->with('success', "Backup {$filename} created successfully.");
    }

    public function download_backup(string $filename)
    {
        $path = storage_path("app/backups/{$filename}");

        abort_unless(file_exists($path), 404, 'Backup file not found.');

        return response()->download($path);
    }

    public function delete_backup(string $filename)
    {
        $path = storage_path("app/backups/{$filename}");

        if (file_exists($path)) {
            unlink($path);
        }

        return back()->with('success', 'Backup deleted.');
    }

    private function get_system_info(): array
    {
        $dbDriver  = config('database.default');
        $dbVersion = 'N/A';

        try {
            $raw = DB::select('SELECT VERSION() as version');
            $dbVersion = $raw[0]->version ?? 'N/A';
        } catch (\Throwable) {}

        $dbLabel = match ($dbDriver) {
            'mysql'  => 'MySQL ' . $dbVersion,
            'pgsql'  => 'PostgreSQL ' . $dbVersion,
            'sqlite' => 'SQLite',
            default  => ucfirst($dbDriver),
        };

        $backups     = $this->get_backups();
        $lastBackup  = $backups[0]['created_at'] ?? 'No backups yet';

        return [
            ['label' => 'Laravel Version', 'value' => app()->version()],
            ['label' => 'PHP Version',     'value' => PHP_VERSION],
            ['label' => 'Database',        'value' => $dbLabel],
            ['label' => 'Environment',     'value' => ucfirst(app()->environment())],
            ['label' => 'Debug Mode',      'value' => config('app.debug') ? 'On' : 'Off'],
            ['label' => 'Last Backup',     'value' => $lastBackup],
        ];
    }

    private function get_storage_info(): array
    {
        $dbName  = config('database.connections.' . config('database.default') . '.database');
        $dbBytes = 0;

        try {
            $rows = DB::select(
                'SELECT SUM(data_length + index_length) AS size
                 FROM information_schema.tables
                 WHERE table_schema = ?',
                [$dbName]
            );
            $dbBytes = (int) ($rows[0]->size ?? 0);
        } catch (\Throwable) {}

        $uploadsBytes = $this->dir_size(storage_path('app/public'));
        $logsBytes    = $this->dir_size(storage_path('logs'));
        $backupBytes  = $this->dir_size(storage_path('app/backups'));

        $totalDisk = disk_total_space(storage_path()) ?: 1;

        $make = function (string $label, int $used, int $total) {
            return [
                'label'   => $label,
                'used'    => $this->format_bytes($used),
                'total'   => $this->format_bytes($total),
                'percent' => $total > 0 ? (int) round($used / $total * 100) : 0,
            ];
        };

        return [
            $make('Database', $dbBytes,      500 * 1024 * 1024),
            $make('Uploads',  $uploadsBytes,  2   * 1024 * 1024 * 1024),
            $make('Logs',     $logsBytes,     200 * 1024 * 1024),
            $make('Backups',  $backupBytes,   500 * 1024 * 1024),
        ];
    }

    private function dir_size(string $dir): int
    {
        if (!is_dir($dir)) return 0;
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    private function get_backups(): array
    {
        $dir = storage_path('app/backups');

        if (!is_dir($dir)) return [];

        $files = glob("{$dir}/*.sql");

        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        return array_map(function ($file) {
            return [
                'filename'   => basename($file),
                'size'       => $this->format_bytes(filesize($file)),
                'created_at' => date('M d, Y g:i A', filemtime($file)),
            ];
        }, $files);
    }

    private function format_bytes(int $bytes): string
    {
        if ($bytes < 1024)       return "{$bytes} B";
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    // ── Cache ──────────────────────────────────────────────────────

    public function clear_cache(string $key)
    {
        $commands = match ($key) {
            'app'    => ['cache:clear'],
            'config' => ['config:clear'],
            'route'  => ['route:clear'],
            'view'   => ['view:clear'],
            'all'    => ['cache:clear', 'config:clear', 'route:clear', 'view:clear'],
            default  => null,
        };

        abort_if($commands === null, 422, 'Invalid cache type.');

        foreach ($commands as $cmd) {
            Artisan::call($cmd);
        }

        $label = ucfirst($key === 'all' ? 'all caches' : "{$key} cache");

        return back()->with('success', "{$label} cleared successfully.");
    }

    // ── Optimize ───────────────────────────────────────────────────

    public function optimize()
    {
        Artisan::call('optimize');

        return back()->with('success', 'Application optimized successfully.');
    }

    // ── Reset ──────────────────────────────────────────────────────

    public function reset()
    {
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

        return back()->with('success', 'Database has been reset and re-seeded.');
    }
}
