<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Jumlah maksimal paket sewa (bundle) per pesanan.
     * Sumber sama dengan halaman detail paket (stock_available package).
     */
    private const BUNDLE_MAX_QUANTITY = 5;

    /**
     * Hitung durasi penyewaan (hari) dari input pengguna.
     *
     * Sumber kebenaran:
     *  - `rent_start` + `rent_end` (tanggal kalender) -> durasi = selisih hari + 1 (INKLUSIF).
     *  - hanya `days` (legacy) -> dipertahankan apa adanya (fallback lama, tanpa tanggal).
     *
     * @return array{days:int, rent_start:?string, rent_end:?string}|array{error:string}
     */
    private function resolveDuration(Request $request, int $fallbackDays): array
    {
        $startRaw = trim((string) $request->input('rent_start'));
        $endRaw = trim((string) $request->input('rent_end'));

        // Legacy / tanpa tanggal: perilaku lama (clamp 1-30) tetap dipertahankan.
        if ($startRaw === '' && $endRaw === '') {
            return [
                'days' => max(1, min(30, $fallbackDays)),
                'rent_start' => null,
                'rent_end' => null,
            ];
        }

        $tz = config('app.timezone');

        try {
            $start = $startRaw !== '' ? Carbon::createFromFormat('Y-m-d', $startRaw, $tz) : null;
            $end = $endRaw !== '' ? Carbon::createFromFormat('Y-m-d', $endRaw, $tz) : null;
        } catch (\Exception) {
            return ['error' => 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD.'];
        }

        if (! $start || ! $end || $start->format('Y-m-d') !== $startRaw || $end->format('Y-m-d') !== $endRaw) {
            return ['error' => 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD.'];
        }

        $startDay = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        if ($startDay->lt(Carbon::today($tz)->startOfDay())) {
            return ['error' => 'Tanggal mulai tidak boleh sebelum hari ini.'];
        }

        if ($endDay->lt($startDay)) {
            return ['error' => 'Tanggal pengembalian tidak boleh sebelum tanggal mulai penyewaan.'];
        }

        $days = (int) $startDay->diffInDays($endDay) + 1;

        if ($days > 30) {
            return ['error' => 'Durasi maksimal 30 hari.'];
        }

        return [
            'days' => $days,
            'rent_start' => $startDay->format('Y-m-d'),
            'rent_end' => $endDay->format('Y-m-d'),
        ];
    }

    /**
     * Tampilan Keranjang Sewa
     */
    public function index(Request $request): View|RedirectResponse
    {
        // Validasi status user jika sedang login
        if ($this->isSuspendedUser($request)) {
            return $this->handleSuspendedUser($request);
        }

        $consentPending = $this->isConsentPending($request);

        $cart = session()->get('cart_items', []);
        $stockWarnings = [];
        $hasInsufficientStock = false;

        // Re-validate against database products and bundles
        foreach ($cart as $key => &$item) {
            // Default pemilihan aktif (barang ikut pengajuan peminjaman).
            if (empty($item['is_selected_set']) && ! array_key_exists('selected', $item)) {
                $item['selected'] = true;
            }
            if (!empty($item['is_bundle'])) {
                $bundle = Bundle::with('products')->find($item['bundle_id'] ?? null);
                if (! $bundle || ! $bundle->is_active) {
                    unset($cart[$key]);
                    continue;
                }
                $item['price_per_day'] = (int) $bundle->price;
                $item['subtotal'] = (int) ($bundle->price * $item['days'] * $item['quantity']);
                $item['image'] = $bundle->image ?: $item['image'];

                // Stok paket dihitung ulang dari database (bukan nilai sesi yang basi).
                $bundleStock = $bundle->availableStock();
                $item['stock_available'] = $bundleStock;
                if ($bundleStock < $item['quantity']) {
                    $hasInsufficientStock = true;
                    $stockWarnings[] = "Stok paket {$bundle->name} tersisa {$bundleStock} paket (Anda meminta {$item['quantity']} paket).";
                }
            } else {
                $product = Product::find($item['id'] ?? $key);
                if (! $product || ! $product->is_active) {
                    unset($cart[$key]);
                    continue;
                }

                // Check current stock in database
                if ($item['quantity'] > $product->stock_available) {
                    $hasInsufficientStock = true;
                    $stockWarnings[] = "Stok {$product->name} tersisa {$product->stock_available} unit (Anda meminta {$item['quantity']} unit).";
                }

                // Sync latest price and image
                $item['price_per_day'] = (int) $product->price_per_day;
                $item['subtotal'] = (int) ($product->price_per_day * $item['days'] * $item['quantity']);
                $item['stock_available'] = (int) $product->stock_available;
                $item['image'] = $product->main_image ?: $item['image'];
            }
        }
        unset($item);

        session(['cart_items' => $cart]);

        $cartItems = array_values($cart);
        [$subtotalRentals, $selectedCount] = $this->selectedTotals($cartItems);
        $serviceFee = $subtotalRentals > 0 ? 25000 : 0;
        $totalPayable = max(0, $subtotalRentals + $serviceFee);

        return view('checkout.cart', compact(
            'cartItems',
            'subtotalRentals',
            'serviceFee',
            'totalPayable',
            'stockWarnings',
            'hasInsufficientStock',
            'selectedCount',
            'consentPending'
        ));
    }

    /**
     * Tambah Item Produk Satuan ke Keranjang Sewa
     */
    public function add(Request $request): RedirectResponse
    {
        // Validasi status user jika sedang login
        if ($this->isSuspendedUser($request)) {
            return $this->handleSuspendedUser($request);
        }

        // User di bawah umur belum boleh menyewa sebelum persetujuan diverifikasi admin.
        if ($this->isConsentPending($request)) {
            return $this->consentBlock($request);
        }

        $productId = $request->input('product_id');
        $duration = $this->resolveDuration($request, (int) $request->input('days', 3));
        if (isset($duration['error'])) {
            return back()->withErrors(['error' => $duration['error']]);
        }
        $days = $duration['days'];
        $rentStart = $duration['rent_start'] ?? null;
        $rentEnd = $duration['rent_end'] ?? null;
        $quantity = max(1, (int) $request->input('quantity', 1));

        $product = Product::with('category')->find($productId);
        if (! $product) {
            return redirect()->route('catalog')->withErrors(['error' => 'Alat tidak ditemukan.']);
        }

        // Strict Stock Validation
        if ($product->stock_available < $quantity) {
            return back()->withErrors(['error' => "Stok alat tidak mencukupi. Tersedia {$product->stock_available} unit."]);
        }

        $cart = session()->get('cart_items', []);

        $pricePerDay = (int) $product->price_per_day;
        $subtotal = $pricePerDay * $days * $quantity;

        $cart[$product->id] = [
            'id' => $product->id,
            'is_bundle' => false,
            'name' => $product->name,
            'category' => $product->category?->name ?? 'EQUIPMENT',
            'subtitle' => $product->subtitle ?? 'Expedition Grade',
            'days' => $days,
            'rent_start' => $rentStart,
            'rent_end' => $rentEnd,
            'quantity' => $quantity,
            'stock_available' => (int) $product->stock_available,
            'price_per_day' => $pricePerDay,
            'subtotal' => $subtotal,
            'selected' => true,
            'image' => $product->main_image ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=400&q=80',
        ];

        session(['cart_items' => $cart]);

        return redirect()->route('cart')->with('status', "{$product->name} ({$days} hari, {$quantity} unit) berhasil dimasukkan ke keranjang sewa!");
    }

    /**
     * Tambah Paket Sewa (Bundle) ke Keranjang Sewa
     */
    public function addBundle(Request $request): RedirectResponse
    {
        // Validasi status user jika sedang login
        if ($this->isSuspendedUser($request)) {
            return $this->handleSuspendedUser($request);
        }

        // User di bawah umur belum boleh menyewa sebelum persetujuan diverifikasi admin.
        if ($this->isConsentPending($request)) {
            return $this->consentBlock($request);
        }

        $bundleId = $request->input('bundle_id');
        $duration = $this->resolveDuration($request, (int) $request->input('days', 3));
        if (isset($duration['error'])) {
            return back()->withErrors(['error' => $duration['error']]);
        }
        $days = $duration['days'];
        $rentStart = $duration['rent_start'] ?? null;
        $rentEnd = $duration['rent_end'] ?? null;
        $quantity = max(1, (int) $request->input('quantity', 1));

        $bundle = Bundle::with('products')->find($bundleId);
        if (! $bundle) {
            return redirect()->route('catalog')->withErrors(['error' => 'Paket sewa tidak ditemukan.']);
        }

        // Validasi backend: jumlah paket tidak boleh melebihi jumlah maksimal package.
        if ($quantity > self::BUNDLE_MAX_QUANTITY) {
            return redirect()->route('catalog')->withErrors(['error' => 'Jumlah maksimal ' . self::BUNDLE_MAX_QUANTITY . ' paket.']);
        }

        // Stok paket aktual dari database (sumber tunggal).
        $bundleMaxStock = $bundle->availableStock();

        // Validasi stok semua produk dalam bundle
        $needed = $quantity;
        if ($bundleMaxStock < $needed) {
            return back()->withErrors(['error' => "Stok paket {$bundle->name} tidak mencukupi. Tersedia {$bundleMaxStock} paket (dibutuhkan {$quantity} paket)."]);
        }

        $cart = session()->get('cart_items', []);
        $cartKey = 'bundle_' . $bundle->id;

        $pricePerDay = (int) $bundle->price;
        $subtotal = $pricePerDay * $days * $quantity;

        $cart[$cartKey] = [
            'id' => $cartKey,
            'bundle_id' => $bundle->id,
            'is_bundle' => true,
            'name' => $bundle->name,
            'category' => 'PAKET SEWA',
            'subtitle' => 'Exclusive Expedition Bundle',
            'days' => $days,
            'rent_start' => $rentStart,
            'rent_end' => $rentEnd,
            'quantity' => $quantity,
            'stock_available' => $bundleMaxStock,
            'price_per_day' => $pricePerDay,
            'subtotal' => $subtotal,
            'selected' => true,
            'image' => $bundle->image ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=400&q=80',
        ];

        session(['cart_items' => $cart]);

        return redirect()->route('cart')->with('status', "{$bundle->name} ({$days} hari, {$quantity} paket) berhasil dimasukkan ke keranjang sewa!");
    }

    /**
     * Update Durasi atau Jumlah Item di Keranjang
     */
    public function update(Request $request, string|int $id): RedirectResponse|JsonResponse
    {
        if ($this->isSuspendedUser($request)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Akun Anda ditangguhkan.'], 403);
            }
            return $this->handleSuspendedUser($request);
        }

        if ($this->isConsentPending($request)) {
            return $this->consentBlock($request);
        }

        $cart = session()->get('cart_items', []);

        if (! isset($cart[$id])) {
            return redirect()->route('cart')->withErrors(['error' => 'Item tidak ditemukan di keranjang.']);
        }

        $item = $cart[$id];

        $fallback = $request->has('days') ? (int) $request->input('days') : (int) ($item['days'] ?? 3);
        $duration = $this->resolveDuration($request, $fallback);
        if (isset($duration['error'])) {
            return $this->cartError($request, $duration['error']);
        }
        $days = $duration['days'];
        $rentStart = $duration['rent_start'] ?? ($item['rent_start'] ?? null);
        $rentEnd = $duration['rent_end'] ?? ($item['rent_end'] ?? null);

        $requestedQty = $request->has('quantity') ? (int) $request->input('quantity') : (int) ($item['quantity'] ?? 1);

        // Sumber maksimum unit sama dengan halaman detail: stok tersedia produk / package.
        $maxQuantity = 1;

        if (empty($item['is_bundle'])) {
            $product = Product::find($id);
            if (! $product) {
                unset($cart[$id]);
                session(['cart_items' => $cart]);
                return $this->cartError($request, 'Alat sudah tidak tersedia.');
            }
            $maxQuantity = max(1, (int) $product->stock_available);
        } else {
            // Paket tidak memiliki kolom stok di DB; jumlah maksimal dihitung ulang
            // dari stok aktual seluruh barang anggota (sumber tunggal), bukan dari
            // nilai sesi yang basi.
            $bundle = Bundle::with('products')->find($item['bundle_id'] ?? null);
            if (! $bundle || ! $bundle->is_active) {
                unset($cart[$id]);
                session(['cart_items' => $cart]);
                return $this->cartError($request, 'Paket sudah tidak tersedia.');
            }
            $recomputedStock = $bundle->availableStock();
            $cart[$id]['stock_available'] = $recomputedStock;
            // Paket dibatasi maksimal BUNDLE_MAX_QUANTITY, dengan batas bawah stok aktual.
            $maxQuantity = min($recomputedStock, self::BUNDLE_MAX_QUANTITY);
        }

        // Validasi backend: quantity >= 1 dan <= jumlah maksimum.
        if ($requestedQty < 1) {
            return $this->cartError($request, 'Jumlah unit minimal 1.');
        }

        if ($requestedQty > $maxQuantity) {
            return $this->cartError($request, "Jumlah unit maksimal {$maxQuantity}.");
        }

        $quantity = $requestedQty;

        $cart[$id]['days'] = $days;
        $cart[$id]['rent_start'] = $rentStart;
        $cart[$id]['rent_end'] = $rentEnd;
        $cart[$id]['quantity'] = $quantity;
        $cart[$id]['subtotal'] = (int) ($cart[$id]['price_per_day'] * $days * $quantity);

        session(['cart_items' => $cart]);

        $cartItems = array_values($cart);
        [$subtotalRentals, $selectedCount] = $this->selectedTotals($cartItems);
        $serviceFee = $subtotalRentals > 0 ? 25000 : 0;
        $totalPayable = max(0, $subtotalRentals + $serviceFee);

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'item' => $cart[$id],
                'formattedItemSubtotal' => 'Rp ' . number_format($cart[$id]['subtotal'], 0, ',', '.'),
                'subtotalRentals' => $subtotalRentals,
                'formattedSubtotalRentals' => 'Rp ' . number_format($subtotalRentals, 0, ',', '.'),
                'serviceFee' => $serviceFee,
                'formattedServiceFee' => 'Rp ' . number_format($serviceFee, 0, ',', '.'),
                'totalPayable' => $totalPayable,
                'formattedTotalPayable' => 'Rp' . number_format($totalPayable, 0, ',', '.'),
                'selectedCount' => $selectedCount,
            ]);
        }

        return redirect()->route('cart')->with('status', 'Keranjang berhasil diperbarui.');
    }

    /**
     * Update status pemilihan barang (checkbox) di keranjang.
     *
     * Mode "item": ubah status selected pada satu item ('id' + 'checked').
     * Mode "select_all": ubah status selected pada SEMUA item ('all' + 'checked').
     */
    public function select(Request $request): JsonResponse|RedirectResponse
    {
        if ($this->isSuspendedUser($request)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Akun Anda ditangguhkan.'], 403);
            }
            return $this->handleSuspendedUser($request);
        }

        if ($this->isConsentPending($request)) {
            return $this->consentBlock($request);
        }

        $cart = session()->get('cart_items', []);
        $checked = $request->boolean('checked');

        if ($request->boolean('all')) {
            foreach ($cart as &$item) {
                $item['selected'] = $checked;
            }
            unset($item);
        } else {
            $id = $request->input('id');
            if ($id !== null && array_key_exists($id, $cart)) {
                $cart[$id]['selected'] = $checked;
            } else {
                return $this->cartError($request, 'Item tidak ditemukan di keranjang.');
            }
        }

        session(['cart_items' => $cart]);

        $cartItems = array_values($cart);
        [$subtotalRentals, $selectedCount] = $this->selectedTotals($cartItems);
        $serviceFee = $subtotalRentals > 0 ? 25000 : 0;
        $totalPayable = max(0, $subtotalRentals + $serviceFee);

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'selectedCount' => $selectedCount,
                'totalItems' => count($cartItems),
                'subtotalRentals' => $subtotalRentals,
                'formattedSubtotalRentals' => 'Rp ' . number_format($subtotalRentals, 0, ',', '.'),
                'serviceFee' => $serviceFee,
                'formattedServiceFee' => 'Rp ' . number_format($serviceFee, 0, ',', '.'),
                'totalPayable' => $totalPayable,
                'formattedTotalPayable' => 'Rp' . number_format($totalPayable, 0, ',', '.'),
            ]);
        }

        return redirect()->route('cart');
    }

    /**
     * Hitung subtotal & jumlah barang yang DIPILIH (checkbox aktif).
     * Barang yang tidak dipilih tidak ikut dihitung.
     *
     * @return array{0:int, 1:int} [subtotalRentals, selectedCount]
     */
    private function selectedTotals(array $cartItems): array
    {
        $subtotal = 0;
        $count = 0;
        foreach ($cartItems as $item) {
            // Item tanpa flag 'selected' dianggap DIPILIH (backward compatible).
            if (! array_key_exists('selected', $item) || ! empty($item['selected'])) {
                $subtotal += (int) ($item['subtotal'] ?? 0);
                $count++;
            }
        }
        return [$subtotal, $count];
    }

    /**
     * Hapus Item dari Keranjang Sewa
     */
    public function remove(Request $request, string|int $id): RedirectResponse
    {
        $cart = session()->get('cart_items', []);

        if (isset($cart[$id])) {
            $name = $cart[$id]['name'];
            unset($cart[$id]);
            session(['cart_items' => $cart]);
            return redirect()->route('cart')->with('status', "Item {$name} berhasil dihapus dari keranjang.");
        }

        return redirect()->route('cart')->with('status', 'Item berhasil dihapus.');
    }

    /**
     * Kosongkan Keranjang Sewa
     */
    public function clear(Request $request): RedirectResponse
    {
        session()->forget('cart_items');
        return redirect()->route('cart')->with('status', 'Keranjang berhasil dikosongkan.');
    }

    private function isSuspendedUser(Request $request): bool
    {
        if ($request->session()->has('account_id') && $request->session()->get('account_role') === 'customer') {
            $user = User::find($request->session()->get('account_id'));
            return $user && ($user->status === 'suspended' || $user->status === 'inactive');
        }
        return false;
    }

    private function handleSuspendedUser(Request $request): RedirectResponse
    {
        $request->session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar', 'cart_items']);
        return redirect()->route('login')->withErrors(['email' => 'Akun Anda telah ditangguhkan (SUSPENDED). Silakan hubungi Administrator.']);
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
     * Blokir aksi penyewaan untuk user di bawah umur yang persetujuannya
     * belum disetujui admin. Response JSON untuk panggilan AJAX, redirect
     * kembali untuk form biasa.
     */
    private function consentBlock(Request $request): JsonResponse|RedirectResponse
    {
        $message = 'Persetujuan orang tua Anda belum diverifikasi admin. Anda belum dapat melakukan penyewaan alat.';

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        return back()->withErrors(['error' => $message]);
    }

    /**
     * Bangun response error seragam: JSON untuk panggilan AJAX, redirect untuk form biasa.
     */
    private function cartError(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return redirect()->route('cart')->withErrors(['error' => $message]);
    }
}
