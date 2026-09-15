<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRecord extends Model
{
    protected $fillable = [
        'order_id',
        'order_item_id',
        'proof_path',
        'note',
        'status',
        'returned_at',
        'condition',
        'damage_description',
        'damage_cost',
        'damage_paid_at',
        'inspection_note',
        'inspection_photo',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
            'damage_cost'  => 'integer',
            'damage_paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * URL tampilan bukti pengembalian via route terkontrol (storage privat).
     */
    public function getProofUrlAttribute(): ?string
    {
        $raw = (string) ($this->proof_path ?? '');

        if ($raw === '' || $raw === 'null') {
            return null;
        }

        return route('file.return-proof', $this->id);
    }

    /**
     * Label kondisi yang user-friendly.
     */
    public function getConditionLabelAttribute(): string
    {
        return match ($this->condition) {
            'excellent'     => 'Kondisi Sangat Baik',
            'good'          => 'Kondisi Baik',
            'needs_cleaning'=> 'Perlu Dibersihkan',
            'minor_damage'  => 'Kerusakan Ringan',
            'major_damage'  => 'Kerusakan Berat',
            default         => 'Belum Diperiksa',
        };
    }

    /**
     * CSS class badge untuk kondisi.
     */
    public function getConditionBadgeClassAttribute(): string
    {
        return match ($this->condition) {
            'excellent'     => 'condition-excellent',
            'good'          => 'condition-good',
            'needs_cleaning'=> 'condition-cleaning',
            'minor_damage'  => 'condition-minor',
            'major_damage'  => 'condition-major',
            default         => 'condition-pending',
        };
    }

    /**
     * Apakah pengembalian ini memiliki kerusakan?
     */
    public function getIsDamagedAttribute(): bool
    {
        return in_array($this->condition, ['minor_damage', 'major_damage']);
    }

    /**
     * Apakah pengembalian ini memiliki denda (nominal kerusakan > 0)?
     */
    public function getHasDendaAttribute(): bool
    {
        return (int) $this->damage_cost > 0;
    }

    /**
     * Apakah denda sudah lunas (status tersimpan di backend via damage_paid_at)?
     */
    public function getIsDendaPaidAttribute(): bool
    {
        return $this->hasDenda && ! is_null($this->damage_paid_at);
    }

    /**
     * Label status pembayaran denda.
     */
    public function getDendaStatusLabelAttribute(): string
    {
        if (! $this->hasDenda) {
            return '';
        }

        return $this->isDendaPaid ? 'Lunas' : 'Belum Lunas';
    }

    /**
     * Apakah masih ada pembayaran denda yang menunggu verifikasi admin?
     */
    public function getHasPendingDendaPaymentAttribute(): bool
    {
        return $this->hasDenda
            && ! $this->isDendaPaid
            && $this->order
            && $this->order->payments->contains(
                fn ($p) => $p->isDendaPayment && $p->status === 'pending'
            );
    }
}
