<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPICategory extends Model
{
    public $table      = 'kpi_categories';
    public $timestamps = false;
    protected $fillable = ['code', 'name', 'weight', 'sort_order'];
    protected $casts = ['weight' => 'float'];

    public function kpis()
    {
        return $this->hasMany(KPI::class, 'category_id')->orderBy('sort_order');
    }

    /**
     * The whole KPI matrix in the shape every front-end screen consumes:
     * categories in sort order, each carrying its own KPIs, with weights as
     * plain 0..1 floats.
     *
     * Everything that renders a category name, a category weight, or a
     * per-category subtotal reads this — so renaming a category, moving a KPI
     * between categories, or re-weighting one on the KPI Catalog page lands on
     * every screen at once instead of drifting against a hardcoded copy of the
     * old split.
     *
     * $kpiExtra adds the per-KPI fields a particular screen needs on top of the
     * shared ones (e.g. the saved target/accomplished values on the KPI Data
     * editor). It receives the KPI model and returns an array to merge in.
     */
    public static function catalog(?callable $kpiExtra = null): array
    {
        return static::with(['kpis' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')->get()
            ->map(fn ($cat) => [
                'id'     => $cat->id,
                'code'   => $cat->code,
                'name'   => $cat->name,
                'weight' => (float) $cat->weight,
                'kpis'   => $cat->kpis->map(fn ($k) => array_merge([
                    'id'              => $k->id,
                    'category_id'     => $k->category_id,
                    'code'            => $k->code,
                    'name'            => $k->name,
                    'weight'          => (float) $k->weight,
                    'is_scored'       => (bool) $k->is_scored,
                    'inverse_scoring' => (bool) $k->inverse_scoring,
                    'derivation_type' => $k->derivation_type,
                ], $kpiExtra ? $kpiExtra($k) : []))->values()->toArray(),
            ])->values()->toArray();
    }

    /** Category codes in matrix order — the canonical order for subtotal columns. */
    public static function orderedCodes(): array
    {
        return static::orderBy('sort_order')->pluck('code')->all();
    }
}
