<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'method',
        'amount',
        'status',
        'reference',
        'paid_at',
        'proof_image',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Prefix reference untuk pembayaran DENDA (denda pengembalian barang),
     * dibedakan dari pembayaran sewa utama (PAY-... / #TRX-...).
     */
    public const FINE_REFERENCE_PREFIX = 'FINE-';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Apakah pembayaran ini adalah pembayaran denda (bukan sewa utama)?
     */
    public function getIsDendaPaymentAttribute(): bool
    {
        return str_starts_with((string) ($this->attributes['reference'] ?? ''), self::FINE_REFERENCE_PREFIX);
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Order::class, 'id', 'id', 'order_id', 'user_id');
    }

    public function getTrxCodeAttribute(): string
    {
        if (! empty($this->attributes['trx_code'])) {
            return $this->attributes['trx_code'];
        }
        $suffix = ['Z', 'K', 'L', 'M', 'X', 'P'][$this->id % 6];
        $num = 99280 + ($this->id * 5) % 900;
        return "#TRX-{$num}-{$suffix}";
    }

    public function getFormattedMethodAttribute(): string
    {
        return match (strtolower($this->method)) {
            'bank_transfer', 'bca', 'bca_transfer' => 'BCA Transfer',
            'mandiri', 'mandiri_va' => 'Mandiri VA',
            'gopay' => 'GOPAY',
            'qris' => 'QRIS',
            'dana' => 'DANA',
            'ovo' => 'OVO',
            'shopeepay' => 'ShopeePay',
            default => strtoupper($this->method),
        };
    }

    public function getProofUrlAttribute(): ?string
    {
        $raw = (string) ($this->attributes['proof_image'] ?? '');

        if ($raw === '' || $raw === 'null') {
            return null;
        }

        // Legacy: proof_image berisi URL publik penuh dari storage/public.
        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://') || str_starts_with($raw, '//')) {
            return $raw;
        }

        // Baru: proof_image berisi path privat -> sajikan via route terkontrol.
        return route('file.payment-proof', $this->id);
    }

    /**
     * Apakah pembayaran ini memiliki bukti pembayaran yang di-upload?
     */
    public function getHasProofAttribute(): bool
    {
        $raw = (string) ($this->attributes['proof_image'] ?? '');
        return $raw !== '' && $raw !== 'null';
    }
}
