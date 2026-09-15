<?php

namespace App\Providers;

use App\View\Composers\AdminLayoutComposer;
use App\View\Composers\SiteSettingsComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bagikan data notifikasi + badge sidebar ke SEMUA halaman admin,
        // sehingga lonceng notifikasi & badge sidebar tersedia secara GLOBAL.
        View::composer([
            'admin.dashboard',
            'admin.alat',
            'admin.penyewaan',
            'admin.pembayaran',
            'admin.pengembalian',
            'admin.refund',
            'admin.refund-detail',
            'admin.users',
            'admin.website',
            'admin.laporan',
            'admin.notifications',
            'admin.profile',
        ], AdminLayoutComposer::class);

        // Bagikan konfigurasi website ($siteSettings) ke semua halaman publik user
        // dan halaman admin (dibutuhkan oleh footer standar untuk nama & nomor hotline).
        View::composer([
            'home.*',
            'products.*',
            'checkout.*',
            'layouts.*',
            'welcome',
            'auth.*',
            'components.*',
            'admin.*',
        ], SiteSettingsComposer::class);

        $source = 'C:/Users/user/.gemini/antigravity/brain/0908694d-86d5-4e5e-8e56-33bdfcbb4750/.user_uploaded/media_1787205042010.png';
        $destDir = public_path('images');
        $dest = public_path('images/logo.png');
        if (file_exists($source) && (!file_exists($dest) || filesize($dest) === 0)) {
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            @copy($source, $dest);
        }
    }
}
