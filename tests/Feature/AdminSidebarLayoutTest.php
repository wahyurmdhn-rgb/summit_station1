<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_admin_sidebar_places_scrollable_menu_above_logout_area(): void
    {
        $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get(route('admin.pengembalian'))
            ->assertOk()
            ->assertSeeInOrder([
                '<aside class="admin-sidebar">',
                'class="sidebar-brand"',
                '<div class="sidebar-scroll">',
                '<div class="sidebar-bottom">',
                'data-logout-open class="menu-link logout-link"',
                '<span>Keluar</span>',
                '</aside>',
            ], false);
    }

    public function test_shared_admin_sidebar_uses_banner_aware_viewport_heights(): void
    {
        $css = file_get_contents(public_path('css/summit-admin.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('--admin-top-banner-height: 4px;', $css);
        $this->assertMatchesRegularExpression(
            '/\.admin-layout\s*\{[^}]*min-height:\s*calc\(100vh - var\(--admin-top-banner-height\)\);[^}]*min-height:\s*calc\(100dvh - var\(--admin-top-banner-height\)\);/s',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/\.admin-sidebar\s*\{[^}]*position:\s*sticky;[^}]*top:\s*0;[^}]*height:\s*calc\(100vh - var\(--admin-top-banner-height\)\);[^}]*height:\s*calc\(100dvh - var\(--admin-top-banner-height\)\);/s',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/\.sidebar-scroll\s*\{[^}]*flex:\s*1 1 auto;[^}]*min-height:\s*0;[^}]*overflow-y:\s*auto;/s',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/\.sidebar-bottom\s*\{[^}]*flex-shrink:\s*0;[^}]*padding:\s*0 0 16px;/s',
            $css
        );
    }
}
