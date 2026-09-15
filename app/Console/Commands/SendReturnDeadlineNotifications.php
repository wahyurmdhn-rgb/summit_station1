<?php

namespace App\Console\Commands;

use App\Services\ReturnNotificationService;
use Illuminate\Console\Command;

/**
 * Pemeriksaan otomatis tenggat pengembalian.
 *
 * Menjalankan pemeriksaan ulang status semua penyewaan yang sedang berjalan
 * dan mengirim notifikasi Pengingat / Tenggat / Terlambat ke user pemilik
 * booking bila belum ada (tanpa duplikasi).
 *
 * Dijalankan oleh Laravel Scheduler: php artisan schedule:run
 */
class SendReturnDeadlineNotifications extends Command
{
    protected $signature = 'return-deadline:notify';

    protected $description = 'Kirim notifikasi tenggat pengembalian (pengingat, hari ini, terlambat) ke user';

    public function handle(): int
    {
        $created = ReturnNotificationService::dispatchAll();

        $this->info("Pemeriksaan tenggat pengembalian selesai. {$created} notifikasi baru dibuat.");

        return self::SUCCESS;
    }
}