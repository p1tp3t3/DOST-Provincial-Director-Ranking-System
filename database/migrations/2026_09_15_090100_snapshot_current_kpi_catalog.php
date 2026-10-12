<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Safety net: the catalog live on this database right now is somebody's work,
    // and the Versions panel ships with a Restore button pointed at the original
    // matrix. Capture the current state as its own save file so the first restore
    // can always be undone.
    public function up(): void
    {
        $alreadySaved = DB::table('kpi_catalog_snapshots')->where('is_baseline', false)->exists();
        if ($alreadySaved) {
            return;
        }

        $categories = DB::table('kpi_categories')->orderBy('sort_order')->get();
        if ($categories->isEmpty()) {
            return;
        }

        $codeById = $categories->pluck('code', 'id');

        $kpis = DB::table('kpis')
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($k) => isset($codeById[$k->category_id]))
            ->map(fn ($k) => [
                'code'            => $k->code,
                'category_code'   => $codeById[$k->category_id],
                'name'            => $k->name,
                'weight'          => (float) $k->weight,
                'is_scored'       => (bool) $k->is_scored,
                'inverse_scoring' => (bool) $k->inverse_scoring,
                'derivation_type' => $k->derivation_type,
                'sort_order'      => (int) $k->sort_order,
            ])->values()->all();

        DB::table('kpi_catalog_snapshots')->insert([
            'label'       => 'Current matrix (auto-saved)',
            'note'        => 'Captured automatically when catalog versions were enabled, so the '
                             . 'catalog in use at that moment can always be restored.',
            'payload'     => json_encode([
                'version'    => 1,
                'categories' => $categories->map(fn ($c) => [
                    'code'       => $c->code,
                    'name'       => $c->name,
                    'weight'     => (float) $c->weight,
                    'sort_order' => (int) $c->sort_order,
                ])->values()->all(),
                'kpis' => $kpis,
            ]),
            'is_baseline' => false,
            'created_by'  => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('kpi_catalog_snapshots')->where('label', 'Current matrix (auto-saved)')->delete();
    }
};
