<?php
/**
 * app/Helpers/idhash_helper.php
 *
 * Bungkus Hashids supaya ID sequential (1, 2, 3, ...) tidak pernah
 * tampil langsung di URL publik. Deterministik & reversible —
 * beda dengan bcrypt yang tidak cocok untuk kasus ini.
 *
 * Daftarkan di app/Config/Autoload.php -> $helpers = ['idhash', ...]
 */

use Hashids\Hashids;

if (!function_exists('id_encode')) {
    function id_encode(int $id): string
    {
        static $hashids = null;
        if ($hashids === null) {
            // SALT WAJIB diganti dan disimpan rahasia (taruh di .env, jangan hardcode
            // di production). Panjang minimal 8 karakter biar hasil hash tidak terlalu pendek.
            $salt = getenv('HASHIDS_SALT') ?: 'ganti-salt-ini-dengan-string-acak-panjang';
            $hashids = new Hashids($salt, 8); // 8 = panjang minimal string hash
        }
        return $hashids->encode($id);
    }
}

if (!function_exists('id_decode')) {
    /**
     * Balikkan hash ke ID asli. Return null kalau hash tidak valid/rusak
     * (misal user asal ketik URL) — WAJIB dicek di controller sebelum query DB.
     */
    function id_decode(string $hash): ?int
    {
        static $hashids = null;
        if ($hashids === null) {
            $salt = getenv('HASHIDS_SALT') ?: 'ganti-salt-ini-dengan-string-acak-panjang';
            $hashids = new Hashids($salt, 8);
        }
        $decoded = $hashids->decode($hash);
        return $decoded[0] ?? null;
    }
}