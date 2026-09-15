<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ReturnRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReturnProofUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Order $activeOrder;

    private Order $otherOrder;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        $this->admin = Admin::create([
            'name' => 'Admin Summit',
            'email' => 'admin.proof@summit.test',
            'password' => 'password',
        ]);

        $category = Category::create([
            'name' => 'Backpacks',
            'slug' => 'backpacks',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'PROOF-BPK-01',
            'name' => 'Osprey Atmos 65L',
            'subtitle' => 'Expedition Pack',
            'description' => 'Test pack description',
            'price_per_day' => 85000,
            'stock_total' => 10,
            'stock_available' => 5,
            'rating' => 4.8,
            'reviews_count' => 10,
        ]);

        $this->user = User::create([
            'name' => 'Wahyu Pratama',
            'email' => 'wahyu@example.com',
            'username' => 'wahyu',
            'password' => 'password',
            'domicile' => 'Jakarta',
        ]);

        $this->otherUser = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'username' => 'budi',
            'password' => 'password',
            'domicile' => 'Bandung',
        ]);

        $this->activeOrder = Order::create([
            'code' => 'ORD-PROOF-01',
            'user_id' => $this->user->id,
            'rent_start' => today()->subDays(3),
            'rent_end' => today(),
            'subtotal' => 255000,
            'service_fee' => 25000,
            'discount' => 0,
            'total' => 280000,
            'status' => 'active',
            'paid_at' => today()->subDays(3),
        ]);

        OrderItem::create([
            'order_id' => $this->activeOrder->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'image' => null,
            'quantity' => 1,
            'days' => 3,
            'unit_price' => 85000,
            'subtotal' => 255000,
        ]);

        $this->otherOrder = Order::create([
            'code' => 'ORD-PROOF-09',
            'user_id' => $this->otherUser->id,
            'rent_start' => today()->subDays(1),
            'rent_end' => today(),
            'subtotal' => 85000,
            'total' => 110000,
            'status' => 'active',
        ]);
    }

    private function customerSession(User $user): array
    {
        return [
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ];
    }

    private function adminSession(): array
    {
        return [
            'account_id' => $this->admin->getKey(),
            'account_name' => $this->admin->name,
            'account_role' => 'admin',
        ];
    }

    /**
     * Membuat file gambar PNG 1x1 yang valid tanpa bergantung pada ekstensi GD.
     * Opsional memperbesar ukuran file hingga melebihi batas (untuk uji max).
     */
    private function makeProofImage(int $sizeKb = 2, string $name = 'bukti_pengembalian.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        $bytes = (int) ($sizeKb * 1024);
        if (strlen($png) < $bytes) {
            $png .= str_repeat("\0", $bytes - strlen($png));
        }

        $path = tempnam(sys_get_temp_dir(), 'proof_');
        file_put_contents($path, $png);

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    /**
     * TEST 1 — User upload foto bukti -> pengembalian tersimpan, foto tersimpan,
     * admin dapat melihat foto pada halaman pengembalian.
     */
    public function test_user_can_submit_return_with_proof_photo_and_admin_can_view_it(): void
    {
        $photo = $this->makeProofImage();

        $response = $this->withSession($this->customerSession($this->user))
            ->post('/history/' . $this->activeOrder->id . '/return', [
                'return_proof' => $photo,
            ]);

        $response->assertRedirect(route('history'));

        $returnRecord = ReturnRecord::where('order_id', $this->activeOrder->id)->first();
        $this->assertNotNull($returnRecord);
        $this->assertSame('pending', $returnRecord->status);
        $this->assertNotNull($returnRecord->proof_path);
        $this->assertNotNull($returnRecord->returned_at);
        Storage::disk('local')->assertExists($returnRecord->proof_path);

        // Status pengembalian TIDAK berubah otomatis hanya karena upload foto.
        $this->assertSame('active', $this->activeOrder->fresh()->status);

        // Admin menerima notification berisi info foto bukti.
        $notif = $this->admin->notifications()->latest()->first();
        $this->assertNotNull($notif);
        $this->assertSame('return', data_get($notif->data, 'type'));
        $this->assertSame('🔔 Pengembalian Barang', data_get($notif->data, 'title'));
        $this->assertStringContainsString('Wahyu Pratama', data_get($notif->data, 'body'));
        $this->assertStringContainsString('mengunggah foto bukti pengembalian', data_get($notif->data, 'body'));

        // Admin membuka halaman detail pengembalian -> foto terlihat di HTML
        // (disajikan via route terkontrol dari storage privat, bukan storage publik).
        $proofUrl = route('file.return-proof', $returnRecord->id);
        $adminPage = $this->withSession($this->adminSession())
            ->get('/admin/pengembalian');
        $adminPage->assertStatus(200);
        $adminPage->assertSee('ORD-PROOF-01');
        $adminPage->assertSee($proofUrl, false);
    }

    /**
     * TEST 2 — User mengirim tanpa foto -> DITOLAK dengan pesan jelas.
     */
    public function test_user_cannot_submit_return_without_proof(): void
    {
        $response = $this->withSession($this->customerSession($this->user))
            ->post('/history/' . $this->activeOrder->id . '/return', [
                'return_proof' => null,
            ]);

        $response->assertSessionHasErrors('return_proof');
        $this->assertSame(
            'Foto bukti pengembalian wajib diupload.',
            session('errors')->first('return_proof')
        );
        $this->assertDatabaseMissing('return_records', ['order_id' => $this->activeOrder->id]);
    }

    /**
     * TEST 3 — File non-gambar (PDF) DITOLAK oleh backend.
     */
    public function test_user_cannot_submit_return_with_invalid_file(): void
    {
        $file = UploadedFile::fake()->create('dokumen.pdf', 200, 'application/pdf');

        $response = $this->withSession($this->customerSession($this->user))
            ->post('/history/' . $this->activeOrder->id . '/return', [
                'return_proof' => $file,
            ]);

        $response->assertSessionHasErrors('return_proof');
        $this->assertStringContainsString('harus berupa gambar', session('errors')->first('return_proof'));
        $this->assertDatabaseMissing('return_records', ['order_id' => $this->activeOrder->id]);
    }

    /**
     * TEST 4 — File melebihi batas ukuran (5MB) DITOLAK dengan pesan jelas.
     */
    public function test_user_cannot_submit_return_with_oversized_file(): void
    {
        // 6000 KB > 5120 KB (5MB)
        $file = $this->makeProofImage(6000, 'terlalu_besar.png');

        $response = $this->withSession($this->customerSession($this->user))
            ->post('/history/' . $this->activeOrder->id . '/return', [
                'return_proof' => $file,
            ]);

        $response->assertSessionHasErrors('return_proof');
        $this->assertStringContainsString('5MB', session('errors')->first('return_proof'));
        $this->assertDatabaseMissing('return_records', ['order_id' => $this->activeOrder->id]);
    }

    /**
     * TEST 5 — Authorization: User A tidak boleh mengajukan pengembalian
     * untuk booking milik User B.
     */
    public function test_user_cannot_submit_return_for_other_users_order(): void
    {
        $photo = $this->makeProofImage(2, 'bukti_a.png');

        $response = $this->withSession($this->customerSession($this->user))
            ->post('/history/' . $this->otherOrder->id . '/return', [
                'return_proof' => $photo,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('return_records', ['order_id' => $this->otherOrder->id]);
    }

    /**
     * Guest dialihkan ke halaman login.
     */
    public function test_guest_is_redirected_when_submitting_return(): void
    {
        $response = $this->post('/history/' . $this->activeOrder->id . '/return', [
            'return_proof' => $this->makeProofImage(),
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('return_records', ['order_id' => $this->activeOrder->id]);
    }

    /**
     * Pengajuan ganda diblokir selama status masih pending/approved.
     */
    public function test_duplicate_return_submission_is_blocked_while_open(): void
    {
        $this->withSession($this->customerSession($this->user))
            ->post('/history/' . $this->activeOrder->id . '/return', [
                'return_proof' => $this->makeProofImage(2, 'pertama.png'),
            ]);

        $response = $this->withSession($this->customerSession($this->user))
            ->post('/history/' . $this->activeOrder->id . '/return', [
                'return_proof' => $this->makeProofImage(2, 'kedua.png'),
            ]);

        $response->assertSessionHasErrors('return');
        $this->assertSame(1, ReturnRecord::where('order_id', $this->activeOrder->id)->count());
    }

    /**
     * Saat admin menginspeksi tanpa mengunggah foto baru, foto bukti user
     * tetap dipertahankan (tidak ditimpa null).
     */
    public function test_admin_record_keeps_user_proof_photo(): void
    {
        ReturnRecord::create([
            'order_id' => $this->activeOrder->id,
            'order_item_id' => null,
            'proof_path' => 'returns/user-proof-kept.jpg',
            'status' => 'pending',
            'returned_at' => now(),
        ]);

        $response = $this->withSession($this->adminSession())
            ->post("/admin/pengembalian/{$this->activeOrder->id}/record", [
                'condition' => 'excellent',
                'inspection_note' => 'Kondisi mulus',
                'damage_cost' => 0,
            ]);

        $response->assertRedirect('/admin/pengembalian');

        $returnRecord = ReturnRecord::where('order_id', $this->activeOrder->id)->first();
        $this->assertSame('approved', $returnRecord->status);
        $this->assertSame('returns/user-proof-kept.jpg', $returnRecord->proof_path);
    }

    /**
     * TEST — Bukti pengembalian disajikan via route TERKONTROL dengan otorisasi:
     * pemilik booking & admin boleh melihat; customer lain dilarang (anti-IDOR).
     */
    public function test_return_proof_route_enforces_ownership_and_admin_access(): void
    {
        Storage::disk('local')->put('returns/private-proof.png', 'png-bytes');

        $returnRecord = ReturnRecord::create([
            'order_id' => $this->activeOrder->id,
            'order_item_id' => null,
            'proof_path' => 'returns/private-proof.png',
            'status' => 'pending',
            'returned_at' => now(),
        ]);

        $url = route('file.return-proof', $returnRecord->id);

        // Pemilik booking diperbolehkan melihat buktinya sendiri.
        $this->withSession($this->customerSession($this->user))
            ->get($url)
            ->assertOk();

        // Customer lain dilarang (anti-IDOR).
        $this->withSession($this->customerSession($this->otherUser))
            ->get($url)
            ->assertForbidden();

        // Admin boleh melihat semua bukti.
        $this->withSession($this->adminSession())
            ->get($url)
            ->assertOk();
    }

    /**
     * TEST — Bukti pembayaran disajikan via route terkontrol dengan otorisasi
     * yang sama (anti-IDOR): pemilik & admin boleh; customer lain dilarang.
     */
    public function test_payment_proof_route_enforces_ownership_and_admin_access(): void
    {
        Storage::disk('local')->put('proofs/private-payment-proof.png', 'png-bytes');

        $payment = Payment::create([
            'order_id' => $this->activeOrder->id,
            'method' => 'qris',
            'amount' => $this->activeOrder->total,
            'status' => 'pending',
            'reference' => 'PAY-PROOF-001',
            'proof_image' => 'proofs/private-payment-proof.png',
        ]);

        $url = route('file.payment-proof', $payment->id);

        // Pemilik booking diperbolehkan melihat bukti pembayarannya sendiri.
        $this->withSession($this->customerSession($this->user))
            ->get($url)
            ->assertOk();

        // Customer lain dilarang (anti-IDOR).
        $this->withSession($this->customerSession($this->otherUser))
            ->get($url)
            ->assertForbidden();

        // Admin boleh melihat semua bukti.
        $this->withSession($this->adminSession())
            ->get($url)
            ->assertOk();
    }
}