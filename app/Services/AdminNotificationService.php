<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\ReturnRecord;
use App\Models\User;
use App\Notifications\AdminActivityNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
     * Tipe notifikasi untuk user yang baru selesai mendaftar akun.
     *
     * Dipakai juga oleh halaman /admin/users untuk mengenali baris pengguna
     * yang perlu disorot ketika notifikasi dibuka.
     */
    public const TYPE_USER_REGISTERED = 'user_registered';

    /**
     * Jumlah notifikasi terbaru yang ditampilkan pada dropdown lonceng.
     */
    public const DROPDOWN_LIMIT = 8;

    /**
     * Kirim satu notifikasi aktivitas ke seluruh akun admin (channel database).
     *
     * @param  array<string, mixed>  $meta  Data pendukung pada payload notifikasi, mis. user_id/username.
     */
    public static function notifyAdmins(
        string $type,
        string $title,
        string $body,
        string $icon = '🔔',
        ?string $url = null,
        array $meta = []
    ): void {
        $admins = Admin::all();
        if ($admins->isEmpty()) {
            return;
        }

        $notification = new AdminActivityNotification($type, $title, $body, $icon, $url, $meta);

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }
    }

    /**
     * Kabari seluruh admin ketika ada user baru yang berhasil mendaftar.
     *
     * Hanya dipanggil SETELAH user benar-benar tersimpan di database, sehingga
     * registrasi yang gagal tidak pernah menghasilkan notifikasi.
     *
     * Notifikasi diarahkan ke halaman manajemen pengguna (/admin/users) dengan
     * parameter `user` supaya baris pengguna baru dapat disorot.
     */
    public static function notifyNewUserRegistered(User $user): void
    {
        $name = trim((string) ($user->name ?: $user->username));
        if ($name === '') {
            $name = 'Pengguna baru';
        }

        $username = trim((string) $user->username);
        $identity = $username !== '' ? $username : (string) $user->email;

        $body = "{$name} baru saja membuat akun di Summit Station.";

        if ($username !== '' && $user->name !== $username) {
            $body = "{$name} (@{$username}) baru saja membuat akun di Summit Station.";
        }

        // User di bawah umur wajib menunggu verifikasi persetujuan orang tua.
        if ($user->parent_consent_status === 'submitted') {
            $body .= ' Menunggu verifikasi persetujuan orang tua/wali.';
        }

        self::notifyAdmins(
            self::TYPE_USER_REGISTERED,
            '👤 User Baru Mendaftar',
            $body,
            '👤',
            route('admin.users', ['user' => $user->getKey()]),
            [
                'user_id' => $user->getKey(),
                'user_name' => $user->name,
                'username' => $username !== '' ? $username : null,
                'email' => $user->email,
                'identity' => $identity,
            ]
        );
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
     *
     * Dipakai oleh View Composer (render awal halaman admin) maupun endpoint
     * polling, sehingga badge & dropdown selalu memakai sumber data yang sama.
     *
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
                $notifications = $admin->notifications()->latest()->limit(self::DROPDOWN_LIMIT)->get();
            }
        }

        return [$notifications, $unreadCount];
    }

    /**
     * Tanda ringkas (signature) dari kondisi notifikasi admin yang sedang login.
     *
     * Diturunkan dari data yang SUDAH diambil notificationsForCurrentAdmin(),
     * sehingga tidak menambah query sama sekali. Dipakai oleh polling navbar
     * untuk mendeteksi notifikasi baru / perubahan status baca tanpa memuat
     * ulang seluruh halaman.
     *
     * Signature berubah bila:
     *  - ada notifikasi baru (kombinasi id item terbaru berubah), atau
     *  - ada notifikasi yang berubah status read/unread.
     */
    public static function notificationSignature(Collection|EloquentCollection $notifications, int $unreadCount): string
    {
        $items = $notifications
            ->take(self::DROPDOWN_LIMIT)
            ->map(fn ($notif) => $notif->getKey().':'.($notif->read_at ? 'r' : 'u'))
            ->implode(',');

        return $unreadCount.'#'.$items;
    }
}
