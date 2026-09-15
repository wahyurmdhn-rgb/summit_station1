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
                'image' => $p->main_image ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=600&q=80',
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

        $thumbnails = $dbProduct->images->pluck('url')->filter()->values()->toArray();
        if (empty($thumbnails)) {
            $thumbnails = [$dbProduct->main_image ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1000&q=80'];
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

        $features = is_array($dbProduct->features) ? $dbProduct->features : [];

        $features = collect($features)->filter(fn ($feature) => ! in_array(strtolower(trim((string) ($feature['title'] ?? ''))), [
            'ketahanan cuaca',
            'integritas struktural',
            'ventilasi aktif',
        ], true))->values()->all();

        $isSuspended = $this->checkIsSuspended();

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
            try {
                $productReviews = $dbProduct->reviews()->with('user')->latest()->get();
                $realReviewsCount = $productReviews->count();
                if ($realReviewsCount > 0) {
                    $realRating = number_format((float) $productReviews->avg('rating'), 1);
                }
            } catch (\Throwable $e2) {
                // Table or relationship issue
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
            'main_image' => $dbProduct->main_image ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1000&q=80',
            'thumbnails' => $thumbnails,
            'features' => $features,
        ];

        return view('products.show', compact('product', 'isSuspended', 'productReviews'));
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

        $thumbnails = [$bundle->image ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1000&q=80'];
        foreach ($bundle->products as $p) {
            if ($p->main_image) {
                $thumbnails[] = $p->main_image;
            }
        }
        $thumbnails = array_values(array_unique($thumbnails));

        $specs = [
            'TIPE' => 'Paket Hemat Lengkap',
            'ITEM TERMASUK' => $bundle->products->count() . ' Jenis Alat',
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
            $bundleReviews = collect();
            $bundleReviewsCount = 0;
            $bundleRating = '0.0';
        }

        $isSuspended = $this->checkIsSuspended();

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
            'main_image' => $bundle->image ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1000&q=80',
            'thumbnails' => $thumbnails,
            'features' => $features,
            'bundle_items' => $bundle->products,
        ];

        return view('products.show', [
            'product' => $product,
            'bundle' => $bundle,
            'isSuspended' => $isSuspended,
            'productReviews' => $bundleReviews,
        ]);
    }

    private function checkIsSuspended(): bool
    {
        if (session('account_id') && session('account_role') === 'customer') {
            $sessionUser = User::find(session('account_id'));
            return $sessionUser && ($sessionUser->status === 'suspended' || $sessionUser->status === 'inactive');
        }
        return false;
    }
}
