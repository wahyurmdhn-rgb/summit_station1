<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $img = fn (string $id, int $w = 600) => "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w={$w}&q=80";

        $products = [
            [
                'sku' => 'TENT-001-OR',
                'category' => 'tents-shelters',
                'name' => 'Apex Ultralight V2',
                'subtitle' => 'Tenda Ekspedisi 4 Musim',
                'description' => 'Tenda ekspedisi ringan berdesain geodesic dome yang dirancang untuk menahan tekanan angin pegunungan alpen dan salju tebal di ketinggian.',
                'rating' => 4.9,
                'reviews_count' => 142,
                'price_per_day' => 250000,
                'stock_total' => 15,
                'stock_available' => 12,
                'main_image' => $img('1504280390367-361c6d9f38f4'),
                'specs' => [
                    'BERAT' => '2.8kg',
                    'KAPASITAS' => '2 Orang',
                    'KONDISI' => 'Sangat Baik',
                    'MUSIM' => '4 Musim',
                ],
                'features' => [
                    ['title' => 'Ketahanan Cuaca', 'icon' => 'droplet', 'desc' => 'Tahan tekanan air 10.000mm pada flysheet dan 15.000mm pada lantai.'],
                ],
                'images' => [$img('1504280390367-361c6d9f38f4', 400)],
            ],
            [
                'sku' => 'BACK-042-GR',
                'category' => 'backpacks',
                'name' => 'Terra 65 Expedition',
                'subtitle' => 'Keseimbangan Beban Ergonomis',
                'description' => 'Ransel trekking tangguh dengan suspensi OPTIFIT dan penopang beban ergonomis yang membantu kenyamanan saat ekspedisi multi-hari.',
                'rating' => 4.8,
                'reviews_count' => 88,
                'price_per_day' => 125000,
                'stock_total' => 10,
                'stock_available' => 2,
                'main_image' => $img('1553062407-98eeb64c6a62'),
                'specs' => [
                    'BERAT' => '2.1kg',
                    'KAPASITAS' => '65 Liter',
                    'KONDISI' => 'Sangat Baik',
                ],
                'features' => [
                    ['title' => 'Kenyamanan Ergonomis', 'icon' => 'wind', 'desc' => 'Sabuk pinggang dan harness yang bernapas, dirancang sesuai bentuk tubuh Anda.'],
                ],
                'images' => [$img('1553062407-98eeb64c6a62', 400)],
            ],
            [
                'sku' => 'SS-TEN-055',
                'category' => 'tents-shelters',
                'name' => 'MSR Hubba Hubba NX',
                'subtitle' => 'Tenda Backpacking Ultralight 2 Orang',
                'description' => 'Tenda backpacking ringan untuk 2 orang, berdiri bebas, dioptimalkan untuk pemasangan cepat dan perlindungan andal dari badai.',
                'rating' => 4.9,
                'reviews_count' => 110,
                'price_per_day' => 140000,
                'stock_total' => 12,
                'stock_available' => 8,
                'main_image' => $img('1504280390367-361c6d9f38f4'),
                'grade' => 'ULTRALIGHT',
                'specs' => ['BERAT' => '1.7kg', 'KAPASITAS' => '2 Orang', 'KONDISI' => 'Sangat Baik'],
                'images' => [$img('1504280390367-361c6d9f38f4', 400)],
            ],
            [
                'sku' => 'SS-FTW-021',
                'category' => 'hardware',
                'name' => 'Lowa Renegade GTX',
                'subtitle' => 'Sepatu Trekking Gore-Tex Semua Medan',
                'description' => 'Sepatu trekking kedap air klasik yang dirancang untuk stabilitas dan cengkeraman kuat di medan pegunungan yang terjal.',
                'rating' => 4.8,
                'reviews_count' => 95,
                'price_per_day' => 65000,
                'stock_total' => 14,
                'stock_available' => 10,
                'main_image' => $img('1522163182402-834f871fd851'),
                'grade' => 'PRO-GRADE',
                'specs' => ['BERAT' => '1.1kg', 'KEDAP AIR' => 'Gore-Tex', 'KONDISI' => 'Sangat Baik'],
                'images' => [$img('1522163182402-834f871fd851', 400)],
            ],
            [
                'sku' => 'SS-TEN-092',
                'category' => 'tents-shelters',
                'name' => 'Apex Predator 4P Expedition',
                'subtitle' => 'Tenda Ekspedisi 4 Musim',
                'description' => 'Dirancang untuk lingkungan paling menantang di bumi. Apex Predator menawarkan perlindungan tangguh terhadap angin ketinggian dan salju tebal, dengan arsitektur geodesic dome dan nilon ripstop berkekuatan tinggi.',
                'rating' => 4.9,
                'reviews_count' => 124,
                'price_per_day' => 125000,
                'stock_total' => 8,
                'stock_available' => 5,
                'main_image' => $img('1504280390367-361c6d9f38f4'),
                'specs' => [
                    'BERAT' => '3.2kg',
                    'KAPASITAS' => '2 Orang',
                    'KONDISI' => 'Sangat Baik',
                    'MUSIM' => '4 Musim',
                ],
                'features' => [
                    ['title' => 'Ketahanan Cuaca', 'icon' => 'droplet', 'desc' => 'Tahan tekanan air 10.000mm pada flysheet dan 15.000mm pada lantai. Jahitan ter-seal penuh dan lapisan PU memastikan tidak ada air yang masuk meski dalam kondisi badai salju.'],
                    ['title' => 'Integritas Struktural', 'icon' => 'tent', 'desc' => 'Tiang aluminium DAC Featherlite NSL memberikan rasio kekuatan-terhadap-berat terbaik, mampu bertahan dari angin hingga 120 km/jam dalam uji lapangan.'],
                    ['title' => 'Ventilasi Aktif', 'icon' => 'wind', 'desc' => 'Ventilasi atas bergaya cerobong ganda dipadukan lubang jaring di bagian bawah menciptakan efek siphon untuk mengurangi kondensasi internal bahkan saat pernapasan berat.'],
                ],
                'images' => [
                    $img('1504280390367-361c6d9f38f4', 400),
                    $img('1478131143081-80f7f84ca84d', 400),
                    $img('1510312305653-8ed496efae75', 400),
                ],
            ],
            [
                'sku' => 'SS-BPK-102',
                'category' => 'backpacks',
                'name' => 'Osprey Aether 65L Pro',
                'subtitle' => 'Keseimbangan Beban Ergonomis',
                'description' => 'Ransel ekspedisi tangguh dengan suspensi Anti-Gravity, harness yang dapat disesuaikan, dan penutup hujan terintegrasi, dirancang untuk tandu alpine multi-hari.',
                'rating' => 4.8,
                'reviews_count' => 98,
                'price_per_day' => 85000,
                'stock_total' => 12,
                'stock_available' => 9,
                'main_image' => $img('1553062407-98eeb64c6a62'),
                'specs' => [
                    'BERAT' => '2.1kg',
                    'KAPASITAS' => '65 Liter',
                    'KONDISI' => 'Sangat Baik',
                    'RANGKA' => 'Anti-Gravity',
                ],
                'features' => [
                    ['title' => 'Transfer Beban', 'icon' => 'wind', 'desc' => 'Suspensi jaring Anti-Gravity memindahkan beban langsung ke sabuk pinggang untuk kenyamanan sepanjang hari di bawah beban berat.'],
                ],
                'images' => [$img('1553062407-98eeb64c6a62', 400)],
            ],
            [
                'sku' => 'SS-SLP-044',
                'category' => 'sleeping-gear',
                'name' => 'North Face Inferno -20F',
                'subtitle' => 'Kantung Tidur Cuaca Ekstrem Dingin',
                'description' => 'Isolasi bulu angsa (down) isian 800-fill terukur hingga -29°C (-20°F), dengan kulit kedap air yang menjaga kehangatan tetap tinggi bahkan di kamp ketinggian yang lembap.',
                'rating' => 4.9,
                'reviews_count' => 76,
                'price_per_day' => 110000,
                'stock_total' => 6,
                'stock_available' => 0,
                'main_image' => $img('1517824806704-9040b037703b'),
                'specs' => [
                    'RATING' => '-20°F / -29°C',
                    'ISIAN' => 'Bulu Angsa 800 Pro',
                    'KONDISI' => 'Sangat Baik',
                    'BERAT' => '1.4kg',
                ],
                'features' => [
                    ['title' => 'Retensi Panas', 'icon' => 'droplet', 'desc' => 'Kerudung kontur dan tabung anti-angin berinsulasi menghilangkan titik dingin hingga -20°F.'],
                ],
                'images' => [$img('1517824806704-9040b037703b', 400)],
            ],
            [
                'sku' => 'SS-COK-210',
                'category' => 'cooking',
                'name' => 'Jetboil Genesis Basecamp',
                'subtitle' => 'Kompor Basecamp Dua Tungku',
                'description' => 'Dua tungku dengan kontrol api presisi dan penahan angin, dapat dikemas ringkas ke dalam satu panci untuk kru memasak di basecamp.',
                'rating' => 5.0,
                'reviews_count' => 51,
                'price_per_day' => 45000,
                'stock_total' => 10,
                'stock_available' => 7,
                'main_image' => $img('1526772662000-3f88f10405ff'),
                'specs' => [
                    'TUNGKU' => '2 x 10.000 BTU',
                    'KONDISI' => 'Sangat Baik',
                    'PENYALA' => 'Tekan & Putar',
                ],
                'features' => [
                    ['title' => 'Kontrol Api Presisi', 'icon' => 'wind', 'desc' => 'Katup tungku yang teratur menghadirkan segalanya, dari air mendidih menggelegak hingga api kecil yang lembut.'],
                ],
                'images' => [$img('1526772662000-3f88f10405ff', 400)],
            ],
            [
                'sku' => 'SS-HRD-318',
                'category' => 'hardware',
                'name' => 'Black Diamond Pursuit',
                'subtitle' => 'Harness Panjat Alpin',
                'description' => 'Harness serbaguna dengan loop kaki yang dapat disesuaikan, empat loop perlengkapan, dan sabuk pinggang tipe bullhorn untuk perjalanan di dinding besar dan gletser.',
                'rating' => 4.7,
                'reviews_count' => 63,
                'price_per_day' => 25000,
                'stock_total' => 15,
                'stock_available' => 11,
                'main_image' => $img('1464822759023-fed622ff2c3b'),
                'specs' => [
                    'BERAT' => '420g',
                    'KONDISI' => 'Sangat Baik',
                    'SERTIFIKASI' => 'UIAA',
                ],
                'features' => [
                    ['title' => 'Kenyamanan & Ukuran', 'icon' => 'tent', 'desc' => 'Loop kaki Traxion yang dapat disesuaikan menjaga ukuran tetap pas di atas sistem pakaian berlapis apa pun.'],
                ],
                'images' => [$img('1464822759023-fed622ff2c3b', 400)],
            ],
            [
                'sku' => 'SS-LGT-507',
                'category' => 'lighting',
                'name' => 'Petzl Swift RL 900',
                'subtitle' => 'Headlamp Reaktif 900 Lumen',
                'description' => 'Headlamp pencahayaan reaktif yang otomatis menyesuaikan kecerahan dengan medan, dengan output maksimum 900 lumen dan baterai CORE yang dapat diisi ulang.',
                'rating' => 4.9,
                'reviews_count' => 87,
                'price_per_day' => 15000,
                'stock_total' => 20,
                'stock_available' => 16,
                'main_image' => $img('1510312305653-8ed496efae75'),
                'specs' => [
                    'OUTPUT' => '900 Lumen',
                    'KONDISI' => 'Sangat Baik',
                    'JANGKAUAN' => '115 m',
                ],
                'features' => [
                    ['title' => 'Cahaya Adaptif', 'icon' => 'droplet', 'desc' => 'Teknologi REACTIVE LIGHTING menyesuaikan kecerahan secara instan dengan medan di depan Anda.'],
                ],
                'images' => [$img('1510312305653-8ed496efae75', 400)],
            ],
        ];

        foreach ($products as $data) {
            $category = Category::where('slug', $data['category'])->firstOrFail();

            $product = Product::updateOrCreate(
                ['sku' => $data['sku']],
                collect($data)->except(['category', 'category_id', 'images'])
                    ->merge(['category_id' => $category->id])
                    ->all()
            );

            foreach ($data['images'] ?? [] as $i => $url) {
                $product->images()->updateOrCreate(
                    ['url' => $url],
                    ['sort_order' => $i]
                );
            }
        }
    }
}
