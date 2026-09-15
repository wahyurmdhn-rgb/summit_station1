<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\LatePenalty;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ReturnRecord;
use App\Models\Review;
use App\Models\User;
use App\Notifications\RefundStatusNotification;
use App\Services\RentalNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    /**
     * Tampilan Dashboard Admin Utama
     */
    public function index(): View
    {
        // ── Statistik Dashboard BERBASIS DATABASE (tanpa data dummy/hardcoded) ──
        // Sumber pendapatan = total pembayaran yang telah disetujui (success).
        $totalPendapatan = (int) Payment::where('status', 'success')->sum('amount');

        // Unit yang tersedia = stok aktual produk + unit paket yang dapat disewakan.
        $produkTersedia = (int) Product::sum('stock_available')
            + Bundle::with('products')->get()->sum(fn ($b) => $b->availableStock());

        // Penyewaan yang sedang berjalan (aktif / sudah dibayar).
        $sedangDisewa = (int) Order::whereIn('status', ['active', 'paid'])->count();

        // Total kapasitas unit (total stok) untuk menghitung persentase ketersediaan.
        $totalKapasitas = (int) Product::sum('stock_total')
            + Bundle::with('products')->get()->sum(fn ($b) => $b->maxStock());

        $stats = [
            'total_pendapatan' => $totalPendapatan,
            'total_produk' => (int) Product::count(),
            'total_paket' => (int) Bundle::count(),
            'total_pengguna' => (int) User::count(),
            'total_pemesanan' => (int) Order::count(),
            'produk_tersedia' => $produkTersedia,
            'kapasitas' => $totalKapasitas > 0 ? (int) round($produkTersedia / $totalKapasitas * 100) : 100,
            'sedang_disewa' => $sedangDisewa,
            'menunggu_proses' => (int) Order::where('status', 'pending')->count(),
        ];

        // ── Peralatan Populer: top 4 berdasarkan jumlah unit yang paling
        //    sering disewa (dari OrderItem), fallback ke produk aktif bila
        //    belum ada data penyewaan. Semua bersumber dari database.
        $popularGear = $this->dashboardPopularGear(4);

        $sidebarCounts = \App\Services\AdminNotificationService::sidebarBadgeCounts();
        $pendingRefundCount = $sidebarCounts['refund'] ?? 0;
        $pendingPengembalianCount = $sidebarCounts['pengembalian'] ?? 0;

        // ── Aktivitas terbaru (data nyata dari database) ──
        $recentOrders = Order::with('user:id,name,email')
            ->latest()
            ->limit(6)
            ->get(['id', 'code', 'user_id', 'total', 'status', 'created_at'])
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'code' => $order->code ?? 'PESANAN',
                'customer' => $order->user?->name ?: 'Pelanggan',
                'total' => (int) $order->total,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ]);

        $recentUsers = User::latest()
            ->limit(5)
            ->get(['id', 'name', 'email', 'domicile', 'created_at'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'initial' => mb_strtoupper(mb_substr(trim((string) $user->name), 0, 1)) ?: 'U',
                'created_at' => $user->created_at,
            ]);

        return view('admin.dashboard', compact(
            'stats',
            'popularGear',
            'pendingRefundCount',
            'pendingPengembalianCount',
            'recentOrders',
            'recentUsers'
        ));
    }

    /**
     * Bangun daftar "Peralatan Populer" untuk dashboard dari data nyata.
     * Prioritas: produk dengan total unit disewa terbanyak (OrderItem).
     * Jika belum ada penyewaan, gunakan produk aktif teratas sebagai pengisi.
     */
    private function dashboardPopularGear(int $limit = 4): array
    {
        // Top rented products (by sum of quantity in OrderItems).
        $topRented = OrderItem::select('product_id')
            ->selectRaw('SUM(quantity) as total_rented')
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('total_rented')
            ->limit($limit)
            ->pluck('product_id')
            ->all();

        $products = Product::with('category')->whereIn('id', $topRented)->get();

        // Fallback: bila tidak ada data penyewaan, ambil produk aktif teratas.
        if ($products->isEmpty()) {
            $products = Product::with('category')
                ->where('is_active', true)
                ->orderByDesc('stock_total')
                ->orderByDesc('rating')
                ->limit($limit)
                ->get();
        }

        $colorPool = ['green', 'brown', 'blue', 'dark'];
        $i = 0;
        $gear = [];

        foreach ($products as $product) {
            $total = max(1, (int) $product->stock_total ?: 1);
            $available = max(0, (int) $product->stock_available);
            $progress = (int) round(($available / $total) * 100);
            $progress = max(0, min(100, $progress));

            $gear[] = [
                'id' => (int) $product->id,
                'name' => $product->name,
                'category' => strtoupper($product->category?->name ?? 'GEAR'),
                'category_color' => in_array($product->category?->slug, ['expedition', 'technical', 'ultralight', 'navigation'], true)
                    ? $product->category->slug
                    : 'expedition',
                'stock' => $available,
                'rating' => (string) ($product->rating ?? '5.0'),
                'progress' => $progress,
                'progress_color' => $progress > 66 ? 'green' : ($progress > 33 ? 'brown' : 'dark'),
                'image' => $product->main_image ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=600&q=80',
            ];
            $i++;
        }

        // Pastikan selalu ada 4 kartu (isi sisa dengan warna tanpa data fiktif).
        while (count($gear) < $limit) {
            $gear[] = [
                'id' => null,
                'name' => 'Belum Ada Data',
                'category' => 'GEAR',
                'category_color' => 'expedition',
                'stock' => 0,
                'rating' => '0.0',
                'progress' => 0,
                'progress_color' => $colorPool[($i++) % count($colorPool)] ?? 'green',
                'image' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=600&q=80',
            ];
        }

        return $gear;
    }

    /**
     * Buka satu notifikasi admin: tandai sebagai dibaca lalu arahkan ke halaman terkait.
     * Hanya admin yang memiliki notifikasi tersebut yang dapat membukanya.
     */
    public function openNotification(string $id): RedirectResponse
    {
        if (session('account_role') !== 'admin' || ! session('account_id')) {
            abort(403);
        }

        $admin = Admin::find(session('account_id'));
        if (! $admin) {
            abort(403);
        }

        $notification = $admin->notifications()->whereKey($id)->first();
        if (! $notification) {
            abort(403);
        }

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $data = is_array($notification->data)
            ? $notification->data
            : (array) json_decode((string) $notification->data, true);

        $url = $data['url'] ?? '';

        // Amankan redirect: hanya terima URL relatif atau URL dengan host aplikasi yang sama.
        $baseUrl = rtrim((string) config('app.url'), '/');
        $safe = false;
        if ($url !== '') {
            if (str_starts_with($url, '/')) {
                $safe = true;
            } elseif (str_starts_with($url, $baseUrl . '/')) {
                $safe = true;
            }
        }

        return redirect($safe ? $url : route('admin.dashboard'));
    }

    /**
     * Tandai semua notifikasi admin sebagai dibaca.
     */
    public function markAllNotificationsRead(): RedirectResponse
    {
        if (session('account_role') === 'admin' && session('account_id')) {
            Admin::find(session('account_id'))?->notifications()
                ->whereNull('read_at')
                ->get()
                ->each->markAsRead();
        }

        return redirect()->back();
    }

    /**
     * Halaman Profil Admin — menampilkan data akun admin yang sedang login.
     */
    public function profile(): View
    {
        $admin = $this->currentAdmin();

        return view('admin.profile', compact('admin'));
    }

    /**
     * Simpan perubahan profil (nama & email) dari akun admin yang sedang login.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $admin = $this->currentAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('admin', 'email')->ignore($admin->getKey(), 'id_admin')],

        ]);

        $admin->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        // Sinkronkan nama di sesi agar navbar/header admin ikut memakai data terbaru.
        session(['account_name' => $admin->name]);

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    /**
     * Ubah password akun admin yang sedang login.
     * Selalu memverifikasi password lama sebelum menyimpan password baru.
     */
    public function updateAdminPassword(Request $request): RedirectResponse
    {
        $admin = $this->currentAdmin();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $admin->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        $admin->update([
            'password' => Hash::make($data['new_password']),
        ]);

        return back()->with('status', 'Password berhasil diperbarui.');
    }

    /**
     * Resolusi akun admin yang sedang login dari sesi.
     */
    private function currentAdmin(): Admin
    {
        if (session('account_role') !== 'admin' || ! session('account_id')) {
            abort(403);
        }

        $admin = Admin::find(session('account_id'));
        abort_if(! $admin, 403);

        return $admin;
    }

    /**
     * Halaman Riwayat Notifikasi Admin (semua / belum dibaca / sudah dibaca).
     * Mendukung pagination dan filter status baca.
     */
    public function notifications(Request $request): View
    {
        if (session('account_role') !== 'admin' || ! session('account_id')) {
            abort(403);
        }

        $admin = Admin::find(session('account_id'));
        if (! $admin) {
            abort(403);
        }

        $filter = $request->input('filter', 'all');

        $query = $admin->notifications();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate(15)->withQueryString();

        return view('admin.notifications', compact('notifications', 'filter'));
    }

    /**
     * Tampilan Halaman Alat (Kelola Produk)
     */
    public function alat(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $categorySlug = (string) $request->input('category', 'all');
        $sort = (string) $request->input('sort', 'terbaru');

        // ── Sumber data inventaris yang dapat disewakan ──
        // Peralatan Ekspedisi bersumber dari tabel `products`, Paket Sewa
        // dari tabel `bundles`. Keduanya digabung menjadi satu daftar kelola.
        $products = Product::with('category')->get();
        $bundles = Bundle::with('products')->get();

        $items = collect();

        foreach ($products as $p) {
            $stock = (int) $p->stock_available;
            $items->push([
                'id' => (int) $p->id,
                'type' => 'peralatan',
                'type_label' => 'Peralatan Ekspedisi',
                'name' => $p->name,
                'kode' => $p->sku,
                'category' => $p->category?->name ?? 'Gear',
                'category_slug' => $p->category?->slug ?? '',
                'price' => (int) $p->price_per_day,
                'stock' => $stock,
                'stock_total' => (int) $p->stock_total,
                'status' => $p->is_active ? ($stock > 0 ? 'aktif' : 'habis') : 'nonaktif',
                'is_active' => (bool) $p->is_active,
                'is_bundle' => false,
                'image' => $p->main_image ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=150&q=80',
                'model' => $p,
            ]);
        }

        $bundlePlaceholderImg = 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=150&q=80';
        foreach ($bundles as $b) {
            $stock = $b->availableStock();
            $items->push([
                'id' => (int) $b->id,
                'type' => 'paket',
                'type_label' => 'Paket Sewa',
                'name' => $b->name,
                'kode' => 'PKT-' . $b->id,
                'category' => 'Paket Sewa',
                'category_slug' => 'paket-sewa',
                'price' => (int) $b->price,
                'stock' => $stock,
                'stock_total' => $stock > 0 ? $stock : 0,
                'status' => $b->is_active ? ($stock > 0 ? 'aktif' : 'habis') : 'nonaktif',
                'is_active' => (bool) $b->is_active,
                'is_bundle' => true,
                'image' => $b->image ?: $bundlePlaceholderImg,
                'description' => $b->description,
                'members' => $b->products->map(fn ($p) => [
                    'id' => (int) $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'quantity' => (int) ($p->pivot->quantity ?? 1),
                ])->values()->all(),
            ]);
        }

        // Filter Pencarian (nama / kode)
        if ($search !== '') {
            $needle = $search;
            $items = $items->filter(function ($item) use ($needle) {
                return str_contains(mb_strtolower($item['name']), mb_strtolower($needle))
                    || str_contains(mb_strtolower($item['kode']), mb_strtolower($needle));
            });
        }

        // Filter Kategori / Tipe (termasuk 'paket-sewa')
        if ($categorySlug !== 'all' && $categorySlug !== '') {
            $items = $items->filter(fn ($item) => $item['category_slug'] === $categorySlug);
        }

        // Sorting
        $items = match ($sort) {
            'stok_asc'  => $items->sortBy('stock'),
            'stok_desc' => $items->sortByDesc('stock'),
            'harga_asc' => $items->sortBy('price'),
            'harga_desc'=> $items->sortByDesc('price'),
            'nama_asc'  => $items->sortBy('name', SORT_STRING | SORT_FLAG_CASE),
            'type'      => $items->sortBy(function ($i) { return [$i['type_label'], $i['category'], $i['name']]; }),
            default     => $items, // 'terbaru' -> urutan asli (produk, kemudian paket)
        };
        $items = $items->values();

        // Pagination manual (karena sumber gabungan dua tabel)
        $perPage = 10;
        $page = max(1, (int) $request->input('page', 1));
        $total = $items->count();
        $chunk = $items->slice(($page - 1) * $perPage, $perPage)->values();
        $products = new \Illuminate\Pagination\LengthAwarePaginator(
            $chunk,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $categories = Category::withCount('products')->orderBy('name')->get();

        // Statistik (dihitung dari database aktual, bukan hardcode)
        $totalSkus = $total;
        $lowStockCount = $items->filter(fn ($i) => $i['stock'] <= 5)->count();
        $categoriesCount = $categories->count() + (Bundle::count() > 0 ? 1 : 0);

        // Total Nilai Inventaris (peralatan + paket, per 30 hari)
        // Pakai $bundles yang sudah eager-load products untuk menghindari N+1.
        $rawTotalValue = Product::sum(\DB::raw('price_per_day * stock_total * 30'))
            + $bundles->sum(fn ($b) => $b->price * max(0, $b->availableStock()) * 30);
        $totalValue = $rawTotalValue > 0 ? $rawTotalValue : 0;

        return view('admin.alat', [
            'products' => $products,
            'categories' => $categories,
            'items' => $chunk,
            'totalItems' => $total,
            'totalSkus' => $totalSkus,
            'lowStockCount' => $lowStockCount,
            'categoriesCount' => $categoriesCount,
            'totalValue' => $totalValue,
            'productOptions' => Product::with('category')->orderBy('name')->get(['id', 'name', 'sku', 'category_id']),
            'selectedCategory' => $categorySlug,
            'selectedSort' => $sort,
            'searchTerm' => $search,
        ]);
    }

    /**
     * Ubah Status Aktif/Nonaktif Paket Sewa (Admin alat).
     */
    public function toggleStatusBundle(int $id): RedirectResponse
    {
        $bundle = Bundle::findOrFail($id);
        $bundle->is_active = ! $bundle->is_active;
        $bundle->save();

        $statusText = $bundle->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('admin.alat')->with('status', "Paket Sewa {$bundle->name} berhasil {$statusText}.");
    }

    /**
     * Hapus Paket Sewa (Admin alat). Relasi pivot ikut terhapus via cascade.
     */
    public function destroyBundle(int $id): RedirectResponse
    {
        $bundle = Bundle::findOrFail($id);
        $name = $bundle->name;
        $bundle->delete();

        return redirect()->route('admin.alat')->with('status', "Paket Sewa {$name} berhasil dihapus dari inventaris.");
    }

    /**
     * Perbarui Data Paket Sewa termasuk barang-barang anggotanya.
     * Stok paket TIDAK disimpan sebagai kolom; ia dihitung dari stok aktual
     * barang anggota (Bundle::availableStock). Mengubah jumlah/anggota paket
     * adalah cara yang benar untuk mengelola ketersediaan, sesuai arsitektur.
     */
    public function updateBundle(Request $request, int $id): RedirectResponse
    {
        $bundle = Bundle::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:bundles,name,' . $id],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:products,id'],
            'quantities' => ['nullable', 'array'],
            'quantities.*' => ['integer', 'min:1'],
        ]);

        $bundle->name = $data['name'];
        $bundle->description = $data['description'] ?? $bundle->description;
        $bundle->price = (int) $data['price'];
        $bundle->is_active = $request->has('is_active') ? (bool) $request->input('is_active') : false;

        // Gambar: jika kosong, pertahankan gambar lama; jika diisi, ganti.
        if (! empty($data['image'])) {
            $bundle->image = $data['image'];
        }

        $bundle->save();

        // Sinkronkan barang anggota (menggunakan quantity). Kunci sync = id produk,
        // sehingga satu barang tidak bisa didaftarkan dua kali dalam satu paket.
        $sync = [];
        foreach (array_unique(array_map('intval', $data['member_ids'] ?? [])) as $productId) {
            $sync[$productId] = ['quantity' => max(1, (int) ($data['quantities'][$productId] ?? 1))];
        }
        $bundle->products()->sync($sync);

        return redirect()->route('admin.alat')->with('status', "Paket Sewa {$bundle->name} berhasil diperbarui.");
    }

    /**
     * Tambah Paket Sewa Baru beserta barang-barang anggotanya.
     * Arsitektur: stok paket dihitung dari stok aktual barang anggota
     * (Bundle::availableStock), bukan disimpan sebagai kolom.
     */
    public function storeBundle(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:bundles,name'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:products,id'],
            'quantities' => ['nullable', 'array'],
            'quantities.*' => ['integer', 'min:1'],
        ]);

        $bundle = Bundle::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => (int) $data['price'],
            'image' => $data['image'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : false,
        ]);

        if ($bundle && ! empty($data['member_ids'])) {
            $sync = [];
            foreach (array_unique(array_map('intval', $data['member_ids'])) as $productId) {
                $sync[$productId] = ['quantity' => max(1, (int) ($data['quantities'][$productId] ?? 1))];
            }
            $bundle->products()->sync($sync);
        }

        return redirect()->route('admin.alat')->with('status', "Paket Sewa {$bundle->name} berhasil ditambahkan.");
    }

    /**
     * Tambah Alat / Produk Baru
     */
    public function storeAlat(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:32', 'unique:products,sku'],
            'category_id' => ['required', 'exists:categories,id'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_per_day' => ['required', 'numeric', 'min:0'],
            'stock_total' => ['required', 'integer', 'min:1'],
            'stock_available' => ['nullable', 'integer', 'min:0'],
            'main_image' => ['nullable', 'string'],
            'weight' => ['nullable', 'string', 'max:50'],
            'capacity' => ['nullable', 'string', 'max:100'],
            'condition' => ['nullable', 'string', 'max:50'],
            'grade' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! isset($data['stock_available'])) {
            $data['stock_available'] = $data['stock_total'];
        }

        if (empty($data['main_image'])) {
            $data['main_image'] = 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=600&q=80';
        }

        $data['condition'] = !empty($data['condition']) ? $data['condition'] : 'Excellent';
        $data['grade'] = !empty($data['grade']) ? $data['grade'] : 'PRO-GRADE';
        $data['weight'] = !empty($data['weight']) ? $data['weight'] : null;
        $data['capacity'] = !empty($data['capacity']) ? $data['capacity'] : null;

        $data['specs'] = [
            'BERAT' => $data['weight'] ?: '3.2 kg',
            'KAPASITAS' => $data['capacity'] ?: '2-4 Orang',
            'KONDISI' => $data['condition'],
            'GRADE' => $data['grade'],
        ];

        $data['is_active'] = $request->has('is_active') ? (bool)$request->is_active : true;

        Product::create($data);

        return redirect()->route('admin.alat')->with('status', 'Alat berhasil ditambahkan ke inventaris.');
    }

    /**
     * Perbarui Data Alat
     */
    public function updateAlat(Request $request, int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:32', 'unique:products,sku,' . $id],
            'category_id' => ['required', 'exists:categories,id'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_per_day' => ['required', 'numeric', 'min:0'],
            'stock_total' => ['required', 'integer', 'min:1'],
            'stock_available' => ['required', 'integer', 'min:0'],
            'main_image' => ['nullable', 'string'],
            'weight' => ['nullable', 'string', 'max:50'],
            'capacity' => ['nullable', 'string', 'max:100'],
            'condition' => ['nullable', 'string', 'max:50'],
            'grade' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['condition'] = !empty($data['condition']) ? $data['condition'] : ($product->condition ?: 'Excellent');
        $data['grade'] = !empty($data['grade']) ? $data['grade'] : ($product->grade ?: 'PRO-GRADE');
        $data['weight'] = !empty($data['weight']) ? $data['weight'] : null;
        $data['capacity'] = !empty($data['capacity']) ? $data['capacity'] : null;

        $existingSpecs = is_array($product->specs) ? $product->specs : [];
        $existingSpecs['BERAT'] = $data['weight'] ?: ($existingSpecs['BERAT'] ?? $existingSpecs['berat'] ?? '3.2 kg');
        $existingSpecs['KAPASITAS'] = $data['capacity'] ?: ($existingSpecs['KAPASITAS'] ?? $existingSpecs['kapasitas'] ?? '2-4 Orang');
        $existingSpecs['KONDISI'] = $data['condition'];
        $existingSpecs['GRADE'] = $data['grade'];
        $data['specs'] = $existingSpecs;

        $data['is_active'] = $request->has('is_active') ? (bool)$request->is_active : false;

        $product->update($data);

        return redirect()->route('admin.alat')->with('status', 'Data alat ' . $product->name . ' berhasil diperbarui.');
    }

    /**
     * Ubah Status Aktif/Nonaktif Alat
     */
    public function toggleStatusAlat(int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $product->is_active = ! $product->is_active;
        $product->save();

        $statusText = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('admin.alat')->with('status', "Alat {$product->name} berhasil {$statusText}.");
    }

    /**
     * Hapus Alat
     */
    public function destroyAlat(int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $name = $product->name;
        $product->images()->delete();
        $product->delete();

        return redirect()->route('admin.alat')->with('status', "Alat {$name} berhasil dihapus dari inventaris.");
    }

    /**
     * Tambah Kategori Produk Baru
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        $category = Category::create([
            'name' => trim($data['name']),
            'slug' => $this->uniqueCategorySlug(Str::slug(trim($data['name']))),
        ]);

        return redirect()->route('admin.alat')->with('status', "Kategori '{$category->name}' berhasil ditambahkan dan siap digunakan pada Alat/Produk.");
    }

    /**
     * Perbarui Nama Kategori Produk (slug ikut disesuaikan)
     */
    public function updateCategory(Request $request, int $id): RedirectResponse
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $id],
        ]);

        $category->name = trim($data['name']);
        $category->slug = $this->uniqueCategorySlug(Str::slug(trim($data['name'])), $category->id);
        $category->save();

        return redirect()->route('admin.alat')->with('status', "Kategori '{$category->name}' berhasil diperbarui.");
    }

    /**
     * Hapus Kategori. Ditolak bila masih dipakai oleh alat/produk apa pun,
     * karena requirements mencegah penghapusan kategori yang masih digunakan.
     */
    public function destroyCategory(int $id): RedirectResponse
    {
        $category = Category::findOrFail($id);

        $inUse = Product::where('category_id', $category->id)->exists();

        if ($inUse) {
            return redirect()->route('admin.alat')
                ->withErrors(['category' => "Kategori '{$category->name}' tidak dapat dihapus karena masih digunakan oleh alat/produk."]);
        }

        $name = $category->name;
        $category->delete();

        return redirect()->route('admin.alat')->with('status', "Kategori '{$name}' berhasil dihapus.");
    }

    /**
     * Buat slug kategoris yang unik (bila sudah ada, tambahkan -2, -3, dst).
     */
    private function uniqueCategorySlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base ?: 'kategori';
        $candidate = $slug;
        $i = 2;

        while (Category::where('slug', $candidate)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $candidate = $slug . '-' . $i;
            $i++;
        }

        return $candidate;
    }

    /**
     * Tampilan Halaman Penyewaan (Rental Inventory Management)
     */
    public function penyewaan(Request $request): View
    {
        $query = Order::with(['user', 'items.product', 'payments']);

        // Filter Tab: All, Active, Past
        $statusFilter = $request->input('filter', 'all');
        if ($statusFilter === 'active') {
            $query->whereIn('status', ['active', 'paid']);
        } elseif ($statusFilter === 'past') {
            $query->whereIn('status', ['completed', 'cancelled']);
        } elseif ($statusFilter === 'pending') {
            $query->where('status', 'pending');
        }

        // Pencarian
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('items', function ($iq) use ($search) {
                      $iq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        // 4 Dynamic Stat Cards (semua dari database — tanpa dummy fallback)
        $activeRentals = (int) Order::whereIn('status', ['active', 'paid'])->count();

        $pendingRequests = (int) Order::where('status', 'pending')->count();

        $expectedReturns = (int) Order::whereIn('status', ['active', 'paid'])
            ->whereDate('rent_end', '<=', now()->addDays(2))
            ->count();

        $rawForecast = (int) Order::whereIn('status', ['active', 'completed', 'paid'])->sum('total');
        if ($rawForecast >= 1000000) {
            $revenueForecast = 'Rp ' . round($rawForecast / 1000000, 1) . 'M';
        } elseif ($rawForecast >= 1000) {
            $revenueForecast = 'Rp ' . round($rawForecast / 1000) . 'K';
        } else {
            $revenueForecast = 'Rp ' . number_format($rawForecast, 0, ',', '.');
        }

        return view('admin.penyewaan', [
            'orders' => $orders,
            'activeRentals' => $activeRentals,
            'pendingRequests' => $pendingRequests,
            'expectedReturns' => $expectedReturns,
            'revenueForecast' => $revenueForecast,
            'currentFilter' => $statusFilter,
            'searchTerm' => $request->input('search', ''),
        ]);
    }

    /**
     * Kurangi stok persediaan (products) dan anggota Paket Sewa (bundles)
     * untuk sebuah pesanan. Stok hanya dipakai SATU KALI ketika pesanan
     * mencapai status yang "mengambil" stok (active).
     *
     * Idempoten: hanya memiliki efek bila barang benar-benar tersedia,
     * sehingga aman dipanggil dari jalur admin mana pun (approve payment
     * maupun konfirmasi penyewaan) tanpa double decrement.
     */
    private function decrementOrderStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product && $item->product->stock_available >= $item->quantity) {
                $item->product->decrement('stock_available', $item->quantity);
            }
            // Anggota Paket Sewa ikut berkurang.
            if ($item->bundle) {
                foreach ($item->bundle->products as $bundleProduct) {
                    $needed = ($bundleProduct->pivot->quantity ?? 1) * ($item->quantity ?? 1);
                    if ($bundleProduct->stock_available >= $needed) {
                        $bundleProduct->decrement('stock_available', $needed);
                    }
                }
            }
        }
    }

    /**
     * Kembalikan stok persediaan (products) dan anggota Paket Sewa (bundles)
     * yang telah dipakai, ketika pesanan meninggalkan status active
     * (selesai / dibatalkan / pengembalian selesai).
     *
     * $restoreUnlessMajorDamaged: bila true (alur pengembalian), produk yang
     * terkena major_damage tidak dikembalikan stoknya karena butuh perawatan.
     */
    private function incrementOrderStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->increment('stock_available', $item->quantity ?? 1);
            }
            if ($item->bundle) {
                foreach ($item->bundle->products as $bundleProduct) {
                    $needed = ($bundleProduct->pivot->quantity ?? 1) * ($item->quantity ?? 1);
                    $bundleProduct->increment('stock_available', $needed);
                }
            }
        }
    }

    /**
     * Konfirmasi Penyewaan (Ubah status pending -> active).
     * Notifikasi pengguna dibuat dalam satu transaksi yang sama dengan
     * perubahan status agar konsisten; hanya setelah status tersimpan
     * dengan sukses user pemilik booking diberitahu.
     */
    public function confirmPenyewaan(int $id): RedirectResponse
    {
        $order = DB::transaction(function () use ($id) {
            $order = Order::with(['items.product', 'items.bundle.products', 'user'])->findOrFail($id);
            $wasActive = $order->status === 'active';
            $order->status = 'active';
            $order->paid_at = now();
            $order->save();

            // Stok hanya berkurang SATU KALI, tepat saat memasuki status 'active'
            // (dari pending). Jika pesanan sudah active, jangan kurangi lagi
            // agar tidak terjadi double decrement.
            if (! $wasActive) {
                $this->decrementOrderStock($order);
            }

            RentalNotificationService::notifyAccepted($order);

            return $order;
        });

        return redirect()->route('admin.penyewaan')->with('status', "Penyewaan #{$order->code} berhasil dikonfirmasi dan status kini Active.");
    }

    /**
     * Tolak Penyewaan (Ubah status pending -> cancelled).
     * Notifikasi penolakan (beserta alasan bila ada) dibuat dalam satu
     * transaksi dengan perubahan status agar konsisten.
     */
    public function rejectPenyewaan(Request $request, int $id): RedirectResponse
    {
        $order = DB::transaction(function () use ($request, $id) {
            $order = Order::with(['items.product', 'items.bundle.products'])->findOrFail($id);
            $wasActive = $order->status === 'active';
            $order->status = 'cancelled';
            if ($reason = $request->input('reason')) {
                $order->notes = ($order->notes ? $order->notes . ' | ' : '') . "Alasan penolakan: " . $reason;
            }
            $order->save();

            // Jika pesanan yang terbatal sempat berstatus active, kembalikan stok
            // (produk + anggota paket) yang telah dipakainya.
            if ($wasActive) {
                $this->incrementOrderStock($order);
            }

            RentalNotificationService::notifyRejected($order, $request->input('reason'));

            return $order;
        });

        return redirect()->route('admin.penyewaan')->with('status', "Penyewaan #{$order->code} telah ditolak.");
    }

    /**
     * Selesaikan Penyewaan (Ubah status active -> completed)
     */
    public function completePenyewaan(int $id): RedirectResponse
    {
        $order = Order::with(['items.product', 'items.bundle.products'])->findOrFail($id);

        // Hanya notifikasi bila pesanan benar-benar berpindah active -> completed
        // (idempoten: menyelesaikan ulang tidak mengirim notifikasi ganda).
        $wasActive = $order->status === 'active';

        $order = DB::transaction(function () use ($order, $wasActive) {
            $order->status = 'completed';
            $order->save();

            // Kembalikan stok (produk + anggota paket) HANYA ketika pesanan benar-benar
            // masih berstatus active sebelumnya, agar tidak double increment.
            if ($wasActive) {
                $this->incrementOrderStock($order);
            }

            return $order;
        });

        // Notifikasi ke user pemilik booking: penyewaan telah diselesaikan.
        if ($wasActive) {
            RentalNotificationService::notifyRentalCompleted($order);
        }

        return redirect()->route('admin.penyewaan')->with('status', "Penyewaan #{$order->code} berhasil diselesaikan. Stok alat telah diperbarui.");
    }

    /**
     * Export Data Penyewaan ke CSV
     */
    public function exportCsvPenyewaan(Request $request): StreamedResponse
    {
        $filter = $request->input('filter', 'all');
        $query = Order::with(['user', 'items.product', 'payments']);

        if ($filter === 'active') {
            $query->whereIn('status', ['active', 'paid']);
        } elseif ($filter === 'past') {
            $query->whereIn('status', ['completed', 'cancelled']);
        } elseif ($filter === 'pending') {
            $query->where('status', 'pending');
        }

        $orders = $query->latest()->get();

        $filename = "penyewaan_export_" . date('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Kode Pesanan',
                'Nama Penyewa',
                'Email',
                'Produk / Alat',
                'Lama Sewa (Hari)',
                'Tanggal Mulai',
                'Tanggal Selesai',
                'Total Biaya (Rp)',
                'Status',
                'Metode Pembayaran',
                'Tanggal Transaksi',
            ]);

            foreach ($orders as $order) {
                $itemNames = $order->items->pluck('name')->join(', ');
                $paymentMethod = $order->latestPayment()?->method ?? 'N/A';
                $days = $order->rent_start && $order->rent_end ? ($order->rent_start->diffInDays($order->rent_end) + 1) : 1;

                fputcsv($file, [
                    $order->code,
                    $order->user?->name ?? 'Guest',
                    $order->user?->email ?? '-',
                    $itemNames ?: 'Peralatan Outdoor',
                    $days,
                    $order->rent_start ? $order->rent_start->format('d/m/Y') : '-',
                    $order->rent_end ? $order->rent_end->format('d/m/Y') : '-',
                    $order->total,
                    strtoupper($order->status),
                    strtoupper($paymentMethod),
                    $order->created_at->format('d/m/Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Tampilan Halaman Pembayaran (Financial Audit Log)
     */
    public function pembayaran(Request $request): View
    {
        $query = Payment::with(['order.user', 'order.items.product']);

        // Filter Status: all, pending, confirmed (success), rejected (failed)
        $statusFilter = $request->input('filter', 'all');
        if ($statusFilter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($statusFilter === 'confirmed' || $statusFilter === 'success') {
            $query->where('status', 'success');
        } elseif ($statusFilter === 'rejected' || $statusFilter === 'failed') {
            $query->whereIn('status', ['failed', 'refunded']);
        }

        // Filter Metode Pembayaran
        if ($methodFilter = $request->input('method')) {
            if ($methodFilter !== 'all') {
                $query->where('method', $methodFilter);
            }
        }

        // Pencarian
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhere('method', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($oq) use ($search) {
                      $oq->where('code', 'like', "%{$search}%")
                         ->orWhereHas('user', function ($uq) use ($search) {
                             $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                         });
                  });
            });
        }

        $payments = $query->latest()->paginate(10)->withQueryString();

        // 4 Dynamic Stat Cards (semua dari database — tanpa dummy fallback)
        $totalRevenue = (int) Payment::where('status', 'success')->sum('amount');

        $pendingCount = (int) Payment::where('status', 'pending')->count();

        $failedCount = (int) Payment::whereIn('status', ['failed', 'refunded'])->count();

        $totalCount = (int) Payment::count();
        $successCount = (int) Payment::where('status', 'success')->count();
        $successRate = $totalCount > 0 ? round(($successCount / $totalCount) * 100, 1) : 0.0;

        return view('admin.pembayaran', [
            'payments' => $payments,
            'totalRevenue' => $totalRevenue,
            'pendingCount' => $pendingCount,
            'failedCount' => $failedCount,
            'successRate' => $successRate,
            'currentFilter' => $statusFilter,
            'selectedMethod' => $request->input('method', 'all'),
            'searchTerm' => $request->input('search', ''),
        ]);
    }

    /**
     * Approve Pembayaran (Ubah status payment -> success, order -> active)
     *
     * Stok dikurangi (produk satuan + anggota Paket Sewa) HANYA SATU KALI,
     * tepat ketika order berpindah ke status active. Jika order sudah berstatus
     * active (mis. sudah dikonfirmasi lewat halaman Penyewaan), stok TIDAK
     * dikurangi lagi — menjaga idempotensi saat approve diulang / halaman di-refresh.
     */
    public function approvePayment(int $id): RedirectResponse
    {
        $payment = Payment::with('order.items.product', 'order.items.bundle.products')->findOrFail($id);

        // Jika ini pembayaran denda (FINE-...), teruskan langsung ke alur approveDendaPayment
        if ($payment->is_denda_payment) {
            return $this->approveDendaPayment($id);
        }

        // Idempotensi: pembayaran yang sudah berstatus success tidak diproses dua kali,
        // sehingga stok tidak pernah dikurangi berulang dan status tidak diganggu.
        if ($payment->status === 'success') {
            return redirect()->route('admin.pembayaran')->with('info', "Pembayaran {$payment->trx_code} sudah disetujui sebelumnya.");
        }

        DB::transaction(function () use ($payment) {
            $payment->status = 'success';
            $payment->paid_at = now();
            $payment->save();

            if ($payment->order) {
                $wasActive = $payment->order->status === 'active';
                $payment->order->status = 'active';
                $payment->order->paid_at = now();
                $payment->order->save();

                if (! $wasActive) {
                    $this->decrementOrderStock($payment->order);
                }
            }
        });

        // Notifikasi ke user pemilik booking: pembayaran disetujui & penyewaan aktif.
        if ($payment->order) {
            RentalNotificationService::notifyPaymentApproved($payment->order, $payment);
        }

        return redirect()->route('admin.pembayaran')->with('status', "Pembayaran {$payment->trx_code} berhasil diverifikasi dan disetujui.");
    }

    /**
     * Tolak Pembayaran (Ubah status payment -> failed, order -> cancelled)
     *
     * Stok dikembalikan hanya jika order sebelumnya sudah sempat active
     * (mis. di-confirm manual lalu pembayarannya ditolak).
     */
    public function rejectPayment(Request $request, int $id): RedirectResponse
    {
        $payment = Payment::with('order.items.product', 'order.items.bundle.products')->findOrFail($id);

        // Jika ini pembayaran denda, teruskan ke alur rejectDendaPayment
        if ($payment->is_denda_payment) {
            return $this->rejectDendaPayment($request, $id);
        }

        $wasCancelled = $payment->order && $payment->order->status === 'cancelled';

        DB::transaction(function () use ($payment, $request) {
            $payment->status = 'failed';
            $payment->save();

            if ($payment->order) {
                $wasActive = $payment->order->status === 'active';
                $payment->order->status = 'cancelled';
                if ($reason = $request->input('reason')) {
                    $payment->order->notes = ($payment->order->notes ? $payment->order->notes . ' | ' : '') . "Penolakan pembayaran: " . $reason;
                }
                $payment->order->save();

                if ($wasActive) {
                    $this->incrementOrderStock($payment->order);
                }
            }
        });

        // Notifikasi ke user pemilik booking: pembayaran ditolak & pesanan dibatalkan.
        if ($payment->order && ! $wasCancelled) {
            RentalNotificationService::notifyPaymentRejected($payment->order, $payment, $request->input('reason'));
        }

        return redirect()->route('admin.pembayaran')->with('status', "Pembayaran {$payment->trx_code} telah ditolak.");
    }

    /**
     * Export Data Pembayaran ke CSV
     */
    public function exportCsvPembayaran(Request $request): StreamedResponse
    {
        $statusFilter = $request->input('filter', 'all');
        $query = Payment::with(['order.user', 'order.items.product']);

        if ($statusFilter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($statusFilter === 'confirmed' || $statusFilter === 'success') {
            $query->where('status', 'success');
        } elseif ($statusFilter === 'rejected' || $statusFilter === 'failed') {
            $query->whereIn('status', ['failed', 'refunded']);
        }

        $payments = $query->latest()->get();

        $filename = "pembayaran_export_" . date('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($payments) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Transaction ID',
                'Nama Customer',
                'Email',
                'Metode Pembayaran',
                'Nominal (Rp)',
                'Status Pembayaran',
                'Kode Referensi',
                'Kode Pesanan',
                'Tanggal Transaksi',
            ]);

            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->trx_code,
                    $payment->order?->user?->name ?? 'Guest User',
                    $payment->order?->user?->email ?? '-',
                    $payment->formatted_method,
                    $payment->amount,
                    strtoupper($payment->status),
                    $payment->reference ?? '-',
                    $payment->order?->code ?? '-',
                    $payment->created_at ? $payment->created_at->format('d/m/Y H:i') : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─────────────────────────────────────────────────────────────
    // PENGEMBALIAN (Return Management)
    // ─────────────────────────────────────────────────────────────

    /**
     * Tampilan Halaman Pengembalian (Return Management)
     *
     * Menyediakan pencarian, filter status (semua / terlambat / menunggu
     * inspeksi / ada denda / selesai / rusak), ringkasan statistik berbasis
     * database, serta penanganan error agar halaman tidak pernah kosong
     * tanpa penjelasan bila sumber data gagal diakses.
     */
    public function pengembalian(Request $request): View
    {
        $filter    = $request->input('filter', 'all');
        $sort      = $request->input('sort', 'desc');
        $search    = trim((string) $request->input('search', ''));
        $loadError = false;

        try {
            // Base query: orders yang status-nya active/completed/paid (already started)
            $query = Order::query()->with(['user', 'items.product', 'returns', 'payments', 'latePenalty'])
                ->whereIn('status', ['active', 'completed', 'paid']);

            // Apply search (kode pesanan / nama & email pelanggan / nama barang)
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('orders.code', 'like', "%{$search}%")
                      ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                                                        ->orWhere('email', 'like', "%{$search}%"))
                      ->orWhereHas('items', fn ($i) => $i->where('name', 'like', "%{$search}%"));
                });
            }

            // Apply filter
            // Apply filter
            if ($filter === 'needs_inspection') {
                // Pengembalian sudah diterima (ReturnRecord + returned_at) tapi
                // kondisi belum diinspeksi admin (condition = null). Hanya memeriksa
                // record level order (order_item_id NULL), bukan record per-item.
                // Order yang sudah completed tidak dihitung menunggu inspeksi lagi.
                $query->where('status', '!=', 'completed')
                      ->whereHas('returns', fn ($r) => $r->whereNull('order_item_id')->whereNull('condition'));
            } elseif ($filter === 'damaged') {
                $query->whereHas('returns', fn ($r) => $r->whereNull('order_item_id')->whereIn('condition', ['minor_damage', 'major_damage']));
            } elseif ($filter === 'terlambat') {
                // Rental aktif yang sudah melewati batas, atau pengembalian yang
                // benar-benar dilakukan setelah tanggal seharusnya (rent_end).
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->whereIn('orders.status', ['active', 'paid'])
                           ->whereDate('orders.rent_end', '<', today());
                    })->orWhereHas('returns', function ($q2) {
                        $q2->whereNull('order_item_id')
                           ->whereRaw('DATE(return_records.returned_at) > DATE(orders.rent_end)');
                    });
                });
            } elseif ($filter === 'ada_denda') {
                // Memiliki denda kerusakan (damage_cost > 0) atau sanksi
                // keterlambatan yang masih aktif.
                $query->where(function ($q) {
                    $q->whereHas('returns', fn ($r) => $r->where('damage_cost', '>', 0))
                      ->orWhereHas('latePenalty', fn ($p) => $p
                          ->where('total_fee', '>', 0)
                          ->where('status', '!=', LatePenalty::STATUS_NO_SANCTION)
                          ->where('status', '!=', LatePenalty::STATUS_CANCELLED));
                });
            } elseif ($filter === 'selesai') {
                $query->where('status', 'completed');
            }

            // Apply sort berdasarkan tanggal pengembalian yg diharapkan
            $query->orderBy('rent_end', $sort === 'asc' ? 'asc' : 'desc');

            $orders = $query->paginate(10)->withQueryString();

            // ── Ringkasan statistik (langsung dari database) ──
            $totalReturns = (int) Order::whereIn('status', ['active', 'paid', 'completed'])->count();

            $pendingInspection = (int) ReturnRecord::query()
            ->join('orders', 'orders.id', '=', 'return_records.order_id')
            ->where('orders.status', '!=', 'completed')
            ->whereNull('return_records.order_item_id')
            ->whereNull('return_records.condition')
            ->distinct()
            ->count('return_records.id');

            // Terlambat = rental aktif yang melewati rent_end ATAU pengembalian
            // tercatat dilakukan setelah rent_end (konsisten dengan Order::calculateOverdue).
            $overdueLate = (int) ReturnRecord::query()
                ->join('orders', 'orders.id', '=', 'return_records.order_id')
                ->where('orders.status', 'completed')
                ->whereNull('return_records.order_item_id')
                ->whereRaw('DATE(return_records.returned_at) > DATE(orders.rent_end)')
                ->distinct()
                ->count('return_records.order_id');

            $overdue = (int) Order::whereIn('status', ['active', 'paid'])
                    ->whereDate('rent_end', '<', today())
                    ->count()
                + $overdueLate;

            // Denda belum lunas = denda kerusakan tanpa damage_paid_at atau
            // sanksi keterlambatan yang masih menunggu pembayaran/verifikasi.
            $fineOrderIds = ReturnRecord::whereNull('order_item_id')
                    ->where('damage_cost', '>', 0)
                    ->whereNull('damage_paid_at')
                    ->pluck('order_id')
                ->merge(
                    LatePenalty::whereIn('status', [
                        LatePenalty::STATUS_UNPROCESSED,
                        LatePenalty::STATUS_PENDING,
                        LatePenalty::STATUS_VERIFYING,
                    ])->where('total_fee', '>', 0)->pluck('order_id')
                )
                ->unique();
            $unpaidFines = $fineOrderIds->count();

            $completedCount = (int) Order::where('status', 'completed')->count();
        } catch (\Throwable $e) {
            $loadError = true;
            $orders = new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                10,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            $totalReturns = $pendingInspection = $overdue = $unpaidFines = $completedCount = 0;
        }

        return view('admin.pengembalian', compact(
            'orders',
            'filter',
            'sort',
            'search',
            'totalReturns',
            'pendingInspection',
            'overdue',
            'unpaidFines',
            'completedCount',
            'loadError'
        ));
    }

    /**
     * Rekam actual return oleh customer — membuat ReturnRecord baru
     */
    public function recordReturn(Request $request, int $orderId): RedirectResponse
    {
        $order = Order::with(['items.product', 'user'])->findOrFail($orderId);

        $request->validate([
            'condition'         => 'required|in:excellent,good,needs_cleaning,minor_damage,major_damage',
            'inspection_note'   => 'nullable|string|max:1000',
            'damage_description'=> 'nullable|string|max:1000',
            'damage_cost'       => 'nullable|integer|min:0',
            'proof_path'        => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        // Foto bukti pengembalian user harus dipertahankan bila admin tidak
        // mengunggah foto inspeksi baru.
        $existingReturn = ReturnRecord::where('order_id', $order->id)
            ->whereNull('order_item_id')
            ->first();

        // Upload inspection photo if present (storage privat; disajikan via route terkontrol).
        $photoPath = null;
        if ($request->hasFile('proof_path')) {
            $photoPath = $request->file('proof_path')->store('returns');
        }

        // Create or update return record for this order
        $returnData = [
            'status'             => 'approved',
            'returned_at'        => now(),
            'condition'          => $request->input('condition'),
            'inspection_note'    => $request->input('inspection_note'),
            'damage_description' => $request->input('damage_description'),
            'damage_cost'        => $request->input('damage_cost', 0),
        ];
        if ($photoPath !== null) {
            $returnData['proof_path'] = $photoPath;
        } elseif ($existingReturn && $existingReturn->proof_path) {
            $returnData['proof_path'] = $existingReturn->proof_path;
        }

        $returnRecord = ReturnRecord::updateOrCreate(
            ['order_id' => $order->id, 'order_item_id' => null],
            $returnData
        );

        // Notifikasi aktivitas pengembalian kepada seluruh admin.
        \App\Services\AdminNotificationService::notifyAdmins(
            'return',
            '📦 Pengembalian Barang',
            "Barang untuk pesanan #{$order->code} (user " . ($order->user?->name ?? 'guest') . ") telah dikembalikan dan tercatat dengan kondisi: " . $returnRecord->condition_label . ".",
            '↩️',
            route('admin.pengembalian'),
        );

        // Notifikasi ke user pemilik booking: pengembalian diinspeksi (hanya saat
        // record BARU dibuat — merekam ulang tidak membuat notifikasi ganda).
        if ($returnRecord->wasRecentlyCreated) {
            RentalNotificationService::notifyReturnRecorded($order, $returnRecord);
        }

        // Notifikasi denda ke user jika admin membebankan nominal kerusakan > 0.
        // Idempoten (per kode booking), dipicu saat aksi pemberian denda, bukan
        // setiap kali halaman riwayat diakses.
        RentalNotificationService::notifyDendaCharged($order, $returnRecord);

        return redirect()->route('admin.pengembalian')
            ->with('success', "Pengembalian order #{$order->code} berhasil direkam dan diinspeksi.");
    }

    /**
     * Selesaikan pengembalian: update order → completed, update product stock
     *
     * Backend MENGAMBIL ALIH aturan kelayakan: pengembalian baru boleh
     * diselesaikan apabila sudah diinspeksi (ada ReturnRecord + kondisi) dan
     * seluruh denda wajib (kerusakan & keterlambatan) sudah lunas. Ini
     * mencegah pengembalian diselesaikan lewat akses langsung ke route,
     * bukan hanya dibatasi tombol di frontend.
     */
    public function completeReturn(Request $request, int $orderId): RedirectResponse
    {
        $order = Order::with(['items.product', 'items.bundle.products', 'returns', 'latePenalty'])->findOrFail($orderId);

        // Pastikan ada return record yang sudah diinspeksi.
        $returnRecord = $order->returns()->whereNull('order_item_id')->latest('id')->first();
        if (! $returnRecord || $returnRecord->condition === null) {
            return redirect()->route('admin.pengembalian')
                ->with('error', "Harap rekam inspeksi pengembalian order #{$order->code} terlebih dahulu.");
        }

        // Denda kerusakan wajib harus lunas sebelum pengembalian diselesaikan.
        if ($returnRecord->has_denda && ! $returnRecord->is_denda_paid) {
            return redirect()->route('admin.pengembalian')
                ->with('error', "Denda kerusakan order #{$order->code} belum lunas. Selesaikan pembayaran denda terlebih dahulu.");
        }

        // Sanksi keterlambatan yang masih harus dibayar ikut memblokir penyelesaian.
        $penalty = $order->latePenalty;
        if ($penalty
            && (int) $penalty->total_fee > 0
            && in_array($penalty->status, [
                LatePenalty::STATUS_UNPROCESSED,
                LatePenalty::STATUS_PENDING,
                LatePenalty::STATUS_VERIFYING,
            ], true)) {
            return redirect()->route('admin.pengembalian')
                ->with('error', "Sanksi keterlambatan order #{$order->code} belum lunas. Selesaikan pembayaran sanksi terlebih dahulu.");
        }

        // Order yang TELAT wajib memiliki sanksi yang sudah dituntaskan
        // (Lunas atau dibebaskan = tidak_ada_sanksi) sebelum diselesaikan.
        // Menutup celah: pengembalian terlambat "dilewati" tanpa sanksi
        // (termasuk sanksi yang dibatalkan) lewat akses langsung ke route.
        $overdueInfo = $order->calculateOverdue($returnRecord?->returned_at);
        if ((int) $overdueInfo['days_overdue'] > 0) {
            $sanctionResolved = $penalty && in_array($penalty->status, [
                LatePenalty::STATUS_PAID,
                LatePenalty::STATUS_NO_SANCTION,
            ], true);
            if (! $sanctionResolved) {
                return redirect()->route('admin.pengembalian')
                    ->with('error', "Sanksi keterlambatan order #{$order->code} belum dituntaskan. Tetapkan atau tuntaskan sanksi terlebih dahulu.");
            }
        }

        // major_damage: alat dalam perawatan, stok tidak langsung dikembalikan.
        // Kondisi lain (baik / minor_damage / dll) mengembalikan stok.
        $restoresStock = ! in_array($returnRecord->condition, ['major_damage'], true);

        // Idempoten: stok hanya dikembalikan SATU KALI, ketika order benar-benar
        // masih berstatus aktif (active/paid) sebelum diselesaikan. Jika complete
        // diulang, stok TIDAK bertambah dua kali.
        $wasActive = in_array($order->status, ['active', 'paid'], true);

        DB::transaction(function () use ($order, $restoresStock, $wasActive) {
            $order->update(['status' => 'completed']);

            if ($wasActive && $restoresStock) {
                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock_available', $item->quantity ?? 1);
                    }
                    // Anggota Paket Sewa ikut dikembalikan stoknya.
                    if ($item->bundle) {
                        foreach ($item->bundle->products as $bundleProduct) {
                            $needed = ($bundleProduct->pivot->quantity ?? 1) * ($item->quantity ?? 1);
                            $bundleProduct->increment('stock_available', $needed);
                        }
                    }
                }
            }
        });

        // Notifikasi ke user pemilik booking: pengembalian telah diselesaikan.
        if ($wasActive) {
            RentalNotificationService::notifyReturnCompleted($order);
        }

        return redirect()->route('admin.pengembalian')
            ->with('success', "Pengembalian order #{$order->code} telah diselesaikan.");
    }

    /**
     * Setujui pembayaran DENDA. Membuat denda pada pengembalian terkait menjadi
     * LUNAS (damage_paid_at diisi) — status tersimpan di backend, bukan frontend.
     *
     * Hanya memproses payment denda (reference berawalan FINE-); tidak menyentuh
     * status order (agar alur pembayaran sewa utama tidak terganggu).
     */
    public function approveDendaPayment(int $paymentId): RedirectResponse
    {
        $payment = Payment::with(['order.returns', 'order.user', 'order.latePenalty'])->findOrFail($paymentId);

        if (! $payment->is_denda_payment) {
            return back()->with('error', 'Pembayaran ini bukan pembayaran denda.');
        }

        if ($payment->status === 'success') {
            return back()->with('info', "Pembayaran denda {$payment->reference} sudah disetujui sebelumnya.");
        }

        DB::transaction(function () use ($payment) {
            $payment->status = 'success';
            $payment->paid_at = now();
            $payment->save();

            $order = $payment->order;
            if ($order) {
                // Update denda kerusakan fisik jika ada
                $record = $order->returns()
                    ->whereNull('order_item_id')
                    ->latest('id')
                    ->first();
                if ($record && $record->has_denda && ! $record->is_denda_paid) {
                    $record->update(['damage_paid_at' => now()]);
                }

                // Cari dan perbarui sanksi keterlambatan secara pasti.
                // Sanksi hanya ditandai LUNAS apabila pembayaran yang disetujui
                // benar-benar terhubung dengan sanksi tersebut (payment_id)
                // atau sanksi sedang berstatus menunggu verifikasi. Ini mencegah
                // sanksi milik pembayaran lain tertandai lunas secara keliru.
                $penalty = LatePenalty::where('order_id', $order->id)->first();

                if ($penalty
                    && $penalty->status !== LatePenalty::STATUS_CANCELLED
                    && ((int) $penalty->payment_id === (int) $payment->id
                        || $penalty->status === LatePenalty::STATUS_VERIFYING)) {
                    $penalty->update([
                        'status'     => LatePenalty::STATUS_PAID,
                        'paid_at'    => now(),
                        'payment_id' => $payment->id,
                    ]);
                    RentalNotificationService::notifyLatePenaltyPaid($order, $penalty);
                }
            }
        });

        if ($payment->order && $payment->order->user) {
            $record = $payment->order->returns()->whereNull('order_item_id')->latest('id')->first();
            if ($record && $record->has_denda) {
                RentalNotificationService::notifyDendaPaid($payment->order);
            }
        }

        return back()->with('success', "Pembayaran denda {$payment->reference} disetujui. Denda kini berstatus Lunas.");
    }

    /**
     * Tetapkan / Perbarui Sanksi Keterlambatan Pengembalian oleh Admin
     */
    public function storeLatePenalty(Request $request, int $orderId): RedirectResponse
    {
        $order = Order::with(['returns', 'latePenalty', 'user', 'items.product', 'items.bundle.products'])->findOrFail($orderId);

        $request->validate([
            'status'      => 'required|in:menunggu_pembayaran,menunggu_verifikasi,tidak_ada_sanksi,belum_diproses,sudah_dibayar',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $latestReturn = $order->returns()->whereNull('order_item_id')->latest('id')->first();
        $actualDate = $latestReturn?->returned_at ?? now();
        $overdueInfo = $order->calculateOverdue($actualDate);

        $daysOverdue = (int) $overdueInfo['days_overdue'];
        $feePerDay = (int) $overdueInfo['fee_per_day'];
        $status = $request->input('status');

        if ($status === 'tidak_ada_sanksi' || $daysOverdue <= 0) {
            $totalFee = 0;
            $status = 'tidak_ada_sanksi';
        } else {
            $totalFee = $daysOverdue * $feePerDay;
        }

        $penalty = LatePenalty::updateOrCreate(
            ['order_id' => $order->id],
            [
                'user_id'          => $order->user_id,
                'return_record_id' => $latestReturn?->id,
                'days_overdue'     => $daysOverdue,
                'fee_per_day'      => $feePerDay,
                'total_fee'        => $totalFee,
                'status'           => $status,
                'admin_notes'      => $request->input('admin_notes'),
                'paid_at'          => $status === 'sudah_dibayar' ? now() : null,
                'cancelled_at'     => null,
            ]
        );

        if ($status === 'sudah_dibayar') {
            foreach ($order->payments as $p) {
                if ($p->is_denda_payment && $p->status === 'pending') {
                    $p->update(['status' => 'success', 'paid_at' => now()]);
                    $penalty->update(['payment_id' => $p->id]);
                }
            }
        }

        // Order hanya boleh diselesaikan di sini apabila sanksi yang dipilih
        // TIDAK menyisakan kewajiban denda (Lunas atau dibebaskan). Memilih
        // "Kenakan Denda (Menunggu Pembayaran)" tidak boleh sekaligus menyelesaikan
        // order — denda harus dituntaskan terlebih dahulu di luar alur ini.
        $canComplete = $request->boolean('complete_order')
            && in_array($order->status, ['active', 'paid'], true)
            && in_array($status, ['sudah_dibayar', 'tidak_ada_sanksi'], true);

        if ($canComplete) {
            $restoresStock = ! ($latestReturn && in_array($latestReturn->condition, ['major_damage'], true));
            DB::transaction(function () use ($order, $restoresStock) {
                $order->update(['status' => 'completed']);
                if ($restoresStock) {
                    foreach ($order->items as $item) {
                        if ($item->product) {
                            $item->product->increment('stock_available', $item->quantity ?? 1);
                        }
                        if ($item->bundle) {
                            foreach ($item->bundle->products as $bundleProduct) {
                                $needed = ($bundleProduct->pivot->quantity ?? 1) * ($item->quantity ?? 1);
                                $bundleProduct->increment('stock_available', $needed);
                            }
                        }
                    }
                }
            });
            RentalNotificationService::notifyReturnCompleted($order);
        }

        if ($status === 'menunggu_pembayaran' && $totalFee > 0) {
            RentalNotificationService::notifyLatePenaltyAssigned($order, $penalty);
        }

        $msg = $status === 'tidak_ada_sanksi'
            ? "Sanksi denda keterlambatan untuk pesanan #{$order->code} dibebaskan (Tidak Ada Sanksi)."
            : "Sanksi keterlambatan ({$daysOverdue} hari - Rp " . number_format($totalFee, 0, ',', '.') . ") berhasil ditetapkan.";

        return redirect()->route('admin.pengembalian')->with('success', $msg);
    }

    /**
     * Batalkan Sanksi Keterlambatan Pengembalian
     */
    public function cancelLatePenalty(Request $request, int $orderId): RedirectResponse
    {
        $order = Order::with('latePenalty')->findOrFail($orderId);
        $penalty = $order->latePenalty;

        if (! $penalty) {
            return redirect()->route('admin.pengembalian')
                ->with('error', 'Sanksi keterlambatan tidak ditemukan.');
        }

        $penalty->update([
            'status'       => 'dibatalkan',
            'cancelled_at' => now(),
        ]);

        return redirect()->route('admin.pengembalian')
            ->with('success', "Sanksi keterlambatan untuk order #{$order->code} berhasil dibatalkan.");
    }

    /**
     * Tolak pembayaran DENDA (status payment -> failed). Denda tetap Belum Lunas.
     */
    public function rejectDendaPayment(Request $request, int $paymentId): RedirectResponse
    {
        $payment = Payment::with('order')->findOrFail($paymentId);

        if (! $payment->is_denda_payment) {
            return back()->with('error', 'Pembayaran ini bukan pembayaran denda.');
        }

        $payment->status = 'failed';
        $payment->save();

        if ($payment->order) {
            $penalty = LatePenalty::where('order_id', $payment->order_id)->first();
            if ($penalty
                && (int) $penalty->payment_id === (int) $payment->id
                && $penalty->status === LatePenalty::STATUS_VERIFYING) {
                $penalty->update(['status' => LatePenalty::STATUS_PENDING]);
            }
        }

        return back()->with('error', "Pembayaran denda {$payment->reference} ditolak. Denda tetap berstatus Belum Lunas.");
    }

    /**
     * ─── USER MANAGEMENT (Halaman Users) ───
     */
    public function users(Request $request): View
    {
        $search = $request->input('search', '');
        $statusFilter = $request->input('status', 'all');
        $domicileFilter = $request->input('domicile', 'everywhere');
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 10;
        }

        // 4 Statistic Cards (Dynamic from database)
        $totalMembers = User::count();
        
        // Active Now: hitung sesi aktif (user yang login) dalam 30 menit terakhir.
        // Catatan: auth custom memakai session('account_id'), bukan kolom sessions.user_id
        // milik guard bawaan Laravel, sehingga kolom user_id di tabel sessions selalu kosong.
        // Karena itu jumlah sesi login aktif tidak bisa dihitung andal dari tabel sessions;
        // menampilkan 0 lebih jujur daripada mengarang angka (fallback dummy 156 dihapus).
        $activeSessionsCount = 0;
        try {
            $activeSessionsCount = \Illuminate\Support\Facades\DB::table('sessions')
                ->where('last_activity', '>=', now()->subMinutes(30)->timestamp)
                ->where('user_id', '>=', 0)
                ->count();
        } catch (\Throwable $e) {
            $activeSessionsCount = 0;
        }
        $activeNow = $activeSessionsCount;

        // New Registrations: past 24 hours
        $newRegistrations = User::where('created_at', '>=', now()->subHours(24))->count();

        // Pending Verification: users with status pending or pending_verification
        $pendingVerification = User::whereIn('status', ['pending', 'pending_verification'])->count();

        // Query Users
        $query = User::query()->withCount('orders');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('domicile', 'like', "%{$search}%");
            });
        }

        if ($statusFilter && $statusFilter !== 'all') {
            if ($statusFilter === 'pending' || $statusFilter === 'pending_verification') {
                $query->whereIn('status', ['pending', 'pending_verification']);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        $allowedDomiciles = ['Jakarta', 'Bogor', 'Depok', 'Tangerang', 'Bekasi'];

        if ($domicileFilter && $domicileFilter !== 'everywhere') {
            if (in_array($domicileFilter, $allowedDomiciles, true)) {
                $query->where(function ($q) use ($domicileFilter) {
                    $q->where('domicile', $domicileFilter)
                      ->orWhere('domicile', 'like', "{$domicileFilter}%");
                });
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        // Pilihan filter wilayah dibatasi hanya untuk 5 wilayah utama Jabodetabek
        $domiciles = $allowedDomiciles;

        return view('admin.users', compact(
            'users',
            'totalMembers',
            'activeNow',
            'newRegistrations',
            'pendingVerification',
            'search',
            'statusFilter',
            'domicileFilter',
            'domiciles',
            'perPage'
        ));
    }

    /**
     * Tambah User Baru
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'username' => 'nullable|string|max:50|unique:users,username',
            'phone' => 'nullable|string|max:30',
            'domicile' => 'nullable|string|max:255',
            'status' => 'required|string|in:active,inactive,suspended,pending,pending_verification',
            'role' => 'nullable|string|in:user,admin,member,customer',
            'password' => 'required|string|min:6',
        ]);

        if (empty($validated['username'])) {
            $validated['username'] = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $validated['name']))) . rand(10, 99);
        }
        $validated['role'] = $validated['role'] ?? 'user';

        User::create($validated);

        return redirect()->route('admin.users')->with('success', "User '{$validated['name']}' berhasil ditambahkan.");
    }

    /**
     * Edit / Update User
     */
    public function updateUser(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'username' => 'nullable|string|max:50|unique:users,username,' . $user->id,
            'phone' => 'nullable|string|max:30',
            'domicile' => 'nullable|string|max:255',
            'status' => 'required|string|in:active,inactive,suspended,pending,pending_verification',
            'role' => 'nullable|string|in:user,admin,member,customer',
            'password' => 'nullable|string|min:6',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users')->with('success', "Data user '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Ubah Status User
     */
    public function changeUserStatus(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:active,inactive,suspended,pending,pending_verification',
        ]);

        $user->update(['status' => $validated['status']]);

        $statusLabel = $user->status_label;
        return redirect()->route('admin.users')->with('success', "Status user '{$user->name}' berhasil diubah menjadi {$statusLabel}.");
    }

    /**
     * Hapus / Soft Delete User
     */
    public function destroyUser(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $userName = $user->name;

        $user->delete();

        return redirect()->route('admin.users')->with('success', "User '{$userName}' berhasil dihapus.");
    }

    /**
     * Export CSV Users
     */
    public function exportCsvUsers(Request $request): StreamedResponse
    {
        $search = $request->input('search', '');
        $statusFilter = $request->input('status', 'all');
        $domicileFilter = $request->input('domicile', 'everywhere');

        $query = User::query()->withCount('orders');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('domicile', 'like', "%{$search}%");
            });
        }

        if ($statusFilter && $statusFilter !== 'all') {
            if ($statusFilter === 'pending' || $statusFilter === 'pending_verification') {
                $query->whereIn('status', ['pending', 'pending_verification']);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        $allowedDomiciles = ['Jakarta', 'Bogor', 'Depok', 'Tangerang', 'Bekasi'];

        if ($domicileFilter && $domicileFilter !== 'everywhere') {
            if (in_array($domicileFilter, $allowedDomiciles, true)) {
                $query->where(function ($q) use ($domicileFilter) {
                    $q->where('domicile', $domicileFilter)
                      ->orWhere('domicile', 'like', "{$domicileFilter}%");
                });
            }
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        $filename = 'users_export_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return Response::stream(function () use ($users) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'User ID',
                'Nama',
                'Email',
                'Username',
                'No Telepon',
                'Domisili',
                'Status',
                'Role',
                'Total Penyewaan',
                'Tanggal Registrasi',
            ]);

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->username ? '@' . ltrim($user->username, '@') : '-',
                    $user->phone ?? '-',
                    $user->domicile ?? '-',
                    $user->status_label,
                    $user->role ?? 'user',
                    $user->orders_count ?? 0,
                    $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * ─── LAPORAN & ANALITIK (Halaman Laporan) ───
     */
    public function laporan(Request $request): View
    {
        $period = $request->input('period', 'all');

        $queryPayments = Payment::where('status', 'success');
        $queryOrders = Order::query();
        $queryReturns = ReturnRecord::query();

        if ($period === 'today') {
            $queryPayments->whereDate('created_at', today());
            $queryOrders->whereDate('created_at', today());
            $queryReturns->whereDate('created_at', today());
        } elseif ($period === 'this_week') {
            $queryPayments->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            $queryOrders->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            $queryReturns->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'this_month') {
            $queryPayments->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
            $queryOrders->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
            $queryReturns->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        // Summary Statistics
        $totalRevenue = (int) $queryPayments->sum('amount');

        $totalOrdersCount = $queryOrders->count();
        $completedOrdersCount = Order::where('status', 'completed')->count();
        $activeOrdersCount = Order::whereIn('status', ['active', 'paid'])->count();
        $pendingOrdersCount = Order::where('status', 'pending')->count();
        $cancelledOrdersCount = Order::where('status', 'cancelled')->count();

        $totalItemsRented = OrderItem::sum('quantity');
        $damagedReturnsCount = ReturnRecord::whereIn('condition', ['minor_damage', 'major_damage'])->count();
        $damageFinesTotal = ReturnRecord::sum('damage_cost');

        // Category breakdown
        $categories = Category::withCount('products')->get()->map(function ($cat) {
            $productIds = $cat->products->pluck('id');
            $rentalCount = OrderItem::whereIn('product_id', $productIds)->sum('quantity');
            return [
                'name' => $cat->name,
                'slug' => $cat->slug,
                'products_count' => $cat->products_count,
                'rentals_count' => $rentalCount,
            ];
        });

        // Top Rented Products
        $topProducts = OrderItem::select('name', 'image')
            ->selectRaw('SUM(quantity) as total_rented, SUM(subtotal) as total_sales')
            ->groupBy('name', 'image')
            ->orderByDesc('total_rented')
            ->take(5)
            ->get();

        // Recent Orders Log
        $recentOrders = Order::with(['user', 'payments', 'items'])
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        return view('admin.laporan', compact(
            'period',
            'totalRevenue',
            'totalOrdersCount',
            'completedOrdersCount',
            'activeOrdersCount',
            'pendingOrdersCount',
            'cancelledOrdersCount',
            'totalItemsRented',
            'damagedReturnsCount',
            'damageFinesTotal',
            'categories',
            'topProducts',
            'recentOrders'
        ));
    }

    /**
     * Export CSV Laporan
     */
    public function exportCsvLaporan(Request $request): StreamedResponse
    {
        $orders = Order::with(['user', 'items', 'payments'])->orderBy('created_at', 'desc')->get();
        $filename = 'laporan_summit_station_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return Response::stream(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Order ID',
                'Customer',
                'Email',
                'Status',
                'Tanggal Sewa Mulai',
                'Tanggal Sewa Selesai',
                'Total Biaya (Rp)',
                'Status Pembayaran',
                'Metode Pembayaran',
                'Dibuat Pada',
            ]);

            foreach ($orders as $o) {
                $payment = $o->payments->first();
                fputcsv($handle, [
                    $o->code,
                    $o->user?->name ?? 'Guest Explorer',
                    $o->user?->email ?? '-',
                    strtoupper($o->status),
                    $o->rent_start ? $o->rent_start->format('Y-m-d') : '-',
                    $o->rent_end ? $o->rent_end->format('Y-m-d') : '-',
                    $o->total,
                    strtoupper($payment?->status ?? 'UNPAID'),
                    $payment?->formatted_method ?? '-',
                    $o->created_at ? $o->created_at->format('Y-m-d H:i') : '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * ─── WEBSITE & CMS OVERVIEW (Halaman Website) ───
     */
    public function website(): View
    {
        $settings = \App\Services\SiteSettingsService::all();

        // Statistik dinamis tetap dihitung dari database.
        $settings['featured_products_count'] = Product::where('is_active', true)->count();
        $settings['total_categories_count'] = Category::count();
        $settings['active_bundles_count'] = Bundle::where('is_active', true)->count();

        $reviews = collect();
        $totalReviewsCount = 0;
        $averageRating = 0.0;

        try {
            $reviews = Review::with(['user', 'product', 'bundle', 'order'])->orderBy('created_at', 'desc')->paginate(10);
            $totalReviewsCount = Review::count();
            $averageRating = $totalReviewsCount > 0 ? round((float) Review::avg('rating'), 1) : 0.0;
        } catch (\Throwable $e) {
            try {
                $reviews = Review::with(['user', 'product'])->orderBy('created_at', 'desc')->paginate(10);
                $totalReviewsCount = Review::count();
                $averageRating = $totalReviewsCount > 0 ? round((float) Review::avg('rating'), 1) : 0.0;
            } catch (\Throwable $e2) {
                // Table doesn't exist
            }
        }

        return view('admin.website', compact('settings', 'reviews', 'totalReviewsCount', 'averageRating'));
    }

    /**
     * Simpan Pengaturan Website ke database (tabel `settings`).
     */
    public function updateWebsiteSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['nullable', 'string', 'max:255'],
            'hotline' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'operating_hours' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:1000'],
        ]);

        \App\Services\SiteSettingsService::saveMany($data);

        return redirect()->route('admin.website')
            ->with('success', 'Pengaturan informasi website dan spotlight toko berhasil diperbarui.');
    }

    // ─────────────────────────────────────────────────────────────
    // REFUND MANAGEMENT (Refund Management)
    // Satu sumber data refund yang sama dipakai user dan admin.
    // ─────────────────────────────────────────────────────────────

    /**
     * Tampilan Halaman Refund Management (Admin)
     */
    public function refund(Request $request): View
    {
        $query = Refund::with(['order.user', 'order.items.product', 'payment', 'processedBy']);

        $statusFilter = $request->input('status', 'all');
        if ($statusFilter === 'pending') {
            $query->where('status', Refund::STATUS_PENDING);
        } elseif ($statusFilter === 'approved') {
            $query->where('status', Refund::STATUS_APPROVED);
        } elseif ($statusFilter === 'rejected') {
            $query->where('status', Refund::STATUS_REJECTED);
        } elseif ($statusFilter === 'completed') {
            $query->where('status', Refund::STATUS_COMPLETED);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('code', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($uq) use ($search) {
                                $uq->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $refunds = $query->latest()->paginate(10)->withQueryString();

        // Statistic Cards
        $pendingCount = Refund::where('status', Refund::STATUS_PENDING)->count();
        $approvedCount = Refund::where('status', Refund::STATUS_APPROVED)->count();
        $completedCount = Refund::where('status', Refund::STATUS_COMPLETED)->count();
        $totalRefunded = Refund::where('status', Refund::STATUS_COMPLETED)->orWhere('status', Refund::STATUS_APPROVED)
            ->sum('refund_amount');

        return view('admin.refund', [
            'refunds' => $refunds,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'completedCount' => $completedCount,
            'totalRefunded' => $totalRefunded,
            'currentFilter' => $statusFilter,
            'searchTerm' => $request->input('search', ''),
        ]);
    }

    /**
     * Halaman Detail Refund (Admin) — memuat data dari database yang sama.
     */
    public function refundDetail(int $id): View
    {
        $refund = Refund::with([
            'order.user',
            'order.items.product',
            'order.payments',
            'payment',
            'processedBy',
        ])->findOrFail($id);

        return view('admin.refund-detail', compact('refund'));
    }

    /**
     * Admin menyetujui refund (PENDING → APPROVED).
     * Backend memeriksa role admin (middleware), status pending, jumlah dari database,
     * lalu menyimpan approved_at + processed_by dan membuat notification untuk user.
     */
    public function approveRefund(Request $request, int $id): RedirectResponse
    {
        if (session('account_role') !== 'admin') {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin administrator.');
        }

        $refund = Refund::with(['order.user', 'payment'])->findOrFail($id);

        if (! $refund->isPending()) {
            return back()->with('error', 'Refund ini sudah diproses dan tidak dapat diubah lagi.');
        }

        // Gunakan nominal dari database sebagai patokan.
        $refundAmount = (int) $refund->refund_amount;
        $adjustmentReason = null;

        // Opsi penyesuaian nominal hanya untuk admin — disimpan terpisah.
        $request->validate([
            'refund_amount' => ['nullable', 'integer', 'min:0'],
            'adjustment_reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->filled('refund_amount') && $request->integer('refund_amount') > 0
            && $request->integer('refund_amount') !== $refundAmount) {
            $refundAmount = $request->integer('refund_amount');
            $adjustmentReason = $request->input('adjustment_reason') ?: 'Penyesuaian nominal oleh admin';
        }

        $refund->update([
            'status' => Refund::STATUS_APPROVED,
            'refund_amount' => $refundAmount,
            'adjustment_reason' => $adjustmentReason,
            'approved_at' => now(),
            'processed_by' => session('account_id'),
        ]);

        // Notification ke user pemilik booking.
        $this->notifyRefundOwner($refund, 'Refund Disetujui',
            "Refund booking #{$refund->order?->code} telah disetujui oleh admin sebesar Rp " . number_format($refundAmount, 0, ',', '.') . '.',
            '✅');

        return redirect()->route('admin.refund')
            ->with('success', "Refund {$refund->code} berhasil disetujui.");
    }

    /**
     * Admin menolak refund (PENDING → REJECTED). Alasan wajib diisi.
     */
    public function rejectRefund(Request $request, int $id): RedirectResponse
    {
        if (session('account_role') !== 'admin') {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin administrator.');
        }

        $refund = Refund::with(['order.user'])->findOrFail($id);

        if (! $refund->isPending()) {
            return back()->with('error', 'Refund ini sudah diproses dan tidak dapat diubah lagi.');
        }

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:2000'],
        ]);

        $refund->update([
            'status' => Refund::STATUS_REJECTED,
            'reject_reason' => $validated['reject_reason'],
            'rejected_at' => now(),
            'processed_by' => session('account_id'),
        ]);

        $this->notifyRefundOwner($refund, 'Refund Ditolak',
            "Refund booking #{$refund->order?->code} ditolak oleh admin. Alasan: {$validated['reject_reason']}",
            '❌');

        return redirect()->route('admin.refund')
            ->with('success', "Refund {$refund->code} telah ditolak.");
    }

    /**
     * Admin menyelesaikan pengembalian dana manual (APPROVED → COMPLETED).
     * Status COMPLETED baru dipakai setelah dana benar-benar dikembalikan.
     */
    public function completeRefund(Request $request, int $id): RedirectResponse
    {
        if (session('account_role') !== 'admin') {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin administrator.');
        }

        $refund = Refund::with(['order.user', 'payment'])->findOrFail($id);

        if ($refund->status !== Refund::STATUS_APPROVED) {
            return back()->with('error', 'Refund harus disetujui terlebih dahulu sebelum diselesaikan.');
        }

        $refund->update([
            'status' => Refund::STATUS_COMPLETED,
            'completed_at' => now(),
            'processed_by' => session('account_id'),
        ]);

        // Perbarui payment menjadi refunded di database yang sama.
        if ($refund->payment) {
            $refund->payment->update(['status' => 'refunded']);
        }

        $this->notifyRefundOwner($refund, 'Refund Selesai',
            "Refund booking #{$refund->order?->code} telah selesai diproses.",
            '💰');

        // Notifikasi aktivitas untuk tim admin (status refund selesai).
        \App\Services\AdminNotificationService::notifyAdmins(
            'refund_done',
            '💰 Refund Selesai',
            "Refund {$refund->code} untuk booking #{$refund->order?->code} telah selesai diproses dan dana berhasil dikembalikan.",
            '✅',
            route('admin.refund'),
        );

        return redirect()->route('admin.refund')
            ->with('success', "Refund {$refund->code} telah diselesaikan.");
    }

    /**
     * Buat notification database untuk user pemilik booking.
     * Notification hanya diterima oleh user yang memiliki booking tersebut.
     */
    private function notifyRefundOwner(Refund $refund, string $title, string $body, string $icon): void
    {
        if (! $refund->user) {
            return;
        }
        $refund->user->notify(new RefundStatusNotification('refund', $title, $body, $icon));
    }
}
