<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pemeriksaan otomatis tenggat pengembalian — jalankan rutin setiap jam.
// (jadwal berjalan saat `php artisan schedule:work` atau cron `schedule:run)
Schedule::command('return-deadline:notify')->hourly();