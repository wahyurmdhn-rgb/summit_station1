<?php

namespace Tests\Smoke;

use App\Models\Bundle;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifies on real MySQL that Admin → Alat now shows BOTH Peralatan Ekspedisi
 * (products) AND Paket Sewa (bundles) as one unified list, with correct
 * stok/status computed via Bundle::availableStock() (AKTIF vs HABIS).
 *
 * Runs against the live seeded database inside a transaction that is rolled back.
 */
class AdminAlatUnifiedMysqlSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    public function test_live_bundles_appear_in_admin_alat_with_status(): void
    {
        $this->assertSame('mysql', config('database.default'));

        $bundles = Bundle::with('products')->get();
        $this->assertGreaterThan(0, $bundles->count(), 'Seeded bundles must exist for realistic test');

        // Fetch the full unified list (page 1..lastPage) so paginated bundles are found.
        $page = 1;
        $pages = [];
        do {
            $response = $this->withSession([
                'account_id' => 1,
                'account_name' => 'Admin Summit',
                'account_role' => 'admin',
            ])->get('/admin/alat?page=' . $page);
            $response->assertStatus(200);
            $pages[$page] = $response->getContent();
            $lastPage = $response->viewData('products')->lastPage();
            $page++;
        } while ($page <= $lastPage && $page <= 20);

        $allHtml = implode("\n", $pages);

        // Modal Edit Paket + checklist anggota harus dirender (fitur edit).
        $this->assertStringContainsString('id="bundleEditModal"', $allHtml);
        $this->assertStringContainsString('bundle-member-check', $allHtml);
        $this->assertStringContainsString('openBundleEditModal', $allHtml);
        $this->assertStringContainsString('bundle_edit_name', $allHtml);
        $this->assertStringContainsString("'/admin/alat/bundle/' + item.id", $allHtml);

        foreach ($bundles as $bundle) {
            $stock = $bundle->availableStock();
            $expectedStatus = $bundle->is_active ? ($stock > 0 ? 'AKTIF' : 'HABIS') : 'NONAKTIF';

            $hasName = str_contains($allHtml, $bundle->name);
            $hasCode = str_contains($allHtml, 'PKT-' . $bundle->id);
            $hasStatus = str_contains($allHtml, $expectedStatus);
            // HABIS row must still be visible even when stock is 0 (rule: never hide).
            $visible = $hasName && $hasCode;

            fwrite(STDERR, 'ADMIN ALAT unified bundle id=' . $bundle->id
                . ' name=' . $bundle->name
                . ' availStock=' . $stock
                . ' expected=' . $expectedStatus
                . ' name_visible=' . ($hasName ? 'YES' : 'NO')
                . ' code_visible=' . ($hasCode ? 'YES' : 'NO')
                . ' correct_status_text=' . ($hasStatus ? 'YES' : 'NO')
                . ' row_visible=' . ($visible ? 'YES' : 'NO') . "\n");

            $this->assertTrue($visible, "Bundle {$bundle->name} must be visible on unified /admin/alat list");
            $this->assertTrue($hasStatus, "Bundle {$bundle->name} should render status {$expectedStatus}");
        }

        // Products (Peralatan Ekspedisi) must still be present too.
        $this->assertStringNotContainsString('Tidak ada data alat', $allHtml);
        $this->assertStringContainsString('Paket Sewa', $allHtml);

        // "Paket Sewa" filter shows all bundles on a single page.
        $filterResponse = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/alat?category=paket-sewa');
        $filterResponse->assertStatus(200);
        $filterHtml = $filterResponse->getContent();
        $filterTotal = $filterResponse->viewData('products')->total();
        fwrite(STDERR, "ADMIN ALAT paket-sewa filter total={$filterTotal}\n");
        $this->assertSame($bundles->count(), $filterTotal, 'Paket Sewa filter should list all bundles only');
    }
}
