<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Access denied: CLI only.');
}

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$execute = in_array('--execute', $_SERVER['argv'], true);
$copied = 0;
$skipped = 0;

foreach (User::query()->whereNotNull('ktp_user_path')->cursor() as $user) {
    $path = (string) $user->ktp_user_path;

    if ($path === '' || str_contains($path, '..') || str_contains($path, '\\') || str_starts_with($path, '/') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $path)) {
        fwrite(STDERR, "SKIP user {$user->id}: unsafe path {$path}\n");
        $skipped++;

        continue;
    }

    if (Storage::disk('local')->exists($path)) {
        fwrite(STDOUT, "SKIP user {$user->id}: private file exists {$path}\n");
        $skipped++;

        continue;
    }

    if (! Storage::disk('public')->exists($path)) {
        fwrite(STDERR, "SKIP user {$user->id}: public source missing {$path}\n");
        $skipped++;

        continue;
    }

    if (! $execute) {
        fwrite(STDOUT, "DRY-RUN user {$user->id}: copy {$path}\n");

        continue;
    }

    Storage::disk('local')->writeStream($path, Storage::disk('public')->readStream($path));
    fwrite(STDOUT, "COPIED user {$user->id}: {$path}\n");
    $copied++;
}

fwrite(STDOUT, ($execute ? 'EXECUTE' : 'DRY-RUN')." complete. Copied: {$copied}; skipped: {$skipped}.\n");

if (! $execute) {
    fwrite(STDOUT, "No files changed. Review output, then run: php deploy/migrate-legacy-ktp.php --execute\n");
}
