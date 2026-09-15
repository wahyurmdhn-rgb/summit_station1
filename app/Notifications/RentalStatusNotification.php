<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi untuk User pemilik booking ketika Admin memproses penyewaan
 * (diterima / ditolak). Dikirim melalui channel database.
 */
class RentalStatusNotification extends Notification
{
    use Queueable;

    public const TYPE_ACCEPTED = 'rental_accepted';
    public const TYPE_REJECTED = 'rental_rejected';

    public const TYPE_RETURN_REMINDER = 'return_reminder';
    public const TYPE_RETURN_DUE = 'return_due';
    public const TYPE_RETURN_OVERDUE = 'return_overdue';

    public const TYPE_BOOKING_CREATED = 'booking_created';
    public const TYPE_PAYMENT_SUBMITTED = 'payment_submitted';
    public const TYPE_PAYMENT_CONFIRMED = 'payment_confirmed';
    public const TYPE_PAYMENT_REJECTED = 'payment_rejected';
    public const TYPE_RENTAL_COMPLETED = 'rental_completed';
    public const TYPE_RETURN_SUBMITTED = 'return_submitted';
    public const TYPE_RETURN_RECORDED = 'return_recorded';
    public const TYPE_RETURN_COMPLETED = 'return_completed';
    public const TYPE_DENDA_CHARGED = 'denda_charged';
    public const TYPE_DENDA_PAID = 'denda_paid';
    public const TYPE_LATE_PENALTY_ASSIGNED = 'late_penalty_assigned';
    public const TYPE_LATE_PENALTY_PAID = 'late_penalty_paid';

    public function __construct(
        public string $type,
        public string $title,
        public string $body,
        public string $icon = '🔔',
        public ?string $url = null,
        public ?string $orderCode = null,
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
            'order_code' => $this->orderCode,
        ];
    }
}