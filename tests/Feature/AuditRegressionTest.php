<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ReturnRecord;
use App\Models\User;
use App\Services\AdminNotificationService;
use App\Services\OrderStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regression tests untuk perbaikan dari audit keamanan & konsistensi data.
 *
 * Setiap test di sini guarding salah satu perbaikan spesifik supaya tidak
 * bisa "dibalik" tanpa test jadi merah.
 */
class AuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private int $adminId;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Admin::create([
            'name' => 'Admin Audit',
            'email' => 'admin.audit@summit.test',
            'password' => 'password',
        ]);
        $this->adminId = $admin->id_admin;

        $this->customer = User::forceCreate([
            'name' => 'Customer Audit',
            'email' => 'audit.customer@example.com',
            'username' => 'auditcust',
            'password' => 'password',
            'role' => 'customer',
            'status' => 'active',
        ]);
    }

    private function customerSession(array $overrides = []): array
    {
        return array_merge([
            'account_id' => $this->customer->id,
            'account_name' => $this->customer->name,
            'account_role' => 'customer',
        ], $overrides);
    }

    private function adminSession(array $overrides = []): array
    {
        return array_merge([
            'account_id' => $this->adminId,
            'account_name' => 'Admin Audit',
            'account_role' => 'admin',
        ], $overrides);
    }

    private function makePaidOrder(array $orderAttributes = [], array $paymentAttributes = []): Order
    {
        $order = Order::create(array_merge([
            'code' => 'RS-AUDIT-'.uniqid(),
            'user_id' => $this->customer->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(2)->endOfDay(),
            'subtotal' => 1000000,
            'service_fee' => 50000,
            'discount' => 0,
            'total' => 1050000,
            'status' => 'active',
        ], $orderAttributes));

        Payment::create(array_merge([
            'order_id' => $order->id,
            'method' => 'bank_transfer',
            'amount' => 1050000,
            'status' => 'success',
            'reference' => 'REF-AUDIT-'.uniqid(),
        ], $paymentAttributes));

        return $order;
    }

    // ---------------------------------------------------------------------
    // 1. Open redirect pada notifikasi admin
    // ---------------------------------------------------------------------

    private function createAdminNotification(string $url): DatabaseNotification
    {
        $notification = new DatabaseNotification([
            'id' => (string) Str::uuid(),
            'type' => AdminNotificationService::class,
            'notifiable_type' => Admin::class,
            'notifiable_id' => $this->adminId,
            'data' => ['url' => $url, 'title' => 'Uji redirect'],
            'read_at' => null,
        ]);
        $notification->save();

        return $notification;
    }

    public static function openRedirectPayloads(): array
    {
        return [
            'protocol relative' => ['//evil.example.com'],
            'backslash bypass' => ['/\\evil.example.com'],
            'https external' => ['https://evil.example.com/admin'],
            'http external' => ['http://evil.example.com'],
            'custom scheme' => ['javascript:alert(1)'],
            'data uri' => ['data:text/html,<script>alert(1)</script>'],
            'backslash after slash' => ['/admin\\/\\evil.com'],
        ];
    }

    #[DataProvider('openRedirectPayloads')]
    public function test_admin_notification_never_redirects_off_site(string $url): void
    {
        $notification = $this->createAdminNotification($url);

        $response = $this->withSession($this->adminSession())
            ->get(route('admin.notifications.open', $notification->id));

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_notification_allows_internal_path(): void
    {
        $notification = $this->createAdminNotification('/admin/penyewaan');

        $response = $this->withSession($this->adminSession())
            ->get(route('admin.notifications.open', $notification->id));

        $response->assertRedirect('/admin/penyewaan');
    }

    public function test_admin_notification_allows_same_host_absolute_url(): void
    {
        $notification = $this->createAdminNotification(config('app.url').'/admin/refund');

        $response = $this->withSession($this->adminSession())
            ->get(route('admin.notifications.open', $notification->id));

        $response->assertRedirect(config('app.url').'/admin/refund');
    }

    public function test_guest_and_customer_cannot_open_admin_notification(): void
    {
        $notification = $this->createAdminNotification('/admin/penyewaan');

        $this->get(route('admin.notifications.open', $notification->id))
            ->assertRedirect(route('admin.login'));

        $this->withSession($this->customerSession())
            ->get(route('admin.notifications.open', $notification->id))
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------------
    // 2. Nominal refund tidak boleh melebihi yang dibayarkan
    // ---------------------------------------------------------------------

    public function test_admin_cannot_approve_refund_above_paid_amount(): void
    {
        $order = $this->makePaidOrder();
        $refund = Refund::create([
            'code' => 'RF-OVER-001',
            'order_id' => $order->id,
            'user_id' => $this->customer->id,
            'original_amount' => 1050000,
            'refund_amount' => 9000000,
            'reason' => 'Uji nominal',
            'description' => 'Nominal sengaja melebihi pembayaran',
            'status' => Refund::STATUS_PENDING,
        ]);

        $this->withSession($this->adminSession())
            ->post(route('admin.refund.approve', $refund->id), [
                'refund_amount' => 9000000,
            ])
            ->assertRedirect();

        $this->assertSame(Refund::STATUS_PENDING, $refund->fresh()->status);
        $this->assertSame(9000000, (int) $refund->fresh()->refund_amount);
    }

    public function test_admin_refund_amount_is_capped_to_paid_amount_when_legacy_row_exceeds(): void
    {
        $order = $this->makePaidOrder();
        $refund = Refund::create([
            'code' => 'RF-LEGACY-001',
            'order_id' => $order->id,
            'user_id' => $this->customer->id,
            'original_amount' => 1050000,
            'refund_amount' => 9000000,
            'reason' => 'Data lama tidak konsisten',
            'description' => 'Nominal legacy melebihi pembayaran',
            'status' => Refund::STATUS_PENDING,
        ]);

        $this->withSession($this->adminSession())
            ->post(route('admin.refund.approve', $refund->id))
            ->assertRedirect(route('admin.refund'));

        $fresh = $refund->fresh();
        $this->assertSame(Refund::STATUS_APPROVED, $fresh->status);
        $this->assertLessThanOrEqual(1050000, (int) $fresh->refund_amount);
    }

    public function test_customer_cannot_send_refund_amount_from_form(): void
    {
        $order = $this->makePaidOrder();

        $this->withSession($this->customerSession())
            ->post(route('refund.store', $order->id), [
                'reason' => 'tidak_jadi',
                'description' => 'Mencoba mengarang nominal',
                'refund_amount' => 9999999,
            ])
            ->assertRedirect(route('history'));

        $refund = Refund::where('order_id', $order->id)->firstOrFail();

        // Nominal selalu berasal dari pembayaran di server, bukan dari form.
        $this->assertSame(1050000, (int) $refund->refund_amount);
        $this->assertSame(1050000, (int) $refund->original_amount);
    }

    // ---------------------------------------------------------------------
    // 3. Refund ganda / konflik status refund
    // ---------------------------------------------------------------------

    public function test_completed_refund_blocks_a_new_refund_request(): void
    {
        $order = $this->makePaidOrder();

        $this->withSession($this->customerSession())
            ->post(route('refund.store', $order->id), [
                'reason' => 'tidak_jadi',
                'description' => 'Pengajuan pertama',
            ])
            ->assertRedirect(route('history'));

        $refund = Refund::where('order_id', $order->id)->firstOrFail();
        $this->withSession($this->adminSession())
            ->post(route('admin.refund.approve', $refund->id))
            ->assertRedirect(route('admin.refund'));
        $this->withSession($this->adminSession())
            ->post(route('admin.refund.complete', $refund->id))
            ->assertRedirect(route('admin.refund'));

        $this->assertSame(Refund::STATUS_COMPLETED, $refund->fresh()->status);
        $this->assertSame('refunded', $refund->payment->fresh()->status);

        // Order sengaja dibiarkan 'active' seperti perilaku aplikasi.
        $this->assertSame('active', $order->fresh()->status);

        // Pengajuan kedua untuk nominal yang sama harus ditolak.
        $this->withSession($this->customerSession())
            ->post(route('refund.store', $order->id), [
                'reason' => 'tidak_jadi',
                'description' => 'Pengajuan kedua setelah dana dikembalikan',
            ])
            ->assertSessionHasErrors('refund');

        $this->assertSame(1, Refund::where('order_id', $order->id)->count());
    }

    public function test_rejected_refund_can_be_requested_again(): void
    {
        $order = $this->makePaidOrder();

        $this->withSession($this->customerSession())
            ->post(route('refund.store', $order->id), [
                'reason' => 'tidak_jadi',
                'description' => 'Pengajuan pertama',
            ]);

        $refund = Refund::where('order_id', $order->id)->firstOrFail();
        $this->withSession($this->adminSession())
            ->post(route('admin.refund.reject', $refund->id), [
                'reject_reason' => 'Bukti tidak memadai',
            ])
            ->assertRedirect(route('admin.refund'));

        $this->withSession($this->customerSession())
            ->post(route('refund.store', $order->id), [
                'reason' => 'tidak_jadi',
                'description' => 'Pengajuan ulang setelah ditolak',
            ])
            ->assertRedirect(route('history'));

        $this->assertSame(2, Refund::where('order_id', $order->id)->count());
    }

    public function test_refund_code_is_unique_across_many_requests(): void
    {
        $codes = [];

        for ($i = 0; $i < 5; $i++) {
            $order = $this->makePaidOrder();

            $this->withSession($this->customerSession())
                ->post(route('refund.store', $order->id), [
                    'reason' => 'tidak_jadi',
                    'description' => 'Pengajuan '.$i,
                ]);

            $codes[] = Refund::where('order_id', $order->id)->firstOrFail()->code;
        }

        $this->assertCount(5, array_unique($codes));
        $this->assertSame(5, Refund::whereIn('code', $codes)->count());
    }

    // ---------------------------------------------------------------------
    // 4. Bukti pengembalian customer tidak ditimpa foto inspeksi admin
    // ---------------------------------------------------------------------

    public function test_admin_inspection_photo_does_not_overwrite_customer_proof(): void
    {
        $order = $this->makePaidOrder();

        $record = ReturnRecord::create([
            'order_id' => $order->id,
            'user_id' => $this->customer->id,
            'status' => 'pending',
            'proof_path' => 'returns/customer-proof.jpg',
            'condition' => 'good',
        ]);

        $this->withSession($this->adminSession())
            ->post(route('admin.pengembalian.record', $order->id), [
                'condition' => 'good',
                'damage_cost' => 0,
                'inspection_photo' => UploadedFile::fake()->create('inspection.jpg', 40, 'image/jpeg'),
            ])
            ->assertRedirect();

        $record->refresh();

        $this->assertSame('returns/customer-proof.jpg', $record->proof_path);
        $this->assertNotNull($record->inspection_photo);
        $this->assertNotSame($record->proof_path, $record->inspection_photo);
    }

    public function test_inspection_photo_is_only_viewable_through_authorized_admin_route(): void
    {
        $order = $this->makePaidOrder();
        $record = ReturnRecord::create([
            'order_id' => $order->id,
            'user_id' => $this->customer->id,
            'status' => 'pending',
            'proof_path' => 'returns/customer-proof.jpg',
            'inspection_photo' => 'returns/inspection.jpg',
        ]);

        $this->withSession($this->adminSession())
            ->get(route('file.return-inspection-photo', $record->id))
            ->assertStatus(404);
    }

    // ---------------------------------------------------------------------
    // 5. Filter periode laporan = export CSV
    // ---------------------------------------------------------------------

    public function test_laporan_export_respects_the_same_period_filter(): void
    {
        $today = $this->makePaidOrder();
        $old = $this->makePaidOrder();

        // `created_at` tidak termasuk fillable Order, jadi harus ditulis langsung.
        DB::table('orders')
            ->where('id', $old->id)
            ->update(['created_at' => now()->subMonths(3)]);

        $response = $this->withSession($this->adminSession())
            ->get(route('admin.laporan.export', ['period' => 'this_month']));

        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString($today->code, $csv);
        $this->assertStringNotContainsString($old->code, $csv);

        // 'all' harus mengembalikan semuanya.
        $all = $this->withSession($this->adminSession())
            ->get(route('admin.laporan.export', ['period' => 'all']));

        $all->assertOk();
        $this->assertStringContainsString($old->code, $all->streamedContent());
    }

    public function test_laporan_page_and_export_use_identical_period_set(): void
    {
        $page = $this->withSession($this->adminSession())
            ->get(route('admin.laporan', ['period' => 'this_month']));

        $page->assertOk();
        $page->assertSee(route('admin.laporan.export', ['period' => 'this_month']), false);
    }

    // ---------------------------------------------------------------------
    // 6. Active Now hanya dari session driver database
    // ---------------------------------------------------------------------

    public function test_active_now_is_unavailable_when_session_driver_is_not_database(): void
    {
        config(['session.driver' => 'file']);

        $this->withSession($this->adminSession())
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('AKTIF SEKARANG')
            ->assertSee('session driver: file');
    }

    public function test_active_now_counts_active_customer_sessions_from_database(): void
    {
        config(['session.driver' => 'database']);

        $other = User::forceCreate([
            'name' => 'Customer Kedua',
            'email' => 'audit.customer2@example.com',
            'username' => 'auditcust2',
            'password' => 'password',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $payload = base64_encode(serialize([
            'account_id' => $this->customer->id,
            'account_name' => $this->customer->name,
            'account_role' => 'customer',
            '_token' => 'x',
        ]));

        $adminPayload = base64_encode(serialize([
            'account_id' => $this->adminId,
            'account_role' => 'admin',
        ]));

        foreach (['sess-a', 'sess-b'] as $sid) {
            DB::table('sessions')->insert([
                'id' => $sid,
                'user_id' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => $payload,
                'last_activity' => now()->getTimestamp(),
            ]);
        }

        // Sesi customer lain juga dihitung.
        DB::table('sessions')->insert([
            'id' => 'sess-c',
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode(serialize([
                'account_id' => $other->id,
                'account_role' => 'customer',
            ])),
            'last_activity' => now()->getTimestamp(),
        ]);

        // Sesi admin TIDAK boleh dihitung.
        DB::table('sessions')->insert([
            'id' => 'sess-d',
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => $adminPayload,
            'last_activity' => now()->getTimestamp(),
        ]);

        $this->withSession($this->adminSession())
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('AKTIF SEKARANG')
            ->assertSee('Keterlibatan waktu nyata');
    }

    // ---------------------------------------------------------------------
    // 7. Role pengguna tidak boleh 'admin'
    // ---------------------------------------------------------------------

    public function test_store_user_rejects_admin_role(): void
    {
        $this->withSession($this->adminSession())
            ->post(route('admin.users.store'), [
                'name' => 'Calon Pseudo Admin',
                'email' => 'pseudo.admin@example.com',
                'username' => 'pseudoadmin',
                'password' => 'rahasia123',
                'role' => 'admin',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'pseudo.admin@example.com']);
    }

    public function test_update_user_normalizes_invalid_legacy_role(): void
    {
        $legacy = User::forceCreate([
            'name' => 'Legacy Role',
            'email' => 'legacy.role@example.com',
            'username' => 'legacyrole',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->withSession($this->adminSession())
            ->put(route('admin.users.update', $legacy->id), [
                'name' => 'Legacy Role Updated',
                'email' => $legacy->email,
                'username' => $legacy->username,
                'role' => 'user',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertSame('user', $legacy->fresh()->role);
    }

    public function test_customer_cannot_reach_admin_user_management(): void
    {
        $this->withSession($this->customerSession())
            ->get(route('admin.users'))
            ->assertForbidden();
    }

    // ---------------------------------------------------------------------
    // 8. Halaman pembayaran tidak menampilkan data palsuan
    // ---------------------------------------------------------------------

    public function test_payment_page_does_not_render_fabricated_reference_or_timestamp(): void
    {
        $order = $this->makePaidOrder();

        $this->withSession($this->adminSession())
            ->get(route('admin.pembayaran'))
            ->assertOk()
            ->assertDontSee('7728399102-X')
            ->assertDontSee('2023-10-24 14:18')
            ->assertDontSee('99%');

        unset($order);
    }

    public function test_payment_page_shows_real_reference_when_payment_exists(): void
    {
        $order = $this->makePaidOrder();
        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $payment->update(['reference' => 'TRX-REAL-9A8B7C']);

        $this->withSession($this->adminSession())
            ->get(route('admin.pembayaran'))
            ->assertOk()
            ->assertSee('TRX-REAL-9A8B7C');
    }

    // ---------------------------------------------------------------------
    // 9. Review hanya untuk order berstatus completed
    // ---------------------------------------------------------------------

    public function test_review_rejected_for_non_completed_order_status(): void
    {
        $order = $this->makePaidOrder(['status' => 'active']);

        $this->withSession($this->customerSession())
            ->post(route('reviews.store'), [
                'order_id' => $order->id,
                'rating' => 5,
                'comment' => 'Uji status tidak valid',
            ])
            ->assertSessionHasErrors('review');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_accepted_for_completed_order(): void
    {
        $order = $this->makePaidOrder(['status' => 'completed']);
        $product = $this->makeProduct();

        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => 1,
            'days' => 1,
            'unit_price' => 100000,
            'subtotal' => 100000,
        ]);

        $this->withSession($this->customerSession())
            ->post(route('reviews.store'), [
                'order_id' => $order->id,
                'product_id' => $product->id,
                'rating' => 5,
                'comment' => 'Uji status completed',
            ]);

        $this->assertDatabaseCount('reviews', 1);
    }

    private function makeProduct(array $attributes = []): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'kategori-audit'],
            ['name' => 'Kategori Audit', 'description' => 'Kategori uji']
        );

        return Product::create(array_merge([
            'category_id' => $category->id,
            'sku' => 'SKU-AUDIT-'.uniqid(),
            'name' => 'Produk Audit',
            'description' => 'Deskripsi produk uji.',
            'main_image' => 'images/logo.png',
            'is_active' => true,
            'price_per_day' => 50000,
            'stock_total' => 5,
            'stock_available' => 5,
        ], $attributes));
    }

    // ---------------------------------------------------------------------
    // 10. Aset & Vite
    // ---------------------------------------------------------------------

    public function test_welcome_view_is_removed_and_no_view_uses_vite_directive(): void
    {
        // View bawaan Laravel tidak lagi dipakai: root -> HomeController@index.
        $this->assertFileDoesNotExist(resource_path('views/welcome.blade.php'));

        // Tidak ada manifest Vite yang di-build di repo ini.
        $this->assertFileDoesNotExist(public_path('build/manifest.json'));

        // Semua aset dibangun lewat public/css + public/js, jadi tidak boleh ada
        // satu pun view yang memakai direktif @vite (akan fatal tanpa manifest).
        $offenders = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')
                && str_contains((string) file_get_contents($file->getPathname()), '@vite')) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $offenders, 'View masih memakai direktif @vite tanpa manifest.');
    }

    public function test_home_page_renders_without_vite_manifest(): void
    {
        // Halaman utama harus tetap bisa dirender tanpa build aset Vite.
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $this->assertStringNotContainsString('@vite', $response->getContent());
    }

    public function test_asset_version_helper_is_stable_and_safe_for_missing_files(): void
    {
        $a = asset_v('images/logo.png');
        $b = asset_v('images/logo.png');

        $this->assertSame($a, $b, 'Versi aset harus stabil antar request.');
        $this->assertNotSame('', $a);
        $this->assertMatchesRegularExpression('/^\d+$/', $a);

        $missing = asset_v('images/tidak-ada-xyz.png');
        $this->assertSame('1', $missing);
    }

    public function test_no_view_uses_time_based_cache_busting(): void
    {
        $offenders = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            if (preg_match('/\?v=<\?php\s*echo\s*time\(\)/', $content)) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame([], $offenders, 'Masih ada view yang memakai time() untuk cache-busting.');
    }

    // ---------------------------------------------------------------------
    // 11. Tampilan produk
    // ---------------------------------------------------------------------

    public function test_product_features_are_shown_and_not_filtered_out(): void
    {
        $category = Category::create([
            'name' => 'Tenda Audit',
            'slug' => 'tenda-audit-'.uniqid(),
            'description' => 'Kategori uji',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-AUDIT-'.uniqid(),
            'name' => 'Tenda Audit 4P',
            'description' => 'Deskripsi produk uji.',
            'main_image' => 'images/logo.png',
            'features' => ['Waterproof', 'Weather Resistance', 'Structural Integrity'],
            'is_active' => true,
            'price_per_day' => 50000,
            'stock_total' => 5,
            'stock_available' => 5,
        ]);

        $this->get('/catalog/'.$product->id)
            ->assertOk()
            ->assertSee('Waterproof')
            ->assertSee('Weather Resistance')
            ->assertSee('Structural Integrity');
    }

    public function test_dashboard_total_products_counts_products_and_bundles(): void
    {
        $category = Category::create([
            'name' => 'Kategori Dashboard',
            'slug' => 'kategori-dashboard-'.uniqid(),
            'description' => 'Kategori uji',
        ]);

        Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-DASH-1',
            'name' => 'Produk Dashboard',
            'description' => 'Deskripsi',
            'main_image' => 'images/logo.png',
            'is_active' => true,
            'price_per_day' => 25000,
            'stock_total' => 1,
            'stock_available' => 1,
        ]);

        Bundle::create([
            'name' => 'Bundle Dashboard',
            'description' => 'Bundle uji',
            'price' => 100000,
            'image' => 'images/logo.png',
            'is_active' => true,
        ]);

        $this->withSession($this->adminSession())
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->assertSame(2, Product::count() + Bundle::count());
    }

    // ---------------------------------------------------------------------
    // 12. Session ID di-regenerate saat login & logout
    // ---------------------------------------------------------------------

    public function test_session_id_is_regenerated_on_customer_login_and_logout(): void
    {
        $before = $this->get(route('login'));
        $before->assertOk();
        $beforeId = session()->getId();

        $this->post(route('login'), [
            'email' => $this->customer->email,
            'password' => 'password',
        ])->assertRedirect();

        $afterLoginId = session()->getId();
        $this->assertNotSame(
            $beforeId,
            $afterLoginId,
            'Session ID harus berubah setelah login (mitigasi session fixation).'
        );
        $this->assertSame($this->customer->id, session('account_id'));

        $this->post(route('logout'));

        $this->assertNull(session('account_id'));
        $this->assertNotSame(
            $afterLoginId,
            session()->getId(),
            'Session ID harus berubah setelah logout.'
        );
    }

    public function test_session_id_is_regenerated_on_admin_login(): void
    {
        $admin = Admin::first();

        $this->get(route('admin.login'))->assertOk();
        $beforeId = session()->getId();

        $this->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertNotSame($beforeId, session()->getId());
        $this->assertSame('admin', session('account_role'));
    }

    // ---------------------------------------------------------------------
    // 13. Admin tidak bisa membuat booking (orders.user_id FK ke users)
    // ---------------------------------------------------------------------

    public function test_admin_cannot_create_order_through_customer_checkout(): void
    {
        $product = $this->makeProduct();

        $this->withSession($this->adminSession())
            ->withSession([
                'payment_deadline' => time() + 900,
                'cart_items' => [
                    1 => [
                        'id' => $product->id,
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'category' => 'Tents',
                        'subtitle' => 'Expedition',
                        'days' => 2,
                        'quantity' => 1,
                        'price_per_day' => 50000,
                        'subtotal' => 100000,
                        'image' => 'images/logo.png',
                        'selected' => true,
                    ],
                ],
            ])
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => UploadedFile::fake()->createWithContent('bukti.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('orders', 0);
    }

    // ---------------------------------------------------------------------
    // 14. Hardening P0: Legacy payment proof blocks open redirect & unsafe paths
    // ---------------------------------------------------------------------

    public function test_payment_proof_rejects_external_or_protocol_relative_legacy_urls(): void
    {
        $order = $this->makePaidOrder();
        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $payment->update(['proof_image' => 'https://evil.example.com/malicious.png']);

        $this->withSession($this->customerSession())
            ->get(route('file.payment-proof', $payment->id))
            ->assertNotFound();

        $payment->update(['proof_image' => '//evil.example.com/malicious.png']);
        $this->withSession($this->customerSession())
            ->get(route('file.payment-proof', $payment->id))
            ->assertNotFound();

        $payment->update(['proof_image' => 'javascript:alert(1)']);
        $this->withSession($this->customerSession())
            ->get(route('file.payment-proof', $payment->id))
            ->assertNotFound();
    }

    public function test_payment_proof_rejects_directory_traversal_paths(): void
    {
        $order = $this->makePaidOrder();
        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $payment->update(['proof_image' => '../../../../windows/win.ini']);

        $this->withSession($this->customerSession())
            ->get(route('file.payment-proof', $payment->id))
            ->assertNotFound();
    }

    // ---------------------------------------------------------------------
    // 15. Hardening P0: Customer routes block admin with 403
    // ---------------------------------------------------------------------

    public function test_customer_cart_routes_block_admin_user(): void
    {
        $this->withSession($this->adminSession())
            ->get(route('cart'))
            ->assertForbidden();

        $this->withSession($this->adminSession())
            ->post(route('cart.add'), ['product_id' => 1])
            ->assertForbidden();

        $this->withSession($this->adminSession())
            ->get(route('payment'))
            ->assertForbidden();
    }

    // =====================================================================
    // 16. Reservasi stok saat checkout (mencegah oversold)
    // =====================================================================

    private function fakeProof(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('bukti.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));
    }

    private function cartWithProduct(Product $product, array $overrides = []): array
    {
        return array_merge([
            'id' => $product->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'category' => 'Tents',
            'subtitle' => 'Expedition',
            'days' => 2,
            'quantity' => 1,
            'price_per_day' => 50000,
            'subtotal' => 100000,
            'image' => 'images/logo.png',
            'selected' => true,
        ], $overrides);
    }

    public function test_checkout_reserves_stock_immediately(): void
    {
        $product = $this->makeProduct(['stock_total' => 3, 'stock_available' => 3]);

        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [1 => $this->cartWithProduct($product, ['quantity' => 2])],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $this->assertDatabaseHas('orders', ['status' => 'pending']);
        // Reservasi terjadi di checkout, bukan menunggu admin.
        $this->assertSame(1, (int) $product->fresh()->stock_available);
    }

    public function test_admin_approval_does_not_deduct_stock_a_second_time(): void
    {
        $product = $this->makeProduct(['stock_total' => 3, 'stock_available' => 3]);

        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [1 => $this->cartWithProduct($product, ['quantity' => 2])],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $order = Order::where('status', 'pending')->firstOrFail();
        $this->assertSame(1, (int) $product->fresh()->stock_available);

        $this->withSession($this->adminSession())
            ->post(route('admin.penyewaan.confirm', $order->id));

        $this->assertSame('active', $order->fresh()->status);
        // Masih 1: stok tidak boleh berkurang dua kali.
        $this->assertSame(1, (int) $product->fresh()->stock_available);
    }

    public function test_second_checkout_for_last_unit_is_rejected(): void
    {
        $product = $this->makeProduct(['stock_total' => 1, 'stock_available' => 1]);

        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [1 => $this->cartWithProduct($product, ['quantity' => 1])],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(0, (int) $product->fresh()->stock_available);

        // Pelanggan kedua: unit terakhir sudah di-reserve order pertama.
        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [1 => $this->cartWithProduct($product, ['quantity' => 1])],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        // Checkout kedua harus ditolak karena stok habis.
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(0, (int) $product->fresh()->stock_available);
    }

    public function test_shared_bundle_member_demand_is_aggregated_and_cannot_oversell(): void
    {
        // Satu produk dipakai dua paket, masing-masing butuh 1 unit, stok hanya 1.
        $shared = $this->makeProduct(['stock_total' => 1, 'stock_available' => 1]);
        $other = $this->makeProduct(['stock_total' => 5, 'stock_available' => 5]);

        $bundleA = Bundle::create(['name' => 'Paket A', 'description' => 'A', 'price' => 90000, 'image' => 'images/logo.png', 'is_active' => true]);
        $bundleB = Bundle::create(['name' => 'Paket B', 'description' => 'B', 'price' => 90000, 'image' => 'images/logo.png', 'is_active' => true]);
        $bundleA->products()->attach([$shared->id => ['quantity' => 1], $other->id => ['quantity' => 1]]);
        $bundleB->products()->attach([$shared->id => ['quantity' => 1], $other->id => ['quantity' => 1]]);

        $cartItem = fn (Bundle $bundle) => [
            'id' => $bundle->id,
            'bundle_id' => $bundle->id,
            'is_bundle' => true,
            'name' => $bundle->name,
            'category' => 'Bundles',
            'subtitle' => 'Package',
            'days' => 2,
            'quantity' => 1,
            'price_per_day' => 90000,
            'subtotal' => 180000,
            'image' => 'images/logo.png',
            'selected' => true,
        ];

        // Kedua paket ditolak: total permintaan 2 unit untuk stok 1.
        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [1 => $cartItem($bundleA), 2 => $cartItem($bundleB)],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, (int) $shared->fresh()->stock_available);
    }

    public function test_rejecting_a_pending_order_releases_reserved_stock(): void
    {
        $product = $this->makeProduct(['stock_total' => 2, 'stock_available' => 2]);

        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [1 => $this->cartWithProduct($product, ['quantity' => 2])],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $this->assertSame(0, (int) $product->fresh()->stock_available);

        $order = Order::where('status', 'pending')->firstOrFail();

        $this->withSession($this->adminSession())
            ->post(route('admin.penyewaan.reject', $order->id));

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(2, (int) $product->fresh()->stock_available, 'Reservasi pending harus dilepas saat ditolak.');
    }

    public function test_repeated_release_never_inflates_stock_above_stock_total(): void
    {
        $product = $this->makeProduct(['stock_total' => 2, 'stock_available' => 2]);

        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [1 => $this->cartWithProduct($product, ['quantity' => 2])],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $order = Order::where('status', 'pending')->firstOrFail();
        $this->assertSame(0, (int) $product->fresh()->stock_available);

        $service = app(OrderStockService::class);

        // Lepas berulang: hanya hasil pertama yang boleh mengembalikan stok,
        // dan ketersediaannya tidak boleh melewati stock_total.
        $service->releaseReservedStock($order);
        $service->releaseReservedStock($order);
        $service->releaseReservedStock($order);

        $this->assertSame(2, (int) $product->fresh()->stock_available);
    }

    // =====================================================================
    // 17. Order pending kedaluwarsa otomatis
    // =====================================================================

    public function test_stale_pending_order_is_cancelled_and_stock_released(): void
    {
        $product = $this->makeProduct(['stock_total' => 2, 'stock_available' => 2]);

        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [1 => $this->cartWithProduct($product, ['quantity' => 2])],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $order = Order::where('status', 'pending')->firstOrFail();
        $this->assertSame(0, (int) $product->fresh()->stock_available);

        // Dipaksa jadi berumur 25 jam.
        Order::whereKey($order->id)->update(['created_at' => now()->subHours(25)]);

        $this->artisan('orders:expire-pending --hours=24')->assertSuccessful();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(2, (int) $product->fresh()->stock_available, 'Stok harus kembali setelah order kedaluwarsa.');
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'failed']);
    }

    public function test_recent_pending_order_is_not_expired(): void
    {
        $product = $this->makeProduct(['stock_total' => 2, 'stock_available' => 2]);

        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [1 => $this->cartWithProduct($product, ['quantity' => 2])],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $order = Order::where('status', 'pending')->firstOrFail();

        $this->artisan('orders:expire-pending --hours=24')->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(0, (int) $product->fresh()->stock_available);
    }

    public function test_expired_cancelled_order_cannot_be_resurrected_by_payment_approval(): void
    {
        $product = $this->makeProduct(['stock_total' => 2, 'stock_available' => 2]);

        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [1 => $this->cartWithProduct($product, ['quantity' => 2])],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $order = Order::where('status', 'pending')->firstOrFail();
        $payment = Payment::where('order_id', $order->id)->firstOrFail();

        // Dibuat 25 jam lalu; perintah yang harus membatalkannya.
        Order::whereKey($order->id)->update(['created_at' => now()->subHours(25)]);

        $this->artisan('orders:expire-pending --hours=24')->assertSuccessful();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(2, (int) $product->fresh()->stock_available);

        // Admin mencoba menyetujui pembayaran order yang sudah kedaluwarsa.
        $this->withSession($this->adminSession())
            ->post(route('admin.pembayaran.approve', $payment->id))
            ->assertSessionHas('error');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(2, (int) $product->fresh()->stock_available);
    }

    // =====================================================================
    // 18. Konsistensi rentang tanggal di checkout
    // =====================================================================

    public function test_checkout_rejects_mixed_dated_and_undated_cart(): void
    {
        $product = $this->makeProduct(['stock_total' => 3, 'stock_available' => 3]);

        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [
                1 => $this->cartWithProduct($product, [
                    'rent_start' => '2026-10-10',
                    'rent_end' => '2026-10-12',
                ]),
                // Item kedua tanpa tanggal -> tidak konsisten dengan item pertama.
                2 => $this->cartWithProduct($product),
            ],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(3, (int) $product->fresh()->stock_available);
    }

    public function test_checkout_rejects_item_with_different_date_range(): void
    {
        $product = $this->makeProduct(['stock_total' => 3, 'stock_available' => 3]);

        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [
                1 => $this->cartWithProduct($product, ['rent_start' => '2026-10-10', 'rent_end' => '2026-10-12']),
                2 => $this->cartWithProduct($product, ['rent_start' => '2026-11-01', 'rent_end' => '2026-11-03']),
            ],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_return_date_before_start_date(): void
    {
        $product = $this->makeProduct(['stock_total' => 3, 'stock_available' => 3]);

        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [
                1 => $this->cartWithProduct($product, ['rent_start' => '2026-10-12', 'rent_end' => '2026-10-10']),
            ],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_accepts_identical_date_range_and_stores_matching_days(): void
    {
        $product = $this->makeProduct(['stock_total' => 3, 'stock_available' => 3]);

        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [
                1 => $this->cartWithProduct($product, ['rent_start' => '2026-10-10', 'rent_end' => '2026-10-12']),
                2 => $this->cartWithProduct($product, ['rent_start' => '2026-10-10', 'rent_end' => '2026-10-12']),
            ],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $order = Order::firstOrFail();
        $this->assertSame('2026-10-10', $order->rent_start->toDateString());
        $this->assertSame('2026-10-12', $order->rent_end->toDateString());
    }

    public function test_order_total_uses_database_price_not_session_price(): void
    {
        $product = $this->makeProduct([
            'stock_total' => 3,
            'stock_available' => 3,
            'price_per_day' => 50000,
        ]);

        // Session memalsukan harga 1 rupiah.
        $this->withSession($this->customerSession([
            'payment_deadline' => time() + 900,
            'cart_items' => [1 => $this->cartWithProduct($product, [
                'price_per_day' => 1,
                'quantity' => 2,
                'days' => 2,
            ])],
        ]))
            ->post(route('payment.process'), [
                'payment_method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
            ]);

        $order = Order::firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->assertSame(50000, (int) $item->unit_price);
        // 50000 x 2 hari x 2 unit = 200.000 (+ 25.000 biaya layanan)
        $this->assertSame(200000, (int) $order->subtotal);
        $this->assertSame(225000, (int) $order->total);
    }

    // =====================================================================
    // 19. Revalidasi session admin
    // =====================================================================

    public function test_admin_session_is_rejected_after_account_is_deactivated(): void
    {
        $this->withSession($this->adminSession())
            ->get(route('admin.dashboard'))
            ->assertStatus(200);

        Admin::whereKey($this->adminId)->update(['status' => 'inactive']);

        $this->withSession($this->adminSession())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_status_column_is_not_null_and_defaults_to_active(): void
    {
        // `status` tidak boleh bisa NULL: null berarti "tidak aktif" menurut
        // EnsureAdmin, jadi kolomnya wajib NOT NULL dengan default 'active'.
        $this->assertTrue(
            Schema::hasColumn('admin', 'status'),
            'Kolom admin.status harus ada.'
        );

        $this->expectException(QueryException::class);
        DB::table('admin')->where('id_admin', $this->adminId)->update(['status' => null]);
    }

    public function test_admin_record_created_without_status_defaults_to_active(): void
    {
        $fresh = Admin::create([
            'name' => 'Tanpa Status',
            'email' => 'tanpa.status@summit.test',
            'password' => 'password',
        ]);

        $this->assertSame('active', $fresh->fresh()->status);
    }

    public function test_admin_session_is_rejected_when_record_is_deleted(): void
    {
        Admin::whereKey($this->adminId)->delete();

        $this->withSession($this->adminSession())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }
}
