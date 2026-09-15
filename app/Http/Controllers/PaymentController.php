<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\AdminNotificationService;
use App\Services\RentalNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Durasi window pembayaran (detik) sejak halaman konfirmasi terbuka.
     */
    private const PAYMENT_DURATION = 300;

    private const ALLOWED_METHODS = ['qris', 'gopay', 'dana', 'ovo', 'shopeepay', 'bank_transfer'];

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];

    private const MAX_PROOF_BYTES = 5120 * 1024;

    /**
     * Halaman Pembayaran Checkout
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($this->isSuspendedUser($request)) {
            return $this->handleSuspendedUser($request);
        }

        $cart = $this->selectedCart();
        if (empty($cart)) {
            return redirect()->route('cart')->withErrors(['error' => 'Belum ada barang yang dipilih. Centang minimal satu barang untuk melanjutkan.']);
        }

        // Re-validate stock before checkout (produk satuan & paket sewa)
        foreach ($cart as $id => $item) {
            if (! empty($item['is_bundle'])) {
                $bundle = Bundle::with('products')->find($item['bundle_id'] ?? null);
                if (! $bundle || ! $bundle->is_active) {
                    return redirect()->route('cart')->withErrors(['error' => "Paket {$item['name']} sudah tidak tersedia. Silakan periksa kembali keranjang Anda."]);
                }
                if ($bundle->availableStock() < $item['quantity']) {
                    return redirect()->route('cart')->withErrors(['error' => "Paket sedang habis dan tidak dapat disewa. Silakan periksa kembali keranjang Anda."]);
                }
            } else {
                $product = Product::find($item['id'] ?? $id);
                if (! $product || $product->stock_available < $item['quantity']) {
                    return redirect()->route('cart')->withErrors(['error' => 'Stok alat tidak mencukupi. Silakan periksa kembali keranjang Anda.']);
                }
            }
        }

        $order = $this->calculateOrder($cart);
        $cartItems = array_values($cart);
        $paymentMethods = $this->paymentMethods();

        return view('checkout.payment', [
            'order' => $order,
            'cartItems' => $cartItems,
            'payment_methods' => $paymentMethods,
        ]);
    }

    /**
     * Halaman Bayar QRIS (Konfirmasi & Upload Bukti)
     */
    public function qris(Request $request): View|RedirectResponse
    {
        if ($this->isSuspendedUser($request)) {
            return $this->handleSuspendedUser($request);
        }

        $cart = $this->selectedCart();
        if (empty($cart)) {
            return redirect()->route('cart')->withErrors(['error' => 'Belum ada barang yang dipilih. Centang minimal satu barang untuk melanjutkan.']);
        }

        $method = $request->query('method', 'qris');
        if (! in_array($method, self::ALLOWED_METHODS, true)) {
            $method = 'qris';
        }

        $paymentDeadline = $this->ensurePaymentDeadline($request);

        $order = $this->calculateOrder($cart);
        $cartItems = array_values($cart);

        return view('checkout.qris', [
            'order' => $order,
            'cartItems' => $cartItems,
            'payment_method' => $method,
            'payment_deadline' => $paymentDeadline,
        ]);
    }

    /**
     * Proses Pembuatan Order & Pembayaran Real di Database
     */
    public function process(Request $request): RedirectResponse|JsonResponse
    {
        if ($this->isSuspendedUser($request)) {
            return $this->handleSuspendedUser($request);
        }

        $cart = $this->selectedCart();
        if (empty($cart)) {
            return redirect()->route('cart')->withErrors(['error' => 'Keranjang masih kosong.']);
        }

        if ($this->isPaymentExpired($request)) {
            return $this->fail($request, 'Waktu pembayaran Anda telah habis. Silakan ulangi proses pembayaran.', 'error');
        }

        $method = $request->input('payment_method', 'qris');
        if (! in_array($method, self::ALLOWED_METHODS, true)) {
            return $this->fail($request, 'Metode pembayaran tidak valid.', 'error');
        }

        $proofRejection = $this->validateProof($request);
        if ($proofRejection !== null) {
            return $proofRejection;
        }

        // Strict Stock Check before finalizing order (produk satuan & paket sewa)
        foreach ($cart as $id => $item) {
            if (! empty($item['is_bundle'])) {
                $bundle = Bundle::with('products')->find($item['bundle_id'] ?? null);
                if (! $bundle || ! $bundle->is_active) {
                    return $this->fail($request, "Paket {$item['name']} sudah tidak tersedia.", 'error');
                }
                $bundleStock = $bundle->availableStock();
                if ($bundleStock < ($item['quantity'] ?? 1)) {
                    return $this->fail($request, 'Paket sedang habis dan tidak dapat disewa.', 'error');
                }
            } else {
                $product = Product::find($item['id'] ?? $id);
                if (! $product || $product->stock_available < ($item['quantity'] ?? 1)) {
                    return redirect()->route('cart')->withErrors([
                        'error' => "Stok alat {$item['name']} tidak mencukupi. Tersisa " . ($product?->stock_available ?? 0) . ' unit.'
                    ]);
                }
            }
        }

        $userId = session('account_id');
        if (! $userId || ! in_array(session('account_role'), ['customer', 'admin'])) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Silakan login terlebih dahulu untuk melakukan booking.']);
        }

        $existingUser = User::find($userId);
        if (! $existingUser || $existingUser->status === 'suspended' || $existingUser->status === 'inactive') {
            return $this->handleSuspendedUser($request);
        }

        $orderCalc = $this->calculateOrder($cart);

        // Buat Order Code unik
        $orderCode = 'RS-' . rand(1000, 9999) . '-' . strtoupper(substr(uniqid(), -3));
        $firstItem = reset($cart);
        $days = max(1, (int) ($firstItem['days'] ?? 3));

        // Rentang penyewaan: pakai tanggal kalender dari item keranjang bila tersedia;
        // fallback legacy = mulai hari ini dengan durasi (days) hari secara inklusif.
        $tz = config('app.timezone');

        if (! empty($firstItem['rent_start']) && ! empty($firstItem['rent_end'])) {
            $rentStart = Carbon::createFromFormat('Y-m-d', $firstItem['rent_start'], $tz)->startOfDay();
            $rentEnd = Carbon::createFromFormat('Y-m-d', $firstItem['rent_end'], $tz)->endOfDay();
        } else {
            $rentStart = now($tz)->startOfDay();
            $rentEnd = $rentStart->copy()->addDays($days - 1)->endOfDay();
        }

        // Buat Order, Order Items, Payment, dan notifikasi dalam SATU transaksi agar
        // tidak ada orok (partial) write: bila ada error apa pun, semua dibatalkan
        // sehingga retry tidak menghasilkan order/payment ganda.
        [$order, $payment] = DB::transaction(function () use ($orderCode, $userId, $rentStart, $rentEnd, $orderCalc, $cart, $days, $request, $method, $existingUser) {
            $order = Order::create([
                'code' => $orderCode,
                'user_id' => $userId,
                'rent_start' => $rentStart,
                'rent_end' => $rentEnd,
                'subtotal' => $orderCalc['base_rental'],
                'service_fee' => $orderCalc['service_fee'],
                'discount' => $orderCalc['discount'],
                'total' => $orderCalc['total'],
                'status' => 'pending',
                'created_at' => now(),
            ]);

            // Buat Order Items
            foreach ($cart as $item) {
                $productId = null;
                if (empty($item['is_bundle'])) {
                    $product = Product::find($item['id']);
                    $productId = $product?->id;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'bundle_id' => ! empty($item['is_bundle']) ? ($item['bundle_id'] ?? null) : null,
                    'name' => $item['name'],
                    'image' => $item['image'],
                    'quantity' => $item['quantity'] ?? 1,
                    'days' => $item['days'] ?? $days,
                    'unit_price' => $item['price_per_day'] ?? 100000,
                    'subtotal' => $item['subtotal'] ?? 300000,
                ]);
            }

            // Upload Bukti Pembayaran (wajib) ke storage PRIVAT (disk default local).
            // Hanya path relatif yang disimpan; file disajikan via route terkontrol.
            $proof = $request->file('proof');
            $filename = 'proof_' . time() . '_' . uniqid() . '.' . strtolower($proof->getClientOriginalExtension());
            $path = $proof->storeAs('proofs', $filename);

            // Buat Payment Record
            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $method,
                'amount' => $order->total,
                'status' => 'pending',
                'reference' => 'PAY-' . strtoupper(uniqid()),
                'proof_image' => $path,
                'created_at' => now(),
            ]);

            // Kirim notifikasi aktivitas penyewaan & pembayaran kepada seluruh admin.
            $this->notifyAdminsOfActivity($order, $payment, $cart, $existingUser, $method);

            // Notifikasi ke USER pemilik booking: booking berhasil dibuat
            // dan pembayaran telah diterima (diproses verifikasi admin).
            RentalNotificationService::notifyBookingCreated($order);
            RentalNotificationService::notifyPaymentSubmitted($order, $payment, $method);

            return [$order, $payment];
        });

        // Reset Cart & Window Pembayaran
        session()->forget('cart_items');
        session()->forget('payment_deadline');

        return redirect()->route('history')
            ->with('status', "Pesanan #{$order->code} berhasil diajukan! Menunggu verifikasi tim admin.");
    }

    /**
     * Kirim notifikasi aktivitas ke seluruh akun admin melalui channel database.
     * Memberitahu admin tentang penyewaan/booking baru dan pengunggahan bukti pembayaran.
     */
    private function notifyAdminsOfActivity($order, $payment, array $cart, User $user, string $method): void
    {
        $username = $user->username ?: ($user->name ?: 'user');
        $itemNames = collect($cart)
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        // 1. Notifikasi Penyewaan Baru -> arahkan ke halaman admin penyewaan.
        AdminNotificationService::notifyAdmins(
            'booking',
            '🔔 Penyewaan Baru',
            "User {$username} melakukan penyewaan alat ({$itemNames}). Kode #{$order->code}, status: {$order->status}.",
            '📦',
            route('admin.penyewaan'),
        );

        // 2. Notifikasi Pembayaran Baru -> arahkan ke halaman admin pembayaran.
        AdminNotificationService::notifyAdmins(
            'payment',
            '🔔 Pembayaran Baru',
            "User {$username} mengunggah bukti pembayaran Rp " . number_format((float) $payment->amount, 0, ',', '.')
                . " untuk pesanan #{$order->code} ({$method}).",
            '💳',
            route('admin.pembayaran'),
        );
    }

    private function isSuspendedUser(Request $request): bool
    {
        if ($request->session()->has('account_id') && $request->session()->get('account_role') === 'customer') {
            $user = User::find($request->session()->get('account_id'));
            return $user && ($user->status === 'suspended' || $user->status === 'inactive');
        }
        return false;
    }

    /**
     * Memastikan sesi pembayaran memiliki batas waktu yang masih berlaku.
     * Batas waktu dibuat di halaman konfirmasi dan dipakai sebagai timer oleh frontend.
     */
    private function ensurePaymentDeadline(Request $request): int
    {
        $deadline = (int) session('payment_deadline', 0);

        if ($deadline <= time()) {
            $deadline = time() + self::PAYMENT_DURATION;
            session(['payment_deadline' => $deadline]);
        }

        return $deadline;
    }

    /**
     * Apakah batas waktu pembayaran sudah habis?
     */
    private function isPaymentExpired(Request $request): bool
    {
        $deadline = (int) session('payment_deadline', 0);

        return $deadline <= 0 || time() > $deadline;
    }

    /**
     * Validasi file bukti pembayaran: wajib ada, ekstensi didukung, maksimal 5MB.
     * Mengembalikan response error atau null jika lolos.
     */
    private function validateProof(Request $request): RedirectResponse|JsonResponse|null
    {
        if (! $request->hasFile('proof')) {
            return $this->fail($request, 'Silakan upload bukti pembayaran terlebih dahulu.', 'proof');
        }

        $file = $request->file('proof');

        if ($file->getError() === UPLOAD_ERR_INI_SIZE || $file->getError() === UPLOAD_ERR_FORM_SIZE) {
            return $this->fail($request, 'Ukuran file maksimal 5MB.', 'proof');
        }

        if (! $file->isValid()) {
            return $this->fail($request, 'Silakan upload bukti pembayaran terlebih dahulu.', 'proof');
        }

        if (! in_array(strtolower($file->getClientOriginalExtension()), self::ALLOWED_EXTENSIONS, true)) {
            return $this->fail($request, 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau PDF.', 'proof');
        }

        if ($file->getSize() > self::MAX_PROOF_BYTES) {
            return $this->fail($request, 'Ukuran file maksimal 5MB.', 'proof');
        }

        return null;
    }

    /**
     * Bangun response error uniform: JSON 400 untuk pemanggilan API, redirect kembali dengan error untuk form biasa.
     */
    private function fail(Request $request, string $message, string $field = 'proof'): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [$field => $message],
            ], 400);
        }

        return back()->withErrors([$field => $message])->withInput();
    }

    private function handleSuspendedUser(Request $request): RedirectResponse
    {
        $request->session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar', 'cart_items', 'checkout_intended']);
        return redirect()->route('login')->withErrors(['email' => 'Akun Anda telah ditangguhkan (SUSPENDED). Tidak dapat melakukan checkout atau transaksi baru.']);
    }

    /**
     * Ambil hanya item keranjang yang DIPILIH (checkbox aktif).
     * Barang yang tidak dipilih tidak ikut dalam pengajuan peminjaman.
     */
    private function selectedCart(): array
    {
        $cart = session()->get('cart_items', []);

        return array_filter($cart, function ($item) {
            // Item tanpa flag 'selected' dianggap DIPILIH (backward compatible).
            return ! array_key_exists('selected', $item) || ! empty($item['selected']);
        });
    }

    private function calculateOrder(array $cart): array
    {
        if (empty($cart)) {
            return [
                'product_name' => 'No Gear Selected',
                'product_subtitle' => '0-Day Rental',
                'days' => 0,
                'rent_start' => null,
                'rent_end' => null,
                'image' => '',
                'base_rental' => 0,
                'insurance' => 0,
                'service_fee' => 0,
                'discount' => 0,
                'total' => 0,
            ];
        }

        $firstItem = reset($cart);
        $totalBase = array_sum(array_column($cart, 'subtotal'));
        $serviceFee = $totalBase > 0 ? 25000 : 0;
        $discount = 0;
        $total = max(0, $totalBase + $serviceFee);

        $itemCount = count($cart);
        $title = $firstItem['name'] . ($itemCount > 1 ? " + " . ($itemCount - 1) . " item lainnya" : "");

        return [
            'product_name' => $title,
            'product_subtitle' => "{$firstItem['days']}-Day Rental • Expedition Grade",
            'days' => $firstItem['days'] ?? 3,
            'rent_start' => $firstItem['rent_start'] ?? null,
            'rent_end' => $firstItem['rent_end'] ?? null,
            'image' => $firstItem['image'],
            'base_rental' => $totalBase,
            'insurance' => 0,
            'service_fee' => $serviceFee,
            'discount' => $discount,
            'total' => $total,
        ];
    }

    private function paymentMethods(): array
    {
        return [
            ['id' => 'qris',      'name' => 'QRIS',      'desc' => 'Pindai & Bayar',      'icon' => 'qris'],
            ['id' => 'gopay',     'name' => 'GOPAY',     'desc' => 'Dompet Digital',        'icon' => 'gopay'],
            ['id' => 'dana',      'name' => 'DANA',      'desc' => 'Pembayaran Instan',     'icon' => 'dana'],
            ['id' => 'ovo',       'name' => 'OVO',       'desc' => 'Siap Cashback',         'icon' => 'ovo'],
            ['id' => 'shopeepay', 'name' => 'SHOPEEPAY', 'desc' => 'Transaksi Lancar',      'icon' => 'shopeepay'],
        ];
    }
}
