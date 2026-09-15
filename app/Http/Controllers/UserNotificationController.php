<?php

namespace App\Http\Controllers;

use App\Notifications\RentalStatusNotification;
use Illuminate\Http\RedirectResponse;

/**
 * Notifikasi database untuk User (customer).
 *
 * User hanya dapat membuka notifikasi miliknya sendiri. Membuka notifikasi
 * menandainya sebagai sudah dibaca lalu mengarahkan pengguna ke halaman
 * penyewaan yang terkait (riwayat sesuai status).
 *
 * PENTING: URL tujuan diturunkan dari TIPE notifikasi (dan selalu berupa
 * halaman area USER /history), BUKAN dari data['url'] yang tersimpan di
 * database. Hal ini memastikan user TIDAK PERNAH diarahkan ke /admin/*
 * meskipun data notifikasi lama/rusak berisi URL admin.
 */
class UserNotificationController extends Controller
{
    public function open(string $id): RedirectResponse
    {
        if (session('account_role') !== 'customer' || ! session('account_id')) {
            abort(403);
        }

        $user = \App\Models\User::find(session('account_id'));
        if (! $user) {
            abort(403);
        }

        // SECURITY: hanya notifikasi milik user yang sedang login.
        // User lain (termasuk user lain yang memiliki notifikasi serupa)
        // tidak akan menemukan notifikasi ini dan ditolak.
        $notification = $user->notifications()->whereKey($id)->first();
        if (! $notification) {
            abort(403);
        }

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $data = is_array($notification->data)
            ? $notification->data
            : (array) json_decode((string) $notification->data, true);

        return redirect($this->resolveUserUrl($data));
    }

    /**
     * Tentukan URL tujuan notifikasi USER.
     *
     * Pemetaan berbasis tipe notifikasi agar selalu konsisten:
     *   - rental_accepted  -> riwayat penyewaan (filter Aktif)
     *   - rental_rejected  -> riwayat penyewaan (filter Dibatalkan)
     *   - refund           -> riwayat penyewaan (semua)
     *
     * Seluruh hasil selalu halaman area user (/history...). Sebagai lapisan
     * pengaman terakhir, URL apa pun yang mengarah ke /admin/* di-override
     * ke riwayat user.
     */
    private function resolveUserUrl(array $data): string
    {
        $type = $data['type'] ?? '';

        $url = match ($type) {
            RentalStatusNotification::TYPE_ACCEPTED,
            RentalStatusNotification::TYPE_PAYMENT_CONFIRMED,
            RentalStatusNotification::TYPE_RETURN_SUBMITTED,
            RentalStatusNotification::TYPE_RETURN_RECORDED => route('history', ['status' => 'active']),
            RentalStatusNotification::TYPE_REJECTED,
            RentalStatusNotification::TYPE_PAYMENT_REJECTED => route('history', ['status' => 'cancelled']),
            RentalStatusNotification::TYPE_RENTAL_COMPLETED,
            RentalStatusNotification::TYPE_RETURN_COMPLETED => route('history', ['status' => 'completed']),
            RentalStatusNotification::TYPE_DENDA_CHARGED,
            RentalStatusNotification::TYPE_DENDA_PAID => route('history', ['status' => 'completed']),
            RentalStatusNotification::TYPE_RETURN_REMINDER,
            RentalStatusNotification::TYPE_RETURN_DUE,
            RentalStatusNotification::TYPE_RETURN_OVERDUE => route('history', ['status' => 'active']),
            default => route('history'),
        };

        // Pengaman terakhir: user tidak boleh diarahkan ke area admin.
        $path = (string) parse_url($url, PHP_URL_PATH);
        if (str_starts_with($path, '/admin')) {
            $url = route('history');
        }

        return $url;
    }
}