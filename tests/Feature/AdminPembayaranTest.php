<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPembayaranTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        $user = User::firstOrCreate(
            ['email' => 'julian.d@example.com'],
            ['name' => 'Julian De Marco', 'username' => 'juliand', 'password' => 'password']
        );

        $order = Order::firstOrCreate(
            ['code' => 'RS-99281-TEST'],
            [
                'user_id' => $user->id,
                'rent_start' => now()->startOfDay(),
                'rent_end' => now()->addDays(5)->endOfDay(),
                'subtotal' => 1400000,
                'service_fee' => 50000,
                'discount' => 0,
                'total' => 1450000,
                'status' => 'pending',
            ]
        );

        Payment::firstOrCreate(
            ['order_id' => $order->id],
            [
                'method' => 'bank_transfer',
                'amount' => 1450000,
                'status' => 'pending',
                'reference' => '7728399102-X',
            ]
        );
    }

    public function test_admin_can_access_pembayaran_page(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/pembayaran');

        $response->assertStatus(200);
        $response->assertSee('Pembayaran');
        $response->assertSee('Financial Audit Log');
        $response->assertSee('TOTAL REVENUE');
        $response->assertSee('PENDING');
        $response->assertSee('FAILED');
        $response->assertSee('SUCCESS RATE');
        $response->assertSee('Payment Verification');
        $response->assertSee('Export CSV');
        $response->assertSee('Julian De Marco');
    }

    public function test_customer_cannot_access_pembayaran_page(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Customer Summit',
            'account_role' => 'customer',
        ])->get('/admin/pembayaran');

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_when_accessing_pembayaran_page(): void
    {
        $response = $this->get('/admin/pembayaran');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_approve_payment(): void
    {
        $user = User::first();
        $order = Order::create([
            'code' => 'RS-PAY-APPROVE',
            'user_id' => $user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(3)->endOfDay(),
            'subtotal' => 500000,
            'total' => 500000,
            'status' => 'pending',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'bank_transfer',
            'amount' => 500000,
            'status' => 'pending',
            'reference' => 'REF-APPROVE-123',
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post('/admin/pembayaran/' . $payment->id . '/approve');

        $response->assertRedirect('/admin/pembayaran');
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'success']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'active']);
    }

    public function test_admin_can_reject_payment(): void
    {
        $user = User::first();
        $order = Order::create([
            'code' => 'RS-PAY-REJECT',
            'user_id' => $user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(3)->endOfDay(),
            'subtotal' => 500000,
            'total' => 500000,
            'status' => 'pending',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'bank_transfer',
            'amount' => 500000,
            'status' => 'pending',
            'reference' => 'REF-REJECT-123',
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post('/admin/pembayaran/' . $payment->id . '/reject', [
            'reason' => 'Nominal transfer tidak sesuai dengan invoice',
        ]);

        $response->assertRedirect('/admin/pembayaran');
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }

    public function test_admin_can_export_csv_pembayaran(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/pembayaran/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
