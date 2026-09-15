<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bundle extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'bundle_product')
            ->withPivot('quantity');
    }

    /**
     * Hitung jumlah paket (bundle) yang benar-benar dapat disewakan
     * berdasarkan stok aktual seluruh barang anggota di database.
     *
     * available_package_quantity =
     *   MIN( floor(stock_item / required_quantity) ) untuk semua item paket.
     *
     * Jika salah satu item stoknya 0 (atau paket tidak punya anggota),
     * hasilnya 0 sehingga paket berstatus HABIS.
     *
     * Sumber tunggal untuk katalog, detail paket, keranjang, dan validasi
     * booking agar konsisten.
     */
    public function availableStock(): int
    {
        $products = $this->products;
        if ($products->isEmpty()) {
            return 0;
        }

        $stock = PHP_INT_MAX;
        foreach ($products as $p) {
            $perBundle = (int) ($p->pivot->quantity ?? 1);
            if ($perBundle > 0) {
                $stock = min($stock, (int) floor($p->stock_available / $perBundle));
            }
        }

        return $stock === PHP_INT_MAX ? 0 : $stock;
    }

    /**
     * Hitung kapasitas maksimal paket berdasarkan total stok seluruh anggota
     * (stock_total), yaitu jumlah paket maksimum yang dapat disediakan saat
     * semua barang anggota tersedia penuh. Dipakai untuk menghitung persentase
     * ketersediaan pada dashboard.
     */
    public function maxStock(): int
    {
        $products = $this->products;
        if ($products->isEmpty()) {
            return 0;
        }

        $stock = PHP_INT_MAX;
        foreach ($products as $p) {
            $perBundle = (int) ($p->pivot->quantity ?? 1);
            if ($perBundle > 0) {
                $stock = min($stock, (int) floor((int) $p->stock_total / $perBundle));
            }
        }

        return $stock === PHP_INT_MAX ? 0 : $stock;
    }

    /**
     * Apakah paket ini tersedia (stok aktual > 0)?
     */
    public function isAvailable(): bool
    {
        return $this->availableStock() > 0;
    }
}
