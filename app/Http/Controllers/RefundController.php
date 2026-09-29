<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Services\AdminNotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return DB::transaction(function () use ($request, $order, $userId) {
            $orderModel = Order::with(['payments'])->lockForUpdate()->find((int) $order);

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
            // COMPLETED ikut dihitung: saat refund selesai, payment ditandai
            // 'refunded' tetapi status order tetap 'active'/'paid'. Tanpa
            // pemeriksaan ini user bisa mengajukan refund kedua untuk nominal
            // yang sama. 'rejected' tetap boleh diajukan ulang.
            $hasActiveRefund = Refund::where('order_id', $orderModel->id)
                ->whereIn('status', [
                    Refund::STATUS_PENDING,
                    Refund::STATUS_APPROVED,
                    Refund::STATUS_COMPLETED,
                ])
                ->lockForUpdate()
                ->exists();

            if ($hasActiveRefund) {
                return back()->withErrors(['refund' => 'Refund untuk booking ini sudah diajukan dan sedang diproses.']);
            }

            // Laundrykan penanda pembayaran: jika payment untuk pesanan ini sudah
            // berstatus 'refunded', jangan pernah buat pengajuan refund lagi.
            if ($orderModel->payments->contains(fn ($p) => $p->status === 'refunded')) {
                return back()->withErrors(['refund' => 'Dana untuk booking ini sudah pernah dikembalikan.']);
            }

            $validated = $request->validate([
                'reason' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string', 'max:2000'],
            ]);

            // Nominal refund dihitung server-side berdasarkan pembayaran yang berhasil.
            $originalAmount = $paidPayment ? (int) $paidPayment->amount : (int) $orderModel->total;

            $reasonLabel = self::REFUND_REASONS[$validated['reason']] ?? $validated['reason'];

            $refund = $this->createRefundWithUniqueCode([
                'order_id' => $orderModel->id,
                'user_id' => $userId,
                'payment_id' => $paidPayment?->id,
                'original_amount' => $originalAmount,
                'refund_amount' => $originalAmount,
                'reason' => $reasonLabel,
                'description' => $validated['description'],
                'status' => Refund::STATUS_PENDING,
            ]);

            // Notifikasi aktivitas ke tim admin: ada pengajuan refund baru.
            AdminNotificationService::notifyAdmins(
                'refund',
                '💳 Pengajuan Refund',
                "User {$orderModel->user?->name} mengajukan refund sebesar Rp "
                    .number_format($refund->refund_amount, 0, ',', '.')
                    ." untuk pesanan #{$orderModel->code}. Alasan: {$reasonLabel}.",
                '💰',
                route('admin.refund'),
            );

            return redirect()->route('history')
                ->with('status', 'Pengajuan refund berhasil dikirim dan sedang menunggu pemeriksaan admin.');
        });
    }

    /**
     * Buat refund dengan kode berurutan yang tetap aman terhadap request bersamaan.
     *
     * `refunds.code` punya constraint UNIQUE di database. Nomor urut
     * (max(id) + 1) bisa dihitung sama oleh dua request bersamaan, sehingga
     * tabrakan ditangani di lapisan database: insert yang gagal karena UNIQUE
     * di-catch lalu dicoba ulang dengan nomor berikutnya.
     *
     * Cara ini bebas race di semua engine dan tidak perlu mengunci tabel.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createRefundWithUniqueCode(array $attributes): Refund
    {
        $attempts = 5;

        while ($attempts-- > 0) {
            $nextNumber = $this->nextRefundCodeNumber();

            try {
                return Refund::create($attributes + [
                    'code' => 'RF-'.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT),
                ]);
            } catch (QueryException $e) {
                if (! $this->isUniqueCodeViolation($e) || $attempts === 0) {
                    throw $e;
                }

                // Tabrakan: ulangi dengan nomor berikutnya.
            }
        }

        // Praktis tidak tercapai: 5 tabrakan kode berturut-turut.
        throw new QueryException(
            Refund::query()->getQuery(),
            'Gagal membuat kode refund unik setelah beberapa percobaan.'
        );
    }

    /**
     * Nomor urut berikutnya yang belum dipakai.
     *
     * `max(id)` saja tidak cukup karena ada baris yang terhapus atau kode
     * dibuat manual, jadi kode yang sudah terpakai juga dilewati.
     */
    private function nextRefundCodeNumber(): int
    {
        $number = (int) Refund::max('id') + 1;

        $guard = 0;
        while (Refund::where('code', 'RF-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT))->exists() && $guard < 100) {
            $number++;
            $guard++;
        }

        return $number;
    }

    /**
     * Deteksi pelanggaran UNIQUE pada kolom `refunds.code`.
     */
    private function isUniqueCodeViolation(QueryException $e): bool
    {
        if (! str_contains($e->getMessage(), 'refunds.code')) {
            return false;
        }

        // SQLite dan PostgreSQL menyebut nama constraint/column yang bentrok.
        return str_contains($e->getMessage(), 'UNIQUE')
            || str_contains($e->getMessage(), 'unique')
            || in_array((string) ($e->errorInfo[0] ?? ''), ['23000', '19', '23505'], true);
    }

    /**
     * Tandai semua notifikasi user sebagai sudah dibaca.
     */
    public function markAllRead(): RedirectResponse
    {
        $userId = session('account_id');
        if ($userId && session('account_role') === 'customer') {
            User::find($userId)?->notifications()->whereNull('read_at')->get()
                ->each->markAsRead();
        }

        return back();
    }
}
