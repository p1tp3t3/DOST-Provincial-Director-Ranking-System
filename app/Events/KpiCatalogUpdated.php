<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired whenever the shared KPI catalog changes (a KPI added, its weight/category
 * edited, or soft-deleted) — anything that affects RankingService's live scoring
 * for every province.
 *
 * Broadcast to every authenticated user, because every role has a screen that
 * renders category names, weights, or the scores derived from them: the
 * dashboards, the performance map, the KPI Data editor and the province
 * profile all listen and refresh without a manual reload. Only Super Admin and
 * Sub Admin can actually change the catalog. See routes/channels.php for the
 * channel authorization, and resources/js/Composables/useKpiCatalogSync.js for
 * the listener each screen uses.
 */
class KpiCatalogUpdated implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public string $action,   // 'created' | 'updated' | 'deleted'
        public int $kpiId,
        public ?string $kpiName = null,
        public ?int $categoryId = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('kpi-catalog')];
    }

    public function broadcastWith(): array
    {
        return [
            'action'      => $this->action,
            'kpi_id'      => $this->kpiId,
            'kpi_name'    => $this->kpiName,
            'category_id' => $this->categoryId,
        ];
    }
}
