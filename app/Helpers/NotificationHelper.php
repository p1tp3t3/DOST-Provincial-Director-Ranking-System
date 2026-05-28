<?php

namespace App\Helpers;

class NotificationHelper
{
    public static function db($sender, $receiver, $content) {
        /*
        return Notifications::create([
            'sender_id' => $sender,
            'receiver_id' => $receiver,
            'content' => $content
        ]);
        */
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
