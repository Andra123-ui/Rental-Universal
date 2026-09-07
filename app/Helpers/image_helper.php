<?php
/**
 * app/Helpers/image_helper.php
 * Daftarkan di app/Config/Autoload.php -> $helpers = ['image', ...]
 * atau load manual: helper('image');
 */

if (!function_exists('item_image_url')) {
    /**
     * Ambil URL gambar item. Kalau $filePath kosong/null, pakai placeholder
     * Picsum yang konsisten per item (seed = id) supaya gambar tidak
     * berubah-ubah setiap refresh.
     */
    function item_image_url(?string $filePath, $seed = 'item', int $width = 600, int $height = 400): string
    {
        if (!empty($filePath)) {
            // Kalau sudah URL penuh (http/https), pakai langsung
            if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
                return $filePath;
            }
            // Kalau path lokal (hasil upload admin), arahkan ke base_url
            return base_url('uploads/' . ltrim($filePath, '/'));
        }

        return "https://picsum.photos/seed/{$seed}/{$width}/{$height}";
    }
}

if (!function_exists('category_image_url')) {
    function category_image_url($seed = 'category', int $width = 500, int $height = 350): string
    {
        return "https://picsum.photos/seed/{$seed}/{$width}/{$height}";
    }
}