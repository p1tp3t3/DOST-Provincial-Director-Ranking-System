<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TestBroadcast implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public int $userId,
        public string $message,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("App.Models.User.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'test.broadcast';
    }

    public function broadcastWith(): array
    {
        return ['message' => $this->message];
    }
}
