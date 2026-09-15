<?php

namespace App\View\Composers;

use App\Services\SiteSettingsService;
use Illuminate\View\View;

/**
 * Menyediakan data konfigurasi website ($siteSettings) secara GLOBAL untuk
 * seluruh halaman publik user, sehingga pengaturan yang diubah admin
 * (Admin → Website) langsung tampil di Beranda, Store Location,
 * Contact Admin, footer, dan navbar.
 */
class SiteSettingsComposer
{
    public function compose(View $view): void
    {
        $view->with('siteSettings', SiteSettingsService::all());
    }
}
