<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ReturnRecord;
use App\Services\AdminNotificationService;
use App\Services\RentalNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    /**
     * User mengajukan pengembalian barang + upload foto bukti.
     *
     * Membuat ReturnRecord ber-status pending (menunggu inspeksi admin).
     * Foto bukti disimpan di storage privat (disk default local); disajikan
     * via route yang diautentikasi, path disimpan ke kolom proof_path.
     */
    public function store(Request $request, $order): RedirectResponse
    {
        if (session('account_role') !== 'customer' || ! session()->has('account_id')) {
            return redirect()->route('login')
                ->with('status', 'Silakan login terlebih dahulu untuk mengajukan pengembalian.');
        }

        $userId = (int) session('account_id');
        $orderModel = Order::with(['user', 'returns'])->find((int) $order);

        if (! $orderModel) {
            return back()->withErrors(['return' => 'Pesanan tidak ditemukan.']);
        }

        // SECURITY: user hanya boleh mengajukan pengembalian untuk booking miliknya.
        if ((int) $orderModel->user_id !== $userId) {
            abort(403, 'Anda tidak memiliki izin untuk mengajukan pengembalian pada pesanan ini.');
        }

        // Hanya penyewaan yang sedang aktif berjalan.
        if ($orderModel->status !== 'active') {
            return back()->withErrors(['return' => 'Pengembalian hanya dapat diajukan untuk penyewaan yang sedang aktif.']);
        }

        // Cegah pengajuan ganda selama masih diproses / sudah disetujui.
        $hasOpenReturn = $orderModel->returns->first(fn ($r) => in_array($r->status, ['pending', 'approved']));
        if ($hasOpenReturn) {
            return back()->withErrors(['return' => 'Pengajuan pengembalian untuk booking ini sudah dikirim dan sedang diproses.']);
        }

        $validated = $request->validate([
            'return_proof' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'return_proof.required' => 'Foto bukti pengembalian wajib diupload.',
            'return_proof.image' => 'File yang diunggah harus berupa gambar.',
            'return_proof.mimes' => 'Format foto tidak valid. Gunakan format JPG, JPEG, PNG, atau WEBP.',
            'return_proof.max' => 'Ukuran foto melebihi batas maksimal 5MB.',
        ]);

        // Simpan foto bukti ke storage PRIVAT (disk default local); disajikan
        // via route terkontrol agar tidak bisa diakses tanpa otorisasi.
        $proofPath = $request->file('return_proof')->store('returns');

        ReturnRecord::create([
            'order_id' => $orderModel->id,
            'order_item_id' => null,
            'proof_path' => $proofPath,
            'status' => 'pending',
            'returned_at' => now(),
        ]);

        // Notifikasi aktivitas ke seluruh admin: ada pengajuan pengembalian baru + foto.
        AdminNotificationService::notifyAdmins(
            'return',
            '🔔 Pengembalian Barang',
            "User {$orderModel->user?->name} telah mengajukan pengembalian barang untuk pesanan #{$orderModel->code} dan mengunggah foto bukti pengembalian.",
            '📦',
            route('admin.pengembalian'),
        );

        // Notifikasi konfirmasi ke user pemilik booking.
        RentalNotificationService::notifyReturnSubmitted($orderModel);

        return redirect()->route('history')
            ->with('status', 'Pengajuan pengembalian berhasil dikirim. Silakan bawa peralatan ke basecamp Summit Station untuk inspeksi.');
    }

    /**
     * User membayar DENDA dengan mengunggah bukti pembayaran.
     *
     * PRINSIP (sesuai aturan integrasi denda):
     *  - nominal selalu dihitung dari database (damage_cost), bukan dari frontend;
     *  - hanya denda milik user yang sedang login dapat dibayar;
     *  - denda yang sudah Lunas tidak dapat dibayar ulang;
     *  - keberadaan denda wajib (damage_cost > 0);
     *  - pembayaran disimpan sebagai Payment berstatus pending (reference FINE-...)
     *    dan baru menjadi Lunas setelah disetujui admin (backend).
     */
    public function payDenda(Request $request, $order): RedirectResponse
    {
        if (session('account_role') !== 'customer' || ! session()->has('account_id')) {
            return redirect()->route('login')
                ->with('status', 'Silakan login terlebih dahulu untuk membayar denda.');
        }

        $userId = (int) session('account_id');
        $orderModel = Order::with(['user', 'returns', 'payments', 'latePenalty'])->find((int) $order);

        if (! $orderModel) {
            return back()->withErrors(['denda' => 'Pesanan tidak ditemukan.']);
        }

        // SECURITY: user hanya boleh membayar denda miliknya sendiri.
        if ((int) $orderModel->user_id !== $userId) {
            abort(403, 'Anda tidak memiliki izin untuk membayar denda pada pesanan ini.');
        }

        $record = $orderModel->returns()
            ->whereNull('order_item_id')
            ->latest('id')
            ->first();

        $latePenalty = $orderModel->latePenalty;

        $hasDamageDenda = $record && (int) $record->damage_cost > 0;
        $isDamagePaid = $record && $record->is_denda_paid;

        // Pesanan harus benar-benar memiliki denda (kerusakan atau keterlambatan).
        if (! $hasDamageDenda && (! $latePenalty || (int) $latePenalty->total_fee <= 0 || $latePenalty->status === 'tidak_ada_sanksi' || $latePenalty->status === 'dibatalkan')) {
            return back()->withErrors(['denda' => 'Pesanan ini tidak memiliki denda yang harus dibayar.']);
        }

        // Denda yang sudah Lunas tidak dapat dibayar ulang.
        $damageNeedsPay = $hasDamageDenda && ! $isDamagePaid;
        $lateNeedsPay = $latePenalty && $latePenalty->status === 'menunggu_pembayaran' && (int) $latePenalty->total_fee > 0;

        if (! $damageNeedsPay && ! $lateNeedsPay) {
            return back()->withErrors(['denda' => 'Semua denda untuk pesanan ini sudah lunas atau tidak aktif.']);
        }

        // Cegah pengajuan pembayaran ganda selama masih pending.
        $hasPending = $orderModel->payments->contains(
            fn ($p) => $p->is_denda_payment && $p->status === 'pending'
        );
        if ($hasPending) {
            return back()->withErrors(['denda' => 'Pembayaran denda sudah diajukan dan sedang menunggu verifikasi admin.']);
        }

        $validated = $request->validate([
            'denda_proof' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'denda_proof.required' => 'Bukti pembayaran denda wajib diupload.',
            'denda_proof.image' => 'File yang diunggah harus berupa gambar.',
            'denda_proof.mimes' => 'Format foto tidak valid. Gunakan format JPG, JPEG, PNG, atau WEBP.',
            'denda_proof.max' => 'Ukuran foto melebihi batas maksimal 5MB.',
        ]);

        // Simpan bukti ke storage privat; disajikan via route terkontrol (authorized).
        $proofPath = $request->file('denda_proof')->store('proofs');

        // Nominal dihitung dari database (kerusakan + sanksi keterlambatan), bukan input user.
        $amount = ($damageNeedsPay ? (int) $record->damage_cost : 0)
                + ($lateNeedsPay ? (int) $latePenalty->total_fee : 0);

        $payment = Payment::create([
            'order_id' => $orderModel->id,
            'method' => 'qris',
            'amount' => $amount,
            'status' => 'pending',
            'reference' => Payment::FINE_REFERENCE_PREFIX . strtoupper(uniqid()),
            'proof_image' => $proofPath,
            'created_at' => now(),
        ]);

        if ($lateNeedsPay && $latePenalty) {
            $latePenalty->update([
                'status'     => \App\Models\LatePenalty::STATUS_VERIFYING,
                'payment_id' => $payment->id,
            ]);
        }

        // Notifikasi aktivitas ke seluruh admin: ada pembayaran denda baru menunggu verifikasi.
        AdminNotificationService::notifyAdmins(
            'return',
            '💸 Pembayaran Denda',
            "User {$orderModel->user?->name} mengunggah bukti pembayaran denda Rp "
                . number_format($amount, 0, ',', '.')
                . " untuk pesanan #{$orderModel->code}.",
            '💸',
            route('admin.pengembalian'),
        );

        return redirect()->route('history')
            ->with('status', 'Bukti pembayaran denda diterima. Status akan menjadi Lunas setelah diverifikasi admin.');
    }
}