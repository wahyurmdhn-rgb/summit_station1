<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression test untuk migration unique username.
 *
 * `users.username` sejak awal nullable dan tanpa unique constraint, jadi
 * database sungguhan bisa sudah berisi username duplikat (dan username kosong).
 * `ALTER TABLE ... ADD UNIQUE` akan gagal kalau begitu, jadi migration wajib
 * membersihkan datanya lebih dulu — tanpa menghapus user.
 */
class UniqueUsernameMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): object
    {
        return require database_path('migrations/2026_10_02_000001_add_unique_username_and_admin_status.php');
    }

    private function dropUniqueIndex(): void
    {
        // SQLite menyimpan unique index sebagai object 'index'.
        DB::statement('DROP INDEX IF EXISTS users_username_unique');
    }

    private function assertIndexExists(): void
    {
        $indexes = Schema::getIndexes('users');

        foreach ($indexes as $index) {
            if ($index['name'] === 'users_username_unique' && $index['unique'] === true) {
                $this->assertTrue(true);

                return;
            }
        }

        $this->fail('Index unik users_username_unique tidak ditemukan.');
    }

    public function test_migration_adds_unique_index_on_a_clean_table(): void
    {
        $this->assertIndexExists();
    }

    public function test_migration_resolves_duplicate_usernames_before_adding_index(): void
    {
        $this->dropUniqueIndex();

        // Tiga user memakai username sama, satu user punya username kosong,
        // dan satu user tanpa username (NULL) sama sekali.
        $first = User::forceCreate(['name' => 'A', 'email' => 'a1@t.test', 'username' => 'budi', 'password' => 'x', 'role' => 'customer', 'status' => 'active']);
        $second = User::forceCreate(['name' => 'B', 'email' => 'b1@t.test', 'username' => 'budi', 'password' => 'x', 'role' => 'customer', 'status' => 'active']);
        $third = User::forceCreate(['name' => 'C', 'email' => 'c1@t.test', 'username' => 'budi', 'password' => 'x', 'role' => 'customer', 'status' => 'active']);
        $empty = User::forceCreate(['name' => 'D', 'email' => 'd1@t.test', 'username' => '', 'password' => 'x', 'role' => 'customer', 'status' => 'active']);
        $null = User::forceCreate(['name' => 'E', 'email' => 'e1@t.test', 'username' => null, 'password' => 'x', 'role' => 'customer', 'status' => 'active']);

        $this->migration()->up();

        // Id terkecil tetap memakai username aslinya.
        $this->assertSame('budi', $first->fresh()->username);
        // Sisanya mendapat suffiks, tidak boleh bentrok dan tidak boleh lost.
        $this->assertNotSame('budi', $second->fresh()->username);
        $this->assertNotSame('budi', $third->fresh()->username);
        $this->assertNotSame($second->fresh()->username, $third->fresh()->username);

        // Username kosong dianggap "belum ada username" -> NULL.
        $this->assertNull($empty->fresh()->username);
        $this->assertNull($null->fresh()->username);

        // Tidak ada user yang hilang.
        $this->assertDatabaseCount('users', 5);
        $this->assertIndexExists();
    }

    public function test_migration_does_not_steal_a_username_needed_by_another_user(): void
    {
        $this->dropUniqueIndex();

        // "siti" duplikat dan harus dilepas; "siti_2" sudah dipakai user lain
        // yang tidak ikut berubah, jadi kandidat barunya harus lewat ke "siti_3".
        $owner = User::forceCreate(['name' => 'A', 'email' => 'a2@t.test', 'username' => 'siti', 'password' => 'x', 'role' => 'customer', 'status' => 'active']);
        $loser = User::forceCreate(['name' => 'B', 'email' => 'b2@t.test', 'username' => 'siti', 'password' => 'x', 'role' => 'customer', 'status' => 'active']);
        $taker = User::forceCreate(['name' => 'C', 'email' => 'c2@t.test', 'username' => 'siti_2', 'password' => 'x', 'role' => 'customer', 'status' => 'active']);

        $this->migration()->up();

        $this->assertSame('siti', $owner->fresh()->username);
        $this->assertSame('siti_2', $taker->fresh()->username, 'Username milik user lain tidak boleh direbut.');
        $this->assertNotSame('siti', $loser->fresh()->username);
        $this->assertNotSame('siti_2', $loser->fresh()->username);
        $this->assertIndexExists();
    }

    public function test_migration_handles_long_usernames_without_exceeding_index_limit(): void
    {
        $this->dropUniqueIndex();

        $long = str_repeat('a', 200);
        User::forceCreate(['name' => 'A', 'email' => 'a3@t.test', 'username' => $long, 'password' => 'x', 'role' => 'customer', 'status' => 'active']);
        $loser = User::forceCreate(['name' => 'B', 'email' => 'b3@t.test', 'username' => $long, 'password' => 'x', 'role' => 'customer', 'status' => 'active']);

        $this->migration()->up();

        $this->assertLessThanOrEqual(191, mb_strlen((string) $loser->fresh()->username));
        $this->assertIndexExists();
    }

    public function test_migration_is_idempotent_and_reversible(): void
    {
        // Menjalankan up() lagi pada data yang sudah bersih tidak boleh rusak.
        $this->migration()->up();
        $this->migration()->up();
        $this->assertIndexExists();

        // down() menghapus index unique (kolom status ikut turun).
        $this->migration()->down();
        $this->assertFalse(Schema::hasColumn('admin', 'status'));

        // up() lagi harus bisa mengembalikan semuanya.
        $this->migration()->up();
        $this->assertTrue(Schema::hasColumn('admin', 'status'));
        $this->assertIndexExists();
    }
}
