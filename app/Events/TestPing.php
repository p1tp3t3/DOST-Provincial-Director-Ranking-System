<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

// Manual connectivity check for the Reverb/Echo pipeline, on the real
// 'kpi-catalog' private channel (see routes/channels.php — super_admin/
// sub_admin only). Trigger from tinker while logged in as one of those roles:
// broadcast(new App\Events\TestPing('hi'));
// and watch the browser console for the "[Echo test]" log from echo.js.
class TestPing implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public string $message,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('kpi-catalog')];
    }

    public function broadcastAs(): string
    {
        return 'test.ping';
    }

    public function broadcastWith(): array
    {
        return [
            'message'   => $this->message,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
