<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired whenever the shared KPI catalog changes (a KPI added, its weight/category
 * edited, or soft-deleted) — anything that affects RankingService's live scoring
 * for every province. Broadcast to super_admin/sub_admin so open KPI Data screens
 * refresh without a manual reload. See routes/channels.php for channel authorization.
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

    public function broadcastAs(): string
    {
        return 'kpi.catalog.updated';
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
