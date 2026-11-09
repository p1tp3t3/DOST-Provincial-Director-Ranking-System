<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Saves the KPI catalog revised from the regional comments (30 KPIs, 60/25/15)
// as a catalog version, next to the "Original (official PSTD matrix)" one, so
// the super admin can switch between the two from Catalog Versions. Built the
// same way as the original: the KPIs of data/kpis.php that
// data/kpi-weights-proposed.php keeps, with its weights. It only adds the
// version; the live catalog changes when someone restores it (a fresh seed
// already starts on it).
return new class extends Migration
{
    public function up(): void
    {
        $proposedFile = database_path('seeders/data/kpi-weights-proposed.php');
        $categoryFile = database_path('seeders/data/kpi-categories.php');
        $kpiFile      = database_path('seeders/data/kpis.php');
        if (!file_exists($proposedFile) || !file_exists($categoryFile) || !file_exists($kpiFile)) {
            return;
        }

        $proposed = require $proposedFile;
        if (DB::table('kpi_catalog_snapshots')->where('label', $proposed['label'])->exists()) {
            return;
        }

        // Only the KPIs the revision keeps; restoring the version removes the rest.
        $kpis = collect(require $kpiFile)->filter(fn ($k) => isset($proposed['weights'][$k['code']]))->map(function ($k) use ($proposed) {
            $weight = (float) $proposed['weights'][$k['code']];
            return [
                'code'            => $k['code'],
                'category_code'   => $k['category_code'],
                'name'            => $k['name'],
                'weight'          => $weight,
                'is_scored'       => $weight > 0 && (bool) $k['is_scored'],
                'inverse_scoring' => (bool) $k['inverse_scoring'],
                'derivation_type' => ($k['derivation_type'] ?? '') === '' ? null : $k['derivation_type'],
                'sort_order'      => (int) $k['sort_order'],
            ];
        })->values();

        // A category's weight is the sum of its KPIs' weights.
        $categories = collect(require $categoryFile)->map(fn ($c) => [
            'code'       => $c['code'],
            'name'       => $c['name'],
            'weight'     => round($kpis->where('category_code', $c['code'])->sum('weight'), 4),
            'sort_order' => (int) $c['sort_order'],
        ])->values()->all();

        DB::table('kpi_catalog_snapshots')->insert([
            'label'       => $proposed['label'],
            'note'        => $proposed['note'],
            'payload'     => json_encode(['version' => 1, 'categories' => $categories, 'kpis' => $kpis->all()]),
            'is_baseline' => false,
            'created_by'  => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        $proposedFile = database_path('seeders/data/kpi-weights-proposed.php');
        if (file_exists($proposedFile)) {
            DB::table('kpi_catalog_snapshots')->where('label', (require $proposedFile)['label'])->delete();
        }
    }
};
