<?php

namespace App\View\Composers;

use App\Services\AdminNotificationService;
use Illuminate\View\View;

/**
 * Menyediakan data global untuk layout admin:
 *  - daftar notifikasi terbaru + jumlah belum dibaca (untuk bell di header),
 *  - hitungan badge sidebar (penyewaan/pembayaran/pengembalian/refund).
 *
 * Melekat pada semua halaman halaman admin sehingga bell & badge sidebar
 * selalu tersedia di setiap halaman, tanpa query yang diulang di tiap controller.
 */
class AdminLayoutComposer
{
    /**
     * Bind data ke tampilan.
     */
    public function compose(View $view): void
    {
        // Didahulukan menghitung badge sidebar & notifikasi hanya untuk role admin.
        if (session('account_role') === 'admin') {
            [$notifications, $unreadCount] = AdminNotificationService::notificationsForCurrentAdmin();

            $view->with('adminNotifications', $notifications);
            $view->with('adminUnreadCount', $unreadCount);
            $view->with('adminSidebar', AdminNotificationService::sidebarBadgeCounts());
        }
    }
}
