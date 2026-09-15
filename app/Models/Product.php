<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'subtitle',
        'description',
        'grade',
        'condition',
        'weight',
        'capacity',
        'rating',
        'reviews_count',
        'price_per_day',
        'stock_total',
        'stock_available',
        'main_image',
        'specs',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'features' => 'array',
            'is_active' => 'boolean',
            'rating' => 'decimal:1',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function bundles()
    {
        return $this->belongsToMany(Bundle::class, 'bundle_product')->withPivot('quantity');
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock_available > 0;
    }
}
