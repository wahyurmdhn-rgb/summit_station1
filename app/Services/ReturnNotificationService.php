<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Notifications\RentalStatusNotification;
use Illuminate\Support\Carbon;

/**
 * Notifikasi tenggat pengembalian untuk User pemilik booking.
 *
 * Tiga tahap berdasarkan rent_end yang SUDAH tersimpan di booking:
 *   - return_reminder : mendekati tenggat (24 jam terakhir sebelum hari jatuh tempo)
 *   - return_due      : ada di hari jatuh tempo (hari ini batas waktu)
 *   - return_overdue  : sudah melewati batas waktu & barang belum dikembalikan
 *
 * Reuse RentalStatusNotification (channel database) — TIDAK membuat sistem
 * notifikasi duplikat. Tidak menghitung penalty / sanksi baru; notifikasi
 * keterlambatan hanya menyebutkan sanksi sesuai ketentuan yang berlaku.
 *
 * Setiap notifikasi dicegah duplikasinya per (type + kode booking): menjalankan
 * pemeriksaan berulang kali (scheduler / refresh halaman) hanya menghasilkan
 * satu notifikasi untuk setiap kondisi.
 *
 * Sumber waktu SELALU waktu server (Asia/Jakarta), bukan browser/JS. Karena itu
 * waktu terus berjalan walaupun browser ditutup, user logout, atau komputer
 * dimatikan: saat user kembali membuka website, dispatchForUser() mengevaluasi
 * ulang berdasarkan (waktu server sekarang - rent_end) dan melengkapi
 * notifikasi yang belum sempat dibuat scheduler.
 */
class ReturnNotificationService
{
    /**
     * Durasi jendela "mendekati tenggat" sebelum hari jatuh tempo (dalam jam).
     * 24 jam => pada hari sebelum rent_end, User mulai mendapat pengingat.
     */
    public const REMINDER_HOURS = 24;

    /**
     * Jenis notifikasi yang sedang "berlaku" untuk order ini (berdasarkan
     * data booking, bukan timer frontend). Mengembalikan null jika:
     *   - booking belum pada tahap yang butuh pengembalian (pending/cancelled)
     *   - barang sudah dikembalikan (completed / ReturnRecord approved)
     *   - masih jauh dari tenggat (belum masuk jendela pengingat)
     */
    public static function intendedType(Order $order): ?string
    {
        if (! in_array($order->status, ['active', 'paid']) || ! $order->rent_end) {
            return null;
        }

        // Sudah dikembalikan → jangan apa pun (tanpa notifikasi terlambat/sanksi).
        if ($order->status === 'completed'
            || $order->returns->contains(fn ($record) => $record->status === 'approved')) {
            return null;
        }

        $tz    = config('app.timezone');
        $now   = now($tz);
        $dayStart = $order->rent_end->copy()->timezone($tz)->startOfDay();
        $dayEnd   = $order->rent_end->copy()->timezone($tz)->endOfDay();

        if ($now->greaterThan($dayEnd)) {
            return RentalStatusNotification::TYPE_RETURN_OVERDUE;
        }

        if ($now->greaterThanOrEqualTo($dayStart)) {
            return RentalStatusNotification::TYPE_RETURN_DUE;
        }

        $reminderStart = $dayStart->copy()->subHours(self::REMINDER_HOURS);
        if ($now->greaterThanOrEqualTo($reminderStart)) {
            return RentalStatusNotification::TYPE_RETURN_REMINDER;
        }

        return null;
    }

    /**
     * Proses semua order yang berhak mendapat notifikasi tenggat.
     *
     * @return int jumlah notifikasi yang BARU dibuat pada pemanggilan ini
     */
    public static function dispatchAll(): int
    {
        $created = 0;

        foreach (self::eligibleOrders() as $order) {
            if (self::ensureNotification($order)) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Evaluasi ulang tenggat untuk SATU user pemilik booking.
     *
     * Dipanggil setiap kali user kembali membuka halaman website (login/refresh
     * apa pun). Karena dasarnya penerapan waktu server, hasilnya identik dengan
     * yang dihitung scheduler — browser ditutup / logout / komputer mati tidak
     * mengubah apa pun: yang dihitung adalah (waktu server - rent_end).
     *
     * = 0 jika user tidak punya penyewaan aktif yang relevan.
     *
     * @return int jumlah notifikasi yang BARU dibuat
     */
    public static function dispatchForUser(User $user): int
    {
        if ($user->status !== 'active') {
            return 0;
        }

        $created = 0;

        foreach ($user->orders()
            ->whereIn('status', ['active', 'paid'])
            ->whereNotNull('rent_end')
            ->with(['user', 'returns', 'items'])
            ->get() as $order) {
            if (self::ensureNotification($order)) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Order yang sedang dalam masa sewa dan belum dikembalikan.
     */
    private static function eligibleOrders()
    {
        return Order::with(['user', 'returns', 'items'])
            ->whereIn('status', ['active', 'paid'])
            ->whereNotNull('rent_end')
            ->get();
    }

    /**
     * Buat notifikasi untuk kondisi yang berlaku bila belum ada.
     * Mengembalikan true hanya jika notifikasi BARU berhasil dibuat.
     */
    public static function ensureNotification(Order $order): bool
    {
        $type = self::intendedType($order);
        if ($type === null || ! $order->user) {
            return false;
        }

        if (self::hasNotification($order, $type)) {
            return false;
        }

        $order->user->notify(new RentalStatusNotification(
            type: $type,
            title: self::titleFor($type),
            body: self::bodyFor($order, $type),
            icon: self::iconFor($type),
            url: route('history', ['status' => 'active']),
            orderCode: $order->code,
        ));

        return true;
    }

    /**
     * Cegah duplikasi: sudah ada notifikasi (type + kode booking) untuk user ini.
     */
    private static function hasNotification(Order $order, string $type): bool
    {
        return $order->user->notifications()
            ->where('type', RentalStatusNotification::class)
            ->where('data->type', $type)
            ->where('data->order_code', $order->code)
            ->exists();
    }

    private static function titleFor(string $type): string
    {
        return match ($type) {
            RentalStatusNotification::TYPE_RETURN_REMINDER => '🔔 Pengingat Pengembalian',
            RentalStatusNotification::TYPE_RETURN_DUE      => '🔔 Batas Pengembalian',
            RentalStatusNotification::TYPE_RETURN_OVERDUE  => '⚠️ Terlambat Mengembalikan',
            default => '🔔 Pengembalian',
        };
    }

    private static function iconFor(string $type): string
    {
        return match ($type) {
            RentalStatusNotification::TYPE_RETURN_OVERDUE  => '⚠️',
            default => '🔔',
        };
    }

    private static function bodyFor(Order $order, string $type): string
    {
        $items = $order->items->pluck('name')->filter()->take(3)->join(', ');

        $lines = match ($type) {
            RentalStatusNotification::TYPE_RETURN_REMINDER => [
                'Besok adalah tanggal pengembalian alat Anda. Harap segera melakukan pengembalian.',
            ],
            RentalStatusNotification::TYPE_RETURN_DUE => [
                'Batas waktu pengembalian alat Anda telah tiba. Segera lakukan pengembalian untuk menghindari sanksi.',
            ],
            RentalStatusNotification::TYPE_RETURN_OVERDUE => [
                'Anda telah melewati batas waktu pengembalian. Segera kembalikan barang untuk menghindari sanksi sesuai ketentuan penyewaan.',
            ],
            default => [],
        };

        $lines[] = 'No. Booking: #' . $order->code;
        if ($items !== '') {
            $lines[] = 'Alat: ' . $items;
        }
        if ($order->rent_end) {
            $lines[] = 'Tenggat pengembalian: ' . self::formatDeadline($order->rent_end);
        }

        return implode("\n", $lines);
    }

    /**
     * Format tanggal tenggat waktu berbahasa Indonesia (hanya tanggal yang
     * tersimpan di booking — tidak membuat tanggal/jam baru).
     */
    private static function formatDeadline(Carbon $date): string
    {
        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return $date->day . ' ' . $months[$date->month - 1] . ' ' . $date->year;
    }
}