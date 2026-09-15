<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Simpan rating dan review dari customer untuk pesanan yang telah selesai.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $userId = $request->session()->get('account_id');
        $userRole = $request->session()->get('account_role');

        if (! $userId || $userRole !== 'customer') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan masuk sebagai pelanggan untuk memberikan ulasan.',
                ], 401);
            }
            return redirect()->route('login')->withErrors(['email' => 'Silakan masuk untuk memberikan ulasan.']);
        }

        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'rating.required' => 'Silakan pilih jumlah bintang rating (1-5).',
            'rating.min' => 'Rating minimal adalah 1 bintang.',
            'rating.max' => 'Rating maksimal adalah 5 bintang.',
            'comment.required' => 'Silakan tulis ulasan atau pengalaman Anda.',
            'comment.min' => 'Ulasan minimal berisi 3 karakter.',
            'comment.max' => 'Ulasan maksimal berisi 1000 karakter.',
        ]);

        // 1. Validasi Kepemilikan Order & User Otorisasi
        $order = Order::with('items')->where('id', $data['order_id'])->where('user_id', $userId)->first();

        if (! $order) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan tidak ditemukan atau Anda tidak memiliki akses.',
                ], 403);
            }
            return back()->withErrors(['review' => 'Pesanan tidak ditemukan atau Anda tidak memiliki akses.']);
        }

        // 2. Validasi Status Penyewaan (Hanya Completed / Returned yang Boleh Di-rating)
        if (! in_array($order->status, ['completed', 'returned'])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rating hanya dapat diberikan setelah penyewaan selesai.',
                ], 422);
            }
            return back()->withErrors(['review' => 'Rating hanya dapat diberikan setelah penyewaan selesai.']);
        }

        // 3. Validasi Anti Rating Ganda (1 Pesanan = 1 Review)
        if ($order->review()->exists()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah memberikan ulasan untuk penyewaan ini.',
                ], 422);
            }
            return back()->withErrors(['review' => 'Anda sudah memberikan ulasan untuk penyewaan ini.']);
        }

        // 4. Hubungkan ke item yang diulas (produk satuan ATAU paket/bundle).
        //    Sumber paling reliable: relationship order -> item -> product/bundle.
        $target = $this->resolveTarget($data['product_id'] ?? null, $order);

        if (! $target['product_id'] && ! $target['bundle_id']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan ini tidak memiliki produk yang dapat diberi ulasan.',
                ], 422);
            }
            return back()->withErrors(['review' => 'Pesanan ini tidak memiliki produk yang dapat diberi ulasan.']);
        }

        $review = Review::create([
            'user_id' => $userId,
            'product_id' => $target['product_id'],
            'bundle_id' => $target['bundle_id'],
            'order_id' => $order->id,
            'rating' => (int) $data['rating'],
            'comment' => strip_tags(trim($data['comment'])),
            'is_visible' => true,
        ]);

        // 5. Update Rating Agregat Produk (khusus ulasan produk satuan).
        //    Ulasan paket dihitung dinamis pada halaman detail paket.
        if ($target['product_id']) {
            $product = Product::find($target['product_id']);
            if ($product) {
                $avg = Review::visible()->where('product_id', $target['product_id'])->avg('rating');
                $count = Review::visible()->where('product_id', $target['product_id'])->count();
                $product->update([
                    'rating' => $avg ? round((float) $avg, 1) : 5.0,
                    'reviews_count' => $count,
                ]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Terima kasih! Ulasan dan rating Anda berhasil dikirim.',
                'review' => $review->load(['user', 'product', 'bundle']),
            ]);
        }

        return back()->with('success', 'Terima kasih! Ulasan dan rating Anda berhasil dikirim.');
    }

    /**
     * Tentukan target ulasan: produk satuan (product_id) ATAU paket (bundle_id).
     *
     * Prioritas sumber data:
     * 1. product_id yang dikirim frontend (jika merujuk ke produk yang valid).
     * 2. relationship order -> order item -> product_id (produk satuan).
     * 3. relationship order -> order item -> bundle_id (paket sewa).
     *
     * Peringatan: nilai yang dikirim dari frontend TIDAK dipercaya secara buta.
     * Selalu divalidasi ulang terhadap database sebelum dipakai.
     *
     * @return array{product_id: int|null, bundle_id: int|null}
     */
    private function resolveTarget(mixed $requestedProductId, Order $order): array
    {
        // 1. Product dari frontend - hanya dipakai jika benar-benar ada
        //    DAN pesanan tersebut memang order produk satuan (bukan bundel).
        $firstItem = $order->items->first();

        $isBundleOrder = $firstItem && $firstItem->bundle_id && ! $firstItem->product_id;

        if (! $isBundleOrder && $requestedProductId && Product::find((int) $requestedProductId)) {
            return ['product_id' => (int) $requestedProductId, 'bundle_id' => null];
        }

        // 2. Produk satuan dari relationship order_item sudah tersedia.
        if ($firstItem && $firstItem->product_id) {
            return ['product_id' => (int) $firstItem->product_id, 'bundle_id' => null];
        }

        // 3. Paket sewa: gunakan bundle_id pada order item bila tersedia.
        if ($firstItem && $firstItem->bundle_id) {
            return ['product_id' => null, 'bundle_id' => (int) $firstItem->bundle_id];
        }

        // Data lama tidak menyimpan bundle_id; carikan bundle berdasarkan nama item.
        if ($firstItem && $firstItem->name) {
            $bundle = Bundle::where('name', $firstItem->name)->first();
            if ($bundle) {
                return ['product_id' => null, 'bundle_id' => (int) $bundle->id];
            }
        }

        return ['product_id' => null, 'bundle_id' => null];
    }

    /**
     * Admin: Toggle visibilitas review (Show / Hide di Home).
     */
    public function adminToggle(Request $request, int $id): RedirectResponse
    {
        $review = Review::findOrFail($id);
        $review->update([
            'is_visible' => ! $review->is_visible,
        ]);

        // Update agregat produk terkait
        if ($review->product_id) {
            $product = Product::find($review->product_id);
            if ($product) {
                $avg = Review::visible()->where('product_id', $product->id)->avg('rating');
                $count = Review::visible()->where('product_id', $product->id)->count();
                $product->update([
                    'rating' => $avg ? round((float) $avg, 1) : 5.0,
                    'reviews_count' => $count,
                ]);
            }
        }

        $statusText = $review->is_visible ? 'ditampilkan' : 'disembunyikan';
        return back()->with('success', "Ulasan berhasil {$statusText}.");
    }

    /**
     * Admin: Hapus review yang tidak pantas.
     */
    public function adminDestroy(Request $request, int $id): RedirectResponse
    {
        $review = Review::findOrFail($id);
        $productId = $review->product_id;
        $review->delete();

        if ($productId) {
            $product = Product::find($productId);
            if ($product) {
                $avg = Review::visible()->where('product_id', $productId)->avg('rating');
                $count = Review::visible()->where('product_id', $productId)->count();
                $product->update([
                    'rating' => $avg ? round((float) $avg, 1) : 5.0,
                    'reviews_count' => $count,
                ]);
            }
        }

        return back()->with('success', 'Ulasan berhasil dihapus.');
    }
}
