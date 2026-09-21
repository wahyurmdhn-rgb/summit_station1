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

class FullIntegrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_end_to_end_rental_lifecycle(): void
    {
        // 1. Setup Master Data
        $category = Category::create([
            'name' => 'Tents & Shelters',
            'slug' => 'tents-shelters',
            'description' => 'Expedition grade tents',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-TEN-001',
            'name' => 'Apex Sentinel 4P',
            'subtitle' => '4-Season Expedition Tent',
            'price_per_day' => 125000,
            'stock_total' => 10,
            'stock_available' => 10,
            'is_active' => true,
        ]);

        $admin = Admin::create([
            'name' => 'Admin Summit',
            'email' => 'admin@summit.test',
            'password' => 'password',
        ]);

        // 2. User Registers and Logins
        $registerResponse = $this->post('/register', [
            'name' => 'Rangga Pratama',
            'username' => 'rangga_hike',
            'email' => 'rangga@summit.id',
            'phone' => '08123456789',
            'domicile' => 'Jakarta',
            'date_of_birth' => '1999-01-01',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => 'on',
        ]);
        $registerResponse->assertRedirect('/');

        $user = User::where('email', 'rangga@summit.id')->first();
        $this->assertNotNull($user);

        // 3. User browses Catalog and Product Detail
        $catalogResponse = $this->get('/catalog');
        $catalogResponse->assertStatus(200);
        $catalogResponse->assertSee('Apex Sentinel 4P');

        $detailResponse = $this->get('/catalog/' . $product->id);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Apex Sentinel 4P');

        // 4. User adds item to cart
        $cartAddResponse = $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ])->post('/cart/add', [
            'product_id' => $product->id,
            'days' => 4,
            'quantity' => 1,
        ]);
        $cartAddResponse->assertRedirect('/cart');

        // 5. User checks out and initiates Payment (QRIS)
        Storage::fake('public');
        $paymentProcessResponse = $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
            'payment_deadline' => time() + 300,
            'cart_items' => [
                $product->id => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'days' => 4,
                    'quantity' => 1,
                    'price_per_day' => 125000,
                    'subtotal' => 500000,
                    'image' => 'tent.jpg',
                ]
            ]
        ])->post('/payment/process', [
            'payment_method' => 'qris',
            'proof' => UploadedFile::fake()->create('proof.jpg', 100),
        ]);
        $paymentProcessResponse->assertRedirect('/history');

        // Verify order created in DB
        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->status);

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);

        // 6. Admin logs in
        $adminLoginResponse = $this->post('/admin/login', [
            'email' => 'admin@summit.test',
            'password' => 'password',
        ]);
        $adminLoginResponse->assertRedirect(route('admin.dashboard'));

        // Admin views Pembayaran
        $adminPembayaranResponse = $this->get('/admin/pembayaran');
        $adminPembayaranResponse->assertStatus(200);
        $adminPembayaranResponse->assertSee($order->code);

        // Admin approves Payment
        $approveResponse = $this->post('/admin/pembayaran/' . $payment->id . '/approve');
        $approveResponse->assertRedirect('/admin/pembayaran');

        $order->refresh();
        $payment->refresh();
        $this->assertEquals('active', $order->status);
        $this->assertEquals('success', $payment->status);

        // 7. Admin records return inspection and completes return
        $recordReturnResponse = $this->post('/admin/pengembalian/' . $order->id . '/record', [
            'condition' => 'excellent',
            'inspection_note' => 'Barang lengkap dan bersih',
        ]);
        $recordReturnResponse->assertRedirect('/admin/pengembalian');

        $completeReturnResponse = $this->post('/admin/pengembalian/' . $order->id . '/complete');
        $completeReturnResponse->assertRedirect('/admin/pengembalian');

        $order->refresh();
        $this->assertEquals('completed', $order->status);

        // 8. User logs back in and views history
        $this->post('/login', [
            'email' => 'rangga@summit.id',
            'password' => 'password123',
        ]);

        $userHistoryResponse = $this->get('/history');
        $userHistoryResponse->assertStatus(200);
        $userHistoryResponse->assertSee($order->code);
        $userHistoryResponse->assertSee('Selesai');

        // 9. Admin views Laporan & Website
        $this->post('/admin/login', [
            'email' => 'admin@summit.test',
            'password' => 'password',
        ]);

        $adminLaporanResponse = $this->get('/admin/laporan');
        $adminLaporanResponse->assertStatus(200);
        $adminLaporanResponse->assertSee('Laporan Operasional', false);

        $adminWebsiteResponse = $this->get('/admin/website');
        $adminWebsiteResponse->assertStatus(200);
        $adminWebsiteResponse->assertSee('Manajemen Website');
    }
}
