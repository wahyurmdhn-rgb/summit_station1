<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Penolakan checkout yang harus membatalkan transaksi database.
 *
 * Dipakai agar validasi stok/status yang dijalankan DI DALAM transaksi
 * (dengan row locking) tetap bisa mengembalikan pesan error ke user tanpa
 * menulis data order setengah jadi.
 */
class CheckoutRejected extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorField = 'error')
    {
        parent::__construct($message);
    }
}
