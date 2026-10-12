<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Restorable versions of the KPI catalog ("save files"). Each row holds a
        // complete JSON copy of the categories + KPIs at the moment it was taken.
        Schema::create('kpi_catalog_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('label', 120);
            $table->text('note')->nullable();
            $table->longText('payload');
            // The seeded pre-revision catalog. Locked: cannot be renamed or deleted,
            // so there is always a way back to the official matrix.
            $table->boolean('is_baseline')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->seedBaseline();
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_catalog_snapshots');
    }

    // Rebuilds the original catalog from the seed data files, so the baseline is
    // available even on databases whose catalog has already been edited.
    private function seedBaseline(): void
    {
        $categoryFile = database_path('seeders/data/kpi-categories.php');
        $kpiFile      = database_path('seeders/data/kpis.php');

        if (!file_exists($categoryFile) || !file_exists($kpiFile)) {
            return;
        }

        $categories = collect(require $categoryFile)->map(fn ($c) => [
            'code'       => $c['code'],
            'name'       => $c['name'],
            'weight'     => (float) $c['weight'],
            'sort_order' => (int) $c['sort_order'],
        ])->values()->all();

        $kpis = collect(require $kpiFile)->map(fn ($k) => [
            'code'            => $k['code'],
            'category_code'   => $k['category_code'],
            'name'            => $k['name'],
            'weight'          => (float) $k['weight'],
            'is_scored'       => (bool) $k['is_scored'],
            'inverse_scoring' => (bool) $k['inverse_scoring'],
            'derivation_type' => ($k['derivation_type'] ?? '') === '' ? null : $k['derivation_type'],
            'sort_order'      => (int) $k['sort_order'],
        ])->values()->all();

        DB::table('kpi_catalog_snapshots')->insert([
            'label'       => 'Original (official PSTD matrix)',
            'note'        => 'The catalog as originally seeded: ' . count($kpis) . ' KPIs across ' . count($categories) . ' categories.',
            'payload'     => json_encode(['version' => 1, 'categories' => $categories, 'kpis' => $kpis]),
            'is_baseline' => true,
            'created_by'  => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
};
