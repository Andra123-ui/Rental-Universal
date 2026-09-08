<?php

namespace App\Libraries;

class OtpService
{
    /** Masa berlaku OTP dalam detik (5 menit) */
    public const OTP_TTL_SECONDS = 300;

    /** Jeda minimum sebelum boleh minta OTP baru untuk phone yang sama */
    public const RESEND_COOLDOWN_SECONDS = 60;

    /** Batas request OTP per phone dalam 1 jam */
    public const MAX_REQUEST_PER_PHONE_PER_HOUR = 5;

    /** Batas request OTP per IP dalam 1 jam */
    public const MAX_REQUEST_PER_IP_PER_HOUR = 10;

    /**
     * Generate kode OTP numerik 6 digit.
     */
    public function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Hash kode OTP sebelum disimpan (jangan pernah simpan plaintext).
     */
    public function hashCode(string $code): string
    {
        return password_hash($code, PASSWORD_DEFAULT);
    }

    /**
     * Verifikasi kode OTP terhadap hash yang tersimpan.
     */
    public function verifyCode(string $code, string $hash): bool
    {
        return password_verify($code, $hash);
    }

    /**
     * Kirim OTP via WhatsApp gateway.
     * Endpoint & token diambil dari system_settings agar bisa diganti tanpa deploy ulang.
     *
     * NOTE: sesuaikan implementasi curl di bawah dengan provider WA yang dipakai
     * (mis. resmi WhatsApp Business API, atau provider pihak ketiga).
     */
    /**
     * Kirim OTP via WhatsApp gateway (server milik sendiri: owa.gusaha.id).
     * API key & endpoint diambil dari .env (bukan database) karena bersifat
     * kredensial/secret — lihat app/Config/Whatsapp.php.
     *
     * Isi di .env:
     *   whatsapp.gatewayUrl = https://owa.gusaha.id:5570/api/send-message
     *   whatsapp.apiKey     = <api_key kamu>
     */
    public function sendViaWhatsapp(string $canonicalPhone, string $code): bool
    {
        $config    = config(\Config\Whatsapp::class);
        $gatewayUrl = $config->gatewayUrl;
        $apiKey     = $config->apiKey;

        if (empty($apiKey)) {
            log_message('error', 'OTP WhatsApp: whatsapp.apiKey belum diisi di .env.');
            return false;
        }

        $message = "🔐 *Rental Universal - Kode OTP Login*\n\n"
            . "Kode verifikasi kamu:\n\n"
            . '*' . implode('  ', str_split($code)) . "*\n\n"
            . '⏱ Berlaku hanya *' . (self::OTP_TTL_SECONDS / 60) . " menit*\n\n"
            . "---\n"
            . "⚠️ Jangan bagikan kode ini ke siapa pun.\n"
            . 'Rental Universal tidak pernah meminta kode OTP kamu.';

        $body = [
            'api_key'  => $apiKey,
            'receiver' => $canonicalPhone,
            'data'     => ['message' => $message],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $gatewayUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => ['Accept: */*', 'Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            log_message('error', 'Gagal kirim OTP WhatsApp (curl): ' . $error);
            return false;
        }

        $json = json_decode($response, true);
        if (isset($json['error']) && $json['error']) {
            log_message('error', 'Gagal kirim OTP WhatsApp (API): ' . $response);
            return false;
        }

        return true;
    }

    /**
     * Normalisasi nomor HP ke format canonical +62.
     * Terima input: 08xxxx, 62xxxx, +62xxxx, dengan/ tanpa spasi/strip.
     */
    public function normalizePhone(string $rawPhone): ?string
    {
        $digits = preg_replace('/[^\d]/', '', $rawPhone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '620')) {
            // kasus "62" + "0" di depan nomor lokal, contoh salah ketik
            $digits = '62' . substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        } elseif (!str_starts_with($digits, '62')) {
            return null;
        }

        // Validasi panjang wajar nomor Indonesia: 62 + 9-13 digit
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        return '+' . $digits;
    }

    /**
     * Samarkan nomor HP untuk ditampilkan ke user, misal:
     * +6281234567890 -> +6281****890
     */
    public function maskPhone(string $canonicalPhone): string
    {
        $len = strlen($canonicalPhone);

        if ($len <= 8) {
            return substr($canonicalPhone, 0, 3) . str_repeat('*', max(0, $len - 3));
        }

        $prefix = substr($canonicalPhone, 0, 5);   // "+6281"
        $suffix = substr($canonicalPhone, -3);      // 3 digit terakhir
        $maskedLen = $len - strlen($prefix) - strlen($suffix);

        return $prefix . str_repeat('*', max(3, $maskedLen)) . $suffix;
    }
}