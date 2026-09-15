<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\ReturnRecord;
use App\Notifications\AdminActivityNotification;
use Illuminate\Support\Collection;

/**
 * Layanan terpusat untuk notifikasi aktivitas admin.
 *
 * Berisi:
 *  - pembuatan notifikasi database kepada seluruh admin,
 *  - pengambilan data notifikasi + hitungan sidebar badge untuk admin yang sedang login.
 *
 * Dipakai bersama oleh View Composer (agar bell & badge sidebar tersedia GLOBAL
 * di semua halaman admin) dan oleh controller yang memicu event.
 */
class AdminNotificationService
{
    /**
     * Kirim satu notifikasi aktivitas ke seluruh akun admin (channel database).
     */
    public static function notifyAdmins(
        string $type,
        string $title,
        string $body,
        string $icon = '🔔',
        ?string $url = null
    ): void {
        $admins = Admin::all();
        if ($admins->isEmpty()) {
            return;
        }

        $notification = new AdminActivityNotification($type, $title, $body, $icon, $url);

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }
    }

    /**
     * Hitung jumlah item yang membutuhkan perhatian admin per menu sidebar,
     * berdasarkan status yang benar-benar ada di database.
     *
     * @return array{penyewaan:int, pembayaran:int, pengembalian:int, refund:int}
     */
    public static function sidebarBadgeCounts(): array
    {
        return [
            // Penyewaan pending yang belum dikonfirmasi admin.
            'penyewaan' => (int) Order::where('status', 'pending')->count(),

            // Pembayaran (bukti transfer) yang menunggu validasi admin.
            'pembayaran' => (int) Payment::where('status', 'pending')->count(),

            // Pengembalian yang menunggu inspeksi admin (condition belum diisi).
            'pengembalian' => (int) ReturnRecord::whereNull('condition')->count(),

            // Refund yang belum diproses admin.
            'refund' => (int) Refund::where('status', Refund::STATUS_PENDING)->count(),
        ];
    }

    /**
     * Ambil notifikasi admin terbaru + jumlah belum dibaca untuk admin yang sedang login.
     * @return array{0: Collection, 1: int}
     */
    public static function notificationsForCurrentAdmin(): array
    {
        $notifications = collect();
        $unreadCount = 0;

        if (session('account_role') === 'admin' && session('account_id')) {
            $admin = Admin::find(session('account_id'));
            if ($admin) {
                $unreadCount = (int) $admin->unreadNotifications()->count();
                $notifications = $admin->notifications()->latest()->limit(8)->get();
            }
        }

        return [$notifications, $unreadCount];
    }
}
