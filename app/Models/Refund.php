<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'code',
        'order_id',
        'user_id',
        'payment_id',
        'original_amount',
        'refund_amount',
        'reason',
        'description',
        'status',
        'reject_reason',
        'adjustment_reason',
        'approved_at',
        'rejected_at',
        'completed_at',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'integer',
            'refund_amount' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by', 'id_admin');
    }

    /**
     * Satu sumber status refund yang konsisten di seluruh aplikasi.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pengembalian Dana Menunggu',
            self::STATUS_APPROVED => 'Pengembalian Dana Disetujui',
            self::STATUS_REJECTED => 'Pengembalian Dana Ditolak',
            self::STATUS_COMPLETED => 'Pengembalian Dana Selesai',
            default => ucfirst($this->status ?? 'pending'),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'refund-badge-pending',
            self::STATUS_APPROVED => 'refund-badge-approved',
            self::STATUS_REJECTED => 'refund-badge-rejected',
            self::STATUS_COMPLETED => 'refund-badge-completed',
            default => 'refund-badge-pending',
        };
    }

    /**
     * Apakah refund masih bisa diproses admin (pending).
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}