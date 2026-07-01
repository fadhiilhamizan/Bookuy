<?php

// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    /**
     * Create an in-app notification for a user.
     * Centralises the notification shape that was previously duplicated across controllers.
     */
    public function send(int $userId, string $title, string $message, string $type = 'system', string $icon = 'icon-info.png'): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message,
            'type'    => $type,
            'icon'    => $icon,
        ]);
    }
}
