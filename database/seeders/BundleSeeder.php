<?php

namespace Database\Seeders;

use App\Models\Bundle;
use App\Models\Product;
use Illuminate\Database\Seeder;

class BundleSeeder extends Seeder
{
    public function run(): void
    {
        $img = fn (string $id, int $w = 600) => "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w={$w}&q=80";

        $bundles = [
            [
                'name' => 'Mountain Summit Package',
                'description' => 'Perlengkapan pendakian ketinggian lengkap, termasuk tenda 4 musim, kantung tidur tahan dingin ekstrem, dan ransel teknis.',
                'price' => 250000,
                'image' => $img('1504280390367-361c6d9f38f4'),
                'items' => [
                    ['sku' => 'SS-TEN-092', 'qty' => 1],
                    ['sku' => 'SS-SLP-044', 'qty' => 2],
                    ['sku' => 'SS-BPK-102', 'qty' => 2],
                ],
            ],
            [
                'name' => '4-Person Camping Package',
                'description' => 'Tenda ukuran keluarga, 4 matras tidur, dan sistem memasak basecamp lengkap.',
                'price' => 180000,
                'image' => $img('1478131143081-80f7f84ca84d'),
                'items' => [
                    ['sku' => 'SS-TEN-092', 'qty' => 1],
                    ['sku' => 'SS-COK-210', 'qty' => 1],
                ],
            ],
            [
                'name' => '2-Person Camping Package',
                'description' => 'Tenda 2P ringan, dua kantung tidur, dan kompor ringkas untuk pasangan.',
                'price' => 110000,
                'image' => $img('1510312305653-8ed496efae75'),
                'items' => [
                    ['sku' => 'SS-TEN-092', 'qty' => 1],
                    ['sku' => 'SS-LGT-507', 'qty' => 2],
                ],
            ],
            [
                'name' => '6-Person Camping Package',
                'description' => 'Tenda kelompok besar, 6 matras, dan peralatan memasak berkapasitas tinggi untuk seluruh kru.',
                'price' => 320000,
                'image' => $img('1526772662000-3f88f10405ff'),
                'items' => [
                    ['sku' => 'SS-TEN-092', 'qty' => 2],
                    ['sku' => 'SS-COK-210', 'qty' => 1],
                    ['sku' => 'SS-HRD-318', 'qty' => 6],
                ],
            ],
        ];

        foreach ($bundles as $data) {
            $items = collect($data['items'])->keyBy('sku');
            unset($data['items']);

            $bundle = Bundle::updateOrCreate(['name' => $data['name']], $data);

            $sync = [];
            foreach ($items as $sku => $item) {
                $product = Product::where('sku', $sku)->first();
                if ($product) {
                    $sync[$product->id] = ['quantity' => $item['qty']];
                }
            }
            $bundle->products()->sync($sync);
        }
    }
}
