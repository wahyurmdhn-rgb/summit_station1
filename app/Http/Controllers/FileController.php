<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ReturnRecord;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyajian file bukti (bukti pembayaran & bukti pengembalian) secara
 * TERKONTROL dari penyimpanan privat.
 *
 * File bukti TIDAK lagi di-serve langsung dari `storage/app/public` (public).
 * Sebagai gantinya disimpan di disk default `local` (storage/app/private) dan
 * disajikan lewat route ini dengan otorisasi:
 *   - Admin: boleh melihat semua bukti (untuk validasi).
 *   - Customer: hanya boleh melihat bukti milik booking-nya sendiri.
 *
 * `<img src>` / modal memakai URL di bawah origin yang sama sehingga cookie
 * sesi ikut terkirim -> otorisasi berbasis session bekerja.
 */
class FileController extends Controller
{
    /**
     * Sajikan bukti pembayaran milik satu payment (dengan otorisasi).
     * Menangani file lama (path public) maupun baru (path privat).
     */
    public function paymentProof(int $paymentId): StreamedResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $payment = Payment::with('order')->findOrFail($paymentId);
        $this->authorizeOrder($payment->order);

        $raw = (string) ($payment->proof_image ?? '');

        // Legacy: proof_image berisi URL publik penuh -> pertahankan perilaku lama.
        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://') || str_starts_with($raw, '//')) {
            return redirect($raw);
        }

        if ($raw === '' || $raw === 'null') {
            abort(404, 'Bukti pembayaran tidak ditemukan.');
        }

        return $this->responseFromDisks($raw);
    }

    /**
     * Sajikan bukti pengembalian milik satu ReturnRecord (dengan otorisasi).
     * Mendukung file lama (disk public) maupun baru (disk privat).
     */
    public function returnProof(int $returnId): \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|StreamedResponse
    {
        $record = ReturnRecord::with('order')->findOrFail($returnId);
        $this->authorizeOrder($record->order);

        $raw = (string) ($record->proof_path ?? '');

        if ($raw === '' || $raw === 'null') {
            abort(404, 'Bukti pengembalian tidak ditemukan.');
        }

        return $this->responseFromDisks($raw);
    }

    /**
     * Otorisasi: admin boleh semua; customer hanya untuk order miliknya.
     */
    private function authorizeOrder(?Order $order): void
    {
        $role = session('account_role');

        if ($role === 'admin' && session('account_id')) {
            return;
        }

        if ($role === 'customer' && session('account_id') && $order
            && (int) $order->user_id === (int) session('account_id')) {
            return;
        }

        abort(403, 'Anda tidak memiliki izin untuk mengakses file ini.');
    }

    /**
     * Cari file pada disk privat (baru) lalu disk publik (legacy), dan stream.
     */
    private function responseFromDisks(string $path)
    {
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path);
            }
        }

        abort(404, 'File bukti tidak ditemukan.');
    }
}
