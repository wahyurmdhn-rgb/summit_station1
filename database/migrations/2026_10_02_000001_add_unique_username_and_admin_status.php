<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan status akun admin + unique index pada `users.username`.
 *
 * Catatan penting soal `username`:
 * Kolom ini sejak awal nullable dan TIDAK pernah punya unique constraint,
 * sehingga database production bisa sudah berisi banyak username duplikat
 * (dan/atau username kosong). `ALTER TABLE ... ADD UNIQUE` akan GAGAL kalau
 * ada duplikat, jadi data harus dibersihkan lebih dulu.
 *
 * Peraturan pembersihan (deterministik, tidak menghapus user):
 * 1. username kosong / hanya spasi -> NULL (artinya "belum punya username",
 *    dan unik mengizinkan banyak NULL).
 * 2. Untuk tiap username yang duplikat, user dengan id terkecil tetap memakai
 *    username aslinya. User berikutnya diberi suffiks angka naik
 *    (`nama`, `nama_2`, `nama_3`, ...) sampai unik.
 * 3. Panjang username dibatasi 191 karakter agar aman untuk index pada
 *    database dengan batas index length (utf8mb4 / InnoDB).
 *
 * Baris soft-deleted (`deleted_at` IS NOT NULL) juga ikut dibersihkan karena
 * unique index berlaku untuk seluruh tabel.
 */
return new class extends Migration
{
    private const MAX_USERNAME_LENGTH = 191;

    public function up(): void
    {
        $this->normalizeUsernames();

        // Guard supaya migration aman dijalankan ulang (mis. setelah failure
        // parsial) tanpa gagal karena index sudah ada.
        if (! $this->hasUniqueUsernameIndex()) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('username', 'users_username_unique');
            });
        }

        if (! Schema::hasColumn('admin', 'status')) {
            Schema::table('admin', function (Blueprint $table) {
                $table->string('status')->default('active')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('admin', 'status')) {
            Schema::table('admin', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            });
        }

        if ($this->hasUniqueUsernameIndex()) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_username_unique');
            });
        }
    }

    private function hasUniqueUsernameIndex(): bool
    {
        if (! Schema::hasTable('users')) {
            return false;
        }

        foreach (Schema::getIndexes('users') as $index) {
            if (($index['name'] ?? null) === 'users_username_unique') {
                return true;
            }
        }

        return false;
    }

    /**
     * Bersihkan username agar aman dibuat unique.
     *
     * Dua tahap agar hasilnya dijamin unik:
     * 1. Username kosong -> NULL.
     * 2. Untuk tiap username yang dipakai lebih dari satu user, user dengan id
     *    terkecil tetap memakai username aslinya. User lainnya diberi suffiks
     *    angka (`nama_2`, `nama_3`, ...) yang belum dipakai oleh siapa pun.
     */
    private function normalizeUsernames(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            return;
        }

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('username')->orWhere('username', '');
            })
            ->update(['username' => null]);

        $rows = DB::table('users')
            ->whereNotNull('username')
            ->where('username', '<>', '')
            ->orderBy('id')
            ->get(['id', 'username']);

        if ($rows->isEmpty()) {
            return;
        }

        // Tahap A: pemilik sah setiap username = user dengan id terkecil.
        $ownerOf = [];
        foreach ($rows as $row) {
            $username = (string) $row->username;
            if (! isset($ownerOf[$username])) {
                $ownerOf[$username] = (int) $row->id;
            }
        }

        // Semua nama yang tetap dipakai didaftarkan DULUAN agar kandidat baru
        // tidak pernah menabrak nama milik user lain.
        $used = [];
        foreach ($rows as $row) {
            $username = (string) $row->username;
            if ($ownerOf[$username] === (int) $row->id) {
                $used[$username] = true;
            }
        }

        // Tahap B: user yang kalah dapat username cadangan.
        $updates = [];
        foreach ($rows as $row) {
            $username = (string) $row->username;
            $id = (int) $row->id;

            if ($ownerOf[$username] === $id) {
                continue;
            }

            $base = $this->baseUsername($username);
            $suffix = 1;
            do {
                $suffix++;
                $candidate = $this->suffixedUsername($base, $suffix);
            } while (isset($used[$candidate]));

            $used[$candidate] = true;
            $updates[$id] = $candidate;
        }

        foreach ($updates as $id => $username) {
            DB::table('users')->where('id', $id)->update(['username' => $username]);
        }
    }

    /**
     * Rapikan nama dasar: trim dan tanpa spasi diUJUNG.
     */
    private function baseUsername(string $username): string
    {
        $base = trim($username);

        return $base === '' ? 'user' : $base;
    }

    /**
     * Username dengan suffiks angka, dipotong agar muat ke MAX_USERNAME_LENGTH.
     */
    private function suffixedUsername(string $base, int $suffix): string
    {
        $tail = '_'.$suffix;
        $room = self::MAX_USERNAME_LENGTH - strlen($tail);
        $head = $room > 0 ? mb_substr($base, 0, $room) : '';

        return ($head !== '' ? $head : 'user').$tail;
    }
};
