<?php

namespace App\Support;

/**
 * Pembaca payload session Laravel secara aman.
 *
 * Driver `database` menyimpan session sebagai base64 dari PHP-serialized array.
 * Aplikasi ini memakai session kustom (session('account_role'), session('account_id'))
 * sehingga payload perlu dibaca langsung dari tabel `sessions` untuk menghitung
 * jumlah customer yang aktif.
 *
 * Pembacaan TIDAK memakai unserialize() telanjang: `allowed_classes => false`
 * membuat payload tidak pernah meng-instantiate object PHP, sehingga payload
 * yang rusak/diselundupkan tidak dapat memicu object injection. Kegagalan
 * deserialize (payload korup/versi berbeda) ditangani sebagai array kosong,
 * bukan exception yang menelan request.
 */
final class SessionPayload
{
    /**
     * Decode payload session menjadi array associative.
     *
     * @return array<string, mixed>
     */
    public static function decode(mixed $payload): array
    {
        if (! is_string($payload) || $payload === '') {
            return [];
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false || $decoded === '') {
            return [];
        }

        // unserialize() gagal dengan E_WARNING/E_NOTICE, bukan exception.
        // Error handler di bawah menelan HANYA pesan unserialize (selama ini
        // unserialize tidak menjalankan kode apa pun karena allowed_classes=false),
        // lalu error handler asli dipulihkan. Ini menggantikan pemakaian
        // operator `@` yang menyembunyikan semua error sekaligus.
        set_error_handler(
            static fn (int $severity, string $message): bool => ! str_starts_with($message, 'unserialize():')
        );

        try {
            $data = unserialize($decoded, ['allowed_classes' => false]);
        } finally {
            restore_error_handler();
        }

        return is_array($data) ? $data : [];
    }
}