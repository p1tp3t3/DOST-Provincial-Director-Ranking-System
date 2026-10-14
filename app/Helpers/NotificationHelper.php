<?php

namespace App\Helpers;

use App\Models\Notification;
use App\Models\User;

class NotificationHelper
{
    // An in-app notification (the header bell). $content: { type, message, url }.
    public static function db($sender, $receiver, $content) {
        return Notification::create([
            'sender_id'   => $sender instanceof User ? $sender->id : $sender,
            'receiver_id' => $receiver instanceof User ? $receiver->id : $receiver,
            'content'     => $content,
        ]);
    }

    // The same notification to several users, each once, never to the sender.
    public static function dbMany(User $sender, iterable $receivers, array $content): void {
        $sent = [];
        foreach ($receivers as $receiver) {
            $id = $receiver instanceof User ? $receiver->id : (int) $receiver;
            if ($id === $sender->id || isset($sent[$id])) continue;
            self::db($sender, $id, $content);
            $sent[$id] = true;
        }
    }

    // What the bell shows: the latest notifications and how many are unread.
    public static function feed(User $user, int $limit = 15): array {
        $base = Notification::where('receiver_id', $user->id);

        return [
            'unread' => (clone $base)->whereNull('read_since')->count(),
            'items'  => (clone $base)->latest('id')->limit($limit)->get()->map(fn(Notification $n) => [
                'id'      => $n->id,
                'type'    => $n->content['type'] ?? 'system',
                'message' => $n->content['message'] ?? '',
                'url'     => $n->content['url'] ?? null,
                'read'    => $n->read_since !== null,
                'time'    => $n->created_at?->diffForHumans(),
            ])->values()->all(),
        ];
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

    public static function web_push($endpoint, $data) {

    }
}
