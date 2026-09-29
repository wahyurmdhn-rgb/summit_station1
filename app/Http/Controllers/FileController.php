<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ReturnRecord;
use App\Models\User;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
    public function paymentProof(int $paymentId): StreamedResponse|BinaryFileResponse|RedirectResponse
    {
        $payment = Payment::with('order')->findOrFail($paymentId);
        $this->authorizeOrder($payment->order);

        $raw = (string) ($payment->proof_image ?? '');

        if ($raw === '' || $raw === 'null') {
            abort(404, 'Bukti pembayaran tidak ditemukan.');
        }

        return $this->responseFromDisks($raw);
    }

    /**
     * Sajikan bukti pengembalian milik satu ReturnRecord (dengan otorisasi).
     * Mendukung file lama (disk public) maupun baru (disk privat).
     */
    public function returnProof(int $returnId): ResponseFactory|Response|StreamedResponse
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
     * Sajikan foto inspeksi admin untuk satu ReturnRecord (dengan otorisasi).
     * Terpisah dari bukti pengembalian user agar tidak saling menimpa.
     */
    public function returnInspectionPhoto(int $returnId): ResponseFactory|Response|StreamedResponse
    {
        $record = ReturnRecord::with('order')->findOrFail($returnId);
        $this->authorizeOrder($record->order);

        $raw = (string) ($record->inspection_photo ?? '');

        if ($raw === '' || $raw === 'null') {
            abort(404, 'Foto inspeksi tidak ditemukan.');
        }

        return $this->responseFromDisks($raw);
    }

    /**
     * Sajikan bukti persetujuan orang tua milik satu User (dengan otorisasi).
     * Hanya admin atau user pemiliknya sendiri yang boleh mengakses.
     */
    public function parentConsent(int $userId): StreamedResponse
    {
        $user = User::findOrFail($userId);

        return $this->serveUserDoc($user, 'parent_consent_path', 'Bukti persetujuan tidak ditemukan.');
    }

    /**
     * Sajikan KTP orang tua/wali milik satu User (user di bawah 17 tahun).
     */
    public function ktpGuardian(int $userId): StreamedResponse
    {
        $user = User::findOrFail($userId);

        return $this->serveUserDoc($user, 'ktp_orang_tua_path', 'KTP orang tua tidak ditemukan.');
    }

    /**
     * Sajikan kartu pelajar milik satu User (user di bawah 17 tahun).
     */
    public function studentCard(int $userId): StreamedResponse
    {
        $user = User::findOrFail($userId);

        return $this->serveUserDoc($user, 'kartu_pelajar_path', 'Kartu pelajar tidak ditemukan.');
    }

    /**
     * Otorisasi & penyajian dokumen identitas/persetujuan milik satu User.
     * Dokumen sensitif hanya boleh diakses oleh admin atau user pemiliknya.
     */
    private function serveUserDoc(User $user, string $column, string $missing): StreamedResponse
    {
        $role = session('account_role');
        $isAdmin = $role === 'admin' && session('account_id');
        $isOwner = $role === 'customer' && session('account_id')
            && (int) session('account_id') === (int) $user->getKey();

        if (! $isAdmin && ! $isOwner) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses file ini.');
        }

        $raw = (string) ($user->{$column} ?? '');

        if ($raw === '' || $raw === 'null') {
            abort(404, $missing);
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($raw)) {
                return Storage::disk($disk)->response($raw);
            }
        }

        abort(404, $missing);
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
        if (! $this->isSafeStoragePath($path)) {
            abort(404, 'File bukti tidak ditemukan.');
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path);
            }
        }

        abort(404, 'File bukti tidak ditemukan.');
    }

    private function isSafeStoragePath(string $path): bool
    {
        return $path !== ''
            && ! str_contains($path, '\\')
            && ! str_contains($path, '..')
            && ! str_starts_with($path, '/')
            && ! preg_match('#^[a-z][a-z0-9+.-]*:#i', $path);
    }
}
