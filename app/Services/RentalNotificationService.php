<?php

namespace App\Services;

use App\Models\LatePenalty;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ReturnRecord;
use App\Notifications\RentalStatusNotification;

/**
 * Layanan untuk notifikasi perubahan status penyewaan yang dikirim ke
 * User pemilik booking ketika Admin menerima atau menolak penyewaan.
 *
 * Penempatan notifikasi di sini memastikan body/URL selalu konsisten
 * dan hanya dikirim kepada user pemilik booking (order->user).
 */
class RentalNotificationService
{
    /**
     * Kirim notifikasi "Penyewaan Diterima" ke user pemilik booking.
     * Tidak melakukan apa pun jika booking tidak memiliki user.
     */
    public static function notifyAccepted(Order $order): void
    {
        if (! $order->user) {
            return;
        }

        if (self::hasNotification($order, RentalStatusNotification::TYPE_ACCEPTED)) {
            return;
        }

        $orderCode = $order->code;
        $items = self::itemList($order);

        $body = "Penyewaan alat Anda telah diterima oleh admin. Barang siap diambil.\n"
            . "No. Booking: #{$orderCode}\n"
            . ($items !== '' ? "Alat: {$items}\n" : '')
            . self::rentalPeriodLine($order);

        $order->user->notify(new RentalStatusNotification(
            RentalStatusNotification::TYPE_ACCEPTED,
            '🔔 Penyewaan Diterima',
            $body,
            '✅',
            route('history', ['status' => 'active']),
            $orderCode,
        ));
    }

    /**
     * Kirim notifikasi "Penyewaan Ditolak" ke user pemilik booking.
     * Menyertakan alasan penolakan apabila tersedia.
     */
    public static function notifyRejected(Order $order, ?string $reason = null): void
    {
        if (! $order->user) {
            return;
        }

        if (self::hasNotification($order, RentalStatusNotification::TYPE_REJECTED)) {
            return;
        }

        $orderCode = $order->code;
        $items = self::itemList($order);
        $reason = trim((string) $reason);

        $body = "Penyewaan alat Anda ditolak oleh admin.\n"
            . ($reason !== '' ? "Alasan: {$reason}\n" : '')
            . "No. Booking: #{$orderCode}\n"
            . ($items !== '' ? "Alat: {$items}\n" : '')
            . self::rentalPeriodLine($order);

        $order->user->notify(new RentalStatusNotification(
            RentalStatusNotification::TYPE_REJECTED,
            '🔔 Penyewaan Ditolak',
            $body,
            '❌',
            route('history', ['status' => 'cancelled']),
            $orderCode,
        ));
    }

    /**
     * Notifikasi saat booking berhasil dibuat oleh user.
     */
    public static function notifyBookingCreated(Order $order): void
    {
        if (! $order->user) {
            return;
        }

        $orderCode = $order->code;
        $items = self::itemList($order);

        $body = "Booking Anda telah berhasil dibuat dan sedang menunggu verifikasi admin.\n"
            . "No. Booking: #{$orderCode}\n"
            . ($items !== '' ? "Alat: {$items}\n" : '')
            . self::rentalPeriodLine($order)
            . "Total: Rp " . self::formatRupiah($order->total) . "\n"
            . "Silakan pantau status penyewaan Anda pada halaman Riwayat.";

        $order->user->notify(new RentalStatusNotification(
            RentalStatusNotification::TYPE_BOOKING_CREATED,
            '📦 Booking Berhasil Dibuat',
            $body,
            '📦',
            route('history'),
            $orderCode,
        ));
    }

    /**
     * Notifikasi saat user mengirim bukti pembayaran (diproses/verifikasi).
     */
    public static function notifyPaymentSubmitted(Order $order, Payment $payment, string $method): void
    {
        if (! $order->user) {
            return;
        }

        $body = "Pembayaran Anda telah kami terima dan sedang diproses untuk verifikasi admin.\n"
            . "No. Booking: #{$order->code}\n"
            . "Metode: " . strtoupper($method) . "\n"
            . "Nominal: Rp " . self::formatRupiah($payment->amount);

        $order->user->notify(new RentalStatusNotification(
            RentalStatusNotification::TYPE_PAYMENT_SUBMITTED,
            '💳 Pembayaran Diterima',
            $body,
            '💳',
            route('history'),
            $order->code,
        ));
    }

    /**
     * Notifikasi saat admin menyetujui pembayaran (order -> active).
     */
    public static function notifyPaymentApproved(Order $order, Payment $payment): void
    {
        if (! $order->user) {
            return;
        }

        if (self::hasNotification($order, RentalStatusNotification::TYPE_PAYMENT_CONFIRMED)) {
            return;
        }

        $body = "Pembayaran Anda untuk booking #{$order->code} telah DISETUJUI.\n"
            . "Status penyewaan kini AKTIF — barang siap diambil.\n"
            . "Terima kasih atas konfirmasi pembayaran Anda.";

        $order->user->notify(new RentalStatusNotification(
            RentalStatusNotification::TYPE_PAYMENT_CONFIRMED,
            '✅ Pembayaran Disetujui',
            $body,
            '✅',
            route('history', ['status' => 'active']),
            $order->code,
        ));
    }

    /**
     * Notifikasi saat admin menolak pembayaran (order -> cancelled).
     */
    public static function notifyPaymentRejected(Order $order, Payment $payment, ?string $reason = null): void
    {
        if (! $order->user) {
            return;
        }

        if (self::hasNotification($order, RentalStatusNotification::TYPE_PAYMENT_REJECTED)) {
            return;
        }

        $reason = trim((string) $reason);

        $body = "Pembayaran Anda untuk booking #{$order->code} telah DITOLAK.\n"
            . ($reason !== '' ? "Alasan: {$reason}\n" : '')
            . "Pesanan dibatalkan. Silakan coba melakukan booking ulang jika diinginkan.";

        $order->user->notify(new RentalStatusNotification(
            RentalStatusNotification::TYPE_PAYMENT_REJECTED,
            '❌ Pembayaran Ditolak',
            $body,
            '❌',
            route('history', ['status' => 'cancelled']),
            $order->code,
        ));
    }

    /**
     * Notifikasi saat penyewaan diselesaikan oleh admin (order -> completed).
     */
    public static function notifyRentalCompleted(Order $order): void
    {
        if (! $order->user) {
            return;
        }

        if (self::hasNotification($order, RentalStatusNotification::TYPE_RENTAL_COMPLETED)) {
            return;
        }

        $body = "Penyewaan Anda untuk booking #{$order->code} telah diselesaikan.\n"
            . "Terima kasih telah menggunakan Summit Station. Jangan ragu untuk menyewa kembali!";

        $order->user->notify(new RentalStatusNotification(
            RentalStatusNotification::TYPE_RENTAL_COMPLETED,
            '🎉 Penyewaan Selesai',
            $body,
            '✅',
            route('history', ['status' => 'completed']),
            $order->code,
        ));
    }

    /**
     * Notifikasi konfirmasi saat user mengajukan pengembalian barang.
     */
    public static function notifyReturnSubmitted(Order $order): void
    {
        if (! $order->user) {
            return;
        }

        if (self::hasNotification($order, RentalStatusNotification::TYPE_RETURN_SUBMITTED)) {
            return;
        }

        $body = "Pengajuan pengembalian Anda untuk booking #{$order->code} telah kami terima.\n"
            . "Silakan bawa peralatan ke basecamp Summit Station untuk inspeksi oleh admin.";

        $order->user->notify(new RentalStatusNotification(
            RentalStatusNotification::TYPE_RETURN_SUBMITTED,
            '📦 Pengembalian Dikirim',
            $body,
            '📦',
            route('history', ['status' => 'active']),
            $order->code,
        ));
    }

    /**
     * Notifikasi saat admin merekam hasil inspeksi pengembalian.
     */
    public static function notifyReturnRecorded(Order $order, ReturnRecord $record): void
    {
        if (! $order->user) {
            return;
        }

        if (self::hasNotification($order, RentalStatusNotification::TYPE_RETURN_RECORDED)) {
            return;
        }

        $body = "Barang Anda untuk booking #{$order->code} telah diterima dan diinspeksi.\n"
            . "Kondisi tercatat: " . ($record->condition_label ?? ucfirst(str_replace('_', ' ', (string) $record->condition))) . ".";

        $order->user->notify(new RentalStatusNotification(
            RentalStatusNotification::TYPE_RETURN_RECORDED,
            '✓ Pengembalian Diinspeksi',
            $body,
            '🔍',
            route('history', ['status' => 'active']),
            $order->code,
        ));
    }

    /**
     * Notifikasi saat pengembalian diselesaikan oleh admin (order -> completed).
     */
    public static function notifyReturnCompleted(Order $order): void
    {
        if (! $order->user) {
            return;
        }

        if (self::hasNotification($order, RentalStatusNotification::TYPE_RETURN_COMPLETED)) {
            return;
        }

        $body = "Pengembalian barang untuk booking #{$order->code} telah diselesaikan.\n"
            . "Penyewaan Anda resmi ditutup. Terima kasih atas kerja samanya!";

        $order->user->notify(new RentalStatusNotification(
            RentalStatusNotification::TYPE_RETURN_COMPLETED,
            '🎉 Pengembalian Selesai',
            $body,
            '✅',
            route('history', ['status' => 'completed']),
            $order->code,
        ));
    }

    /**
     * Notifikasi saat Admin memberikan denda pada proses pengembalian barang.
     * Data nominal diambil dari ReturnRecord (damage_cost) — bukan hardcode.
     */
    public static function notifyDendaCharged(Order $order, ReturnRecord $record): void
    {
        if (! $order->user) {
            return;
        }

        if ((int) $record->damage_cost <= 0) {
            return;
        }

        // Cegah duplikasi: hanya satu notifikasi denda per kode booking.
        if (self::hasNotification($order, RentalStatusNotification::TYPE_DENDA_CHARGED)) {
            return;
        }

        $body = "Pesanan #{$order->code} dikenakan denda sebesar Rp "
            . self::formatRupiah($record->damage_cost) . ".\n"
            . "Alasan: " . (trim((string) $record->damage_description) ?: $record->condition_label) . "\n"
            . "Silakan lunasi denda melalui halaman Riwayat Penyewaan.";

        $order->user->notify(new RentalStatusNotification(
            RentalStatusNotification::TYPE_DENDA_CHARGED,
            '💸 Denda Diberikan',
            $body,
            '💸',
            route('history', ['status' => 'completed']),
            $order->code,
        ));
    }

    /**
     * Notifikasi saat pembayaran denda disetujui admin (denda jadi Lunas).
     */
    public static function notifyDendaPaid(Order $order): void
    {
        if (! $order->user) {
            return;
        }

        if (self::hasNotification($order, RentalStatusNotification::TYPE_DENDA_PAID)) {
            return;
        }

        $body = "Pembayaran denda untuk pesanan #{$order->code} telah DISETUJUI.\n"
            . "Denda Anda kini berstatus LUNAS. Terima kasih.";

        $order->user->notify(new RentalStatusNotification(
            RentalStatusNotification::TYPE_DENDA_PAID,
            '✅ Denda Lunas',
            $body,
            '✅',
            route('history', ['status' => 'completed']),
            $order->code,
        ));
    }

    /**
     * Notifikasi saat denda sanksi keterlambatan diberikan ke user.
     */
     public static function notifyLatePenaltyAssigned(Order $order, LatePenalty $penalty): void
     {
         if (! $order->user) {
             return;
         }

         if ((int) $penalty->total_fee <= 0) {
             return;
         }

         $body = "Pengembalian pesanan #{$order->code} terlambat {$penalty->days_overdue} hari. Dikenakan denda sanksi keterlambatan sebesar Rp "
             . self::formatRupiah($penalty->total_fee) . ".\n"
             . ($penalty->admin_notes ? "Catatan Admin: {$penalty->admin_notes}\n" : '')
             . "Silakan lakukan pembayaran sanksi denda melalui Riwayat Penyewaan.";

         $order->user->notify(new RentalStatusNotification(
             RentalStatusNotification::TYPE_LATE_PENALTY_ASSIGNED,
             '⚠️ Sanksi Keterlambatan Diberikan',
             $body,
             '⚠️',
             route('history', ['status' => 'completed']),
             $order->code,
         ));
     }

     /**
      * Notifikasi saat pembayaran sanksi keterlambatan disetujui admin (status lunas).
      */
     public static function notifyLatePenaltyPaid(Order $order, LatePenalty $penalty): void
     {
         if (! $order->user) {
             return;
         }

         $body = "Pembayaran sanksi keterlambatan untuk pesanan #{$order->code} sebesar Rp "
             . self::formatRupiah($penalty->total_fee) . " telah DISETUJUI dan berstatus LUNAS. Terima kasih.";

         $order->user->notify(new RentalStatusNotification(
             RentalStatusNotification::TYPE_LATE_PENALTY_PAID,
             '✅ Sanksi Keterlambatan Lunas',
             $body,
             '✅',
             route('history', ['status' => 'completed']),
             $order->code,
         ));
     }

     /**
      * Baris periode penyewaan (rent_start - rent_end) untuk body notifikasi.
      */
    protected static function rentalPeriodLine(Order $order): string
    {
        if (! $order->rent_start || ! $order->rent_end) {
            return '';
        }

        return 'Periode Sewa: '
            . $order->rent_start->format('d M Y')
            . ' - '
            . $order->rent_end->format('d M Y');
    }

    /**
     * Nama barang (maksimal 3) untuk body notifikasi.
     */
    protected static function itemList(Order $order): string
    {
        return $order->items->pluck('name')->filter()->take(3)->join(', ');
    }

    /**
     * Format nominal Rupiah (tanpa titik desimal).
     */
    protected static function formatRupiah($amount): string
    {
        return number_format((float) $amount, 0, ',', '.');
    }

    /**
     * Cegah duplikasi: sudah ada notifikasi (type + kode booking) untuk user ini.
     */
    protected static function hasNotification(Order $order, string $type): bool
    {
        return $order->user->notifications()
            ->where('type', RentalStatusNotification::class)
            ->where('data->type', $type)
            ->where('data->order_code', $order->code)
            ->exists();
    }
}