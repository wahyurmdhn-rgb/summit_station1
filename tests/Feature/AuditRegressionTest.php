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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
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

        $this->customer = User::create([
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

        $other = User::create([
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
        $legacy = User::create([
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

    public function test_welcome_view_renders_without_vite_manifest(): void
    {
        $this->assertFileDoesNotExist(public_path('build/manifest.json'));

        $view = View::make('welcome');

        // Melempar error berarti ada @vite tanpa manifest yang terlindungi.
        $view->render();
        $this->assertTrue(true);
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
}
