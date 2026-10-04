<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use App\Notifications\AdminActivityNotification;
use App\Services\AdminNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Notifikasi admin saat ada USER baru yang melakukan pendaftaran akun.
 *
 * Alur yang diuji:
 *   Registrasi berhasil -> backend membuat notifikasi database untuk admin
 *   -> badge lonceng bertambah -> klik notifikasi -> read + pindah ke
 *   /admin/users.
 */
class NewUserRegistrationNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Admin Summit',
            'email' => 'admin@summit.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
    }

    /**
     * Payload formulir registrasi yang valid.
     *
     * @return array<string, mixed>
     */
    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'johndoe@example.com',
            'phone' => '081234567890',
            'domicile' => 'Jakarta',
            'date_of_birth' => '2000-01-01',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminSession(): array
    {
        return [
            'account_id' => $this->admin->getKey(),
            'account_name' => $this->admin->name,
            'account_role' => 'admin',
        ];
    }

    /**
     * Badge hanya boleh muncul pada markup lonceng bila memang ada notifikasi
     * belum dibaca, dan markupnya harus identik dengan desain awal: span badge
     * absolut di dalam tombol lonceng (bukan elemen "0" yang disembunyikan).
     */
    public function test_bell_markup_matches_original_design(): void
    {
        $bell = function (string $html): string {
            $start = strpos($html, '<div class="admin-notif-wrapper"');
            $end = strpos($html, '</div>', strpos($html, 'admin-notif-toggle'));

            return $start === false ? '' : substr($html, $start, $end - $start);
        };

        // Tanpa notifikasi -> tidak boleh ada elemen badge pada lonceng.
        $empty = $this->withSession($this->adminSession())->get(route('admin.dashboard'));
        $empty->assertOk();
        $this->assertStringNotContainsString('admin-notif-badge', $bell($empty->getContent()));

        // Ikon, ukuran tombol, dan posisi tetap sama seperti desain awal.
        $empty->assertSee('class="header-icon-btn admin-notif-toggle"', false);
        $empty->assertSee('<svg width="20" height="20" viewBox="0 0 24 24"', false);

        // Ada notifikasi -> badge dirender persis seperti desain awal.
        $this->post('/register', $this->registrationPayload())->assertRedirect('/');

        $withBadge = $this->withSession($this->adminSession())->get(route('admin.dashboard'));
        $withBadge->assertOk();

        $markup = $bell($withBadge->getContent());
        $this->assertStringContainsString('<span class="admin-notif-badge">1</span>', $markup);
        $this->assertStringNotContainsString('is-hidden', $markup);
        $this->assertStringNotContainsString('>0<', $markup);

        // Badge adalah anak tombol lonceng, bukan sibling yang bisa mendorong
        // layout navbar. Ikon & ukuran tombol tidak berubah.
        $buttonStart = strpos($markup, '<button');
        $buttonEnd = strpos($markup, '</button>', $buttonStart);
        $button = substr($markup, $buttonStart, $buttonEnd - $buttonStart);

        $this->assertStringContainsString('<svg width="20" height="20"', $button);
        $this->assertStringContainsString('<span class="admin-notif-badge">1</span>', $button);
    }

    /**
     * TEST 1: Registrasi berhasil -> user dibuat + notifikasi "User Baru
     * Mendaftar" tersimpan untuk admin dan tampil di halaman admin.
     */
    public function test_successful_registration_creates_admin_notification(): void
    {
        $this->post('/register', $this->registrationPayload())->assertRedirect('/');

        // User benar-benar dibuat.
        $user = User::where('email', 'johndoe@example.com')->first();
        $this->assertNotNull($user);

        // Notifikasi tercatat di database (persistent, channel database).
        $notif = $this->admin->notifications()->latest()->first();
        $this->assertNotNull($notif, 'Notifikasi user baru harus dibuat di backend.');
        $this->assertSame(
            AdminActivityNotification::class,
            $notif->type,
            'Harus memakai notifikasi Laravel yang sudah ada, bukan tabel custom.'
        );
        $this->assertSame(AdminNotificationService::TYPE_USER_REGISTERED, data_get($notif->data, 'type'));
        $this->assertSame('👤 User Baru Mendaftar', data_get($notif->data, 'title'));
        $this->assertSame('👤', data_get($notif->data, 'icon'));

        // Pesan memuat identitas user yang mendaftar.
        $body = (string) data_get($notif->data, 'body');
        $this->assertStringContainsString('John Doe', $body);
        $this->assertStringContainsString('@johndoe', $body);
        $this->assertStringContainsString('baru saja membuat akun', $body);

        // Data pendukung: user yang mendaftar.
        $this->assertSame($user->id, data_get($notif->data, 'meta.user_id'));
        $this->assertSame('johndoe', data_get($notif->data, 'meta.username'));
        $this->assertSame('johndoe@example.com', data_get($notif->data, 'meta.email'));

        // Notifikasi baru -> belum dibaca, dan diarahkan ke /admin/users.
        $this->assertNull($notif->read_at);
        $this->assertStringStartsWith(route('admin.users'), (string) data_get($notif->data, 'url'));
        $this->assertStringContainsString('user='.$user->id, (string) data_get($notif->data, 'url'));

        // Muncul di halaman admin.
        $dashboard = $this->withSession($this->adminSession())->get(route('admin.dashboard'));
        $dashboard->assertOk();
        $dashboard->assertSee('User Baru Mendaftar', false);
        $dashboard->assertSee('John Doe', false);
        $dashboard->assertViewHas('adminUnreadCount', 1);
    }

    /**
     * TEST 2: Beberapa user mendaftar -> badge = jumlah notifikasi belum dibaca.
     */
    public function test_badge_counts_all_unread_registrations(): void
    {
        foreach (['satu', 'dua', 'tiga'] as $index => $slug) {
            $this->post('/register', $this->registrationPayload([
                'name' => 'Pengguna '.$slug,
                'username' => $slug,
                'email' => $slug.'@example.com',
            ]))->assertRedirect('/');

            $this->assertSame($index + 1, $this->admin->unreadNotifications()->count());
        }

        // Badge pada lonceng menampilkan angka nyata (bukan dummy/hardcode).
        $page = $this->withSession($this->adminSession())->get(route('admin.users'));
        $page->assertOk();
        $page->assertViewHas('adminUnreadCount', 3);
        $page->assertSee('data-notif-initial-unread="3"', false);
        $page->assertSee(route('admin.notifications.poll'), false);

        // Ditambah notifikasi lain (mis. booking) -> badge ikut bertambah.
        $this->admin->notify(new AdminActivityNotification('booking', '🔔 Penyewaan Baru', 'Body', '📦', route('admin.penyewaan')));
        $this->assertSame(4, $this->admin->unreadNotifications()->count());
    }

    /**
     * TEST 3: Klik notifikasi -> ditandai read, badge berkurang, dan admin
     * diarahkan ke halaman manajemen pengguna (/admin/users).
     */
    public function test_clicking_notification_marks_read_and_redirects_to_user_management(): void
    {
        $this->post('/register', $this->registrationPayload())->assertRedirect('/');

        // Dua pendaftaran lagi untuk memastikan badge menghitung semuanya.
        $this->post('/register', $this->registrationPayload([
            'name' => 'Budi Santoso',
            'username' => 'budisan',
            'email' => 'budisan@example.com',
        ]))->assertRedirect('/');

        $user = User::where('email', 'johndoe@example.com')->first();
        $notif = $this->admin->notifications()
            ->where('data->type', AdminNotificationService::TYPE_USER_REGISTERED)
            ->latest()
            ->first();

        $this->assertNotNull($notif);
        $this->assertNull($notif->read_at);

        $response = $this->withSession($this->adminSession())
            ->get(route('admin.notifications.open', $notif->id));

        $response->assertRedirect(route('admin.users', ['user' => $user->id]));

        // Status berubah menjadi read (TIDAK dihapus).
        $refreshed = $this->admin->notifications()->find($notif->id);
        $this->assertNotNull($refreshed, 'Notifikasi tidak boleh dihapus saat dibaca.');
        $this->assertNotNull($refreshed->read_at);

        // Badge berkurang dari 2 menjadi 1.
        $this->assertSame(1, $this->admin->unreadNotifications()->count());
        $this->assertSame(2, $this->admin->notifications()->count());

        // Halaman tujuan menyorot baris user yang baru mendaftar.
        $users = $this->withSession($this->adminSession())->get(route('admin.users', ['user' => $user->id]));
        $users->assertOk();
        $users->assertSee('user-row-focused', false);
        $users->assertViewHas('focusUserId', $user->id);
    }

    /**
     * TEST 4: "Tandai semua dibaca" dan status read bertahan setelah reload.
     */
    public function test_read_state_persists_across_requests(): void
    {
        $this->post('/register', $this->registrationPayload())->assertRedirect('/');

        $notifId = $this->admin->notifications()->latest()->first()->id;

        $this->withSession($this->adminSession())
            ->get(route('admin.notifications.open', $notifId))
            ->assertRedirect();

        // Refresh halaman (simulasi user menekan F5) -> badge tetap 0 dan
        // notifikasi tidak kembali menjadi unread.
        $this->withSession($this->adminSession())->get(route('admin.dashboard'))->assertOk();
        $this->withSession($this->adminSession())->get(route('admin.dashboard'))->assertOk();

        $this->assertSame(0, $this->admin->unreadNotifications()->count());
        $this->assertSame(1, $this->admin->notifications()->count());
        $this->assertNotNull($this->admin->notifications()->find($notifId)->read_at);

        // Tandai semua dibaca untuk notifikasi kedua.
        $this->post('/register', $this->registrationPayload([
            'name' => 'Siti Aminah',
            'username' => 'sitiaminah',
            'email' => 'sitiaminah@example.com',
        ]))->assertRedirect('/');

        $this->assertSame(1, $this->admin->unreadNotifications()->count());

        $this->withSession($this->adminSession())
            ->post(route('admin.notifications.readAll'))
            ->assertRedirect();

        $this->assertSame(0, $this->admin->unreadNotifications()->count());
        $this->assertSame(2, $this->admin->notifications()->count());
    }

    /**
     * TEST 5: Registrasi GAGAL tidak boleh membuat notifikasi apa pun.
     */
    #[DataProvider('failedRegistrationProvider')]
    public function test_failed_registration_creates_no_notification(array $payload, bool $registerFirst = false): void
    {
        // Sebagian kasus gagal karena konflik data, jadi akun yang bentrok
        // harus didaftarkan lebih dulu.
        if ($registerFirst) {
            $this->post('/register', $this->registrationPayload())->assertRedirect('/');
        }

        $before = $this->admin->notifications()->count();

        $this->post('/register', $payload);

        $this->assertSame($before, $this->admin->notifications()->count());
        $this->assertSame($registerFirst ? 1 : 0, $this->admin->notifications()->count());
        $this->assertSame($registerFirst ? 1 : 0, $this->admin->unreadNotifications()->count());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1?: bool}>
     */
    public static function failedRegistrationProvider(): array
    {
        $base = [
            'name' => 'Gagal Daftar',
            'username' => 'gagaldaftar',
            'email' => 'gagaldaftar@example.com',
            'phone' => '081234567890',
            'domicile' => 'Jakarta',
            'date_of_birth' => '2000-01-01',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ];

        return [
            'nama kosong' => [array_merge($base, ['name' => ''])],
            'email tidak valid' => [array_merge($base, ['email' => 'bukan-email'])],
            'email sudah dipakai' => [array_merge($base, ['email' => 'johndoe@example.com']), true],
            'username sudah dipakai' => [array_merge($base, ['username' => 'johndoe']), true],
            'konfirmasi password salah' => [array_merge($base, ['password_confirmation' => 'lain12345'])],
            'password terlalu pendek' => [array_merge($base, ['password' => 'rahasia', 'password_confirmation' => 'rahasia'])],
            'terms tidak disetujui' => [array_merge($base, ['terms' => null])],
            'domisili di luar daftar' => [array_merge($base, ['domicile' => 'Surabaya'])],
            'tanggal lahir di masa depan' => [array_merge($base, ['date_of_birth' => now()->addDay()->toDateString()])],
            'nomor telepon tidak valid' => [array_merge($base, ['phone' => 'bukan-nomor'])],
        ];
    }

    /**
     * TEST 6: Notifikasi ini hanya untuk admin — user biasa tidak menerimanya
     * dan tidak bisa membukanya.
     */
    public function test_registration_notification_is_admin_only(): void
    {
        $this->post('/register', $this->registrationPayload())->assertRedirect('/');

        $user = User::where('email', 'johndoe@example.com')->first();
        $notif = $this->admin->notifications()->latest()->first();
        $this->assertNotNull($notif);

        // User baru tidak memiliki notifikasi admin apa pun.
        $this->assertSame(0, $user->notifications()->count());
        $this->assertSame(0, $user->unreadNotifications()->count());

        // Customer tidak bisa membuka notifikasi admin.
        $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ])->get(route('admin.notifications.open', $notif->id))->assertStatus(403);

        // Customer tidak bisa mengakses endpoint polling admin.
        $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ])->get(route('admin.notifications.poll'))->assertStatus(403);

        // Tamu juga ditolak (session dibersihkan agar benar-benar tanpa login).
        $this->flushSession();
        $this->get(route('admin.notifications.poll'))->assertRedirect(route('admin.login'));

        // Notifikasi tetap belum dibaca (tidak bocor/berubah).
        $this->assertNull($this->admin->notifications()->find($notif->id)->read_at);
    }

    /**
     * Endpoint polling mengembalikan badge & signature dari database, dan HTML
     * dropdown hanya dirender ketika diminta (?items=1).
     */
    public function test_poll_endpoint_returns_real_counts_and_dropdown_html(): void
    {
        $this->post('/register', $this->registrationPayload())->assertRedirect('/');

        $notif = $this->admin->notifications()->latest()->first();

        $response = $this->withSession($this->adminSession())
            ->getJson(route('admin.notifications.poll'))
            ->assertOk();

        $response->assertJsonPath('unread_count', 1);
        $this->assertNotEmpty($response->json('signature'));
        $this->assertArrayNotHasKey('html', $response->json());

        // Dengan ?items=1 dropdown ikut dirender (markup sama dengan halaman).
        $withItems = $this->withSession($this->adminSession())
            ->getJson(route('admin.notifications.poll', ['items' => 1]))
            ->assertOk();

        $this->assertSame(1, $withItems->json('unread_count'));
        $html = (string) $withItems->json('html');
        $this->assertStringContainsString('User Baru Mendaftar', $html);
        $this->assertStringContainsString('John Doe', $html);
        $this->assertStringContainsString(route('admin.notifications.open', $notif->id), $html);
        $this->assertStringContainsString('unread', $html);

        // Setelah notifikasi dibaca, badge turun tanpa reload halaman.
        $this->admin->notifications()->find($notif->id)->markAsRead();

        $after = $this->withSession($this->adminSession())
            ->getJson(route('admin.notifications.poll'))
            ->assertOk();

        $after->assertJsonPath('unread_count', 0);
        $this->assertNotSame($response->json('signature'), $after->json('signature'));
    }

    /**
     * Polling tidak boleh menambah query tak terduga: 1 aggregate unread +
     * 1 daftar notifikasi terbaru per request polling.
     */
    public function test_poll_endpoint_query_cost_is_bounded(): void
    {
        $this->post('/register', $this->registrationPayload())->assertRedirect('/');

        $session = $this->adminSession();
        $this->withSession($session)->getJson(route('admin.notifications.poll'))->assertOk();

        $queries = [];
        \DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->withSession($session)->getJson(route('admin.notifications.poll'))->assertOk();

        $notificationQueries = array_values(array_filter(
            $queries,
            fn ($sql) => str_contains($sql, 'notifications')
        ));

        $this->assertLessThanOrEqual(
            3,
            count($notificationQueries),
            'Polling harus tetap memakai jumlah query yang sedikit dan stabil: '.implode(' | ', $notificationQueries)
        );
    }

    /**
     * Notifikasi "user baru" tidak boleh bocor ke halaman /admin/users milik
     * customer, dan user biasa tetap tidak punya endpoint notifikasi admin.
     */
    public function test_user_role_cannot_reach_admin_user_management_page(): void
    {
        $this->post('/register', $this->registrationPayload())->assertRedirect('/');

        $user = User::where('email', 'johndoe@example.com')->first();

        $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ])->get(route('admin.users'))->assertStatus(403);
    }
}
