<?php

namespace Tests\Smoke;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Smoke test: verifies the DB-driven refund + notification flow against the
 * MySQL dev database (summit_station12). Uses .env DB config; no RefreshDatabase,
 * so dev data is preserved. All created rows are cleaned up in tearDown.
 */
class RefundMysqlSmokeTest extends TestCase
{
    private array $cleanupUsers = [];

    private array $cleanupAdmins = [];

    private array $cleanupNotifications = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupNotifications as $id) {
            DB::table('notifications')->where('id', $id)->delete();
        }
        foreach ($this->cleanupUsers as $id) {
            DB::table('users')->where('id', $id)->delete();
        }
        foreach ($this->cleanupAdmins as $id) {
            DB::table('admin')->where('id_admin', $id)->delete();
        }
        parent::tearDown();
    }

    private function makeAdmin(): Admin
    {
        $admin = Admin::create([
            'name' => 'Smoke Admin',
            'email' => 'smoke.admin.' . uniqid() . '@summit.test',
            'password' => 'password',
        ]);
        $this->cleanupAdmins[] = $admin->id_admin;
        return $admin;
    }

    private function makeCustomer(string $name = 'Smoke Customer'): User
    {
        $user = User::create([
            'name' => $name,
            'email' => 'smoke.customer.' . uniqid() . '@summit.test',
            'username' => 'smoke_' . uniqid(),
            'password' => 'password',
        ]);
        $this->cleanupUsers[] = $user->id;
        return $user;
    }

    private function makePaidOrder(User $user, int $total = 1050000): Order
    {
        $order = Order::create([
            'code' => 'RS-SMOKE-' . strtoupper(uniqid()),
            'user_id' => $user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(3)->endOfDay(),
            'subtotal' => $total,
            'service_fee' => 0,
            'discount' => 0,
            'total' => $total,
            'status' => 'active',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'method' => 'bank_transfer',
            'amount' => $total,
            'status' => 'success',
            'reference' => 'SMOKE-PAY-' . uniqid(),
        ]);

        return $order;
    }

    private function customerSession(User $user): array
    {
        return [
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ];
    }

    private function adminSession(Admin $admin): array
    {
        return [
            'account_id' => $admin->id_admin,
            'account_name' => $admin->name,
            'account_role' => 'admin',
        ];
    }

    public function test_full_refund_lifecycle_on_mysql(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer('Budi Refund');
        $paidOrder = $this->makePaidOrder($customer, 1050000);

        $this->assertEquals('mysql', config('database.default'));
        $this->assertSame('summit_station12', config('database.connections.mysql.database'));

        // ─── 1. Customer submits refund for own paid order ───
        $response = $this->withSession($this->customerSession($customer))
            ->post('/history/' . $paidOrder->id . '/refund', [
                'reason' => 'tidak_jadi',
                'description' => 'Tidak jadi menggunakan barang karena perubahan jadwal.',
            ]);
        $response->assertRedirect(route('history'));

        $refund = Refund::where('order_id', $paidOrder->id)->first();
        $this->assertNotNull($refund, 'Refund row must be created');
        $this->assertEquals(Refund::STATUS_PENDING, $refund->status);
        $this->assertEquals(1050000, $refund->original_amount);
        $this->assertEquals(1050000, $refund->refund_amount);
        $this->assertEquals('Tidak jadi menggunakan barang', $refund->reason);
        $this->assertEquals($customer->id, $refund->user_id);
        $this->assertMatchesRegularExpression('/^RF-\d+$/', $refund->code);
        $this->assertSame('Pengembalian Dana Menunggu', $refund->status_label);

        // DB relationships resolve
        $this->assertEquals($customer->id, $refund->order->user->id);
        $this->assertEquals($paidOrder->id, $refund->payment->order_id);

        // ─── 2. History page shows refund request + pending badge ───
        $history = $this->withSession($this->customerSession($customer))->get('/history');
        $history->assertStatus(200);
        $history->assertSee('Ajukan Refund');
        $history->assertSee('Pengembalian Dana Menunggu');

        // ─── 3. Duplicate while pending is blocked ───
        $dup = $this->withSession($this->customerSession($customer))
            ->post('/history/' . $paidOrder->id . '/refund', [
                'reason' => 'ganti_pesanan',
                'description' => 'duplikat',
            ]);
        $dup->assertSessionHasErrors('refund');
        $this->assertEquals(1, Refund::where('order_id', $paidOrder->id)->count());

        // ─── 4. Backend ignores client-supplied amount (amount from server payment) ───
        $otherPaidOrder = $this->makePaidOrder($customer, 750000);
        $this->withSession($this->customerSession($customer))
            ->post('/history/' . $otherPaidOrder->id . '/refund', [
                'reason' => 'lainnya',
                'description' => 'uji manipulasi amount',
                'refund_amount' => 1,
                'amount' => 999999,
            ]);
        $otherRefund = Refund::where('order_id', $otherPaidOrder->id)->first();
        $this->assertNotNull($otherRefund);
        $this->assertEquals(750000, $otherRefund->original_amount);
        $this->assertEquals(750000, $otherRefund->refund_amount);
        $this->assertNotEquals(1, $otherRefund->refund_amount);
        $this->assertNotEquals(999999, $otherRefund->refund_amount);

        // ─── 5. Customer cannot submit refund for another user's order ───
        $otherCustomer = $this->makeCustomer('Orang Lain');
        $otherUserOrder = $this->makePaidOrder($otherCustomer, 900000);
        $denied = $this->withSession($this->customerSession($customer))
            ->post('/history/' . $otherUserOrder->id . '/refund', [
                'reason' => 'tidak_jadi',
                'description' => 'tidak boleh',
            ]);
        $denied->assertForbidden();
        $this->assertNull(Refund::where('order_id', $otherUserOrder->id)->first());

        // ─── 6. Guest is redirected to login ───
        $this->flushSession();
        $guest = $this->post('/history/' . $paidOrder->id . '/refund', [
            'reason' => 'tidak_jadi',
            'description' => 'guest',
        ]);
        $guest->assertRedirect(route('login'));

        // ─── 7. Customer cannot access admin refund pages ───
        $this->withSession($this->customerSession($customer))->get('/admin/refund')->assertForbidden();
        $this->withSession($this->customerSession($customer))->get('/admin/refund/' . $refund->id)->assertForbidden();

        // ─── 8. Admin lists + opens refund detail ───
        $list = $this->withSession($this->adminSession($admin))->get('/admin/refund');
        $list->assertStatus(200);
        $this->assertTrue(str_contains($list->getContent(), 'Pengembalian Dana Menunggu'));

        $detail = $this->withSession($this->adminSession($admin))->get('/admin/refund/' . $refund->id);
        $detail->assertStatus(200);
        $detail->assertSee($refund->code);

        // ─── 9. Admin cannot complete an unapproved refund ───
        $premature = $this->withSession($this->adminSession($admin))
            ->post('/admin/refund/' . $refund->id . '/complete');
        $premature->assertSessionHas('error');
        $this->assertSame(Refund::STATUS_PENDING, $refund->fresh()->status);

        // ─── 10. Reject requires a reason ───
        $noReason = $this->withSession($this->adminSession($admin))
            ->post('/admin/refund/' . $refund->id . '/reject');
        $noReason->assertSessionHasErrors('reject_reason');
        $this->assertSame(Refund::STATUS_PENDING, $refund->fresh()->status);

        // ─── 11. Reject with reason → REJECTED + notification to owner ───
        $reject = $this->withSession($this->adminSession($admin))
            ->post('/admin/refund/' . $refund->id . '/reject', [
                'reject_reason' => 'Barang sudah dipakai, tidak memenuhi syarat refund.',
            ]);
        $reject->assertRedirect(route('admin.refund'));
        $reject->assertSessionHas('success');
        $refund->refresh();
        $this->assertSame(Refund::STATUS_REJECTED, $refund->status);
        $this->assertNotNull($refund->rejected_at);
        $this->assertEquals($admin->id_admin, $refund->processed_by);
        $this->assertEquals('Barang sudah dipakai, tidak memenuhi syarat refund.', $refund->reject_reason);
        $this->registerNotifications($customer);

        // ─── 12. Approve the other refund (PENDING → APPROVED) ───
        $approve = $this->withSession($this->adminSession($admin))
            ->post('/admin/refund/' . $otherRefund->id . '/approve');
        $approve->assertRedirect(route('admin.refund'));
        $approve->assertSessionHas('success');
        $otherRefund->refresh();
        $this->assertSame(Refund::STATUS_APPROVED, $otherRefund->status);
        $this->assertNotNull($otherRefund->approved_at);
        $this->assertEquals($admin->id_admin, $otherRefund->processed_by);
        $this->assertEquals(750000, $otherRefund->refund_amount);
        $this->registerNotifications($customer);

        // ─── 13. Complete approved refund → COMPLETED + payment refunded ───
        $complete = $this->withSession($this->adminSession($admin))
            ->post('/admin/refund/' . $otherRefund->id . '/complete');
        $complete->assertRedirect(route('admin.refund'));
        $complete->assertSessionHas('success');
        $otherRefund->refresh();
        $this->assertSame(Refund::STATUS_COMPLETED, $otherRefund->status);
        $this->assertNotNull($otherRefund->completed_at);
        $this->assertEquals('refunded', $otherRefund->payment->fresh()->status);
        $this->registerNotifications($customer);

        // ─── 14. Notification badge: customer has unread for reject + approve + complete ───
        $this->assertSame(3, $customer->unreadNotifications()->count());

        // History page badge reflects unread (rendered by navbar)
        $this->withSession($this->customerSession($customer))->get('/history')->assertStatus(200);

        // ─── 15. Mark all read clears badge ───
        $readAll = $this->withSession($this->customerSession($customer))
            ->post('/notifications/read-all');
        $readAll->assertRedirect();
        $this->assertSame(0, $customer->unreadNotifications()->count());

        // ─── 16. Customer attempting admin actions is forbidden ───
        $otherRefund->refresh();
        $this->withSession($this->customerSession($customer))
            ->post('/admin/refund/' . $otherRefund->id . '/approve')
            ->assertStatus(403);
        $this->withSession($this->customerSession($customer))
            ->post('/admin/refund/' . $otherRefund->id . '/reject', ['reject_reason' => 'x'])
            ->assertStatus(403);
        $this->withSession($this->customerSession($customer))
            ->post('/admin/refund/' . $otherRefund->id . '/complete')
            ->assertStatus(403);
        $this->assertSame(Refund::STATUS_COMPLETED, $otherRefund->fresh()->status);

        // ─── 17. Admin dashboard shows refund sidebar entry ───
        $dashboard = $this->withSession($this->adminSession($admin))->get('/admin/dashboard');
        $dashboard->assertStatus(200);
        $this->assertTrue(str_contains($dashboard->getContent(), 'Refund'));

        fwrite(STDERR, 'SMOKE OK: database=' . config('database.connections.mysql.database')
            . ' refunds_created=' . $otherRefund->id . ' badges=3->0' . "\n");
    }

    private function registerNotifications(User $user): void
    {
        $ids = $user->notifications()->whereNull('read_at')->pluck('id')->all();
        foreach ($ids as $id) {
            $this->cleanupNotifications[] = $id;
        }
    }
}