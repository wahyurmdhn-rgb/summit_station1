<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'bundle_id',
        'quantity',
        'days',
        'rent_start',
    ];

    protected function casts(): array
    {
        return [
            'rent_start' => 'date',
        ];
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

    public function getSubtotalAttribute(): int
    {
        if ($this->product) {
            $price = (int) $this->product->price_per_day;
        } elseif ($this->bundle) {
            $price = (int) $this->bundle->price;
        } else {
            $price = 0;
        }

        return $price * $this->quantity * $this->days;
    }
}
