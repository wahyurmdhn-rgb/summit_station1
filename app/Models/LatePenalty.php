<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LatePenalty extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'return_record_id',
        'payment_id',
        'days_overdue',
        'fee_per_day',
        'total_fee',
        'status',
        'admin_notes',
        'paid_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'days_overdue' => 'integer',
            'fee_per_day'  => 'integer',
            'total_fee'    => 'integer',
            'paid_at'      => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public const STATUS_NO_SANCTION = 'tidak_ada_sanksi';
    public const STATUS_UNPROCESSED  = 'belum_diproses';
    public const STATUS_PENDING      = 'menunggu_pembayaran';
    public const STATUS_VERIFYING    = 'menunggu_verifikasi';
    public const STATUS_PAID         = 'sudah_dibayar';
    public const STATUS_CANCELLED    = 'dibatalkan';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function returnRecord(): BelongsTo
    {
        return $this->belongsTo(ReturnRecord::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NO_SANCTION => 'Tidak Ada Sanksi',
            self::STATUS_UNPROCESSED  => 'Belum Diproses',
            self::STATUS_PENDING      => 'Menunggu Pembayaran',
            self::STATUS_VERIFYING    => 'Menunggu Verifikasi',
            self::STATUS_PAID         => 'Lunas',
            self::STATUS_CANCELLED    => 'Dibatalkan',
            default                   => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NO_SANCTION => 'badge-no-sanction',
            self::STATUS_UNPROCESSED  => 'badge-unprocessed',
            self::STATUS_PENDING      => 'badge-pending-penalty',
            self::STATUS_VERIFYING    => 'badge-verifying-penalty',
            self::STATUS_PAID         => 'badge-paid-penalty',
            self::STATUS_CANCELLED    => 'badge-cancelled-penalty',
            default                   => 'badge-default',
        };
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === self::STATUS_PAID || ! is_null($this->paid_at);
    }

    public function getIsVerifyingAttribute(): bool
    {
        return $this->status === self::STATUS_VERIFYING;
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
