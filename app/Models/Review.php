<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'bundle_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_visible' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Check if the reviews table has a specific column (cached per request).
     *
     * Kolom order_id & is_visible kini dijamin tersedia oleh migration
     * (2026_08_27_000001 & 2026_08_31_195909), sehingga model tidak lagi
     * memutasi skema saat runtime. Metode ini hanya dipakai sebagai guard
     * pembacaan yang aman untuk environment lama / read-only.
     */
    private static array $columnCache = [];

    public static function hasCol(string $col): bool
    {
        if (! isset(static::$columnCache[$col])) {
            try {
                static::$columnCache[$col] = Schema::hasColumn('reviews', $col);
            } catch (\Throwable $e) {
                static::$columnCache[$col] = false;
            }
        }
        return static::$columnCache[$col];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope: only visible reviews (safe if is_visible column doesn't exist).
     */
    public function scopeVisible(Builder $query): Builder
    {
        if (static::hasCol('is_visible')) {
            return $query->where('is_visible', true);
        }
        return $query;
    }
}
