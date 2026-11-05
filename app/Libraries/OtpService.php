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

    protected ?string $lastError = null;
    protected ?array $lastResponse = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getLastResponse(): ?array
    {
        return $this->lastResponse;
    }

    /**
     * Generate kode OTP numerik 6 digit menggunakan cryptographically secure random generator.
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
     * Kirim OTP via WhatsApp gateway resmi CV. OTW Computer Gusaha.
     */
    public function sendViaWhatsapp(string $canonicalPhone, string $code): bool
    {
        $this->lastError = null;
        $this->lastResponse = null;

        $config = config(\Config\Whatsapp::class);

        if (empty($config->apiKey) || empty($config->gatewayUrl)) {
            $this->lastError = 'Konfigurasi WhatsApp gateway belum lengkap di .env.';
            log_message('error', 'OTP WhatsApp: whatsapp.gatewayUrl / whatsapp.apiKey belum diisi di .env.');
            return false;
        }

        $message = "🔐 *Rental Universal - Kode OTP Login*\n\n"
            . "Kode verifikasi kamu:\n\n"
            . '*' . implode('  ', str_split($code)) . "*\n\n"
            . '⏱ Berlaku hanya *' . (self::OTP_TTL_SECONDS / 60) . " menit*\n\n"
            . "---\n"
            . "⚠️ Jangan bagikan kode ini ke siapa pun.\n"
            . 'Rental Universal tidak pernah meminta kode OTP kamu.';

        $payload = [
            'api_key'   => $config->apiKey,
            'device_id' => (int) $config->deviceId,
            'to'        => ltrim($canonicalPhone, '+'),   // +62812... -> 62812...
            'type'      => 'text',
            'text'      => $message,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $config->gatewayUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error || $response === false) {
            $this->lastError = 'Gagal terhubung ke WhatsApp gateway: ' . $error;
            log_message('error', 'Gagal kirim OTP WhatsApp (curl): ' . $error);
            return false;
        }

        log_message('info', "OTP WhatsApp: HTTP {$status} - {$response}");

        $json = json_decode($response, true);
        $this->lastResponse = is_array($json) ? $json : ['raw' => $response];

        if ($status < 200 || $status >= 300) {
            $this->lastError = "WhatsApp gateway mengembalikan status HTTP {$status}";
            log_message('error', "Gagal kirim OTP WhatsApp (HTTP {$status}): {$response}");
            return false;
        }

        if (is_array($json) && (
            !empty($json['error'])
            || (array_key_exists('ok', $json) && $json['ok'] === false)
            || (array_key_exists('success', $json) && $json['success'] === false)
            || (array_key_exists('status', $json) && $json['status'] === false)
        )) {    
            $errMsg = $json['message'] ?? $json['error'] ?? 'Provider error';
            $this->lastError = "Provider WhatsApp menolak pengiriman: {$errMsg}";
            log_message('error', 'Gagal kirim OTP WhatsApp (API): ' . $response);
            return false;
        }

        return true;
    }

    /**
     * Validasi dan normalisasi nomor HP sesuai standar:
     * - Nomor lokal diawali 8
     * - Panjang nomor lokal (setelah +62 / 0 / 62): 10 - 13 digit
     * - Mengembalikan status, tipe error, canonical, dan lokal
     */
    public function validateAndNormalizePhone(string $rawPhone): array
    {
        $trimmed = trim($rawPhone);
        if ($trimmed === '') {
            return [
                'valid'      => false,
                'error_type' => 'empty',
                'title'      => 'Nomor Telepon Kosong',
                'message'    => 'Silakan masukkan nomor WhatsApp Anda.',
                'canonical'  => null,
                'local'      => null,
            ];
        }

        // Cek karakter ilegal (hanya angka, +, spasi, strip yang ditoleransi)
        if (preg_match('/[^0-9+\-\s]/', $trimmed)) {
            return [
                'valid'      => false,
                'error_type' => 'invalid_format',
                'title'      => 'Format Tidak Valid',
                'message'    => 'Masukkan nomor telepon dengan format yang benar.',
                'canonical'  => null,
                'local'      => null,
            ];
        }

        $clean = preg_replace('/[^\d]/', '', $trimmed);
        if ($clean === '') {
            return [
                'valid'      => false,
                'error_type' => 'empty',
                'title'      => 'Nomor Telepon Kosong',
                'message'    => 'Silakan masukkan nomor WhatsApp Anda.',
                'canonical'  => null,
                'local'      => null,
            ];
        }

        // Normalisasi awalan nomor
        $local = $clean;
        if (str_starts_with($local, '620')) {
            $local = substr($local, 3);
        } elseif (str_starts_with($local, '62')) {
            $local = substr($local, 2);
        } elseif (str_starts_with($local, '0')) {
            $local = substr($local, 1);
        }

        // Nomor seluler Indonesia harus berawalan angka 8
        if (!str_starts_with($local, '8')) {
            return [
                'valid'      => false,
                'error_type' => 'invalid_format',
                'title'      => 'Format Tidak Valid',
                'message'    => 'Masukkan nomor telepon dengan format yang benar.',
                'canonical'  => null,
                'local'      => null,
            ];
        }

        $len = strlen($local);
        if ($len < 10) {
            return [
                'valid'      => false,
                'error_type' => 'too_short',
                'title'      => 'Nomor Tidak Lengkap',
                'message'    => 'Nomor WhatsApp minimal 10 digit.',
                'canonical'  => null,
                'local'      => null,
            ];
        }

        if ($len > 13) {
            return [
                'valid'      => false,
                'error_type' => 'too_long',
                'title'      => 'Nomor Terlalu Panjang',
                'message'    => 'Nomor WhatsApp maksimal 13 digit.',
                'canonical'  => null,
                'local'      => null,
            ];
        }

        return [
            'valid'      => true,
            'error_type' => null,
            'title'      => null,
            'message'    => null,
            'canonical'  => '+62' . $local,
            'local'      => $local,
        ];
    }

    /**
     * Normalisasi nomor HP ke format canonical +62.
     * Mengembalikan +628xxxxxxxxxx jika valid, null jika tidak valid.
     */
    public function normalizePhone(string $rawPhone): ?string
    {
        $res = $this->validateAndNormalizePhone($rawPhone);
        return $res['valid'] ? $res['canonical'] : null;
    }

    /**
     * Samarkan nomor HP untuk ditampilkan ke user, misal:
     * +6281234567890 -> +62 812-****-7890 (sesuai spesifikasi Bagian G)
     */
    public function maskPhone(string $canonicalPhone): string
    {
        $clean = preg_replace('/[^\d]/', '', $canonicalPhone);
        if (str_starts_with($clean, '62')) {
            $local = substr($clean, 2);
        } else {
            $local = $clean;
        }

        $len = strlen($local);
        if ($len < 7) {
            return '+62 ' . substr($local, 0, 3) . '****';
        }

        $prefix = substr($local, 0, 3); // misal 812
        $suffix = substr($local, -4);    // 4 digit terakhir
        return '+62 ' . $prefix . '-****-' . $suffix;
    }
}
