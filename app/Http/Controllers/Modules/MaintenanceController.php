<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    public function index()
    {
        return inertia('Admin/Maintenance/Main', [
            'backups'           => $this->get_backups(),
            'storage_backups'   => $this->get_storage_backups(),
            'system_backups'    => $this->get_system_backups(),
            'system_info'       => $this->get_system_info(),
            'storage_info'      => $this->get_storage_info(),
            'maintenance_mode'  => (bool) Cache::get('app_maintenance_mode', false),
        ]);
    }

    // ── Maintenance Mode Toggle ────────────────────────────────────

    public function toggle_maintenance()
    {
        $current = (bool) Cache::get('app_maintenance_mode', false);

        if ($current) {
            Cache::forget('app_maintenance_mode');
            $msg = 'Maintenance mode disabled. The system is now live.';
        } else {
            Cache::put('app_maintenance_mode', true, now()->addYear());
            $msg = 'Maintenance mode enabled. Only super admins can access the system.';
        }

        return back()->with('success', $msg);
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

        $mysqldumpPath = 'C:\xampp\mysql\bin\mysqldump.exe';

        // 2. Wrap the binary inside outer double quotes so the Windows shell executes it flawlessly
        $command = sprintf(
            '"%s" --host=%s --port=%s --user=%s --password=%s %s > "%s" 2>&1',
            $mysqldumpPath,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($name),
            $path
        );

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

        $uploadsBytes        = $this->dir_size(storage_path('app/public'));
        $logsBytes           = $this->dir_size(storage_path('logs'));
        $dbBackupBytes       = $this->dir_size(storage_path('app/backups'));
        $storageBackupBytes  = $this->dir_size(storage_path('app/storage-backups'));
        $systemBackupBytes   = $this->dir_size(storage_path('app/system-backups'));

        $make = function (string $label, int $used, int $total) {
            return [
                'label'   => $label,
                'used'    => $this->format_bytes($used),
                'total'   => $this->format_bytes($total),
                'percent' => $total > 0 ? (int) round($used / $total * 100) : 0,
            ];
        };

        return [
            $make('Database',        $dbBytes,             500 * 1024 * 1024),
            $make('Uploads',         $uploadsBytes,          2 * 1024 * 1024 * 1024),
            $make('Logs',            $logsBytes,           200 * 1024 * 1024),
            $make('DB Backups',      $dbBackupBytes,       500 * 1024 * 1024),
            $make('Storage Backups', $storageBackupBytes,  500 * 1024 * 1024),
            $make('System Backups',  $systemBackupBytes,     2 * 1024 * 1024 * 1024),
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

    // ── System Backup (DB + Storage + .env) ───────────────────────

    public function create_system_backup()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename  = "system_export_{$timestamp}.zip";
        $dir       = storage_path('app/system-backups');
        $path      = "{$dir}/{$filename}";
        $tempSql   = "{$dir}/temp_db_{$timestamp}.sql";

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Step 1: PHP-native DB dump (no mysqldump PATH dependency)
        try {
            file_put_contents($tempSql, $this->dump_database_php());
        } catch (\Throwable $e) {
            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }

        if (!file_exists($tempSql) || filesize($tempSql) === 0) {
            return back()->with('error', 'Export failed: database dump was empty.');
        }

        // Step 2: Build ZIP
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($tempSql);
            return back()->with('error', 'Could not create ZIP archive.');
        }

        $zip->addFile($tempSql, 'database.sql');

        // Normalize all paths to forward-slashes for cross-platform comparison
        $norm        = fn(string $p) => rtrim(str_replace('\\', '/', $p), '/');
        $projectRoot = $norm(realpath(base_path()));

        $skipDirs = array_values(array_filter(array_map(fn($p) => $p ? $norm($p) : null, [
            realpath($projectRoot . '/vendor'),
            realpath($projectRoot . '/node_modules'),
            realpath($projectRoot . '/.git'),
            realpath($dir),
            realpath(storage_path('app/backups')),
            realpath(storage_path('app/storage-backups')),
        ])));

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($projectRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $real = $norm($item->getRealPath());
            if (!$real) continue;

            foreach ($skipDirs as $skipDir) {
                if ($real === $skipDir || str_starts_with($real, $skipDir . '/')) {
                    continue 2;
                }
            }

            $relative = 'project/' . substr($real, strlen($projectRoot) + 1);

            if ($item->isDir()) {
                $zip->addEmptyDir($relative);
            } elseif ($item->isFile() && $item->isReadable()) {
                $zip->addFile($item->getRealPath(), $relative);
            }
        }

        $zip->close();
        @unlink($tempSql);

        return back()->with('success', "System export {$filename} created successfully.");
    }

    private function dump_database_php(): string
    {
        $lines   = [];
        $lines[] = '-- PRISM System Export | Generated: ' . now()->toDateTimeString();
        $lines[] = '-- Database: ' . DB::getDatabaseName();
        $lines[] = '';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $lines[] = 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";';
        $lines[] = '';

        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $tableRow) {
            $table = current((array) $tableRow);

            $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
            $createSql    = $createResult[0]->{'Create Table'};

            $lines[] = "-- Table: `{$table}`";
            $lines[] = "DROP TABLE IF EXISTS `{$table}`;";
            $lines[] = $createSql . ';';
            $lines[] = '';

            $rows = DB::table($table)->get();

            if ($rows->isEmpty()) continue;

            $columns = array_map(fn($c) => "`{$c}`", array_keys((array) $rows->first()));
            $colStr  = implode(', ', $columns);

            foreach ($rows as $row) {
                $values = array_map(function ($val) {
                    if ($val === null) return 'NULL';
                    return "'" . str_replace(["\\", "'", "\n", "\r"], ["\\\\", "\\'", "\\n", "\\r"], (string) $val) . "'";
                }, (array) $row);

                $lines[] = "INSERT INTO `{$table}` ({$colStr}) VALUES (" . implode(', ', $values) . ');';
            }

            $lines[] = '';
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode("\n", $lines);
    }

    public function download_system_backup(string $filename)
    {
        $path = storage_path("app/system-backups/{$filename}");

        abort_unless(file_exists($path), 404, 'Backup file not found.');

        return response()->download($path);
    }

    public function delete_system_backup(string $filename)
    {
        $path = storage_path("app/system-backups/{$filename}");

        if (file_exists($path)) {
            unlink($path);
        }

        return back()->with('success', 'System backup deleted.');
    }

    private function get_system_backups(): array
    {
        $dir = storage_path('app/system-backups');

        if (!is_dir($dir)) return [];

        $files = glob("{$dir}/system_export_*.zip") ?: [];

        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        return array_map(fn($file) => [
            'filename'   => basename($file),
            'size'       => $this->format_bytes(filesize($file)),
            'created_at' => date('M d, Y g:i A', filemtime($file)),
        ], $files);
    }

    // ── Storage Backup ─────────────────────────────────────────────

    public function create_storage_backup()
    {
        $filename = 'storage_backup_' . now()->format('Y-m-d_H-i') . '.zip';
        $dir      = storage_path('app/storage-backups');
        $path     = "{$dir}/{$filename}";

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $source = storage_path('app/public');

        if (!is_dir($source)) {
            return back()->with('error', 'Storage directory does not exist.');
        }

        $zip = new \ZipArchive();

        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create ZIP archive.');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isReadable()) continue;
            $relative = substr($file->getRealPath(), strlen($source) + 1);
            $zip->addFile($file->getRealPath(), $relative);
        }

        $zip->close();

        return back()->with('success', "Storage backup {$filename} created successfully.");
    }

    public function download_storage_backup(string $filename)
    {
        $path = storage_path("app/storage-backups/{$filename}");

        abort_unless(file_exists($path), 404, 'Backup file not found.');

        return response()->download($path);
    }

    public function delete_storage_backup(string $filename)
    {
        $path = storage_path("app/storage-backups/{$filename}");

        if (file_exists($path)) {
            unlink($path);
        }

        return back()->with('success', 'Storage backup deleted.');
    }

    private function get_storage_backups(): array
    {
        $dir = storage_path('app/storage-backups');

        if (!is_dir($dir)) return [];

        $files = glob("{$dir}/*.zip") ?: [];

        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        return array_map(function ($file) {
            return [
                'filename'   => basename($file),
                'size'       => $this->format_bytes(filesize($file)),
                'created_at' => date('M d, Y g:i A', filemtime($file)),
            ];
        }, $files);
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
