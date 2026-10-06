<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiCatalogSnapshot extends Model
{
    public $table = 'kpi_catalog_snapshots';

    protected $fillable = ['label', 'note', 'payload', 'is_baseline', 'created_by'];

    protected $casts = [
        'payload'     => 'array',
        'is_baseline' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
