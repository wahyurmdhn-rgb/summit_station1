<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi untuk Admin (channel database).
 * Memberitahu admin ketika terjadi aktivitas penting dari user, misalnya
 * penyewaan/booking baru atau pengunggahan bukti pembayaran.
 */
class AdminActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,
        public string $title,
        public string $body,
        public string $icon = '🔔',
        public ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->icon,
            'url' => $this->url,
        ];
    }
}
