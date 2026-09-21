<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'code',
        'user_id',
        'rent_start',
        'rent_end',
        'subtotal',
        'service_fee',
        'discount',
        'total',
        'status',
        'notes',
        'delivery_method',
        'recipient_name',
        'recipient_phone',
        'delivery_address',
        'delivery_note',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'rent_start' => 'date',
            'rent_end' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Label human-readable untuk metode pengambilan barang sewa.
     */
    public function getDeliveryMethodLabelAttribute(): string
    {
        return $this->delivery_method === 'delivery' ? 'Dikirim ke Lokasi' : 'Ambil di Tempat';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRecord::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function latePenalty(): HasOne
    {
        return $this->hasOne(LatePenalty::class);
    }

    public function latestPayment(): ?Payment
    {
        return $this->payments()->latest('id')->first();
    }

    /**
     * Hitung status keterlambatan pengembalian secara dinamis.
     * Menggunakan:
     * - Tanggal pengembalian yang seharusnya: rent_end
     * - Tanggal pengembalian aktual: parameter tanggal / returned_at pada ReturnRecord / now() jika rental aktif
     */
    public function calculateOverdue(?\Carbon\CarbonInterface $actualDate = null, int $feePerDay = 10000): array
    {
        if (! $this->rent_end) {
            return [
                'is_overdue'      => false,
                'days_overdue'    => 0,
                'status'          => 'tepat_waktu',
                'status_label'    => 'Tepat Waktu',
                'fee_per_day'     => $feePerDay,
                'fee'             => 0,
                'calculated_fee'  => 0,
                'total_fee'       => 0,
                'expected_date'   => null,
                'actual_date'     => null,
                'is_returned'     => false,
            ];
        }

        $expectedDate = \Carbon\Carbon::parse($this->rent_end)->startOfDay();

        // Tentukan tanggal aktual pengembalian
        $isReturned = false;
        if (! $actualDate) {
            $record = $this->returns->firstWhere('order_item_id', null) ?? $this->returns->first();
            if ($record && $record->returned_at) {
                $actualDate = \Carbon\Carbon::parse($record->returned_at)->startOfDay();
                $isReturned = true;
            } elseif ($this->status === 'completed') {
                $actualDate = \Carbon\Carbon::parse($this->updated_at)->startOfDay();
                $isReturned = true;
            } elseif (in_array($this->status, ['active', 'paid'])) {
                $actualDate = now()->startOfDay();
                $isReturned = false;
            } else {
                $actualDate = \Carbon\Carbon::parse($this->updated_at)->startOfDay();
                $isReturned = true;
            }
        } else {
            $actualDate = \Carbon\Carbon::parse($actualDate)->startOfDay();
            $isReturned = true;
        }

        $daysOverdue = 0;
        if ($actualDate->greaterThan($expectedDate)) {
            $daysOverdue = (int) $expectedDate->diffInDays($actualDate);
        }

        $isOverdue = $daysOverdue > 0;
        $statusKey = $isOverdue
            ? ($isReturned ? 'terlambat' : 'belum_dikembalikan_terlambat')
            : 'tepat_waktu';

        $statusLabel = $isOverdue
            ? ($isReturned ? "{$daysOverdue} Hari Terlambat" : "Belum Dikembalikan / Terlambat ({$daysOverdue} Hari)")
            : 'Tepat Waktu';

        return [
            'is_overdue'      => $isOverdue,
            'days_overdue'    => $daysOverdue,
            'status'          => $statusKey,
            'status_label'    => $statusLabel,
            'fee_per_day'     => $feePerDay,
            'fee'             => $daysOverdue * $feePerDay,
            'calculated_fee'  => $daysOverdue * $feePerDay,
            'total_fee'       => $daysOverdue * $feePerDay,
            'expected_date'   => $expectedDate,
            'actual_date'     => $actualDate,
            'is_returned'     => $isReturned,
        ];
    }

    /**
     * Durasi penyewaan dalam hari (INKLUSIF): selisih tanggal kalender + 1.
     * Misal 30/08 -> 02/09 = 4 hari penyewaan.
     */
    public function rentalDays(): int
    {
        if ($this->rent_start && $this->rent_end) {
            return max(1, $this->rent_start->diffInDays($this->rent_end) + 1);
        }

        return 1;
    }

    /**
     * Tentukan status pengembalian barang sewa (dievaluasi dari data booking,
     * bukan hanya timer frontend). Hasil: 'not_due' | 'due' | 'overdue' | 'returned'.
     *
     * - returned: order sudah completed, atau sudah ada ReturnRecord yang disetujui admin.
     * - due     : hari ini sudah mencapai tanggal pengembalian (rent_end) & rental aktif.
     * - overdue : waktu sekarang sudah melewati akhir hari rent_end & rental aktif.
     * - not_due : belum waktunya / status tidak relevan (pending/cancelled).
     */
    public function returnState(): array
    {
        $returned = $this->status === 'completed'
            || $this->returns->contains(fn ($record) => $record->status === 'approved');

        if ($returned) {
            return [
                'state' => 'returned',
                'label' => 'Selesai / Returned',
                'badge' => 'returned',
                'message' => 'Barang telah dikembalikan dan dikonfirmasi oleh admin.',
            ];
        }

        // Notifikasi hanya berlaku untuk penyewaan yang sedang aktif berjalan.
        if (! in_array($this->status, ['active', 'paid']) || ! $this->rent_end) {
            return ['state' => 'not_due', 'label' => '', 'badge' => '', 'message' => ''];
        }

        $tz = config('app.timezone');
        $now = now($tz);
        $dayStart = $this->rent_end->copy()->timezone($tz)->startOfDay();
        $dayEnd = $this->rent_end->copy()->timezone($tz)->endOfDay();

        if ($now->greaterThan($dayEnd)) {
            return [
                'state' => 'overdue',
                'label' => 'Terlambat Mengembalikan',
                'badge' => 'overdue',
                'message' => 'Waktu pengembalian telah lewat. Silakan segera mengembalikan barang.',
            ];
        }

        if ($now->greaterThanOrEqualTo($dayStart)) {
            return [
                'state' => 'due',
                'label' => 'Waktunya Dikembalikan',
                'badge' => 'due',
                'message' => 'Waktu pengembalian barang Anda sudah tiba. Silakan segera mengembalikan barang.',
            ];
        }

        return ['state' => 'not_due', 'label' => '', 'badge' => '', 'message' => ''];
    }
}
