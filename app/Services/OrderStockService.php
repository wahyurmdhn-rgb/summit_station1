<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;

/**
 * Pelepasan stok yang di-reserve sebuah order.
 *
 * Stok di-reserve saat CHECKOUT (PaymentController::reserveCartStock) memakai
 * conditional decrement, jadi order berstatus `pending` pun sudah "memegang"
 * stok. Setiap kali order meninggalkan status yang memegang stok
 * (pending/active), reservasi harus dikembalikan agar stok tidak terkunci
 * selamanya.
 *
 * Logika ini dipakai bersama oleh panel admin (tolak/batal/selesai) dan oleh
 * perintah `orders:expire-pending`, sehingga perhitungannya hanya ada di satu
 * tempat dan tidak bisa berbeda antar jalur.
 */
class OrderStockService
{
    /**
     * Kembalikan stok yang di-reserve order ke kolom `stock_available`.
     *
     * Aman dipanggil berulang: pemanggil wajib memastikan order sudah berada
     * di luar status `pending`/`active` (dalam transaksi + lock baris order)
     * sebelum memanggil method ini.Sebagai pengaman tambahan, increment diberi
     * batas `stock_available < stock_total` supaya pelepasan ganda tidak pernah
     * menciptakan stok hantu (ketersediaan melebihi jumlah(unit sebenarnya).
     */
    public function releaseReservedStock(Order $order): void
    {
        // Muat ulang relasi bila pemanggil hanya mengoper model order polos.
        if (! $order->relationLoaded('items')) {
            $order->load(['items.product', 'items.bundle.products']);
        }

        foreach ($order->items as $item) {
            if ($item->product_id) {
                $this->giveBack((int) $item->product_id, max(1, (int) ($item->quantity ?? 1)));
            }

            if (! $item->bundle_id) {
                continue;
            }

            $bundle = $item->bundle ?: $item->bundle()->with('products')->first();

            foreach ($bundle?->products ?? [] as $bundleProduct) {
                $needed = (int) ($bundleProduct->pivot->quantity ?? 1) * max(1, (int) ($item->quantity ?? 1));
                $this->giveBack((int) $bundleProduct->id, $needed);
            }
        }
    }

    /**
     * Tambah `stock_available` TANPA melewati `stock_total`.
     */
    private function giveBack(int $productId, int $amount): void
    {
        Product::whereKey($productId)
            ->whereColumn('stock_available', '<', 'stock_total')
            ->increment('stock_available', $amount);
    }
}
