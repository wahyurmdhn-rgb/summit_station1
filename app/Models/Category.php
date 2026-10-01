<?php

namespace App\Models;

use App\Services\SkuService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'sku'];

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (empty($category->sku)) {
                $category->sku = SkuService::generateCategorySku($category->name, $category->id);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
