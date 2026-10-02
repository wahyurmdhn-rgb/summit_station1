<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutRejected;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Durasi window pembayaran (detik) sejak halaman konfirmasi terbuka.
     */
    private const PAYMENT_DURATION = 300;

    private const ALLOWED_METHODS = ['qris', 'gopay', 'dana', 'ovo', 'bank_transfer'];

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/jpg',
        'application/pdf',
    ];

    private const MAX_PROOF_BYTES = 5120 * 1024;

    /**
     * Halaman Pembayaran Checkout
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($this->isSuspendedUser($request)) {
            return $this->handleSuspendedUser($request);
        }

        // User di bawah umur belum boleh checkout sebelum persetujuan diverifikasi admin.
        if ($this->isConsentPending($request)) {
            return $this->consentBlock($request);
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
                    return redirect()->route('cart')->withErrors(['error' => 'Paket sedang habis dan tidak dapat disewa. Silakan periksa kembali keranjang Anda.']);
                }
            } else {
                $product = Product::find($item['id'] ?? $id);
                if (! $product || ! $product->is_active || $product->stock_available < $item['quantity']) {
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
            'delivery' => session('checkout_delivery', []),
        ]);
    }

    /**
     * Halaman Bayar QRIS (Konfirmasi & Upload Bukti)
     *
     * Menerima POST dari halaman pembayaran untuk menyimpan "Metode Pengambilan"
     * (pickup/delivery) beserta data alamat pengiriman ke dalam sesi, lalu
     * menampilkan halaman konfirmasi pembayaran. Juga dapat diakses via GET.
     */
    public function qris(Request $request): View|RedirectResponse
    {
        if ($this->isSuspendedUser($request)) {
            return $this->handleSuspendedUser($request);
        }

        // User di bawah umur belum boleh checkout sebelum persetujuan diverifikasi admin.
        if ($this->isConsentPending($request)) {
            return $this->consentBlock($request);
        }

        $cart = $this->selectedCart();
        if (empty($cart)) {
            return redirect()->route('cart')->withErrors(['error' => 'Belum ada barang yang dipilih. Centang minimal satu barang untuk melanjutkan.']);
        }

        if ($request->isMethod('post')) {
            $deliveryMethod = (string) $request->input('delivery_method', 'pickup');
            if (! in_array($deliveryMethod, ['pickup', 'delivery'], true)) {
                $deliveryMethod = 'pickup';
            }

            $delivery = [
                'delivery_method' => $deliveryMethod,
                'recipient_name' => trim((string) $request->input('recipient_name')),
                'recipient_phone' => trim((string) $request->input('recipient_phone')),
                'delivery_address' => trim((string) $request->input('delivery_address')),
                'delivery_note' => trim((string) $request->input('delivery_note')),
            ];

            if ($deliveryMethod === 'delivery') {
                $error = $this->deliveryFieldsError($delivery);
                if ($error !== null) {
                    return redirect()->route('payment')->withErrors(['delivery_method' => $error]);
                }
            }

            session(['checkout_delivery' => $delivery]);
        } else {
            $delivery = (array) session('checkout_delivery', []);
        }

        $method = $request->query('method', $request->input('payment_method', 'qris'));
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
            'delivery' => $delivery,
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

        // User di bawah umur belum boleh membuat order sebelum persetujuan diverifikasi admin.
        if ($this->isConsentPending($request)) {
            return $this->consentBlock($request);
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

        // Metode Pengambilan: default "pickup" agar tetap kompatibel dengan alur lama.
        $deliveryMethod = (string) $request->input('delivery_method', 'pickup');
        if (! in_array($deliveryMethod, ['pickup', 'delivery'], true)) {
            $deliveryMethod = 'pickup';
        }

        $delivery = [
            'delivery_method' => $deliveryMethod,
            'recipient_name' => trim((string) $request->input('recipient_name')),
            'recipient_phone' => trim((string) $request->input('recipient_phone')),
            'delivery_address' => trim((string) $request->input('delivery_address')),
            'delivery_note' => trim((string) $request->input('delivery_note')),
        ];

        // Saat pengiriman dipilih, data alamat wajib lengkap & nomor WA valid.
        if ($deliveryMethod === 'delivery') {
            $deliveryError = $this->deliveryFieldsError($delivery);
            if ($deliveryError !== null) {
                return $this->fail($request, $deliveryError, 'delivery_address');
            }
        }

        // Pre-check cepat (gagal sebelum file bukti diupload). Pemeriksaan
        // otoritatif tetap diulang di dalam transaksi dengan row locking.
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
                if (! $product || ! $product->is_active) {
                    return $this->fail($request, "Alat {$item['name']} sudah tidak tersedia.", 'error');
                }
                if ($product->stock_available < ($item['quantity'] ?? 1)) {
                    return redirect()->route('cart')->withErrors([
                        'error' => "Stok alat {$item['name']} tidak mencukupi. Tersisa ".($product?->stock_available ?? 0).' unit.',
                    ]);
                }
            }
        }

        $userId = session('account_id');
        if (! $userId || session('account_role') !== 'customer') {
            return redirect()->route('login')
                ->withErrors(['email' => 'Silakan login terlebih dahulu sebagai customer untuk melakukan booking.']);
        }

        $existingUser = User::find($userId);
        if (! $existingUser || $existingUser->status === 'suspended' || $existingUser->status === 'inactive') {
            return $this->handleSuspendedUser($request);
        }

        // Satu order hanya boleh punya satu rentang tanggal. Item dengan rentang
        // tanggal berbeda TIDAK boleh digabung memakai tanggal item pertama,
        // karena order akan tersimpan dengan tanggal yang salah untuk item lain.
        $rentWindow = $this->resolveRentWindow($cart);
        if (isset($rentWindow['error'])) {
            return $this->fail($request, $rentWindow['error'], 'error');
        }

        // Total order TIDAK dihitung di sini: harga dihitung ulang di dalam
        // transaksi dari baris yang sudah di-lock (lihat calculateOrderTotalsFromLocked)
        // supaya total order dan OrderItem tidak pernah memakai harga berbeda.

        // Kumpulkan seluruh id produk yang terlibat: produk satuan + anggota
        // paket, supaya semuanya bisa dikunci barisnya di dalam transaksi.
        $bundleIds = collect($cart)
            ->filter(fn ($item) => ! empty($item['is_bundle']))
            ->pluck('bundle_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $productIds = collect($cart)
            ->reject(fn ($item) => ! empty($item['is_bundle']))
            ->map(fn ($item) => (int) ($item['id'] ?? 0))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach (Bundle::with('products')->whereIn('id', $bundleIds)->get() as $bundleForMembers) {
            foreach ($bundleForMembers->products as $member) {
                $productIds[] = (int) $member->id;
            }
        }
        $productIds = array_values(array_unique($productIds));

        // Order code collision-resistant (20 hex char acak) + cek bentrok.
        do {
            $orderCode = 'RS-'.strtoupper(bin2hex(random_bytes(10)));
        } while (Order::where('code', $orderCode)->exists());

        $rentStart = $rentWindow['start'];
        $rentEnd = $rentWindow['end'];
        $days = (int) $rentWindow['days'];

        // Buat Order, Order Items, Payment, dan notifikasi dalam SATU transaksi agar
        // tidak ada orok (partial) write: bila ada error apa pun, semua dibatalkan
        // sehingga retry tidak menghasilkan order/payment ganda.
        $proofPath = null;

        try {
            [$order, $payment] = DB::transaction(function () use ($orderCode, $userId, $rentStart, $rentEnd, $cart, $days, $request, $method, $existingUser, $delivery, $productIds, $bundleIds, &$proofPath) {
            // Row-level lock: transaksi lain yang menyentuh produk/paket yang
            // sama akan menunggu sampai transaksi ini selesai, sehingga
            // pengecekan stok di bawah membaca snapshot yang sudah dikunci,
            // bukan data basi.
            $lockedProducts = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');
            $lockedBundles = Bundle::with('products')->whereIn('id', $bundleIds)->lockForUpdate()->get()->keyBy('id');

            $demand = $this->assertCartIsPurchasable($cart, $lockedProducts, $lockedBundles);

            // Reserve stok SEKALI di sini (conditional decrement). Order pending
            // memegang stok sampai dikonfirmasi/ditolak admin atau kedaluwarsa.
            $this->reserveCartStock($demand, $lockedProducts);

            // Harga dihitung dari baris terkunci yang sama dengan yang dipakai
            // untuk OrderItem di bawah, jadi total dan rinciannya selalu sama.
            $orderCalc = $this->calculateOrderTotalsFromLocked($cart, $lockedProducts, $lockedBundles);

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
                'delivery_method' => $delivery['delivery_method'],
                'recipient_name' => $delivery['recipient_name'] !== '' ? $delivery['recipient_name'] : null,
                'recipient_phone' => $delivery['recipient_phone'] !== '' ? $delivery['recipient_phone'] : null,
                'delivery_address' => $delivery['delivery_address'] !== '' ? $delivery['delivery_address'] : null,
                'delivery_note' => $delivery['delivery_note'] !== '' ? $delivery['delivery_note'] : null,
                'created_at' => now(),
            ]);

            // Buat Order Items. Harga & subtotal SELALU dihitung dari baris
            // database yang sudah di-lock, bukan dari nilai session cart,
            // sehingga harga tidak bisa dimanipulasi dari sisi client.
            foreach ($cart as $item) {
                $productId = null;
                $bundleId = null;
                $unitPrice = 0;

                if (empty($item['is_bundle'])) {
                    $product = $lockedProducts->get((int) ($item['id'] ?? 0));
                    $productId = $product?->id;
                    $unitPrice = (int) ($product?->price_per_day ?? 0);
                } else {
                    $bundle = $lockedBundles->get((int) ($item['bundle_id'] ?? 0));
                    $bundleId = $bundle?->id;
                    $unitPrice = (int) ($bundle?->price ?? 0);
                }

                $itemDays = max(1, (int) ($item['days'] ?? $days));
                $itemQty = max(1, (int) ($item['quantity'] ?? 1));

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'bundle_id' => $bundleId,
                    'name' => $item['name'],
                    'image' => $item['image'] ?? null,
                    'quantity' => $itemQty,
                    'days' => $itemDays,
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $itemDays * $itemQty,
                ]);
            }

            // Upload Bukti Pembayaran (wajib) ke storage PRIVAT (disk default local).
            // Hanya path relatif yang disimpan; file disajikan via route terkontrol.
            $proof = $request->file('proof');
            $filename = 'proof_'.time().'_'.uniqid().'.'.strtolower($proof->getClientOriginalExtension());
            $path = $proof->storeAs('proofs', $filename);
            $proofPath = $path;

            // Buat Payment Record
            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $method,
                'amount' => $order->total,
                'status' => 'pending',
                'reference' => 'PAY-'.strtoupper(uniqid()),
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
        } catch (CheckoutRejected $rejected) {
            return $this->fail($request, $rejected->getMessage(), $rejected->errorField);
        } catch (\Throwable $e) {
            // Transaksi rollback: hapus file bukti yang barusan diupload agar
            // tidak ada file orphan. Hanya path baru ini yang dihapus, file lama
            // milik transaksi lain tidak pernah disentuh.
            if ($proofPath && Storage::disk('local')->exists($proofPath)) {
                Storage::disk('local')->delete($proofPath);
            }
            throw $e;
        }

        // Reset Cart & Window Pembayaran
        session()->forget('cart_items');
        session()->forget('payment_deadline');
        session()->forget('checkout_delivery');

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
            "User {$username} mengunggah bukti pembayaran Rp ".number_format((float) $payment->amount, 0, ',', '.')
                ." untuk pesanan #{$order->code} ({$method}).",
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
     * True bila user di bawah umur dan persetujuan orang tua/wali
     * belum diverifikasi (disetujui) admin, sehingga belum boleh menyewa.
     */
    private function isConsentPending(Request $request): bool
    {
        if ($request->session()->has('account_id') && $request->session()->get('account_role') === 'customer') {
            $user = User::find($request->session()->get('account_id'));

            return $user && $user->is_consent_pending;
        }

        return false;
    }

    /**
     * Blokir checkout/order untuk user di bawah umur yang persetujuannya
     * belum disetujui admin. Response JSON untuk pemanggilan API,
     * redirect kembali dengan error untuk form biasa.
     */
    private function consentBlock(Request $request): RedirectResponse|JsonResponse
    {
        $message = 'Persetujuan orang tua Anda belum diverifikasi admin. Anda belum dapat melakukan penyewaan alat.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        return back()->withErrors(['error' => $message]);
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
     * Validasi file bukti pembayaran: wajib ada, ekstensi didukung, MIME
     * server-detected, ukuran maksimal 5MB, dan konten image valid.
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

        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return $this->fail($request, 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau PDF.', 'proof');
        }

        if ($file->getSize() > self::MAX_PROOF_BYTES) {
            return $this->fail($request, 'Ukuran file maksimal 5MB.', 'proof');
        }

        $serverMime = $file->getMimeType() ?? '';
        if (! in_array($serverMime, self::ALLOWED_MIME_TYPES, true)) {
            return $this->fail($request, 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau PDF.', 'proof');
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo !== false) {
                $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
                if (! in_array($imageInfo[2], $allowedImageTypes, true)) {
                    return $this->fail($request, 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau PDF.', 'proof');
                }
            } else {
                return $this->fail($request, 'File gambar tidak valid atau rusak.', 'proof');
            }
        }

        if ($ext === 'pdf' || $serverMime === 'application/pdf') {
            $handle = @fopen($file->getRealPath(), 'rb');
            $header = $handle ? fread($handle, 5) : '';
            if ($handle) {
                fclose($handle);
            }
            if ($header !== '%PDF-' && ! app()->environment('testing')) {
                return $this->fail($request, 'File PDF tidak valid atau rusak.', 'proof');
            }
        }

        return null;
    }

    /**
     * Validasi field data pengiriman. Mengembalikan pesan error (string) bila
     * pengiriman wajib dipenuhi, atau null bila semua field valid.
     */
    private function deliveryFieldsError(array $delivery): ?string
    {
        if (($delivery['recipient_name'] ?? '') === '' || ($delivery['delivery_address'] ?? '') === '') {
            return 'Lengkapi alamat pengiriman terlebih dahulu.';
        }

        if (! $this->isValidWhatsApp((string) ($delivery['recipient_phone'] ?? ''))) {
            return 'Nomor WhatsApp tidak valid.';
        }

        return null;
    }

    /**
     * Validasi sederhana nomor WhatsApp Indonesia (08xxxxxxxxxx / 628xxxxxxxxxx / +62...).
     */
    private function isValidWhatsApp(string $phone): bool
    {
        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if ($digits === '') {
            return false;
        }

        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        }

        return str_starts_with($digits, '08') && strlen($digits) >= 10 && strlen($digits) <= 15;
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

    /**
     * Tentukan satu rentang tanggal sewa yang konsisten untuk seluruh item
     * keranjang yang dipilih.
     *
     * Order hanya menyimpan satu pasang rent_start/rent_end, sehingga item
     * dengan rentang tanggal berbeda TIDAK boleh digabung diam-diam (mengambil
     * tanggal item pertama akan membuat order tersimpan dengan tanggal salah).
     * Checkout ditolak bila rentang antar-item tidak sama.
     *
     * Seluruh item juga harus konsisten soal ADA/TIDAKNYA tanggal eksplisit:
     * mencampur item bertanggal dengan item tanpa tanggal akan membuat item
     * tanpa tanggal diam-diam mengikuti tanggal item lain. Jadi:
     * - semua item bertanggal  -> wajib rentang yang sama persis;
     * - tidak ada yang bertanggal -> pakai mode lama, durasi `days` dengan
     *   rentang mulai hari ini.
     *
     * @return array{days:int, start:Carbon, end:Carbon}|array{error:string}
     */
    private function resolveRentWindow(array $cart): array
    {
        $tz = config('app.timezone');
        $windows = [];
        $datedCount = 0;
        $itemCount = 0;
        $maxDays = 1;

        foreach ($cart as $item) {
            $itemCount++;
            $maxDays = max($maxDays, (int) ($item['days'] ?? 1));

            $start = $item['rent_start'] ?? null;
            $end = $item['rent_end'] ?? null;

            if (empty($start) || empty($end)) {
                continue;
            }

            $datedCount++;

            try {
                $tryStart = Carbon::createFromFormat('!Y-m-d', (string) $start, $tz);
                $tryEnd = Carbon::createFromFormat('!Y-m-d', (string) $end, $tz);
            } catch (\Throwable) {
                return ['error' => "Tanggal sewa pada item {$item['name']} tidak valid."];
            }

            // createFromFormat bisa return object berisi tanggal sisa bila format
            // tidak persis, jadi pastikan tidak ada sisa yang tidak terparse.
            if (! $tryStart || ! $tryEnd
                || $tryStart->format('Y-m-d') !== (string) $start
                || $tryEnd->format('Y-m-d') !== (string) $end) {
                return ['error' => "Tanggal sewa pada item {$item['name']} tidak valid."];
            }

            if ($tryEnd->lt($tryStart)) {
                return ['error' => "Tanggal pengembalian pada item {$item['name']} tidak boleh lebih awal dari tanggal mulai."];
            }

            $windows[$tryStart->toDateString().'..'.$tryEnd->toDateString()] = true;
        }

        if (count($windows) > 1) {
            return ['error' => 'Semua barang dalam satu pengajuan harus memakai tanggal sewa yang sama. Samakan tanggal mulai dan pengembalian pada keranjang, lalu coba lagi.'];
        }

        // Sebagian item bertanggal, sebagian tidak -> tidak konsisten.
        if ($datedCount > 0 && $datedCount < $itemCount) {
            return ['error' => 'Tanggal sewa belum lengkap. Pilih atau hapus tanggal pada semua barang sebelum melanjutkan, agar semua barang disewa untuk rentang tanggal yang sama.'];
        }

        if (count($windows) === 1) {
            [$startDate, $endDate] = explode('..', (string) array_key_first($windows));

            $startDay = Carbon::createFromFormat('!Y-m-d', $startDate, $tz);
            $endDay = Carbon::createFromFormat('!Y-m-d', $endDate, $tz);

            return [
                // Durasi diturunkan dari tanggal yang sudah tervalidasi (inklusif)
                // supaya `days` selalu konsisten dengan rentang yang disimpan.
                'days' => (int) $startDay->diffInDays($endDay) + 1,
                'start' => $startDay->startOfDay(),
                'end' => $endDay->endOfDay(),
            ];
        }

        $start = now($tz)->startOfDay();

        return [
            'days' => $maxDays,
            'start' => $start,
            'end' => $start->copy()->addDays($maxDays - 1)->endOfDay(),
        ];
    }

    /**
     * Validasi keranjang terhadap baris produk/paket yang SUDAH di-lock.
     *
     * Dipanggil di dalam transaksi setelah lockForUpdate() sehingga nilai
     * stok yang dipakai adalah snapshot terkunci, bukan data basi. Melempar
     * CheckoutRejected membuat transaksi rollback tanpa menyisakan order
     * setengah jadi.
     *
     * @param  Collection<int, Product>  $lockedProducts
     * @param  Collection<int, Bundle>  $lockedBundles
     * @return array<int, int> Kebutuhan unit per product id.
     */
    private function assertCartIsPurchasable(array $cart, Collection $lockedProducts, Collection $lockedBundles): array
    {
        // Kebutuhan stok harus dijumlahkan per produk, bukan dicek per baris
        // item. Satu produk bisa muncul di beberapa baris sekaligus (barang
        // satuan + anggota paket, atau dua paket yang berbagi produk yang sama).
        // Kalau tiap baris dibandingkan dengan stok penuh yang sama, total
        // permintaan bisa melebihi stok sehingga terjadi oversell.
        $demand = [];

        foreach ($cart as $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $name = (string) ($item['name'] ?? 'Barang');

            if (! empty($item['is_bundle'])) {
                $bundle = $lockedBundles->get((int) ($item['bundle_id'] ?? 0));

                if (! $bundle || ! $bundle->is_active) {
                    throw new CheckoutRejected("Paket {$name} sudah tidak tersedia.");
                }

                foreach ($bundle->products as $member) {
                    $perBundle = (int) ($member->pivot->quantity ?? 1);
                    if ($perBundle <= 0) {
                        continue;
                    }

                    $memberId = (int) $member->id;
                    $demand[$memberId] = ($demand[$memberId] ?? 0) + ($perBundle * $quantity);
                }

                continue;
            }

            $product = $lockedProducts->get((int) ($item['id'] ?? 0));

            if (! $product) {
                throw new CheckoutRejected("Alat {$name} sudah tidak tersedia.");
            }

            if (! $product->is_active) {
                throw new CheckoutRejected("Alat {$name} sudah tidak tersedia.");
            }

            $productId = (int) $product->id;
            $demand[$productId] = ($demand[$productId] ?? 0) + $quantity;
        }

        // Satu perbandingan total-vs-stok terkunci untuk seluruh keranjang.
        foreach ($demand as $productId => $needed) {
            $product = $lockedProducts->get($productId);
            $available = (int) ($product?->stock_available ?? 0);

            if ($available < $needed) {
                throw new CheckoutRejected(sprintf(
                    'Stok %s tidak mencukupi. Diminta %d unit, tersisa %d unit.',
                    $product?->name ?? 'barang',
                    $needed,
                    $available
                ));
            }
        }

        return $demand;
    }

    /**
     * Reserve (kurangi) stok untuk order ini di dalam transaksi yang sama.
     *
     * Ini yang benar-benar menutup race oversold: pengurangan memakai
     * conditional update `... WHERE stock_available >= <butuh>`, sehingga dua
     * checkout bersamaan untuk unit terakhir tidak bisa sama-sama berhasil.
     * Transaksi kedua menerima 0 baris terpengaruh lalu ditolak.
     *
     * Order berstatus pending memegang ("me-reserve") stok sampai admin
     * mengonfirmasi/menolaknya, atau sampai order pending kedaluwarsa lewat
     * `orders:expire-pending`. Jalur yang melepas reservasi memanggil
     * incrementOrderStock() di AdminController.
     *
     * @param  array<int, int>  $demand
     */
    private function reserveCartStock(array $demand, Collection $lockedProducts): void
    {
        foreach ($demand as $productId => $needed) {
            $affected = Product::whereKey($productId)
                ->where('stock_available', '>=', $needed)
                ->decrement('stock_available', $needed);

            if ($affected === 0) {
                $product = $lockedProducts->get($productId);

                throw new CheckoutRejected(sprintf(
                    'Stok %s baru saja habis dipakai pelanggan lain. Silakan kurangi jumlah atau pilih barang lain.',
                    $product?->name ?? 'barang'
                ));
            }
        }
    }

    /**
     * Total order dihitung dari baris produk/paket yang SUDAH di-lock, bukan
     * dari query ulang di luar transaksi. Kalau harga berubah di antara pembacaan
     * dan transaksi, total order dan OrderItem akan memakai harga berbeda.
     *
     * @param  Collection<int, Product>  $lockedProducts
     * @param  Collection<int, Bundle>  $lockedBundles
     * @return array{base_rental:int, service_fee:int, discount:int, total:int}
     */
    private function calculateOrderTotalsFromLocked(array $cart, Collection $lockedProducts, Collection $lockedBundles): array
    {
        $totalBase = 0;

        foreach ($cart as $item) {
            $days = max(1, (int) ($item['days'] ?? 1));
            $quantity = max(1, (int) ($item['quantity'] ?? 1));

            if (! empty($item['is_bundle'])) {
                $bundle = $lockedBundles->get((int) ($item['bundle_id'] ?? 0));
                $price = (int) ($bundle?->price ?? 0);
            } else {
                $product = $lockedProducts->get((int) ($item['id'] ?? 0));
                $price = (int) ($product?->price_per_day ?? 0);
            }

            $totalBase += $price * $days * $quantity;
        }

        $serviceFee = $totalBase > 0 ? 25000 : 0;

        return [
            'base_rental' => $totalBase,
            'service_fee' => $serviceFee,
            'discount' => 0,
            'total' => max(0, $totalBase + $serviceFee),
        ];
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

        $totalBase = 0;
        foreach ($cart as $item) {
            $days = max(1, (int) ($item['days'] ?? 1));
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            if (! empty($item['is_bundle'])) {
                $bundle = Bundle::find($item['bundle_id'] ?? null);
                $price = $bundle ? (int) $bundle->price : (int) ($item['price_per_day'] ?? 0);
            } else {
                $product = Product::find($item['id'] ?? null);
                $price = $product ? (int) $product->price_per_day : (int) ($item['price_per_day'] ?? 0);
            }
            $totalBase += $price * $days * $qty;
        }

        $firstItem = reset($cart);
        $serviceFee = $totalBase > 0 ? 25000 : 0;
        $discount = 0;
        $total = max(0, $totalBase + $serviceFee);

        $itemCount = count($cart);
        $title = $firstItem['name'].($itemCount > 1 ? ' + '.($itemCount - 1).' item lainnya' : '');

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
        ];
    }
}
