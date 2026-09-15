<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ReturnRecord;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            BundleSeeder::class,
        ]);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'avatar_path' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&q=80',
            ]
        );

        $customer = User::firstOrCreate(
            ['email' => 'customer@summit.test'],
            [
                'name' => 'Customer Summit',
                'username' => 'customer',
                'domicile' => 'Jakarta',
                'avatar_path' => 'https://images.unsplash.com/photo-1527980965255-d3b416303d12?auto=format&fit=crop&w=200&q=80',
                'password' => 'password',
            ]
        );

        $aris = User::firstOrCreate(
            ['email' => 'aris.h@example.com'],
            [
                'name' => 'Aris Hidayat',
                'username' => 'arish',
                'domicile' => 'Bandung',
                'avatar_path' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=200&q=80',
                'password' => 'password',
            ]
        );

        $siti = User::firstOrCreate(
            ['email' => 'siti.m@domain.io'],
            [
                'name' => 'Siti Maemunah',
                'username' => 'sitim',
                'domicile' => 'Surabaya',
                'avatar_path' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=200&q=80',
                'password' => 'password',
            ]
        );

        $budi = User::firstOrCreate(
            ['email' => 'budi.k@web.com'],
            [
                'name' => 'Budi Kusuma',
                'username' => 'budik',
                'domicile' => 'Yogyakarta',
                'avatar_path' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=200&q=80',
                'password' => 'password',
            ]
        );

        Admin::firstOrCreate(
            ['email' => 'admin@summit.test'],
            [
                'name' => 'Admin Summit',
                'password' => 'password',
            ]
        );

        $this->seedDemoOrders($customer);
        $this->seedMockupOrders($aris, $siti, $budi);
        $this->seedPaymentMockupData();
        $this->seedReturnMockupData();
        $this->seedUserMockupData();
    }

    private function seedUserMockupData(): void
    {
        $users = [
            [
                'name' => 'Aris Setiawan',
                'username' => 'aris_mountain',
                'email' => 'aris.s@trekking.com',
                'phone' => '081234567890',
                'domicile' => 'Malang, Jawa Timur',
                'avatar_path' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80',
                'status' => 'active',
                'role' => 'user',
                'password' => 'password',
            ],
            [
                'name' => 'Dian Wijaya',
                'username' => 'dian_trek',
                'email' => 'dian.w@explore.id',
                'phone' => '082198765432',
                'domicile' => 'Bandung, Jawa Barat',
                'avatar_path' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=200&q=80',
                'status' => 'inactive',
                'role' => 'user',
                'password' => 'password',
            ],
            [
                'name' => 'Bambang Kurnia',
                'username' => 'bambang_k',
                'email' => 'bkurnia@web.com',
                'phone' => '085712345678',
                'domicile' => 'Jakarta Selatan',
                'avatar_path' => null, // Will render initials BK
                'status' => 'active',
                'role' => 'user',
                'password' => 'password',
            ],
            [
                'name' => 'Rina Melati',
                'username' => 'melati_adventure',
                'email' => 'rina.mel@hike.co',
                'phone' => '087812349988',
                'domicile' => 'Denpasar, Bali',
                'avatar_path' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=200&q=80',
                'status' => 'suspended',
                'role' => 'user',
                'password' => 'password',
            ],
            [
                'name' => 'Maya Anggraini',
                'username' => 'maya_climber',
                'email' => 'maya.a@summit.org',
                'phone' => '081399887766',
                'domicile' => 'Yogyakarta, DIY',
                'avatar_path' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=200&q=80',
                'status' => 'pending_verification',
                'role' => 'user',
                'password' => 'password',
            ],
            [
                'name' => 'Fajar Pratama',
                'username' => 'fajar_peaks',
                'email' => 'fajar.p@outdoor.net',
                'phone' => '082233445566',
                'domicile' => 'Surabaya, Jawa Timur',
                'avatar_path' => null,
                'status' => 'active',
                'role' => 'user',
                'password' => 'password',
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                $u
            );
        }
    }

    private function seedPaymentMockupData(): void
    {
        $julian = User::firstOrCreate(
            ['email' => 'julian.d@example.com'],
            ['name' => 'Julian De Marco', 'username' => 'juliand', 'domicile' => 'Jakarta Selatan', 'password' => 'password']
        );

        $sarah = User::firstOrCreate(
            ['email' => 'sarah.w@example.com'],
            ['name' => 'Sarah Widjaja', 'username' => 'sarahw', 'domicile' => 'Surabaya Barat', 'password' => 'password']
        );

        $bambang = User::firstOrCreate(
            ['email' => 'bambang.k@example.com'],
            ['name' => 'Bambang Kusuma', 'username' => 'bambangk', 'domicile' => 'Bandung Utara', 'password' => 'password']
        );

        $tent = Product::where('sku', 'TENT-001-OR')->first() ?? Product::first();
        $pack = Product::where('sku', 'BACK-042-GR')->first() ?? Product::first();

        // 1. Julian De Marco - Pending Verification
        if (! Order::where('code', 'RS-99281-Z')->exists()) {
            $ord1 = Order::create([
                'code' => 'RS-99281-Z',
                'user_id' => $julian->id,
                'rent_start' => now()->startOfDay(),
                'rent_end' => now()->addDays(5)->endOfDay(),
                'subtotal' => 1400000,
                'service_fee' => 50000,
                'discount' => 0,
                'total' => 1450000,
                'status' => 'pending',
                'created_at' => now()->setTime(14, 22),
            ]);

            if ($tent) {
                OrderItem::create([
                    'order_id' => $ord1->id,
                    'product_id' => $tent->id,
                    'name' => $tent->name,
                    'image' => $tent->main_image,
                    'quantity' => 1,
                    'days' => 5,
                    'unit_price' => 250000,
                    'subtotal' => 1250000,
                ]);
            }

            Payment::create([
                'order_id' => $ord1->id,
                'method' => 'bank_transfer',
                'amount' => 1450000,
                'status' => 'pending',
                'reference' => '7728399102-X',
                'created_at' => now()->setTime(14, 22),
            ]);
        }

        // 2. Sarah Widjaja - Confirmed
        if (! Order::where('code', 'RS-99275-K')->exists()) {
            $ord2 = Order::create([
                'code' => 'RS-99275-K',
                'user_id' => $sarah->id,
                'rent_start' => now()->startOfDay(),
                'rent_end' => now()->addDays(4)->endOfDay(),
                'subtotal' => 850000,
                'service_fee' => 40000,
                'discount' => 0,
                'total' => 890000,
                'status' => 'active',
                'paid_at' => now()->setTime(12, 5),
                'created_at' => now()->setTime(12, 5),
            ]);

            if ($pack) {
                OrderItem::create([
                    'order_id' => $ord2->id,
                    'product_id' => $pack->id,
                    'name' => $pack->name,
                    'image' => $pack->main_image,
                    'quantity' => 1,
                    'days' => 4,
                    'unit_price' => 125000,
                    'subtotal' => 500000,
                ]);
            }

            Payment::create([
                'order_id' => $ord2->id,
                'method' => 'mandiri_va',
                'amount' => 890000,
                'status' => 'success',
                'reference' => '883921102-A',
                'paid_at' => now()->setTime(12, 5),
                'created_at' => now()->setTime(12, 5),
            ]);
        }

        // 3. Bambang Kusuma - Failed / Declined
        if (! Order::where('code', 'RS-99264-L')->exists()) {
            $ord3 = Order::create([
                'code' => 'RS-99264-L',
                'user_id' => $bambang->id,
                'rent_start' => now()->subDay()->startOfDay(),
                'rent_end' => now()->addDays(3)->endOfDay(),
                'subtotal' => 2050000,
                'service_fee' => 50000,
                'discount' => 0,
                'total' => 2100000,
                'status' => 'cancelled',
                'created_at' => now()->subDay()->setTime(18, 45),
            ]);

            Payment::create([
                'order_id' => $ord3->id,
                'method' => 'gopay',
                'amount' => 2100000,
                'status' => 'failed',
                'reference' => '993810023-B',
                'created_at' => now()->subDay()->setTime(18, 45),
            ]);
        }
    }

    private function seedMockupOrders(User $aris, User $siti, User $budi): void
    {
        $osprey = Product::where('sku', 'SS-BPK-102')->first() ?? Product::first();
        $msr = Product::where('sku', 'SS-TEN-055')->first() ?? Product::first();
        $lowa = Product::where('sku', 'SS-FTW-021')->first() ?? Product::first();

        // 1. Aris Hidayat - Active Order
        if (! Order::where('code', 'RS-1020-001')->exists()) {
            $order1 = Order::create([
                'code' => 'RS-1020-001',
                'user_id' => $aris->id,
                'rent_start' => now()->subDays(1)->startOfDay(),
                'rent_end' => now()->addDays(3)->endOfDay(),
                'subtotal' => 340000,
                'service_fee' => 20000,
                'discount' => 0,
                'total' => 360000,
                'status' => 'active',
                'paid_at' => now()->subDays(1),
                'created_at' => now()->subDays(1)->setTime(9, 45),
            ]);

            if ($osprey) {
                OrderItem::create([
                    'order_id' => $order1->id,
                    'product_id' => $osprey->id,
                    'name' => $osprey->name,
                    'image' => $osprey->main_image,
                    'quantity' => 1,
                    'days' => 4,
                    'unit_price' => 85000,
                    'subtotal' => 340000,
                ]);
            }

            Payment::create([
                'order_id' => $order1->id,
                'method' => 'qris',
                'amount' => 360000,
                'status' => 'success',
                'reference' => 'QRIS-1020-001',
                'paid_at' => now()->subDays(1),
            ]);
        }

        // 2. Siti Maemunah - Pending Order
        if (! Order::where('code', 'RS-1022-002')->exists()) {
            $order2 = Order::create([
                'code' => 'RS-1022-002',
                'user_id' => $siti->id,
                'rent_start' => now()->addDay()->startOfDay(),
                'rent_end' => now()->addDays(4)->endOfDay(),
                'subtotal' => 420000,
                'service_fee' => 25000,
                'discount' => 0,
                'total' => 445000,
                'status' => 'pending',
                'created_at' => now()->setTime(14, 20),
            ]);

            if ($msr) {
                OrderItem::create([
                    'order_id' => $order2->id,
                    'product_id' => $msr->id,
                    'name' => $msr->name,
                    'image' => $msr->main_image,
                    'quantity' => 1,
                    'days' => 3,
                    'unit_price' => 140000,
                    'subtotal' => 420000,
                ]);
            }

            Payment::create([
                'order_id' => $order2->id,
                'method' => 'gopay',
                'amount' => 445000,
                'status' => 'pending',
                'reference' => 'GOPAY-1022-002',
            ]);
        }

        // 3. Budi Kusuma - Completed Order
        if (! Order::where('code', 'RS-1012-003')->exists()) {
            $order3 = Order::create([
                'code' => 'RS-1012-003',
                'user_id' => $budi->id,
                'rent_start' => now()->subDays(14)->startOfDay(),
                'rent_end' => now()->subDays(7)->endOfDay(),
                'subtotal' => 455000,
                'service_fee' => 20000,
                'discount' => 0,
                'total' => 475000,
                'status' => 'completed',
                'paid_at' => now()->subDays(14),
                'created_at' => now()->subDays(14)->setTime(10, 0),
            ]);

            if ($lowa) {
                OrderItem::create([
                    'order_id' => $order3->id,
                    'product_id' => $lowa->id,
                    'name' => $lowa->name,
                    'image' => $lowa->main_image,
                    'quantity' => 1,
                    'days' => 7,
                    'unit_price' => 65000,
                    'subtotal' => 455000,
                ]);
            }

            Payment::create([
                'order_id' => $order3->id,
                'method' => 'bank_transfer',
                'amount' => 475000,
                'status' => 'success',
                'reference' => 'TRF-1012-003',
                'paid_at' => now()->subDays(14),
            ]);
        }
    }

    private function seedDemoOrders(User $customer): void
    {
        if (Order::where('user_id', $customer->id)->exists()) {
            return;
        }

        $tent = Product::where('sku', 'SS-TEN-092')->first();
        $pack = Product::where('sku', 'SS-BPK-102')->first();
        $lamp = Product::where('sku', 'SS-LGT-507')->first();

        // Pesanan aktif
        $active = Order::create([
            'code' => 'RS-1024-001',
            'user_id' => $customer->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(6)->endOfDay(),
            'subtotal' => 1250000,
            'service_fee' => 25000,
            'discount' => 50000,
            'total' => 1225000,
            'status' => 'active',
            'paid_at' => now()->subDays(1),
        ]);

        foreach ([
            [$tent, 125000],
            [$pack, 85000],
        ] as [$product, $price]) {
            if (! $product) {
                continue;
            }

            OrderItem::create([
                'order_id' => $active->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'image' => $product->main_image,
                'quantity' => 1,
                'days' => 5,
                'unit_price' => $price,
                'subtotal' => $price * 5,
            ]);
        }

        Payment::create([
            'order_id' => $active->id,
            'method' => 'qris',
            'amount' => $active->total,
            'status' => 'success',
            'reference' => $active->code,
            'paid_at' => $active->paid_at,
        ]);

        // Pesanan selesai
        $completed = Order::create([
            'code' => 'RS-0928-002',
            'user_id' => $customer->id,
            'rent_start' => now()->subDays(30),
            'rent_end' => now()->subDays(23),
            'subtotal' => 450000,
            'service_fee' => 25000,
            'discount' => 0,
            'total' => 475000,
            'status' => 'completed',
            'paid_at' => now()->subDays(31),
        ]);

        if ($lamp) {
            OrderItem::create([
                'order_id' => $completed->id,
                'product_id' => $lamp->id,
                'name' => $lamp->name,
                'image' => $lamp->main_image,
                'quantity' => 2,
                'days' => 7,
                'unit_price' => 15000,
                'subtotal' => 210000,
            ]);
        }

        Payment::create([
            'order_id' => $completed->id,
            'method' => 'gopay',
            'amount' => $completed->total,
            'status' => 'success',
            'reference' => $completed->code,
            'paid_at' => $completed->paid_at,
        ]);

        // Review produk untuk melengkapi rating demo
        foreach ([$tent, $pack] as $product) {
            if ($product) {
                Review::updateOrCreate(
                    ['product_id' => $product->id, 'user_id' => $customer->id],
                    [
                        'rating' => 5,
                        'title' => 'Gear prima!',
                        'comment' => 'Peralatan bersih, terawat, dan siap pakai. Pasti sewa lagi di trip berikutnya.',
                    ]
                );
            }
        }
    }

    private function seedReturnMockupData(): void
    {
        $alex = User::firstOrCreate(
            ['email' => 'alex.t@example.com'],
            ['name' => 'Alex Thompson', 'username' => 'alexth', 'domicile' => 'Jakarta Barat', 'password' => 'password']
        );

        $sarah = User::firstOrCreate(
            ['email' => 'sarah.j@example.com'],
            ['name' => 'Sarah Jenkins', 'username' => 'sarahj', 'domicile' => 'Bandung Kota', 'password' => 'password']
        );

        $michael = User::firstOrCreate(
            ['email' => 'michael.c@example.com'],
            ['name' => 'Michael Chen', 'username' => 'michaelc', 'domicile' => 'Surabaya Timur', 'password' => 'password']
        );

        $backpack = Product::where('sku', 'SS-BPK-102')->first() ?? Product::where('sku', 'BACK-042-GR')->first() ?? Product::first();
        $boots = Product::where('sku', 'SS-FTW-021')->first() ?? Product::first();
        $tent = Product::where('sku', 'SS-TEN-092')->first() ?? Product::where('sku', 'TENT-001-OR')->first() ?? Product::first();

        // 1. Alex Thompson - ORD-9921-X, Backpack, Expected: today (on-time), Actual: Oct 24, 14:30, Excellent Condition
        if (! Order::where('code', 'ORD-9921-X')->exists()) {
            $ord1 = Order::create([
                'code' => 'ORD-9921-X',
                'user_id' => $alex->id,
                'rent_start' => today()->subDays(4),
                'rent_end' => today(),
                'subtotal' => 340000,
                'service_fee' => 25000,
                'discount' => 0,
                'total' => 365000,
                'status' => 'active',
                'paid_at' => today()->subDays(4),
            ]);

            OrderItem::create([
                'order_id' => $ord1->id,
                'product_id' => $backpack?->id,
                'name' => $backpack?->name ?? 'Osprey Aether 65L Pro',
                'image' => $backpack?->main_image ?? 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=300&q=80',
                'quantity' => 1,
                'days' => 4,
                'unit_price' => 85000,
                'subtotal' => 340000,
            ]);

            ReturnRecord::create([
                'order_id' => $ord1->id,
                'status' => 'approved',
                'returned_at' => now(),
                'condition' => 'excellent',
                'inspection_note' => 'Barang kembali lengkap dan sangat bersih, siap disewakan kembali.',
                'damage_description' => null,
                'damage_cost' => 0,
            ]);
        }

        // 2. Sarah Jenkins - ORD-8842-B, Boots, Expected: 2 days ago (2 Days Overdue), Actual: Oct 24, 09:15, Needs Cleaning
        if (! Order::where('code', 'ORD-8842-B')->exists()) {
            $ord2 = Order::create([
                'code' => 'ORD-8842-B',
                'user_id' => $sarah->id,
                'rent_start' => today()->subDays(5),
                'rent_end' => today()->subDays(2),
                'subtotal' => 195000,
                'service_fee' => 25000,
                'discount' => 0,
                'total' => 220000,
                'status' => 'active',
                'paid_at' => today()->subDays(5),
            ]);

            OrderItem::create([
                'order_id' => $ord2->id,
                'product_id' => $boots?->id,
                'name' => $boots?->name ?? 'Lowa Renegade GTX Trekking Boots',
                'image' => $boots?->main_image ?? 'https://images.unsplash.com/photo-1522163182402-834f871fd851?auto=format&fit=crop&w=300&q=80',
                'quantity' => 1,
                'days' => 3,
                'unit_price' => 65000,
                'subtotal' => 195000,
            ]);

            ReturnRecord::create([
                'order_id' => $ord2->id,
                'status' => 'approved',
                'returned_at' => now()->subHours(5),
                'condition' => 'needs_cleaning',
                'inspection_note' => 'Sepatu berlumpur setelah pendakian, butuh deep cleaning standar.',
                'damage_description' => null,
                'damage_cost' => 0,
            ]);
        }

        // 3. Michael Chen - ORD-9104-Z, Tent, Expected: today (on-time), Actual: In Progress, Minor Damage
        if (! Order::where('code', 'ORD-9104-Z')->exists()) {
            $ord3 = Order::create([
                'code' => 'ORD-9104-Z',
                'user_id' => $michael->id,
                'rent_start' => today()->subDays(3),
                'rent_end' => today(),
                'subtotal' => 375000,
                'service_fee' => 25000,
                'discount' => 0,
                'total' => 400000,
                'status' => 'active',
                'paid_at' => today()->subDays(3),
            ]);

            OrderItem::create([
                'order_id' => $ord3->id,
                'product_id' => $tent?->id,
                'name' => $tent?->name ?? 'Apex Predator 4P Expedition Tent',
                'image' => $tent?->main_image ?? 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=300&q=80',
                'quantity' => 1,
                'days' => 3,
                'unit_price' => 125000,
                'subtotal' => 375000,
            ]);

            ReturnRecord::create([
                'order_id' => $ord3->id,
                'status' => 'pending',
                'returned_at' => null,
                'condition' => 'minor_damage',
                'inspection_note' => 'Frame pole sedikit bengkok di sambungan kedua akibat angin kencang.',
                'damage_description' => 'Pasak tenda bengkok 2 buah, frame pole sambungan kedua agak longgar.',
                'damage_cost' => 50000,
            ]);
        }
    }
}

