<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Order $paidOrder;

    private int $adminId;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Admin::create([
            'name' => 'Admin Summit',
            'email' => 'admin.refund@summit.test',
            'password' => 'password',
        ]);
        $this->adminId = $admin->id_admin;

        $this->customer = User::create([
            'name' => 'Customer Refund',
            'email' => 'refund.customer@example.com',
            'username' => 'refundcust',
            'password' => 'password',
        ]);

        $this->paidOrder = Order::create([
            'code' => 'RS-REFUND-0001',
            'user_id' => $this->customer->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(3)->endOfDay(),
            'subtotal' => 1000000,
            'service_fee' => 50000,
            'discount' => 0,
            'total' => 1050000,
            'status' => 'active',
        ]);

        Payment::create([
            'order_id' => $this->paidOrder->id,
            'method' => 'bank_transfer',
            'amount' => 1050000,
            'status' => 'success',
            'reference' => 'REF-REFUND-PAY-001',
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
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ], $overrides);
    }

    private function submitRefund(int $orderId, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->withSession($this->customerSession())
            ->post('/history/' . $orderId . '/refund', array_merge([
                'reason' => 'tidak_jadi',
                'description' => 'Tidak jadi menggunakan barang karena perubahan jadwal.',
            ], $payload));
    }

    public function test_customer_can_submit_refund_for_paid_order(): void
    {
        $response = $this->submitRefund($this->paidOrder->id);

        $response->assertRedirect(route('history'));
        $this->assertDatabaseHas('refunds', [
            'order_id' => $this->paidOrder->id,
            'user_id' => $this->customer->id,
            'status' => Refund::STATUS_PENDING,
            'original_amount' => 1050000,
            'refund_amount' => 1050000,
            'reason' => 'Tidak jadi menggunakan barang',
        ]);

        $refund = Refund::where('order_id', $this->paidOrder->id)->first();
        $this->assertNotNull($refund);
        $this->assertMatchesRegularExpression('/^RF-00\d$/', $refund->code);
        $this->assertSame('Pengembalian Dana Menunggu', $refund->status_label);
    }

    public function test_customer_cannot_submit_refund_for_unpaid_order(): void
    {
        $pendingOrder = Order::create([
            'code' => 'RS-REFUND-UNPAID',
            'user_id' => $this->customer->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(2)->endOfDay(),
            'subtotal' => 500000,
            'total' => 500000,
            'status' => 'pending',
        ]);

        $response = $this->submitRefund($pendingOrder->id);

        $response->assertSessionHasErrors('refund');
        $this->assertDatabaseMissing('refunds', ['order_id' => $pendingOrder->id]);
    }

    public function test_customer_cannot_submit_refund_for_other_users_order(): void
    {
        $otherUser = User::create([
            'name' => 'Other Customer',
            'email' => 'other.refund@example.com',
            'username' => 'otherrefund',
            'password' => 'password',
        ]);

        $otherOrder = Order::create([
            'code' => 'RS-REFUND-OTHER',
            'user_id' => $otherUser->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(2)->endOfDay(),
            'subtotal' => 800000,
            'total' => 800000,
            'status' => 'active',
        ]);

        $response = $this->submitRefund($otherOrder->id);

        $response->assertForbidden();
        $this->assertDatabaseMissing('refunds', ['order_id' => $otherOrder->id]);
    }

    public function test_guest_is_redirected_when_submitting_refund(): void
    {
        $response = $this->post('/history/' . $this->paidOrder->id . '/refund', [
            'reason' => 'tidak_jadi',
            'description' => 'test',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('refunds', ['order_id' => $this->paidOrder->id]);
    }

    public function test_duplicate_refund_is_blocked_while_pending(): void
    {
        $this->submitRefund($this->paidOrder->id);

        $response = $this->submitRefund($this->paidOrder->id);

        $response->assertSessionHasErrors('refund');
        $this->assertSame(1, Refund::where('order_id', $this->paidOrder->id)->count());
    }

    public function test_customer_cannot_access_admin_refund_page(): void
    {
        $response = $this->withSession($this->customerSession())->get('/admin/refund');

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_from_admin_refund_page(): void
    {
        $response = $this->get('/admin/refund');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_list_and_open_refund_detail(): void
    {
        $this->submitRefund($this->paidOrder->id);
        $refund = Refund::first();

        $list = $this->withSession($this->adminSession())->get('/admin/refund');
        $list->assertStatus(200);
        $list->assertSee($refund->code);

        $detail = $this->withSession($this->adminSession())->get('/admin/refund/' . $refund->id);
        $detail->assertStatus(200);
        $detail->assertSee('Refund Detail');
        $detail->assertSee($refund->code);
        $detail->assertSee($this->customer->name);
    }

    public function test_admin_can_approve_refund_and_notify_owner(): void
    {
        $this->submitRefund($this->paidOrder->id);
        $refund = Refund::first();

        $response = $this->withSession($this->adminSession())->post('/admin/refund/' . $refund->id . '/approve');

        $response->assertRedirect(route('admin.refund'));
        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_APPROVED,
            'processed_by' => $this->adminId,
        ]);
        $this->assertNotNull($refund->fresh()->approved_at);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->customer->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_admin_can_reject_refund_with_reason_and_notify_owner(): void
    {
        $this->submitRefund($this->paidOrder->id);
        $refund = Refund::first();

        $response = $this->withSession($this->adminSession())
            ->post('/admin/refund/' . $refund->id . '/reject', [
                'reject_reason' => 'Barang sudah digunakan, tidak memenuhi syarat refund.',
            ]);

        $response->assertRedirect(route('admin.refund'));
        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_REJECTED,
            'reject_reason' => 'Barang sudah digunakan, tidak memenuhi syarat refund.',
        ]);
        $this->assertNotNull($refund->fresh()->rejected_at);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->customer->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_reject_requires_reason(): void
    {
        $this->submitRefund($this->paidOrder->id);
        $refund = Refund::first();

        $response = $this->withSession($this->adminSession())
            ->post('/admin/refund/' . $refund->id . '/reject');

        $response->assertSessionHasErrors('reject_reason');
        $this->assertDatabaseHas('refunds', ['id' => $refund->id, 'status' => Refund::STATUS_PENDING]);
    }

    public function test_admin_can_complete_refund_and_mark_payment_refunded(): void
    {
        $this->submitRefund($this->paidOrder->id);
        $refund = Refund::first();

        $this->withSession($this->adminSession())->post('/admin/refund/' . $refund->id . '/approve');

        $response = $this->withSession($this->adminSession())->post('/admin/refund/' . $refund->id . '/complete');

        $response->assertRedirect(route('admin.refund'));
        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_COMPLETED,
        ]);
        $this->assertNotNull($refund->fresh()->completed_at);
        $this->assertDatabaseHas('payments', [
            'id' => $this->paidOrder->payments->first()->id,
            'status' => 'refunded',
        ]);
        $this->assertEquals(2, $this->customer->notifications()->count());
    }

    public function test_admin_cannot_complete_unapproved_refund(): void
    {
        $this->submitRefund($this->paidOrder->id);
        $refund = Refund::first();

        $response = $this->withSession($this->adminSession())->post('/admin/refund/' . $refund->id . '/complete');

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('refunds', ['id' => $refund->id, 'status' => Refund::STATUS_PENDING]);
    }

    public function test_history_page_displays_refund_status_and_request_button(): void
    {
        $this->submitRefund($this->paidOrder->id);

        $response = $this->withSession($this->customerSession())->get('/history');

        $response->assertStatus(200);
        $response->assertSee('Ajukan Refund');
        $response->assertSee('Pengembalian Dana Menunggu');
        $response->assertSee('ALASAN REFUND');
    }

    public function test_mark_all_read_clears_notifications(): void
    {
        $this->submitRefund($this->paidOrder->id);
        $refund = Refund::first();

        $this->withSession($this->adminSession())->post('/admin/refund/' . $refund->id . '/approve');

        $this->assertSame(1, $this->customer->unreadNotifications()->count());

        $response = $this->withSession($this->customerSession())->post('/notifications/read-all');

        $response->assertRedirect();
        $this->assertSame(0, $this->customer->unreadNotifications()->count());
    }

    public function test_navbar_renders_with_unread_refund_notifications(): void
    {
        $this->submitRefund($this->paidOrder->id);
        $refund = Refund::first();

        $this->withSession($this->adminSession())->post('/admin/refund/' . $refund->id . '/approve');

        $response = $this->withSession($this->customerSession())->get('/history');

        $response->assertStatus(200);
        $response->assertSee('Refund Disetujui');
        $response->assertSee('Pengembalian Dana Disetujui');
    }
}