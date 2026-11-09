<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when the super admin turns maintenance mode on or off, so every open
 * page reacts without a manual refresh: on → everyone except super admins is
 * sent to /maintenance-notice; off → people waiting on that notice are sent
 * back in. Listener: resources/js/echo.js.
 *
 * A public channel on purpose: signed-out visitors (landing, login) are
 * affected too, and the payload is only an on/off flag.
 */
class MaintenanceModeChanged implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(public bool $enabled) {}

    public function broadcastOn(): array
    {
        return [new Channel('system')];
    }

    public function broadcastAs(): string
    {
        return 'maintenance.changed';
    }

    public function broadcastWith(): array
    {
        return ['enabled' => $this->enabled];
    }
}
