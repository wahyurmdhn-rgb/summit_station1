<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RentalStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private User $owner;

    private User $otherUser;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Admin Summit',
            'email' => 'admin.rental@summit.test',
            'password' => Hash::make('password123'),
        ]);

        $this->owner = User::create([
            'name' => 'Pemilik Booking',
            'username' => 'pemilik',
            'email' => 'pemilik@summit.test',
            'domicile' => 'Bandung',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->otherUser = User::create([
            'name' => 'User Lain',
            'username' => 'userlain',
            'email' => 'userlain@summit.test',
            'domicile' => 'Bogor',
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
            'sku' => 'SS-TEN-002',
            'name' => 'Apex Summit 4P',
            'subtitle' => '4-Season Expedition Tent',
            'price_per_day' => 150000,
            'stock_total' => 10,
            'stock_available' => 10,
            'is_active' => true,
        ]);
    }

    private function adminSession(array $overrides = []): array
    {
        return array_merge([
            'account_id' => $this->admin->getKey(),
            'account_name' => $this->admin->name,
            'account_role' => 'admin',
        ], $overrides);
    }

    private function customerSession(User $user): array
    {
        return [
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_username' => $user->username,
            'account_role' => 'customer',
        ];
    }

    private function makePendingOrder(User $user, string $code): Order
    {
        $order = Order::create([
            'code' => $code,
            'user_id' => $user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(2)->endOfDay(),
            'subtotal' => 300000,
            'service_fee' => 20000,
            'discount' => 0,
            'total' => 320000,
            'status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'quantity' => 1,
            'days' => 2,
            'unit_price' => 150000,
            'subtotal' => 300000,
        ]);

        return $order->load('items', 'user');
    }

    /**
     * KOMPONEN UTAMA 1 — Admin menerima penyewaan:
     * status berubah menjadi active DAN user pemilik booking menerima notifikasi.
     */
    public function test_admin_accepting_rental_notifies_order_owner(): void
    {
        $order = $this->makePendingOrder($this->owner, 'RS-ACC-0001');

        $response = $this->withSession($this->adminSession())
            ->post(route('admin.penyewaan.confirm', $order->id));

        $response->assertRedirect(route('admin.penyewaan'));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'active']);

        $this->assertEquals(1, $this->owner->notifications()->count(), 'Pemilik booking harus menerima 1 notifikasi.');

        $notif = $this->owner->notifications()->latest()->first();
        $data = $notif->data;
        $this->assertSame('rental_accepted', $data['type']);
        $this->assertSame('🔔 Penyewaan Diterima', $data['title']);
        $this->assertStringContainsString('diterima oleh admin', $data['body']);
        $this->assertStringContainsString('RS-ACC-0001', $data['body']);
        $this->assertStringContainsString('Apex Summit 4P', $data['body']);
        $this->assertSame(route('history', ['status' => 'active']), $data['url']);

        // Belum dibaca.
        $this->assertNull($notif->read_at);
        $this->assertEquals(1, $this->owner->unreadNotifications()->count());
    }

    /**
     * KOMPONEN UTAMA 2 — Admin menolak penyewaan:
     * status berubah menjadi cancelled DAN user pemilik booking menerima
     * notifikasi yang menyertakan alasan penolakan.
     */
    public function test_admin_rejecting_rental_notifies_owner_with_reason(): void
    {
        $order = $this->makePendingOrder($this->owner, 'RS-REJ-0001');

        $response = $this->withSession($this->adminSession())
            ->post(route('admin.penyewaan.reject', $order->id), [
                'reason' => 'Bukti pembayaran tidak sesuai.',
            ]);

        $response->assertRedirect(route('admin.penyewaan'));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);

        $notif = $this->owner->notifications()->latest()->first();
        $this->assertNotNull($notif);
        $data = $notif->data;
        $this->assertSame('rental_rejected', $data['type']);
        $this->assertSame('🔔 Penyewaan Ditolak', $data['title']);
        $this->assertStringContainsString('ditolak oleh admin', $data['body']);
        $this->assertStringContainsString('Bukti pembayaran tidak sesuai.', $data['body']);
        $this->assertStringContainsString('RS-REJ-0001', $data['body']);
        $this->assertSame(route('history', ['status' => 'cancelled']), $data['url']);
        $this->assertNull($notif->read_at);
    }

    /**
     * Penolakan tanpa alasan tetap mengirim notifikasi (tanpa baris "Alasan").
     */
    public function test_rejected_rental_without_reason_still_notifies_owner(): void
    {
        $order = $this->makePendingOrder($this->owner, 'RS-REJ-0002');

        $this->withSession($this->adminSession())
            ->post(route('admin.penyewaan.reject', $order->id))
            ->assertRedirect(route('admin.penyewaan'));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);

        $notif = $this->owner->notifications()->latest()->first();
        $this->assertNotNull($notif);
        $this->assertSame('rental_rejected', $notif->data['type']);
        $this->assertStringContainsString('No. Booking: #RS-REJ-0002', $notif->data['body']);
    }

    /**
     * KEAMANAN — Notifikasi hanya dikirim ke pemilik booking, bukan ke user lain.
     */
    public function test_notification_goes_only_to_order_owner(): void
    {
        $order = $this->makePendingOrder($this->owner, 'RS-TGT-0001');

        $this->withSession($this->adminSession())
            ->post(route('admin.penyewaan.confirm', $order->id))
            ->assertRedirect(route('admin.penyewaan'));

        $this->assertEquals(1, $this->owner->notifications()->count());
        $this->assertEquals(0, $this->otherUser->notifications()->count());
        $this->assertEquals(1, $this->owner->unreadNotifications()->count());
        $this->assertEquals(0, $this->otherUser->unreadNotifications()->count());
    }

    /**
     * KOMPONEN UNREAD — Notifikasi baru status unread; setelah dibuka menjadi read,
     * dan "tandai semua dibaca" menjadikan badge 0 (nol).
     */
    public function test_notification_is_unread_and_marked_read_when_opened(): void
    {
        $order = $this->makePendingOrder($this->owner, 'RS-RD-0001');

        $this->withSession($this->adminSession())
            ->post(route('admin.penyewaan.confirm', $order->id));

        $notif = $this->owner->notifications()->latest()->first();
        $this->assertNull($notif->read_at);
        $this->assertEquals(1, $this->owner->unreadNotifications()->count());

        // Buka notifikasi sebagai pemilik: ditandai dibaca + dialihkan ke riwayat.
        $this->withSession($this->customerSession($this->owner))
            ->get(route('notifications.open', $notif->id))
            ->assertRedirect(route('history', ['status' => 'active']));

        $this->assertNotNull($this->owner->notifications()->find($notif->id)->read_at);
        $this->assertEquals(0, $this->owner->unreadNotifications()->count());
    }

    /**
     * KEAMANAN — User lain tidak dapat membuka notifikasi milik user lain (403),
     * dan notifikasi pemilik tetap tidak berubah.
     */
    public function test_other_user_cannot_open_owners_notification(): void
    {
        $order = $this->makePendingOrder($this->owner, 'RS-SEC-0001');

        $this->withSession($this->adminSession())
            ->post(route('admin.penyewaan.reject', $order->id), ['reason' => 'Stok tidak tersedia.']);

        $notif = $this->owner->notifications()->latest()->first();
        $this->assertNotNull($notif);

        $this->withSession($this->customerSession($this->otherUser))
            ->get(route('notifications.open', $notif->id))
            ->assertStatus(403);

        // Notifikasi pemilik tetap unread & tidak bocor ke user lain.
        $this->assertNull($this->owner->notifications()->find($notif->id)->read_at);
        $this->assertEquals(0, $this->otherUser->notifications()->count());
    }

    /**
     * KOMPONEN UNREAD — "Tandai semua dibaca" menjadikan semua notifikasi read.
     */
    public function test_mark_all_read_zeroes_unread_badge(): void
    {
        $orderA = $this->makePendingOrder($this->owner, 'RS-MAR-0001');
        $orderB = $this->makePendingOrder($this->owner, 'RS-MAR-0002');

        $this->withSession($this->adminSession())
            ->post(route('admin.penyewaan.confirm', $orderA->id));
        $this->withSession($this->adminSession())
            ->post(route('admin.penyewaan.reject', $orderB->id), ['reason' => 'Maintenance.']);

        $this->assertEquals(2, $this->owner->unreadNotifications()->count());

        $this->withSession($this->customerSession($this->owner))
            ->post(route('notifications.readAll'))
            ->assertRedirect();

        $this->assertEquals(0, $this->owner->unreadNotifications()->count());
        $this->assertEquals(2, $this->owner->notifications()->count());
    }

    /**
     * Keamanan — endpoint notifikasi hanya read/redirect, tidak mengubah status
     * penyewaan. Status order hanya berubah lewat endpoint admin (confirm/reject).
     */
    public function test_opening_notification_does_not_change_order_status(): void
    {
        $order = $this->makePendingOrder($this->owner, 'RS-NC-0001');

        $this->withSession($this->adminSession())
            ->post(route('admin.penyewaan.reject', $order->id))
            ->assertRedirect(route('admin.penyewaan'));

        $notif = $this->owner->notifications()->latest()->first();

        $this->withSession($this->customerSession($this->owner))
            ->get(route('notifications.open', $notif->id))
            ->assertRedirect(route('history', ['status' => 'cancelled']));

        // Status tetap cancelled (tidak diubah oleh user lewat notifikasi).
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }

    /**
     * REGRESI BUG — Notifikasi user yang datanya berisi URL admin ('/admin/dashboard')
     * TIDAK BOLEH mengarahkan customer ke area admin. Customer harus tetap
     * diarahkan ke halaman user (riwayat penyewaan).
     */
    public function test_user_notification_with_admin_url_never_redirects_to_admin(): void
    {
        // Simulasikan data notifikasi lama/rusak yang menyimpan URL admin.
        $this->owner->notify(new \App\Notifications\RentalStatusNotification(
            \App\Notifications\RentalStatusNotification::TYPE_ACCEPTED,
            '🔔 Penyewaan Diterima',
            'Body apa pun.',
            '✅',
            '/admin/dashboard',
            'RS-ADM-0001',
        ));

        $notif = $this->owner->notifications()->latest()->first();
        $this->assertNotNull($notif);
        $this->assertSame('/admin/dashboard', $notif->data['url']);

        $response = $this->withSession($this->customerSession($this->owner))
            ->get(route('notifications.open', $notif->id));

        // HARUS menuju halaman user, bukan /admin/dashboard.
        $response->assertRedirect(route('history', ['status' => 'active']));
        $this->assertFalse($this->isRedirectToAdmin($response), 'Notifikasi user tidak boleh mengarah ke area admin.');
        $this->assertNotNull($this->owner->notifications()->find($notif->id)->read_at);
    }

    /**
     * REGRESI BUG — Notifikasi user tipe apa pun (asli maupun rusak) tidak pernah
     * berakhir di /admin/*; selalu di halaman riwayat user.
     */
    public function test_user_notification_types_map_to_user_pages_only(): void
    {
        $cases = [
            [\App\Notifications\RentalStatusNotification::TYPE_ACCEPTED, '/admin/dashboard', route('history', ['status' => 'active'])],
            [\App\Notifications\RentalStatusNotification::TYPE_REJECTED, '/admin/penyewaan', route('history', ['status' => 'cancelled'])],
            ['refund', '/admin/refund', route('history')],
            ['unknown_type', '/admin/dashboard', route('history')],
        ];

        foreach ($cases as $i => [$type, $badUrl, $expected]) {
            $this->owner->notify(new \App\Notifications\RentalStatusNotification(
                $type,
                'Title ' . $i,
                'Body ' . $i,
                '🔔',
                $badUrl,
            ));
            $notif = $this->owner->notifications()->get()
                ->first(fn ($n) => data_get($n->data, 'title') === 'Title ' . $i);
            $this->assertNotNull($notif);

            $response = $this->withSession($this->customerSession($this->owner))
                ->get(route('notifications.open', $notif->id));

            $response->assertRedirect($expected);
            $this->assertFalse($this->isRedirectToAdmin($response));
        }
    }

    /**
     * Admin yang memakai endpoint notifikasi user ditolak (403); admin
     * tetap memakai endpoint admin.notifications.open untuk notifikasi admin.
     */
    public function test_admin_cannot_use_user_notification_endpoint(): void
    {
        $this->owner->notify(new \App\Notifications\RentalStatusNotification(
            \App\Notifications\RentalStatusNotification::TYPE_ACCEPTED,
            '🔔 Penyewaan Diterima',
            'Body admin mencoba.',
            '✅',
            route('history', ['status' => 'active']),
        ));
        $notif = $this->owner->notifications()->latest()->first();

        $this->withSession($this->adminSession())
            ->get(route('notifications.open', $notif->id))
            ->assertStatus(403);

        // Notifikasi user tetap tidak dibaca oleh admin.
        $this->assertNull($this->owner->notifications()->find($notif->id)->read_at);
    }

    private function isRedirectToAdmin(\Illuminate\Testing\TestResponse $response): bool
    {
        $location = $response->headers->get('Location') ?? '';
        $path = (string) parse_url($location, PHP_URL_PATH);

        return str_starts_with($path, '/admin');
    }
}