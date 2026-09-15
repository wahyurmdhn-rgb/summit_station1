<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Admin Summit',
            'email' => 'admin@summit.test',
            'password' => Hash::make('password123'),
        ]);

        $this->user = User::create([
            'name' => 'Wahyu Pratama',
            'username' => 'wahyu',
            'email' => 'wahyu@summit.id',
            'domicile' => 'Jakarta',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Tents & Shelters',
            'slug' => 'tents-shelters',
            'description' => 'Expedition grade tents',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-TEN-001',
            'name' => 'Apex Sentinel 4P',
            'subtitle' => '4-Season Expedition Tent',
            'price_per_day' => 125000,
            'stock_total' => 10,
            'stock_available' => 10,
            'is_active' => true,
        ]);
    }

    /**
     * TEST 1 & 2: Penyewaan/booking + upload bukti pembayaran
     * menghasilkan notifikasi untuk admin.
     */
    public function test_booking_and_payment_upload_create_admin_notifications(): void
    {
        Storage::fake('public');

        $response = $this->withSession([
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
            'payment_deadline' => time() + 300,
            'cart_items' => [
                $this->product->id => [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'category' => 'Tents & Shelters',
                    'subtitle' => '4-Season Expedition Tent',
                    'days' => 3,
                    'quantity' => 1,
                    'price_per_day' => 125000,
                    'subtotal' => 375000,
                    'image' => 'tent.jpg',
                ],
            ],
        ])->post('/payment/process', [
            'payment_method' => 'qris',
            'proof' => UploadedFile::fake()->create('proof.jpg', 100),
        ]);

        $response->assertRedirect('/history');

        // Admin harus menerima 2 notifikasi: Penyewaan Baru + Pembayaran Baru.
        $notifs = $this->admin->notifications()->orderBy('created_at')->get();
        $this->assertCount(2, $notifs);

        $titles = $notifs->map(fn ($n) => data_get($n->data, 'title'))->values()->all();
        $this->assertContains('🔔 Penyewaan Baru', $titles);
        $this->assertContains('🔔 Pembayaran Baru', $titles);

        $booking = $notifs->first(fn ($n) => data_get($n->data, 'type') === 'booking');
        $body = data_get($booking->data, 'body');
        $this->assertStringContainsString('wahyu', $body);
        $this->assertStringContainsString('Apex Sentinel 4P', $body);
        $this->assertStringContainsString('pending', $body);

        $paymentNotif = $notifs->first(fn ($n) => data_get($n->data, 'type') === 'payment');
        $this->assertStringContainsString('wahyu', data_get($paymentNotif->data, 'body'));
        $this->assertStringContainsString('Rp 400.000', data_get($paymentNotif->data, 'body'));

        // Kedua notifikasi belum dibaca (unread).
        $this->assertEquals(2, $this->admin->unreadNotifications()->count());
    }

    /**
     * TEST 3: Admin membuka notifikasi -> berubah menjadi Read.
     */
    public function test_admin_opening_notification_marks_it_read(): void
    {
        $this->admin->notify(new \App\Notifications\AdminActivityNotification(
            'booking',
            '🔔 Penyewaan Baru',
            'Body test',
            '📦',
            route('admin.penyewaan'),
        ));

        $notif = $this->admin->notifications()->first();
        $this->assertNotNull($notif);
        $this->assertNull($notif->read_at);

        $this->withSession([
            'account_id' => $this->admin->getKey(),
            'account_name' => $this->admin->name,
            'account_role' => 'admin',
        ])->get(route('admin.notifications.open', $notif->id))
            ->assertRedirect(route('admin.penyewaan'));

        $this->assertNotNull($this->admin->notifications()->find($notif->id)->read_at);
        $this->assertEquals(0, $this->admin->unreadNotifications()->count());
    }

    /**
     * TEST 4: Badge menghitung notifikasi unread; setelah "tandai semua dibaca" jadi 0.
     */
    public function test_admin_dashboard_badge_and_mark_all_read(): void
    {
        foreach (['booking', 'payment', 'return'] as $i => $type) {
            $this->admin->notify(new \App\Notifications\AdminActivityNotification(
                $type,
                '🔔 ' . ucfirst($type) . ' Baru',
                'Body ' . $type,
                '🔔',
                null,
            ));
        }
        $this->assertEquals(3, $this->admin->unreadNotifications()->count());

        $dashboard = $this->withSession([
            'account_id' => $this->admin->getKey(),
            'account_name' => $this->admin->name,
            'account_role' => 'admin',
        ])->get(route('admin.dashboard'));

        $dashboard->assertOk();
        $dashboard->assertSee('admin-notif-badge', false);

        // Tandai semua dibaca.
        $this->withSession([
            'account_id' => $this->admin->getKey(),
            'account_name' => $this->admin->name,
            'account_role' => 'admin',
        ])->post(route('admin.notifications.readAll'))
            ->assertRedirect();

        $this->assertEquals(0, $this->admin->unreadNotifications()->count());
        $this->assertEquals(3, $this->admin->notifications()->count());
    }

    /**
     * TEST 5: Customer tidak boleh mengakses endpoint notifikasi admin.
     */
    public function test_customer_cannot_access_admin_notification_endpoints(): void
    {
        $this->admin->notify(new \App\Notifications\AdminActivityNotification(
            'booking',
            '🔔 Penyewaan Baru',
            'Body rahasia admin',
            '📦',
            route('admin.penyewaan'),
        ));
        $notif = $this->admin->notifications()->first();

        $session = [
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
        ];

        // Buka notifikasi admin sebagai customer -> 403.
        $this->withSession($session)
            ->get(route('admin.notifications.open', $notif->id))
            ->assertStatus(403);

        // Tandai semua dibaca sebagai customer -> 403 (diblokir middleware admin).
        $this->withSession($session)
            ->post(route('admin.notifications.readAll'))
            ->assertStatus(403);

        // Notifikasi admin tetap tidak dibaca (tidak bocor/berubah oleh customer).
        $this->assertNull($this->admin->notifications()->find($notif->id)->read_at);
    }

    /**
     * Notifikasi admin tidak boleh muncul di daftar notifikasi customer.
     */
    public function test_customer_does_not_see_admin_notifications(): void
    {
        $this->admin->notify(new \App\Notifications\AdminActivityNotification(
            'booking',
            '🔔 Penyewaan Baru',
            'Body admin',
            '📦',
            route('admin.penyewaan'),
        ));

        // Notifikasi milik admin, bukan customer.
        $this->assertEquals(0, $this->user->notifications()->count());
        $this->assertEquals(1, $this->admin->unreadNotifications()->count());
    }

    /**
     * Sidebar badge menghitung item yang benar-benar butuh perhatian admin
     * (order pending, pembayaran pending, pengembalian belum diinspeksi, refund pending).
     */
    public function test_sidebar_badge_counts_reflect_real_database_statuses(): void
    {
        // Order pending -> badge penyewaan.
        Order::create([
            'code' => 'RS-BADGE-001',
            'user_id' => $this->user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(2)->endOfDay(),
            'subtotal' => 200000,
            'service_fee' => 10000,
            'discount' => 0,
            'total' => 210000,
            'status' => 'pending',
        ]);

        // Pembayaran pending -> badge pembayaran.
        $order = Order::create([
            'code' => 'RS-BADGE-002',
            'user_id' => $this->user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(2)->endOfDay(),
            'subtotal' => 300000,
            'total' => 300000,
            'status' => 'active',
        ]);
        Payment::create([
            'order_id' => $order->id,
            'method' => 'bank_transfer',
            'amount' => 300000,
            'status' => 'pending',
        ]);

        // Refund pending -> badge refund.
        \App\Models\Refund::create([
            'code' => 'RF-BADGE-001',
            'order_id' => $order->id,
            'user_id' => $this->user->id,
            'status' => \App\Models\Refund::STATUS_PENDING,
            'original_amount' => 300000,
            'refund_amount' => 300000,
            'reason' => 'Tidak jadi menggunakan barang',
        ]);

        // Pengembalian menunggu inspeksi (condition NULL) -> badge pengembalian.
        $pendingReturn = Order::create([
            'code' => 'RS-BADGE-003',
            'user_id' => $this->user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(1)->endOfDay(),
            'subtotal' => 150000,
            'total' => 150000,
            'status' => 'active',
        ]);
        \App\Models\ReturnRecord::create([
            'order_id' => $pendingReturn->id,
            'status' => 'pending',
            'returned_at' => now(),
        ]);

        $counts = \App\Services\AdminNotificationService::sidebarBadgeCounts();

        $this->assertEquals(1, $counts['penyewaan']);
        $this->assertEquals(1, $counts['pembayaran']);
        $this->assertEquals(1, $counts['pengembalian']);
        $this->assertEquals(1, $counts['refund']);
    }

    /**
     * Bell notifikasi GLOBAL + badge sidebar muncul di halaman admin selain dasbor
     * (mis. /admin/alat) via View Composer.
     */
    public function test_global_bell_and_sidebar_badges_appear_on_non_dashboard_pages(): void
    {
        // Satu notifikasi belum dibaca -> bell badge & badge menu Notifikasi tampil.
        $this->admin->notify(new \App\Notifications\AdminActivityNotification(
            'payment',
            '🔔 Pembayaran Baru',
            'Body global',
            '💳',
            route('admin.pembayaran'),
        ));

        // Satu order pending -> badge sidebar "Penyewaan" tampil.
        Order::create([
            'code' => 'RS-GLB-001',
            'user_id' => $this->user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(2)->endOfDay(),
            'subtotal' => 200000,
            'total' => 200000,
            'status' => 'pending',
        ]);

        $response = $this->withSession([
            'account_id' => $this->admin->getKey(),
            'account_name' => $this->admin->name,
            'account_role' => 'admin',
        ])->get(route('admin.alat'));

        $response->assertOk();

        // Bell global dengan badge unread.
        $response->assertSee('admin-notif-wrapper', false);
        $response->assertSee('admin-notif-badge', false);
        $response->assertSee('admin-sidebar-badge', false);

        // Tombol "Lihat semua notifikasi" menuju halaman riwayat.
        $response->assertSee(route('admin.notifications.index'), false);
    }

    /**
     * Halaman riwayat notifikasi /admin/notifikasi merender daftar, filter, dan pagination.
     */
    public function test_notification_history_page_renders_filters_and_pagination(): void
    {
        foreach (['booking', 'payment', 'return'] as $i => $type) {
            $this->admin->notify(new \App\Notifications\AdminActivityNotification(
                $type,
                '🔔 ' . ucfirst($type) . ' Baru',
                'Body ' . $type,
                '🔔',
                null,
            ));
        }
        // Tandai notifikasi "booking" sebagai sudah dibaca.
        $bookingNotif = $this->admin->notifications()->get()
            ->first(fn ($n) => data_get($n->data, 'type') === 'booking');
        $this->assertNotNull($bookingNotif);
        $bookingNotif->markAsRead();

        $session = [
            'account_id' => $this->admin->getKey(),
            'account_name' => $this->admin->name,
            'account_role' => 'admin',
        ];

        // Filter "all".
        $all = $this->withSession($session)->get(route('admin.notifications.index', ['filter' => 'all']));
        $all->assertOk();
        $all->assertSee('Riwayat Notifikasi');
        $all->assertSee('Semua');
        $all->assertSee('Belum Dibaca');
        $all->assertSee('Sudah Dibaca');
        $all->assertSee('🔔 Booking Baru', false);
        $all->assertSee('admin-notif-item', false);

        // Filter "unread" hanya menampilkan yang belum dibaca.
        $unread = $this->withSession($session)->get(route('admin.notifications.index', ['filter' => 'unread']));
        $unread->assertOk();
        $unread->assertSee('Body payment');
        $unread->assertSee('Body return');
        // Validasi via koleksi view: hanya payment & return (bukan booking yang sudah dibaca).
        $unread->assertViewHas('notifications', function ($notifs) {
            $types = $notifs->pluck('data.type')->all();
            sort($types);
            return $types === ['payment', 'return'];
        });

        // Filter "read" hanya menampilkan yang sudah dibaca.
        $read = $this->withSession($session)->get(route('admin.notifications.index', ['filter' => 'read']));
        $read->assertOk();
        $read->assertSee('Body booking');
        // Validasi via koleksi view: hanya booking yang sudah dibaca.
        $read->assertViewHas('notifications', function ($notifs) {
            $types = $notifs->pluck('data.type')->all();
            return $types === ['booking'];
        });

        // Customer tidak boleh akses halaman riwayat notifikasi admin.
        $this->withSession([
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
        ])->get(route('admin.notifications.index'))->assertStatus(403);
    }

    /**
     * Pengajuan refund membuat notifikasi aktivitas untuk admin ("Pengajuan Refund").
     */
    public function test_refund_submission_creates_admin_notification(): void
    {
        $order = Order::create([
            'code' => 'RS-RF-NOTIF',
            'user_id' => $this->user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(3)->endOfDay(),
            'subtotal' => 1000000,
            'service_fee' => 50000,
            'discount' => 0,
            'total' => 1050000,
            'status' => 'active',
        ]);
        Payment::create([
            'order_id' => $order->id,
            'method' => 'bank_transfer',
            'amount' => 1050000,
            'status' => 'success',
        ]);

        $this->withSession([
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
        ])->post('/history/' . $order->id . '/refund', [
            'reason' => 'tidak_jadi',
            'description' => 'Tidak jadi menggunakan barang.',
        ])->assertRedirect(route('history'));

        $notif = $this->admin->notifications()->latest()->first();
        $this->assertNotNull($notif);
        $this->assertSame('refund', data_get($notif->data, 'type'));
        $this->assertSame('💳 Pengajuan Refund', data_get($notif->data, 'title'));
        $this->assertStringContainsString('tidak jadi', strtolower(data_get($notif->data, 'body')));
    }

    /**
     * Merekam pengembalian barang menciptakan notifikasi aktivitas untuk admin.
     */
    public function test_return_record_creates_admin_notification(): void
    {
        $order = Order::create([
            'code' => 'RS-RTN-NOTIF',
            'user_id' => $this->user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(2)->endOfDay(),
            'subtotal' => 250000,
            'total' => 250000,
            'status' => 'active',
        ]);

        $this->withSession([
            'account_id' => $this->admin->getKey(),
            'account_name' => $this->admin->name,
            'account_role' => 'admin',
        ])->post(route('admin.pengembalian.record', $order->id), [
            'condition' => 'good',
            'inspection_note' => 'Baik, siap disewakan lagi.',
            'damage_cost' => 0,
        ])->assertRedirect(route('admin.pengembalian'));

        $notif = $this->admin->notifications()->latest()->first();
        $this->assertNotNull($notif);
        $this->assertSame('return', data_get($notif->data, 'type'));
        $this->assertSame('📦 Pengembalian Barang', data_get($notif->data, 'title'));
        $this->assertStringContainsString('RS-RTN-NOTIF', data_get($notif->data, 'body'));
        $this->assertStringContainsString('Kondisi Baik', data_get($notif->data, 'body'));
    }

    /**
     * Menyelesaikan refund menciptakan notifikasi aktivitas untuk admin.
     */
    public function test_refund_complete_creates_admin_notification(): void
    {
        $order = Order::create([
            'code' => 'RS-RFD-COMP',
            'user_id' => $this->user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(3)->endOfDay(),
            'subtotal' => 1000000,
            'total' => 1000000,
            'status' => 'active',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'bank_transfer',
            'amount' => 1000000,
            'status' => 'success',
        ]);
        $refund = \App\Models\Refund::create([
            'code' => 'RF-COMP-001',
            'order_id' => $order->id,
            'user_id' => $this->user->id,
            'status' => \App\Models\Refund::STATUS_APPROVED,
            'original_amount' => 1000000,
            'refund_amount' => 1000000,
            'reason' => 'Batal',
            'payment_id' => $payment->id,
        ]);

        $this->withSession([
            'account_id' => $this->admin->getKey(),
            'account_name' => $this->admin->name,
            'account_role' => 'admin',
        ])->post(route('admin.refund.complete', $refund->id))
            ->assertRedirect(route('admin.refund'));

        $notif = $this->admin->notifications()->where('data->type', 'refund_done')->latest()->first();
        $this->assertNotNull($notif);
        $this->assertSame('💰 Refund Selesai', data_get($notif->data, 'title'));
        $this->assertStringContainsString('selesai diproses', data_get($notif->data, 'body'));
    }
}
