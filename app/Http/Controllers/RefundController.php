<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Refund;
use App\Services\AdminNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public const REFUND_REASONS = [
        'tidak_jadi' => 'Tidak jadi menggunakan barang',
        'ganti_pesanan' => 'Ingin mengganti pesanan',
        'stok_kurang' => 'Barang yang dikirim tidak lengkap',
        'kerusakan' => 'Barang rusak / tidak sesuai deskripsi',
        'cancel_rental' => 'Pembatalan penyewaan',
        'lainnya' => 'Lainnya',
    ];

    /**
     * User mengajukan refund untuk booking miliknya.
     * Semua nominal dihitung dari data server (payment/order), bukan dari frontend.
     */
    public function store(Request $request, $order): RedirectResponse
    {
        if (session('account_role') !== 'customer' || ! session()->has('account_id')) {
            return redirect()->route('login')
                ->with('status', 'Silakan login terlebih dahulu untuk mengajukan refund.');
        }

        $userId = (int) session('account_id');
        $orderModel = Order::with(['payments'])->find((int) $order);

        if (! $orderModel) {
            return back()->withErrors(['refund' => 'Pesanan tidak ditemukan.']);
        }

        // SECURITY: user hanya boleh mengajukan refund untuk booking miliknya.
        if ((int) $orderModel->user_id !== $userId) {
            abort(403, 'Anda tidak memiliki izin untuk mengajukan refund pada pesanan ini.');
        }

        // Pastikan booking sudah dibayar (ada payment success atau order paid).
        $paidPayment = $orderModel->payments->first(fn ($p) => $p->status === 'success');
        $isPaid = $paidPayment !== null || $orderModel->status === 'paid'
            || in_array($orderModel->status, ['active', 'completed']);

        if (! $isPaid) {
            return back()->withErrors(['refund' => 'Refund hanya dapat diajukan untuk booking yang sudah dibayar.']);
        }

        if (in_array($orderModel->status, ['pending', 'cancelled', 'completed'])) {
            return back()->withErrors(['refund' => 'Booking dengan status ini tidak dapat diajukan refund.']);
        }

        // Cegah refund ganda selama masih pending/approved.
        $hasActiveRefund = Refund::where('order_id', $orderModel->id)
            ->whereIn('status', [Refund::STATUS_PENDING, Refund::STATUS_APPROVED])
            ->exists();

        if ($hasActiveRefund) {
            return back()->withErrors(['refund' => 'Refund untuk booking ini sudah diajukan dan sedang diproses.']);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        // Nominal refund dihitung server-side berdasarkan pembayaran yang berhasil.
        $originalAmount = $paidPayment ? (int) $paidPayment->amount : (int) $orderModel->total;

        $nextId = (int) Refund::max('id') + 1;
        $reasonLabel = self::REFUND_REASONS[$validated['reason']] ?? $validated['reason'];

        $refund = Refund::create([
            'code' => 'RF-' . str_pad((string) $nextId, 3, '0', STR_PAD_LEFT),
            'order_id' => $orderModel->id,
            'user_id' => $userId,
            'payment_id' => $paidPayment?->id,
            'original_amount' => $originalAmount,
            'refund_amount' => $originalAmount,
            'reason' => $reasonLabel,
            'description' => $validated['description'],
            'status' => Refund::STATUS_PENDING,
        ]);

        // Pastikan code unik (fallback dengan id setelah insert).
        if (Refund::where('code', $refund->code)->where('id', '!=', $refund->id)->exists()) {
            $refund->update(['code' => 'RF-' . str_pad((string) $refund->id, 3, '0', STR_PAD_LEFT)]);
        }

        // Notifikasi aktivitas ke tim admin: ada pengajuan refund baru.
        AdminNotificationService::notifyAdmins(
            'refund',
            '💳 Pengajuan Refund',
            "User {$orderModel->user?->name} mengajukan refund sebesar Rp "
                . number_format($refund->refund_amount, 0, ',', '.')
                . " untuk pesanan #{$orderModel->code}. Alasan: {$reasonLabel}.",
            '💰',
            route('admin.refund'),
        );

        return redirect()->route('history')
            ->with('status', 'Pengajuan refund berhasil dikirim dan sedang menunggu pemeriksaan admin.');
    }

    /**
     * Tandai semua notifikasi user sebagai sudah dibaca.
     */
    public function markAllRead(): RedirectResponse
    {
        $userId = session('account_id');
        if ($userId && session('account_role') === 'customer') {
            \App\Models\User::find($userId)?->notifications()->whereNull('read_at')->get()
                ->each->markAsRead();
        }

        return back();
    }
}