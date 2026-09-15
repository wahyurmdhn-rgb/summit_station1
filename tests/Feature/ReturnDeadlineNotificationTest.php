<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRecord;
use App\Models\User;
use App\Notifications\RentalStatusNotification;
use App\Services\ReturnNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Notifikasi tenggat pengembalian (Pengingat / Tenggat hari ini / Terlambat).
 *
 * Pengujian memakai waktu server sesuai timezone project (Asia/Jakarta).
 */
class ReturnDeadlineNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $otherUser;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Penyewa Deadline',
            'username' => 'penyewa_deadline',
            'email' => 'penyewa.deadline@summit.test',
            'domicile' => 'Bandung',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $this->otherUser = User::create([
            'name' => 'Penyewa Lain',
            'username' => 'penyewa_lain',
            'email' => 'penyewa.lain@summit.test',
            'domicile' => 'Bogor',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Tents & Shelters',
            'slug' => 'tents-shelters',
            'description' => 'Expedition grade tents',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-TEN-099',
            'name' => 'Apex Summit 6P',
            'subtitle' => '6-Season Expedition Tent',
            'price_per_day' => 200000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);
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

    private function makeOrder(
        User $user,
        string $code,
        string $rentEnd,
        string $status = 'active',
    ): Order {
        $order = Order::create([
            'code' => $code,
            'user_id' => $user->id,
            'rent_start' => now()->subWeek()->startOfDay(),
            'rent_end' => $rentEnd,
            'subtotal' => 400000,
            'service_fee' => 20000,
            'discount' => 0,
            'total' => 420000,
            'status' => $status,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'quantity' => 1,
            'days' => 5,
            'unit_price' => 200000,
            'subtotal' => 400000,
        ]);

        return $order;
    }

    private function today(): string
    {
        return now()->startOfDay()->format('Y-m-d');
    }

    private function tomorrow(): string
    {
        return now()->startOfDay()->addDay()->format('Y-m-d');
    }

    private function future(): string
    {
        return now()->startOfDay()->addDays(10)->format('Y-m-d');
    }

    private function yesterday(): string
    {
        return now()->startOfDay()->subDay()->format('Y-m-d');
    }

    private function returnDeadlineNotifications(User $user): \Illuminate\Support\Collection
    {
        return $user->notifications()
            ->where('type', RentalStatusNotification::class)
            ->get()
            ->filter(fn ($notif) => in_array(data_get($notif->data, 'type'), [
                RentalStatusNotification::TYPE_RETURN_REMINDER,
                RentalStatusNotification::TYPE_RETURN_DUE,
                RentalStatusNotification::TYPE_RETURN_OVERDUE,
            ], true));
    }

    /* ================================================================
     * TEST 1 — BELUM JATUH TEMPO
     * ============================================================= */

    public function test_no_deadline_notification_when_return_date_is_far_in_future(): void
    {
        $order = $this->makeOrder($this->owner, 'SS-FUT-0001', $this->future());

        $this->assertNull(ReturnNotificationService::intendedType($order));
        $this->assertSame(0, ReturnNotificationService::dispatchAll());
        $this->assertCount(0, $this->returnDeadlineNotifications($this->owner));
    }

    /* ================================================================
     * TEST 2 — MENDEKATI TENGGAT (Pengingat Pengembalian)
     * ============================================================= */

    public function test_reminder_notification_when_return_is_tomorrow(): void
    {
        $order = $this->makeOrder($this->owner, 'SS-REM-0001', $this->tomorrow());

        $this->assertSame(RentalStatusNotification::TYPE_RETURN_REMINDER, ReturnNotificationService::intendedType($order));

        ReturnNotificationService::dispatchAll();

        $reminder = $this->returnDeadlineNotifications($this->owner)->first();
        $this->assertNotNull($reminder, 'User harus mendapat notifikasi Pengingat Pengembalian.');
        $this->assertSame(RentalStatusNotification::TYPE_RETURN_REMINDER, data_get($reminder->data, 'type'));
        $this->assertStringContainsString('Pengingat Pengembalian', data_get($reminder->data, 'title'));
        $this->assertStringContainsString('No. Booking: #SS-REM-0001', data_get($reminder->data, 'body'));
        $this->assertStringContainsString('Tenggat pengembalian:', data_get($reminder->data, 'body'));
        $this->assertNull($reminder->read_at, 'Notifikasi baru harus berstatus Unread.');
        $this->assertSame(1, $this->owner->unreadNotifications()->count());
    }

    /* ================================================================
     * TEST 3 — TENGGAT HARI INI
     * ============================================================= */

    public function test_due_today_notification(): void
    {
        $order = $this->makeOrder($this->owner, 'SS-DUE-0001', $this->today());

        $this->assertSame(RentalStatusNotification::TYPE_RETURN_DUE, ReturnNotificationService::intendedType($order));

        ReturnNotificationService::dispatchAll();

        $notif = $this->returnDeadlineNotifications($this->owner)->first();
        $this->assertNotNull($notif, 'User harus mendapat notifikasi tenggat di hari terakhir.');
        $this->assertSame(RentalStatusNotification::TYPE_RETURN_DUE, data_get($notif->data, 'type'));
        $this->assertStringContainsString('Batas waktu pengembalian alat Anda telah tiba', data_get($notif->data, 'body'));
        $this->assertStringContainsString('Tenggat pengembalian:', data_get($notif->data, 'body'));
        $this->assertNull($notif->read_at);
    }

    /* ================================================================
     * TEST 4 — TERLAMBAT + SANKSI
     * ============================================================= */

    public function test_overdue_notification_with_sanction_note(): void
    {
        $order = $this->makeOrder($this->owner, 'SS-OVR-0001', $this->yesterday());

        $this->assertSame(RentalStatusNotification::TYPE_RETURN_OVERDUE, ReturnNotificationService::intendedType($order));

        ReturnNotificationService::dispatchAll();

        $notif = $this->returnDeadlineNotifications($this->owner)->first();
        $this->assertNotNull($notif, 'User yang terlambat harus mendapat notifikasi.');
        $this->assertSame(RentalStatusNotification::TYPE_RETURN_OVERDUE, data_get($notif->data, 'type'));
        $this->assertStringContainsString('Terlambat Mengembalikan', data_get($notif->data, 'title'));
        $this->assertStringContainsString('melewati batas waktu pengembalian', data_get($notif->data, 'body'));
        $this->assertStringContainsString('sanksi', data_get($notif->data, 'body'), 'Keterlambatan harus menyebutkan sanksi.');
        $this->assertNull($notif->read_at);
    }

    public function test_overdue_applies_to_paid_status_too(): void
    {
        $order = $this->makeOrder($this->owner, 'SS-OVR-0002', $this->yesterday(), 'paid');

        $this->assertSame(RentalStatusNotification::TYPE_RETURN_OVERDUE, ReturnNotificationService::intendedType($order));
    }

    /* ================================================================
     * TEST 5 — SUDAH DIKEMBALIKAN / STATUS TIDAK RELEVAN
     * ============================================================= */

    public function test_completed_order_gets_no_deadline_notification(): void
    {
        // Sudah dikembalikan tepat waktu (completed) — tidak ada notifikasi terlambat.
        $order = $this->makeOrder($this->owner, 'SS-CMP-0001', $this->yesterday(), 'completed');

        $this->assertNull(ReturnNotificationService::intendedType($order));
        $this->assertSame(0, ReturnNotificationService::dispatchAll());
        $this->assertCount(0, $this->returnDeadlineNotifications($this->owner));
    }

    public function test_approved_return_record_never_triggers_overdue(): void
    {
        // Tenggat lewat tapi ReturnRecord sudah disetujui admin → dianggap sudah
        // dikembalikan, BUKAN terlambat, tanpa sanksi.
        $order = $this->makeOrder($this->owner, 'SS-RTN-0001', $this->yesterday());

        ReturnRecord::create([
            'order_id' => $order->id,
            'order_item_id' => $order->items->first()->id,
            'status' => 'approved',
            'returned_at' => now(),
        ]);

        $order->load('returns');
        $this->assertNull(ReturnNotificationService::intendedType($order));
        $this->assertSame(0, ReturnNotificationService::dispatchAll());
        $this->assertCount(0, $this->returnDeadlineNotifications($this->owner));
    }

    public function test_pending_and_cancelled_orders_are_ignored(): void
    {
        $pending = $this->makeOrder($this->owner, 'SS-PND-0001', $this->yesterday(), 'pending');
        $cancelled = $this->makeOrder($this->owner, 'SS-CAN-0001', $this->yesterday(), 'cancelled');

        $this->assertNull(ReturnNotificationService::intendedType($pending));
        $this->assertNull(ReturnNotificationService::intendedType($cancelled));

        $this->assertSame(0, ReturnNotificationService::dispatchAll());
        $this->assertCount(0, $this->returnDeadlineNotifications($this->owner));
    }

    /* ================================================================
     * TEST 6 — TIDAK ADA DUPLIKASI (scheduler/refresh berulang kali)
     * ============================================================= */

    public function test_repeated_dispatches_create_no_duplicate_notifications(): void
    {
        $this->makeOrder($this->owner, 'SS-DUP-0001', $this->yesterday());

        ReturnNotificationService::dispatchAll();
        ReturnNotificationService::dispatchAll();
        ReturnNotificationService::dispatchAll();

        $this->assertCount(1, $this->returnDeadlineNotifications($this->owner));

        // Simulasi refresh halaman User (GET history) lalu scheduler jalan lagi.
        $this->withSession($this->customerSession($this->owner))
            ->get(route('history', ['status' => 'active']))
            ->assertOk();

        ReturnNotificationService::dispatchAll();

        $this->assertCount(1, $this->returnDeadlineNotifications($this->owner));
        $this->assertSame(1, $this->owner->unreadNotifications()->count());
    }

    public function test_tahapan_tidak_bikin_notifikasi_ganda_paralel(): void
    {
        // Satu booking di hari tenggat harus menghasilkan satu notifikasi 'due'
        // saja (bukan reminder + due sekaligus).
        $this->makeOrder($this->owner, 'SS-ONE-0001', $this->today());

        ReturnNotificationService::dispatchAll();

        $this->assertSame(1, $this->returnDeadlineNotifications($this->owner)->count());
        $this->assertSame(
            1,
            $this->returnDeadlineNotifications($this->owner)
                ->filter(fn ($n) => data_get($n->data, 'type') === RentalStatusNotification::TYPE_RETURN_DUE)
                ->count(),
        );
    }

    public function test_artisan_command_runs_dispatch(): void
    {
        $order = $this->makeOrder($this->owner, 'SS-CMD-0001', $this->yesterday());

        $this->artisan('return-deadline:notify')
            ->expectsOutputToContain('Pemeriksaan tenggat pengembalian selesai')
            ->assertSuccessful();

        $notif = $this->returnDeadlineNotifications($this->owner)->first();
        $this->assertNotNull($notif);
        $this->assertSame($order->code, data_get($notif->data, 'order_code'));
    }

    /* ================================================================
     * WAKTU TETAP BERJALAN WALAU BROWSER DITUTUP / LOGOUT / KOMPUTER MATI
     * (evaluasi murni dari waktu SERVER — tanpa halaman terbuka sebelumnya)
     * ============================================================= */

    public function test_browser_closed_then_reopen_detects_overdue_on_page_load(): void
    {
        // Booking dibuat beberapa hari lalu, browser ditutup, deadline sudah
        // terlewat — scheduler TIDAK dijalankan. Begitu user membuka halaman
        // kembali, sistem mengevaluasi dari waktu server dan memberi notifikasi.
        $this->makeOrder($this->owner, 'SS-BRW-0001', $this->yesterday());

        $this->assertCount(0, $this->returnDeadlineNotifications($this->owner));

        $this->withSession($this->customerSession($this->owner))
            ->get(route('history'))
            ->assertOk();

        $notif = $this->returnDeadlineNotifications($this->owner)->first();
        $this->assertNotNull($notif, 'Setelah membuka website lagi, sistem harus tahu deadline sudah lewat.');
        $this->assertSame(RentalStatusNotification::TYPE_RETURN_OVERDUE, data_get($notif->data, 'type'));
    }

    public function test_opening_home_page_after_deadline_still_detects_overdue(): void
    {
        // Simulasi "komputer mati / website lama tidak dibuka": user kembali ke
        // halaman umum (home), bukan halaman khusus portal.
        $this->makeOrder($this->owner, 'SS-OFF-0001', $this->yesterday());

        $this->withSession($this->customerSession($this->owner))
            ->get(route('home'))
            ->assertOk();

        $notif = $this->returnDeadlineNotifications($this->owner)->first();
        $this->assertNotNull($notif);
        $this->assertSame(RentalStatusNotification::TYPE_RETURN_OVERDUE, data_get($notif->data, 'type'));
    }

    public function test_logout_then_login_later_gets_notification_after_relogin(): void
    {
        // User logout sebelum deadline. Setelah deadline lewat, user login lagi
        // dan membuka halaman — notifikasi keterlambatan harus muncul.
        $this->makeOrder($this->owner, 'SS-LOG-0001', $this->yesterday());

        // Selama "logout" (tanpa mengunjungi halaman apa pun), belum ada notif.
        $this->assertCount(0, $this->returnDeadlineNotifications($this->owner));

        // "Login kembali" = restore sesi customer, lalu buka halaman.
        $this->withSession($this->customerSession($this->owner))
            ->get(route('profile'))
            ->assertOk();

        $notif = $this->returnDeadlineNotifications($this->owner)->first();
        $this->assertNotNull($notif);
        $this->assertSame(RentalStatusNotification::TYPE_RETURN_OVERDUE, data_get($notif->data, 'type'));
        $this->assertNull($notif->read_at);
    }

    public function test_returned_before_deadline_gets_no_notification_even_on_page_load(): void
    {
        // Barang sudah dikembalikan (ReturnRecord approved) sebelum sempat
        // terlewat — membuka halaman tetap tidak membuat notifikasi lagi.
        $order = $this->makeOrder($this->owner, 'SS-RT2-0001', $this->tomorrow());

        ReturnRecord::create([
            'order_id' => $order->id,
            'order_item_id' => $order->items->first()->id,
            'status' => 'approved',
            'returned_at' => now(),
        ]);

        $this->withSession($this->customerSession($this->owner))
            ->get(route('history'))
            ->assertOk();

        $this->assertCount(0, $this->returnDeadlineNotifications($this->owner));
    }

    public function test_refreshing_pages_does_not_duplicate_notifications(): void
    {
        $this->makeOrder($this->owner, 'SS-RFR-0001', $this->yesterday());

        for ($i = 0; $i < 5; $i++) {
            $this->withSession($this->customerSession($this->owner))
                ->get(route('history'))
                ->assertOk();
        }

        $this->assertCount(1, $this->returnDeadlineNotifications($this->owner));
        $this->assertSame(1, $this->owner->unreadNotifications()->count());
    }

    public function test_admin_page_load_does_not_create_user_deadline_notifications(): void
    {
        $admin = \App\Models\Admin::create([
            'name' => 'Admin Deadlines',
            'email' => 'admin.deadline@summit.test',
            'password' => 'password123',
        ]);

        $this->makeOrder($this->owner, 'SS-ADM-0001', $this->yesterday());

        $this->withSession([
            'account_id' => $admin->getKey(),
            'account_name' => $admin->name,
            'account_role' => 'admin',
        ])->get(route('admin.dashboard'))->assertOk();

        $this->assertCount(0, $this->returnDeadlineNotifications($this->owner));
    }

    /* ================================================================
     * SECURITY & PERILAKU MEMBUKA NOTIFIKASI
     * ============================================================= */

    public function test_notifications_only_sent_to_owner_and_owner_can_open_them(): void
    {
        $ownerOrder = $this->makeOrder($this->owner, 'SS-OWN-0001', $this->yesterday());
        $otherOrder = $this->makeOrder($this->otherUser, 'SS-OTH-0001', $this->yesterday());

        ReturnNotificationService::dispatchAll();

        $ownerNotif = $this->returnDeadlineNotifications($this->owner)->first();
        $this->assertNotNull($ownerNotif);
        $this->assertSame($ownerOrder->code, data_get($ownerNotif->data, 'order_code'));

        $otherNotifs = $this->returnDeadlineNotifications($this->otherUser);
        $this->assertCount(1, $otherNotifs);
        $this->assertSame($otherOrder->code, data_get($otherNotifs->first()->data, 'order_code'));

        // Tidak ada data booking user lain di notifikasi pemilik.
        $this->assertStringNotContainsString($otherOrder->code, data_get($ownerNotif->data, 'body'));

        // Pemilik membuka → diarahkan ke riwayat aktif & ditandai Read.
        $this->withSession($this->customerSession($this->owner))
            ->get(route('notifications.open', $ownerNotif->id))
            ->assertRedirect(route('history', ['status' => 'active']));

        $this->assertNotNull($ownerNotif->fresh()->read_at);
    }

    public function test_other_user_cannot_open_owners_deadline_notification(): void
    {
        $this->makeOrder($this->owner, 'SS-SEC-0001', $this->yesterday());
        ReturnNotificationService::dispatchAll();

        $ownerNotif = $this->returnDeadlineNotifications($this->owner)->first();

        $this->withSession($this->customerSession($this->otherUser))
            ->get(route('notifications.open', $ownerNotif->id))
            ->assertStatus(403);

        // Notifikasi pemilik tetap Unread & tidak bocor.
        $this->assertNull($ownerNotif->fresh()->read_at);
        $this->assertCount(0, $this->returnDeadlineNotifications($this->otherUser)
            ->filter(fn ($n) => $n->id === $ownerNotif->id));
    }
}