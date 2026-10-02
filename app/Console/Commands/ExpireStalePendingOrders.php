<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Batalkan order `pending` yang sudah terlalu lama dan lepaskan stok yang
 * di-reservasinya.
 *
 * Latar belakang: stok di-reserve saat checkout supaya dua pelanggan tidak
 * bisa mengambil unit terakhir bersamaan. Order pending yang ditinggalkan
 * (checkout tidak diselesaikan / pembayaran tidak pernah diverifikasi admin)
 * akan terus memegang stok bila tidak dilepas, sehingga pelanggan lain
 * ditolak walau barang sebenarnya tersedia.
 *
 * Jadi setiap order pending yang umurnya melewati ambang (default 24 jam)
 * dibatalkan otomatis dan stoknya dikembalikan.
 *
 * Dijalankan oleh Laravel Scheduler: php artisan schedule:run
 */
class ExpireStalePendingOrders extends Command
{
    protected $signature = 'orders:expire-pending
                            {--hours=24 : Umur maksimum order pending sebelum dibatalkan}';

    protected $description = 'Batalkan order pending yang kedaluwarsa dan lepaskan stok reservasinya';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);

        // Hanya ambil id dulu; tiap order diproses dalam transaksinya sendiri
        // supaya satu order bermasalah tidak membatalkan seluruh batch.
        $staleIds = Order::where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->orderBy('id')
            ->pluck('id');

        if ($staleIds->isEmpty()) {
            $this->info("Tidak ada order pending yang lewat {$hours} jam.");

            return self::SUCCESS;
        }

        $cancelled = 0;

        foreach ($staleIds as $orderId) {
            try {
                $didCancel = DB::transaction(function () use ($orderId, $hours) {
                    // Kunci baris order lalu periksa ulang statusnya. Admin bisa
                    // saja sudah memproses order ini di detik yang sama, dan
                    // tanpa penguncian itu reservasi bisa dilepas dua kali.
                    $order = Order::with(['items.product', 'items.bundle.products'])
                        ->lockForUpdate()
                        ->find($orderId);

                    if (! $order || $order->status !== 'pending') {
                        return false;
                    }

                    // Syarat `created_at <= cutoff` sudah difilter pada query
                    // awal; penguncian + pemeriksaan status di atas yang menjaga
                    // agar order tidak dibatalkan dua kali.
                    $order->status = 'cancelled';
                    $order->notes = trim(($order->notes ? $order->notes.' | ' : '')
                        ."Order dibatalkan otomatis karena tidak diproses admin dalam {$hours} jam. Stok yang dicadangkan telah dikembalikan.");
                    $order->save();

                    // Pembayaran yang masih menggantung ikut dibatalkan agar admin
                    // tidak bisa menyetujui order yang sudah kedaluwarsa.
                    // (`payments` tidak punya kolom catatan; alasannya tersimpan
                    // di `orders.notes` di atas.)
                    Payment::where('order_id', $order->id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'failed',
                            'updated_at' => now(),
                        ]);

                    app(OrderStockService::class)->releaseReservedStock($order);

                    return true;
                });

                if ($didCancel) {
                    $cancelled++;
                }
            } catch (\Throwable $e) {
                // Jangan hentikan seluruh batch; catat lalu lanjut.
                Log::error('Gagal membatalkan order pending kedaluwarsa', [
                    'order_id' => $orderId,
                    'message' => $e->getMessage(),
                ]);

                $this->warn("Order #{$orderId} gagal diproses: ".$e->getMessage());
            }
        }

        $this->info("Order pending kedaluwarsa (> {$hours} jam): {$cancelled} dibatalkan dari {$staleIds->count()} kandidat.");

        return self::SUCCESS;
    }
}
