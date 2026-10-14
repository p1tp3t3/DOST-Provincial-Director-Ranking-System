<?php

namespace App\Helpers;

use App\Models\User;
use App\Services\ProvinceOverviewService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use Throwable;

class NotificationHelper
{
    /**
     * Notify users in the system and through web push:
     *
     *   1. in-system — saved to the notifications table (the header bell) and
     *      broadcast over Reverb on the user's private channel
     *      (App.Models.User.{id}, event "system-notification") so an open bell
     *      shows it instantly;
     *   2. web push — a browser notification, even with PRISM closed (users who
     *      haven't turned on browser notifications are skipped).
     *
     * The push is sent right away (an HTTP call per browser), so for many users
     * call this from a queued job — see App\Jobs\SendKpiReminder.
     *
     * @param User|iterable<User> $users
     * @param string|null $url       page to open when the notification is clicked
     * @param string      $category  e.g. 'kpi', 'account', 'system' — icon/colour in the bell
     * @param string|null $tag       a newer browser notification with the same tag replaces the older one
     * @param User|null   $sender    who sent it; defaults to the signed-in user, and is
     *                               null (shown as "System") when nobody is signed in,
     *                               e.g. from the scheduler or a queued job
     */
    public static function notify($users, string $title, string $body, ?string $url = null, string $category = 'system', ?string $tag = null, ?User $sender = null): void
    {
        $users  = $users instanceof User ? [$users] : $users;
        $sender ??= Auth::user();

        foreach ($users as $user) {
            self::inSystem($user, $title, $body, $url, $category, $sender);
            self::webPush($user, $title, $body, $url, $tag ?? $category);
        }
    }

    // Bell notification: stored, then broadcast over Reverb.
    private static function inSystem(User $user, string $title, string $body, ?string $url, string $category, ?User $sender): void
    {
        $notification = $user->notifications()->create([
            'id'        => (string) Str::uuid(),
            'type'      => $category,
            'sender_id' => $sender?->id,
            'data'      => ['title' => $title, 'body' => $body, 'url' => $url, 'category' => $category],
        ]);

        try {
            Broadcast::private("App.Models.User.{$user->id}")
                ->as('system-notification')
                ->with(self::forBell($notification, $sender ? [$sender->id => self::senderName($sender)] : []))
                ->sendNow(); // like the app's other events (ShouldBroadcastNow)
        } catch (Throwable $e) {
            // Reverb being down mustn't lose the notification: it's already
            // stored, and the bell shows it on the next page load.
            Log::warning('Notification broadcast failed: ' . $e->getMessage());
        }
    }

    // Browser push through the webpush package's channel (VAPID keys from
    // config/webpush.php; expired subscriptions are removed automatically).
    private static function webPush(User $user, string $title, string $body, ?string $url, string $tag): void
    {
        $message = (new WebPushMessage)
            ->title($title)
            ->body($body)
            ->icon('/assets/logo.png')
            ->tag($tag)
            ->data(['url' => $url ?? '/dashboard'])
            ->options(['TTL' => 86400]);

        $push = new class($message) extends Notification {
            public function __construct(private WebPushMessage $message) {}

            public function toWebPush($notifiable, $notification): WebPushMessage
            {
                return $this->message;
            }
        };

        try {
            app(WebPushChannel::class)->send($user, $push);
        } catch (Throwable $e) {
            Log::warning("Web push to user {$user->id} failed: " . $e->getMessage());
        }
    }

    // The shape the header bell reads (also what /notifications returns).
    // $senderNames: sender_id => name, to avoid a lookup per notification.
    public static function forBell($notification, array $senderNames = []): array
    {
        $senderId = $notification->sender_id;

        return [
            'id'         => $notification->id,
            'sender'     => $senderId === null
                ? 'System'
                : ($senderNames[$senderId] ?? self::senderName(User::with('profile')->find($senderId))),
            'title'      => $notification->data['title'] ?? '',
            'body'       => $notification->data['body'] ?? '',
            'url'        => $notification->data['url'] ?? null,
            'category'   => $notification->data['category'] ?? $notification->type,
            'read'       => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    // A sender's display name; deleted or missing senders show as "System".
    public static function senderName(?User $sender): string
    {
        return ProvinceOverviewService::personName($sender) ?? 'System';
    }

    public static function email($type, $email, $data) {
        switch($type) {
            case 'otp':
                break;
            case 'account-credentials':
                break;
        }
    }

    public static function sms($type, $contact_num, $message) {

    }
}
