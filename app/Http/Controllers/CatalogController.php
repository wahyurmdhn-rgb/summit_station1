<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Tampilan Katalog Produk & Paket Sewa
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', 'popular');
        $selectedCategories = (array) $request->query('category', []);
        $availability = (string) $request->query('availability', 'all');
        $maxPrice = $request->query('max_price') ? (int) $request->query('max_price') : null;

        // Query Bundles — tampilkan Paket Sewa dari database (aktif maupun non-aktif)
        // agar tidak ada paket yang hilang dari katalog. Ketersediaan (stok) ditentukan
        // dari data aktual via Bundle::availableStock().
        $bundles = Bundle::with('products.category')->get();

        // Terapkan filter pencarian, kategori, ketersediaan, dan harga yang sama seperti
        // produk, sehingga paket sewa ikut tersaring (tidak hilang saat pencarian/filter).
        if (! empty($search) || ! empty($selectedCategories) || $availability === 'available' || ($maxPrice && $maxPrice > 0)) {
            $searchLower = strtolower($search);
            $bundles = $bundles->filter(function ($bundle) use ($search, $searchLower, $selectedCategories, $availability, $maxPrice) {
                $stock = $bundle->availableStock();

                if (! empty($search)) {
                    $matches = str_contains(strtolower($bundle->name), $searchLower)
                        || str_contains(strtolower((string) $bundle->description), $searchLower);
                    if (! $matches) {
                        $matches = $bundle->products->contains(function ($p) use ($searchLower) {
                            return str_contains(strtolower($p->name), $searchLower)
                                || str_contains(strtolower((string) $p->description), $searchLower)
                                || str_contains(strtolower((string) $p->subtitle), $searchLower)
                                || str_contains(strtolower((string) $p->sku), $searchLower);
                        });
                    }
                    if (! $matches) {
                        return false;
                    }
                }

                if (! empty($selectedCategories)) {
                    $memberCat = $bundle->products->contains(function ($p) use ($selectedCategories) {
                        $c = $p->category;
                        return $c && (
                            in_array($c->slug, $selectedCategories, true)
                            || in_array($c->id, $selectedCategories, true)
                            || in_array($c->name, $selectedCategories, true)
                        );
                    });
                    if (! $memberCat) {
                        return false;
                    }
                }

                if ($availability === 'available' && $stock <= 0) {
                    return false;
                }

                if ($maxPrice && $maxPrice > 0 && (int) $bundle->price > $maxPrice) {
                    return false;
                }

                return true;
            });
        }

        $bundles = $bundles->map(function ($bundle) {
            $stock = $bundle->availableStock();
            $bundle->stock_available = $stock;
            $bundle->in_stock = $stock > 0;
            return $bundle;
        })->values();

        // Query Products
        $query = Product::with('category')->where('is_active', true);

        // Search Filter
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subtitle', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category Filter
        if (! empty($selectedCategories)) {
            $query->whereHas('category', function ($q) use ($selectedCategories) {
                $q->whereIn('slug', $selectedCategories)
                  ->orWhereIn('id', $selectedCategories)
                  ->orWhereIn('name', $selectedCategories);
            });
        }

        // Availability Filter
        if ($availability === 'available') {
            $query->where('stock_available', '>', 0);
        }

        // Price Filter
        if ($maxPrice && $maxPrice > 0) {
            $query->where('price_per_day', '<=', $maxPrice);
        }

        // Sorting
        match ($sort) {
            'price_low'  => $query->orderBy('price_per_day', 'asc'),
            'price_high' => $query->orderBy('price_per_day', 'desc'),
            'newest'     => $query->orderBy('created_at', 'desc'),
            'rating'     => $query->orderBy('rating', 'desc'),
            default      => $query->orderBy('stock_total', 'desc')->orderBy('rating', 'desc'),
        };

        // Paginate items
        $paginatedProducts = $query->paginate(12)->withQueryString();

        $items = $paginatedProducts->through(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category?->name ?? 'Equipment',
                'category_slug' => $p->category?->slug ?? '',
                'rating' => (string) ($p->rating ?? '4.9'),
                'price' => (int) $p->price_per_day,
                'stock_available' => (int) $p->stock_available,
                'in_stock' => (int) $p->stock_available > 0,
                'image' => $p->main_image ?: asset('images/placeholder.svg'),
            ];
        });

        $categories = Category::withCount('products')->get();

        return view('products.catalog', compact(
            'bundles',
            'items',
            'paginatedProducts',
            'categories',
            'selectedCategories',
            'search',
            'sort',
            'availability',
            'maxPrice'
        ));
    }

    /**
     * Detail Produk Alat (Individual Product)
     */
    public function show(string|int $id): View
    {
        $dbProduct = Product::with(['category', 'images', 'reviews.user'])->find($id);

        if (! $dbProduct) {
            return view('products.not_found', [
                'searchedId' => $id,
                'isBundle' => false,
            ]);
        }

        // Produk nonaktif tidak boleh ditampilkan/dibeli lagi lewat direct URL.
        if (! $dbProduct->is_active) {
            return view('products.not_found', [
                'searchedId' => $id,
                'isBundle' => false,
            ]);
        }

        $placeholder = asset('images/placeholder.svg');

        $thumbnails = $dbProduct->images->pluck('url')->filter()->values()->toArray();
        if (empty($thumbnails)) {
            $thumbnails = [$dbProduct->main_image ?: $placeholder];
        }

        $rawSpecs = is_array($dbProduct->specs) ? $dbProduct->specs : [];
        $weightVal = $dbProduct->weight ?: ($rawSpecs['BERAT'] ?? $rawSpecs['berat'] ?? '3.2 kg');
        $capacityVal = $dbProduct->capacity ?: ($rawSpecs['KAPASITAS'] ?? $rawSpecs['kapasitas'] ?? '2-4 Orang');
        $conditionVal = $dbProduct->condition ?: ($rawSpecs['KONDISI'] ?? $rawSpecs['kondisi'] ?? 'Excellent');
        $gradeVal = $dbProduct->grade ?: ($rawSpecs['GRADE'] ?? $rawSpecs['grade'] ?? 'PRO-GRADE');

        $specs = [
            'BERAT' => $weightVal,
            'KAPASITAS' => $capacityVal,
            'KONDISI' => $conditionVal,
            'GRADE' => $gradeVal,
        ];

        foreach ($rawSpecs as $rk => $rv) {
            $uk = strtoupper($rk);
            if (!in_array($uk, ['BERAT', 'KAPASITAS', 'KONDISI', 'GRADE'])) {
                $specs[$rk] = $rv;
            }
        }

        $features = $this->normalizeFeatures($dbProduct->features);

        // Default feature highlights bila produk belum punya data features
        // sendiri, agar section keunggulan tetap tampil di halaman detail.
        if (empty($features)) {
            $features = [
                [
                    'title' => 'Weather Resistance',
                    'icon' => 'droplet',
                    'desc' => 'Material tahan cuaca ekstrem menjaga perlengkapan tetap kering dan siap dipakai di segala medan.',
                ],
                [
                    'title' => 'Structural Integrity',
                    'icon' => 'tent',
                    'desc' => 'Rangka kokoh dan stabil memberikan perlindungan maksimal saat kondisi lapangan berubah cepat.',
                ],
                [
                    'title' => 'Active Ventilation',
                    'icon' => 'wind',
                    'desc' => 'Sirkulasi udara aktif mengurangi kelembapan dan menjaga kenyamanan selama ekspedisi.',
                ],
            ];
        }

        $isSuspended = $this->checkIsSuspended();
        $isConsentPending = $this->checkIsConsentPending();

        $productReviews = collect();
        $realReviewsCount = 0;
        $realRating = $dbProduct->rating ? (string) $dbProduct->rating : '5.0';

        try {
            $productReviews = $dbProduct->reviews()->visible()->with('user')->latest()->get();
            $realReviewsCount = $productReviews->count();
            if ($realReviewsCount > 0) {
                $realRating = number_format((float) $productReviews->avg('rating'), 1);
            }
        } catch (\Throwable $e) {
            \App\Support\ErrorReporter::soft($e, 'CatalogController::show product reviews', ['product_id' => $dbProduct->id]);

            try {
                $productReviews = $dbProduct->reviews()->with('user')->latest()->get();
                $realReviewsCount = $productReviews->count();
                if ($realReviewsCount > 0) {
                    $realRating = number_format((float) $productReviews->avg('rating'), 1);
                }
            } catch (\Throwable $e2) {
                // Table or relationship issue
                \App\Support\ErrorReporter::soft($e2, 'CatalogController::show product reviews fallback', ['product_id' => $dbProduct->id]);
            }
        }

        $product = [
            'id' => $dbProduct->id,
            'is_bundle' => false,
            'name' => $dbProduct->name,
            'subtitle' => $dbProduct->subtitle ?? ($dbProduct->category?->name . ' • Kelas Profesional'),
            'category' => strtoupper($dbProduct->category?->name ?? 'EXPEDITION GEAR'),
            'rating' => (string) $realRating,
            'reviews_count' => (string) $realReviewsCount,
            'grade' => $gradeVal,
            'in_stock' => $dbProduct->stock_available > 0,
            'stock_available' => (int) $dbProduct->stock_available,
            'description' => $dbProduct->description ?? 'Peralatan standar ekspedisi profesional yang siap digunakan untuk berbagai medan petualangan alam bebas.',
            'specs' => $specs,
            'price' => (int) $dbProduct->price_per_day,
            'main_image' => $dbProduct->main_image ?: $placeholder,
            'thumbnails' => $thumbnails,
            'features' => $features,
        ];

        return view('products.show', compact('product', 'isSuspended', 'isConsentPending', 'productReviews'));
    }

    /**
     * Detail Paket Sewa (Exclusive Bundle)
     */
    public function showBundle(string|int $id): View
    {
        $bundle = Bundle::with(['products.category', 'products.images'])->find($id);

        if (! $bundle) {
            return view('products.not_found', [
                'searchedId' => "Bundle #{$id}",
                'isBundle' => true,
            ]);
        }

        // Paket nonaktif tidak boleh ditampilkan/dibeli lagi lewat direct URL.
        if (! $bundle->is_active) {
            return view('products.not_found', [
                'searchedId' => "Bundle #{$id}",
                'isBundle' => true,
            ]);
        }

        $placeholder = asset('images/placeholder.svg');

        // Anggota paket yang sudah nonaktif disembunyikan dari detail paket
        // supaya user tidak melihat barang yang tidak bisa disewa.
        $activeProducts = $bundle->products->filter(fn ($p) => (bool) $p->is_active)->values();

        $thumbnails = [$bundle->image ?: $placeholder];
        foreach ($activeProducts as $p) {
            if ($p->main_image) {
                $thumbnails[] = $p->main_image;
            }
        }
        $thumbnails = array_values(array_unique($thumbnails));

        $specs = [
            'TIPE' => 'Paket Hemat Lengkap',
            'ITEM TERMASUK' => $activeProducts->count().' Jenis Alat',
            'KONDISI' => 'Tersanitasi Kelas Pro',
            'HEMAT' => 'Hemat hingga 30%',
        ];

        $features = [
            [
                'title' => 'Perlengkapan Terintegrasi',
                'icon' => 'tent',
                'desc' => 'Semua peralatan dalam paket ini dipilih secara cermat oleh pemandu ekspedisi untuk kecocokan maksimal di alam bebas.',
            ],
            [
                'title' => 'Inspeksi & Sanitasi Steril',
                'icon' => 'droplet',
                'desc' => 'Setiap tenda, kantung tidur, dan peralatan masak dibersihkan secara higienis sebelum diserahkan.',
            ],
            [
                'title' => 'Harga Paket Lebih Hemat',
                'icon' => 'wind',
                'desc' => 'Tarif sewa bundling lebih ekonomis dibanding menyewa masing-masing perlengkapan secara terpisah.',
            ],
        ];

        $bundleStock = $bundle->availableStock();

        // Rating & jumlah ulasan paket diambil langsung dari ulasan yang
        // terhubung ke bundel ini (bundle_id). Tidak lagi menscan produk
        // anggota sehingga ulasan tidak bocor antar bundel.
        $bundleReviews = collect();
        $bundleRating = '0.0';
        $bundleReviewsCount = 0;
        try {
            $bundleReviews = Review::visible()
                ->where('bundle_id', $bundle->id)
                ->with(['user', 'bundle'])
                ->latest()
                ->get();
            $bundleReviewsCount = $bundleReviews->count();
            if ($bundleReviewsCount > 0) {
                $bundleRating = number_format((float) $bundleReviews->avg('rating'), 1);
            }
        } catch (\Throwable $e) {
            \App\Support\ErrorReporter::soft($e, 'CatalogController::showBundle reviews', ['bundle_id' => $bundle->id]);

            $bundleReviews = collect();
            $bundleReviewsCount = 0;
            $bundleRating = '0.0';
        }

        $isSuspended = $this->checkIsSuspended();
        $isConsentPending = $this->checkIsConsentPending();

        $product = [
            'id' => $bundle->id,
            'is_bundle' => true,
            'name' => $bundle->name,
            'subtitle' => 'Paket Ekspedisi Eksklusif',
            'category' => 'PAKET SEWA / BUNDLE EKSKLUSIF',
            'rating' => $bundleRating,
            'reviews_count' => (string) $bundleReviewsCount,
            'grade' => 'EXCLUSIVE BUNDLE',
            'in_stock' => $bundleStock > 0,
            'stock_available' => $bundleStock,
            'description' => $bundle->description,
            'specs' => $specs,
            'price' => (int) $bundle->price,
            'main_image' => $bundle->image ?: $placeholder,
            'thumbnails' => $thumbnails,
            'features' => $features,
            'bundle_items' => $activeProducts,
        ];

        return view('products.show', [
            'product' => $product,
            'bundle' => $bundle,
            'isSuspended' => $isSuspended,
            'isConsentPending' => $isConsentPending,
            'productReviews' => $bundleReviews,
        ]);
    }

    /**
     * Samakan bentuk data `features` ke ['title' => ..., 'icon' => ..., 'desc' => ...].
     *
     * View detail produk accessing `$feat['title']` / `$feat['desc']` secara
     * langsung. Data lama di database bisa berupa list string biasa, atau
     * associative array dengan kunci lain — bentuk itu akan membuat halaman
     * error 500. Fungsi ini mempertahankan seluruh data yang bisa dibaca,
     * hanya melengkapinya yang kurang, dan membuang entri yang sama sekali
     * tidak punya teks.
     *
     * @param  mixed  $raw
     * @return array<int, array{title: string, icon: string, desc: string}>
     */
    private function normalizeFeatures($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [$raw];
        }

        if (! is_array($raw)) {
            return [];
        }

        // Bentuk asosiatif tunggal: ['title' => '...', 'desc' => '...']
        if (isset($raw['title']) || isset($raw['desc'])) {
            $raw = [$raw];
        }

        $normalized = [];

        foreach ($raw as $entry) {
            if (is_string($entry)) {
                $title = trim($entry);
                $desc = '';
                $icon = 'tent';
            } elseif (is_array($entry)) {
                $title = trim((string) ($entry['title'] ?? $entry['name'] ?? $entry['label'] ?? ''));
                $desc = trim((string) ($entry['desc'] ?? $entry['description'] ?? ''));
                $icon = (string) ($entry['icon'] ?? 'tent');
            } else {
                continue;
            }

            if ($title === '' && $desc === '') {
                continue;
            }

            $normalized[] = [
                'title' => $title !== '' ? $title : 'Keunggulan',
                'icon' => $icon !== '' ? $icon : 'tent',
                'desc' => $desc,
            ];
        }

        return $normalized;
    }

    private function checkIsSuspended(): bool
    {
        if (session('account_id') && session('account_role') === 'customer') {
            $sessionUser = User::find(session('account_id'));
            return $sessionUser && ($sessionUser->status === 'suspended' || $sessionUser->status === 'inactive');
        }
        return false;
    }

    private function checkIsConsentPending(): bool
    {
        if (session('account_id') && session('account_role') === 'customer') {
            $sessionUser = User::find(session('account_id'));
            return $sessionUser && $sessionUser->is_consent_pending;
        }
        return false;
    }
}
