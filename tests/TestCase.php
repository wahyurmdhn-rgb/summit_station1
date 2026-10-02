<?php

namespace Tests;

use App\Models\Admin;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seedDefaultAdmin();
    }

    /**
     * Pastikan setiap test punya satu akun admin yang benar-benar ada di
     * tabel `admin` dengan status aktif.
     *
     * Middleware EnsureAdmin memvalidasi ulang session admin ke database
     * (bukan sekadar mempercayai string di session), sehingga test yang
     * mengirim `account_id` di session harus punya record admin yang cocok.
     * Record default ini memakai id_admin = 1 supaya test yang memakai
     * session `account_id => 1` tetap valid tanpa diubah.
     */
    protected function seedDefaultAdmin(): void
    {
        if (! Schema::hasTable('admin')) {
            return;
        }

        if (Admin::query()->where('id_admin', 1)->exists()) {
            return;
        }

        Admin::query()->create([
            'name' => 'Admin Summit',
            'email' => 'admin.default@summittest.local',
            'password' => 'password',
            'status' => 'active',
        ]);
    }
}