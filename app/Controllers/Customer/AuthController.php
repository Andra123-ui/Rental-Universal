<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Libraries\OtpService;
use App\Models\ActivityLogModel;
use App\Models\CustomerAccountModel;
use App\Models\CustomerLoginOtpModel;
use App\Models\CustomerModel;

class AuthController extends BaseController
{
    protected CustomerModel $customerModel;
    protected CustomerAccountModel $accountModel;
    protected CustomerLoginOtpModel $otpModel;
    protected OtpService $otpService;
    protected ActivityLogModel $activityLogModel;

    // Pesan generik — JANGAN pernah bocorkan apakah nomor terdaftar atau tidak
    protected const GENERIC_MESSAGE = 'Jika nomor Anda terdaftar, kode OTP telah dikirim melalui WhatsApp.';

    // Pesan verifikasi
    protected const ERR_INVALID_CODE = 'Kode OTP salah. Silakan coba lagi.';
    protected const ERR_EXPIRED = 'Kode OTP sudah tidak berlaku. Silakan minta kode baru.';
    protected const ERR_BLOCKED = 'Terlalu banyak percobaan salah. Silakan minta kode baru.';
    protected const ERR_NO_SESSION = 'Sesi login tidak ditemukan. Silakan mulai ulang dari halaman login.';
    protected const DOC_TYPES = [
        'KTP' => 'KTP',
        'SIM' => 'SIM',
        'PASPOR' => 'Paspor',
    ];

    public function __construct()
    {
        // Paksa zona waktu agar perhitungan OTP akurat dan tidak minus
        date_default_timezone_set('Asia/Jakarta');

        $this->customerModel = new CustomerModel();
        $this->accountModel = new CustomerAccountModel();
        $this->otpModel = new CustomerLoginOtpModel();
        $this->otpService = new OtpService();
        $this->activityLogModel = new ActivityLogModel();
    }

    /**
     * Cek apakah client meminta respons JSON/AJAX.
     */
    protected function wantsJson(): bool
    {
        return $this->request->isAJAX()
            || str_contains((string) $this->request->getHeaderLine('Accept'), 'application/json')
            || (string) $this->request->getPost('ajax') === '1'
            || (string) $this->request->getGet('ajax') === '1';
    }

    /**
     * Helper standard JSON response.
     */
    protected function jsonResponse(array $data, int $statusCode = 200)
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setJSON($data);
    }

    /**
     * GET /account/login
     */
    public function login()
    {
        if (session()->get('customer_logged_in')) {
            return redirect()->to(site_url('account'));
        }

        $returnUrl = $this->request->getGet('return_url');
        if ($returnUrl !== null && $this->isSafeReturnUrl($returnUrl)) {
            session()->set('customer_login_return_url', $returnUrl);
        }

        return view('customer/login', [
            'returnUrl' => session()->get('customer_login_return_url') ?? '/',
        ]);
    }

    /**
     * POST /account/login/send-otp
     */
    public function sendOtp()
    {
        $rawPhone = (string) $this->request->getPost('phone');
        $ip = $this->request->getIPAddress();
        $userAgent = (string) $this->request->getUserAgent();

        // 1. Validasi nomor telepon
        $valResult = $this->otpService->validateAndNormalizePhone($rawPhone);
        if (!$valResult['valid']) {
            $errCode = match ($valResult['error_type']) {
                'empty'     => 'empty_phone',
                'too_short' => 'phone_too_short',
                'too_long'  => 'phone_too_long',
                default     => 'invalid_format',
            };

            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'     => 'error',
                    'code'       => $errCode,
                    'error_type' => $valResult['error_type'],
                    'title'      => $valResult['title'],
                    'message'    => $valResult['message'],
                ], 422);
            }

            return redirect()->back()->withInput()->with('popup', [
                'type'    => 'warning',
                'title'   => $valResult['title'],
                'message' => $valResult['message'],
            ])->with('errors', ['phone' => $valResult['message']]);
        }

        $phone = $valResult['canonical'];

        // Bypass OTP sementara
        if ($this->isOtpBypassEnabled()) {
            return $this->loginWithoutOtp($phone, $ip, $userAgent);
        }

        // --- Rate limit per phone + IP ---
        if ($this->isRateLimited($phone, $ip)) {
            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'code'    => 'server_error',
                    'title'   => 'Gangguan Sistem',
                    'message' => 'Terjadi kendala pada server atau batas percobaan terlampaui. Silakan coba beberapa saat lagi.',
                ], 429);
            }

            return redirect()->back()->withInput()->with('popup', [
                'type'    => 'error',
                'title'   => 'Gangguan Sistem',
                'message' => 'Terjadi kendala pada server atau batas percobaan terlampaui. Silakan coba beberapa saat lagi.',
            ]);
        }

        // Cegah kirim ulang terlalu cepat
        $pending = $this->otpModel->latestPendingByPhone($phone);
        if ($pending !== null) {
            $createdAt = strtotime($pending['created_at']);
            $elapsed = time() - $createdAt;
            
            if ($elapsed < OtpService::RESEND_COOLDOWN_SECONDS) {
                $remaining = OtpService::RESEND_COOLDOWN_SECONDS - $elapsed;
                
                // PENGAMAN: Maksimal 60 detik, minimal 1 detik
                if ($remaining > 60) $remaining = 60;
                if ($remaining < 1) $remaining = 1;

                if ($this->wantsJson()) {
                    return $this->jsonResponse([
                        'status'    => 'error',
                        'code'      => 'cooldown',
                        'title'     => 'Mohon Tunggu',
                        'message'   => "Kode OTP baru saja dikirim. Silakan tunggu {$remaining} detik sebelum meminta kode baru.",
                        'remaining' => $remaining,
                    ], 429);
                }

                return redirect()->back()->withInput()->with('notice', self::GENERIC_MESSAGE);
            }

            // Sudah lewat cooldown, tandai expired OTP sebelumnya
            $this->otpModel->markExpired($pending['id']);
        }

        $account = $this->accountModel->findByLoginPhone($phone);

        // Nomor belum pernah dipakai login -> auto-registrasi
        if ($account === null) {
            $customerId = $this->customerModel->insert([
                'name'  => $phone,
                'phone' => $phone,
            ], true);

            $accountId = $this->accountModel->insert([
                'customer_id' => $customerId,
                'login_phone' => $phone,
                'status'      => CustomerAccountModel::STATUS_PENDING,
            ], true);

            $account = $this->accountModel->find($accountId);
        }

        $code = $this->otpService->generateCode();
        $codeHash = $this->otpService->hashCode($code);

        $this->otpModel->insert([
            'customer_account_id' => $account['id'],
            'phone'               => $phone,
            'purpose'             => CustomerLoginOtpModel::PURPOSE_LOGIN,
            'code_hash'           => $codeHash,
            'expires_at'          => date('Y-m-d H:i:s', time() + OtpService::OTP_TTL_SECONDS),
            'attempt_count'       => 0,
            'max_attempts'        => 5,
            'status'              => CustomerLoginOtpModel::STATUS_PENDING,
            'requested_ip'        => $ip,
            'user_agent'          => $userAgent,
        ]);

        // Kirim OTP melalui WhatsApp Gateway
        $sent = $this->otpService->sendViaWhatsapp($phone, $code);

        if (!$sent) {
            $lastErr = $this->otpService->getLastError() ?? 'Unknown error';
            log_message('error', "Gagal mengirim OTP WhatsApp ke {$phone}: {$lastErr}");

            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'code'    => 'otp_failed',
                    'title'   => 'Gagal Mengirim OTP',
                    'message' => 'Terjadi kesalahan saat mengirim kode OTP. Pastikan nomor terdaftar di WhatsApp atau coba lagi nanti.',
                    'debug'   => ENVIRONMENT !== 'production' ? $lastErr : null,
                ], 500);
            }

            return redirect()->back()->withInput()->with('popup', [
                'type'    => 'error',
                'title'   => 'Gagal Mengirim OTP',
                'message' => 'Terjadi kesalahan saat mengirim kode OTP. Pastikan nomor terdaftar di WhatsApp atau coba lagi nanti.',
            ]);
        }

        // Pengiriman sukses
        $this->activityLogModel->record(
            ActivityLogModel::EVENT_LOGIN_OTP_SENT,
            $account['id'],
            $ip,
            $userAgent
        );

        session()->set('customer_login_phone', $phone);
        session()->set('customer_login_account_id', $account['id']);
        session()->set('customer_login_otp_display_expires_at', date('Y-m-d H:i:s', time() + OtpService::OTP_TTL_SECONDS));

        if ($this->wantsJson()) {
            return $this->jsonResponse([
                'status'          => 'success',
                'code'            => 'otp_sent',
                'title'           => 'Kode OTP Berhasil Dikirim',
                'message'         => 'Kode OTP telah dikirim ke nomor WhatsApp Anda. Silakan periksa pesan masuk.',
                'canonical_phone' => $phone,
                'masked_phone'    => $this->otpService->maskPhone($phone),
                'redirect'        => site_url('account/verify'),
                'expires_in'      => OtpService::OTP_TTL_SECONDS,
                'cooldown'        => OtpService::RESEND_COOLDOWN_SECONDS,
            ]);
        }

        return redirect()->to(site_url('account/verify'))
            ->with('popup', [
                'type'    => 'success',
                'title'   => 'Kode OTP Berhasil Dikirim',
                'message' => 'Kode OTP telah dikirim ke nomor WhatsApp Anda. Silakan periksa pesan masuk.',
            ])
            ->with('notice', self::GENERIC_MESSAGE);
    }

    /**
     * GET /account/verify
     */
    public function verifyForm()
    {
        $phone = session()->get('customer_login_phone');

        if (!$phone) {
            return redirect()->to(site_url('account/login'))
                ->with('errors', ['session' => self::ERR_NO_SESSION]);
        }

        $account = $this->accountModel->findByLoginPhone($phone);
        $otp = $account ? $this->otpModel->latestPendingByAccountId((int) $account['id']) : null;

        $expiresAt = $otp['expires_at'] ?? session()->get('customer_login_otp_display_expires_at');

        return view('customer/verify', [
            'maskedPhone'    => $this->otpService->maskPhone($phone),
            'expiresAt'      => $expiresAt,
            'resendCooldown' => OtpService::RESEND_COOLDOWN_SECONDS,
        ]);
    }

    /**
     * POST /account/verify
     */
    public function verifyOtp()
    {
        $phone = session()->get('customer_login_phone');
        $ip = $this->request->getIPAddress();
        $userAgent = (string) $this->request->getUserAgent();

        if (!$phone) {
            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'   => 'error',
                    'code'     => 'session_expired',
                    'title'    => 'Sesi Berakhir',
                    'message'  => self::ERR_NO_SESSION,
                    'redirect' => site_url('account/login'),
                ], 401);
            }

            return redirect()->to(site_url('account/login'))
                ->with('errors', ['session' => self::ERR_NO_SESSION]);
        }

        $code = (string) $this->request->getPost('code');

        if (empty($code) || strlen($code) !== 6 || !ctype_digit($code)) {
            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'code'    => 'otp_invalid',
                    'title'   => 'Kode OTP Tidak Sesuai',
                    'message' => 'Kode yang Anda masukkan salah. Silakan periksa kembali.',
                ], 422);
            }

            return redirect()->to(site_url('account/verify'))
                ->with('popup', [
                    'type'    => 'error',
                    'title'   => 'Kode OTP Tidak Sesuai',
                    'message' => 'Kode yang Anda masukkan salah. Silakan periksa kembali.',
                ])
                ->with('errors', ['code' => self::ERR_INVALID_CODE]);
        }

        $account = $this->accountModel->findByLoginPhone($phone);

        if ($account === null) {
            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'code'    => 'otp_invalid',
                    'title'   => 'Kode OTP Tidak Sesuai',
                    'message' => 'Kode yang Anda masukkan salah. Silakan periksa kembali.',
                ], 422);
            }

            return redirect()->to(site_url('account/verify'))
                ->with('popup', [
                    'type'    => 'error',
                    'title'   => 'Kode OTP Tidak Sesuai',
                    'message' => 'Kode yang Anda masukkan salah. Silakan periksa kembali.',
                ]);
        }

        $accountId = (int) $account['id'];
        $otp = $this->otpModel->latestPendingByAccountId($accountId);

        if ($otp === null) {
            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'code'    => 'otp_expired',
                    'title'   => 'Kode OTP Kedaluwarsa',
                    'message' => 'Kode verifikasi telah habis masa berlakunya. Silakan minta kode baru.',
                ], 422);
            }

            return redirect()->to(site_url('account/verify'))
                ->with('popup', [
                    'type'    => 'warning',
                    'title'   => 'Kode OTP Kedaluwarsa',
                    'message' => 'Kode verifikasi telah habis masa berlakunya. Silakan minta kode baru.',
                ]);
        }

        // Cek expiry
        if (strtotime($otp['expires_at']) < time()) {
            $this->otpModel->markExpired($otp['id']);
            $this->activityLogModel->record(
                ActivityLogModel::EVENT_LOGIN_OTP_EXPIRED,
                $accountId,
                $ip,
                $userAgent
            );

            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'code'    => 'otp_expired',
                    'title'   => 'Kode OTP Kedaluwarsa',
                    'message' => 'Kode verifikasi telah habis masa berlakunya. Silakan minta kode baru.',
                ], 422);
            }

            return redirect()->to(site_url('account/verify'))
                ->with('popup', [
                    'type'    => 'warning',
                    'title'   => 'Kode OTP Kedaluwarsa',
                    'message' => 'Kode verifikasi telah habis masa berlakunya. Silakan minta kode baru.',
                ]);
        }

        // Cek max attempts
        if ($otp['attempt_count'] >= $otp['max_attempts']) {
            $this->otpModel->markBlocked($otp['id']);
            $this->activityLogModel->record(
                ActivityLogModel::EVENT_LOGIN_OTP_BLOCKED,
                $accountId,
                $ip,
                $userAgent
            );

            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'code'    => 'otp_invalid',
                    'title'   => 'Kode OTP Diblokir',
                    'message' => 'Terlalu banyak percobaan salah. Silakan minta kode baru.',
                ], 422);
            }

            return redirect()->to(site_url('account/verify'))
                ->with('popup', [
                    'type'    => 'error',
                    'title'   => 'Kode OTP Diblokir',
                    'message' => 'Terlalu banyak percobaan salah. Silakan minta kode baru.',
                ]);
        }

        // Cek kode OTP
        if (!$this->otpService->verifyCode($code, $otp['code_hash'])) {
            $this->otpModel->incrementAttempt($otp['id']);
            $this->activityLogModel->record(
                ActivityLogModel::EVENT_LOGIN_OTP_FAILED,
                $accountId,
                $ip,
                $userAgent
            );

            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'code'    => 'otp_invalid',
                    'title'   => 'Kode OTP Tidak Sesuai',
                    'message' => 'Kode yang Anda masukkan salah. Silakan periksa kembali.',
                ], 422);
            }

            return redirect()->to(site_url('account/verify'))
                ->with('popup', [
                    'type'    => 'error',
                    'title'   => 'Kode OTP Tidak Sesuai',
                    'message' => 'Kode yang Anda masukkan salah. Silakan periksa kembali.',
                ]);
        }

        // --- Sukses Verifikasi ---
        $this->otpModel->markUsed($otp['id']);

        if ($this->accountModel->isSuspended($account)) {
            $this->activityLogModel->record(
                ActivityLogModel::EVENT_LOGIN_OTP_FAILED,
                $accountId,
                $ip,
                $userAgent,
                'Login ditolak: akun suspended'
            );

            session()->remove(['customer_login_phone', 'customer_login_account_id', 'customer_login_otp_display_expires_at']);

            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'code'    => 'account_suspended',
                    'title'   => 'Akun Dinonaktifkan',
                    'message' => 'Akun Anda sedang tidak dapat digunakan. Hubungi bantuan pelanggan.',
                ], 403);
            }

            return redirect()->to(site_url('account/login'))
                ->with('errors', ['code' => 'Akun Anda sedang tidak dapat digunakan. Hubungi bantuan pelanggan.']);
        }

        $this->accountModel->update($accountId, [
            'phone_verified_at' => date('Y-m-d H:i:s'),
            'status'            => CustomerAccountModel::STATUS_ACTIVE,
            'last_login_at'     => date('Y-m-d H:i:s'),
            'last_login_ip'     => $ip,
            'failed_attempts'   => 0,
        ]);

        session()->regenerate(true);

        session()->set([
            'customer_logged_in'  => true,
            'customer_id'         => $account['customer_id'],
            'customer_account_id' => $account['id'],
        ]);
        session()->remove(['customer_login_phone', 'customer_login_account_id', 'customer_login_otp_display_expires_at']);

        $this->activityLogModel->record(
            ActivityLogModel::EVENT_LOGIN_SUCCESS,
            $accountId,
            $ip,
            $userAgent
        );

        $freshAccount = $this->accountModel->find($accountId);
        $targetUrl = empty($freshAccount['onboarded_at'])
            ? site_url('account/onboarding')
            : site_url(ltrim(session()->get('customer_login_return_url') ?? 'account', '/'));

        session()->remove('customer_login_return_url');

        if ($this->wantsJson()) {
            return $this->jsonResponse([
                'status'   => 'success',
                'code'     => 'login_success',
                'title'    => 'Login Berhasil',
                'message'  => 'Mengalihkan ke dashboard...',
                'redirect' => $targetUrl,
            ]);
        }

        return redirect()->to($targetUrl)->with('popup', [
            'type'    => 'success',
            'title'   => 'Login Berhasil',
            'message' => 'Mengalihkan ke dashboard...',
        ]);
    }

    /**
     * POST /account/verify/resend
     */
    public function resendOtp()
    {
        $phone = session()->get('customer_login_phone');
        $ip = $this->request->getIPAddress();
        $userAgent = (string) $this->request->getUserAgent();

        if (!$phone) {
            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'   => 'error',
                    'code'     => 'session_expired',
                    'title'    => 'Sesi Berakhir',
                    'message'  => self::ERR_NO_SESSION,
                    'redirect' => site_url('account/login'),
                ], 401);
            }

            return redirect()->to(site_url('account/login'))
                ->with('errors', ['session' => self::ERR_NO_SESSION]);
        }

        session()->set('customer_login_otp_display_expires_at', date('Y-m-d H:i:s', time() + OtpService::OTP_TTL_SECONDS));

        if ($this->isRateLimited($phone, $ip)) {
            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'code'    => 'server_error',
                    'title'   => 'Gangguan Sistem',
                    'message' => 'Terjadi kendala pada server atau limit tercapai. Silakan coba beberapa saat lagi.',
                ], 429);
            }

            return redirect()->to(site_url('account/verify'))
                ->with('notice', self::GENERIC_MESSAGE);
        }

        $pending = $this->otpModel->latestPendingByPhone($phone);
        if ($pending !== null) {
            $createdAt = strtotime($pending['created_at']);
            $elapsed = time() - $createdAt;
            
            if ($elapsed < OtpService::RESEND_COOLDOWN_SECONDS) {
                $remaining = OtpService::RESEND_COOLDOWN_SECONDS - $elapsed;
                
                // PENGAMAN: Maksimal 60 detik, minimal 1 detik
                if ($remaining > 60) $remaining = 60;
                if ($remaining < 1) $remaining = 1;

                if ($this->wantsJson()) {
                    return $this->jsonResponse([
                        'status'    => 'error',
                        'code'      => 'cooldown',
                        'title'     => 'Mohon Tunggu',
                        'message'   => "Silakan tunggu {$remaining} detik sebelum meminta kode OTP kembali.",
                        'remaining' => $remaining,
                    ], 429);
                }

                return redirect()->to(site_url('account/verify'))
                    ->with('notice', self::GENERIC_MESSAGE);
            }

            $this->otpModel->markExpired($pending['id']);
        }

        $account = $this->accountModel->findByLoginPhone($phone);
        if ($account === null) {
            $customerId = $this->customerModel->insert([
                'name'  => $phone,
                'phone' => $phone,
            ], true);

            $accountId = $this->accountModel->insert([
                'customer_id' => $customerId,
                'login_phone' => $phone,
                'status'      => CustomerAccountModel::STATUS_PENDING,
            ], true);
        } else {
            $accountId = (int) $account['id'];
        }

        $code = $this->otpService->generateCode();
        $codeHash = $this->otpService->hashCode($code);

        $this->otpModel->insert([
            'customer_account_id' => $accountId,
            'phone'               => $phone,
            'purpose'             => CustomerLoginOtpModel::PURPOSE_LOGIN,
            'code_hash'           => $codeHash,
            'expires_at'          => date('Y-m-d H:i:s', time() + OtpService::OTP_TTL_SECONDS),
            'attempt_count'       => 0,
            'max_attempts'        => 5,
            'status'              => CustomerLoginOtpModel::STATUS_PENDING,
            'requested_ip'        => $ip,
            'user_agent'          => $userAgent,
        ]);

        $sent = $this->otpService->sendViaWhatsapp($phone, $code);

        if (!$sent) {
            $lastErr = $this->otpService->getLastError() ?? 'Unknown error';
            log_message('error', "Gagal mengirim ulang OTP WhatsApp ke {$phone}: {$lastErr}");

            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'code'    => 'otp_failed',
                    'title'   => 'Gagal Mengirim OTP',
                    'message' => 'Terjadi kesalahan saat mengirim kode OTP. Pastikan nomor terdaftar di WhatsApp atau coba lagi nanti.',
                    'debug'   => ENVIRONMENT !== 'production' ? $lastErr : null,
                ], 500);
            }

            return redirect()->to(site_url('account/verify'))->with('popup', [
                'type'    => 'error',
                'title'   => 'Gagal Mengirim OTP',
                'message' => 'Terjadi kesalahan saat mengirim kode OTP. Pastikan nomor terdaftar di WhatsApp atau coba lagi nanti.',
            ]);
        }

        $this->activityLogModel->record(
            ActivityLogModel::EVENT_LOGIN_OTP_SENT,
            $accountId,
            $ip,
            $userAgent,
            'Resend'
        );

        if ($this->wantsJson()) {
            return $this->jsonResponse([
                'status'     => 'success',
                'code'       => 'otp_sent',
                'title'      => 'Kode OTP Berhasil Dikirim',
                'message'    => 'Kode OTP telah dikirim ke nomor WhatsApp Anda. Silakan periksa pesan masuk.',
                'expires_in' => OtpService::OTP_TTL_SECONDS,
                'cooldown'   => OtpService::RESEND_COOLDOWN_SECONDS,
            ]);
        }

        return redirect()->to(site_url('account/verify'))->with('popup', [
            'type'    => 'success',
            'title'   => 'Kode OTP Berhasil Dikirim',
            'message' => 'Kode OTP telah dikirim ke nomor WhatsApp Anda. Silakan periksa pesan masuk.',
        ]);
    }

    /**
     * GET /account/verify/change-phone
     */
    public function changePhone()
    {
        session()->remove(['customer_login_phone', 'customer_login_account_id', 'customer_login_otp_display_expires_at']);
        return redirect()->to(site_url('account/login'));
    }

    /**
     * GET /account/logout
     */
    public function logout()
    {
        $ip = $this->request->getIPAddress();
        $userAgent = (string) $this->request->getUserAgent();
        $accountId = session()->get('customer_account_id');

        if ($accountId) {
            $this->activityLogModel->record('LOGOUT', (int) $accountId, $ip, $userAgent);
        }

        session()->remove(['customer_logged_in', 'customer_id', 'customer_account_id']);
        session()->regenerate(true);

        $returnUrl = $this->request->getGet('return_url');
        if ($returnUrl !== null && $this->isSafeReturnUrl($returnUrl)) {
            return redirect()->to(site_url(ltrim($returnUrl, '/')));
        }

        return redirect()->to(site_url('/'));
    }

    /**
     * GET /account/onboarding
     */
    public function onboardingForm()
    {
        $subject = $this->onboardingSubject();
        if ($subject === null) {
            return redirect()->to(site_url('account/login'))
                ->with('errors', ['session' => self::ERR_NO_SESSION]);
        }
        [$customer, $account] = $subject;

        if (!empty($account['onboarded_at'])) {
            return $this->redirectAfterLogin();
        }

        $prefillName = ($customer['name'] === $customer['phone']) ? '' : $customer['name'];

        return view('customer/onboarding', [
            'customer'    => $customer,
            'prefillName' => $prefillName,
            'docTypes'    => self::DOC_TYPES,
        ]);
    }

    /**
     * POST /account/onboarding
     */
    public function onboardingSubmit()
    {
        $subject = $this->onboardingSubject();
        if ($subject === null) {
            return redirect()->to(site_url('account/login'))
                ->with('errors', ['session' => self::ERR_NO_SESSION]);
        }
        [$customer, $account] = $subject;

        if (!empty($account['onboarded_at'])) {
            return $this->redirectAfterLogin();
        }

        $customerId = (int) $customer['id'];
        $docType = strtoupper(trim((string) $this->request->getPost('document_type')));

        $rules = [
            'name'    => 'required|min_length[3]|max_length[150]',
            'email'   => 'permit_empty|valid_email|max_length[150]',
            'address' => 'required|min_length[5]|max_length[500]',
            'document_type'   => 'required|in_list[' . implode(',', array_keys(self::DOC_TYPES)) . ']',
            'document_number' => 'required|min_length[5]|max_length[80]',
            'id_file'         => 'uploaded[id_file]|max_size[id_file,4096]|mime_in[id_file,image/jpg,image/jpeg,image/png,application/pdf]',
            'emergency_contact_name'  => 'permit_empty|max_length[150]',
            'emergency_contact_phone' => 'permit_empty|min_length[9]|max_length[30]|regex_match[/^[0-9+\-\s]+$/]',
            'notes' => 'permit_empty|max_length[500]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $profile = [
            'name'                    => trim((string) $this->request->getPost('name')),
            'email'                   => $this->postOrNull('email'),
            'address'                 => $this->postOrNull('address'),
            'emergency_contact_name'  => $this->postOrNull('emergency_contact_name'),
            'emergency_contact_phone' => $this->postOrNull('emergency_contact_phone'),
            'notes'                   => $this->postOrNull('notes'),
            'id_type'                 => $docType,
            'id_number'               => trim((string) $this->request->getPost('document_number')),
        ];
        $db->table('customers')->where('id', $customerId)->update($profile);

        $file = $this->request->getFile('id_file');
        if ($file === null || !$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->withInput()->with('errors', ['id_file' => 'File identitas tidak valid. Pilih ulang file Anda.']);
        }

        $db->table('customer_documents')->insert([
            'customer_id'     => $customerId,
            'document_type'   => $docType,
            'document_number' => $profile['id_number'],
            'file_path'       => $file->store('customer_documents/' . $customerId),
            'is_verified'     => 0,
        ]);

        $db->table('customer_accounts')->where('id', (int) $account['id'])->update(['onboarded_at' => date('Y-m-d H:i:s')]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('errors', ['form' => 'Gagal menyimpan data. Silakan coba lagi.']);
        }

        return $this->redirectAfterLogin()->with('success', 'Data profil Anda berhasil disimpan.');
    }

    protected function onboardingSubject(): ?array
    {
        $customerId = session()->get('customer_id');
        $accountId = session()->get('customer_account_id');
        if (!$customerId || !$accountId) {
            return null;
        }

        $customer = $this->customerModel->find($customerId);
        $account = $this->accountModel->find($accountId);

        return ($customer && $account) ? [$customer, $account] : null;
    }

    protected function isOtpBypassEnabled(): bool
    {
        return ENVIRONMENT !== 'production' && filter_var(env('otp.bypass', false), FILTER_VALIDATE_BOOLEAN);
    }

    protected function loginWithoutOtp(string $phone, string $ip, string $userAgent)
    {
        $account = $this->accountModel->findByLoginPhone($phone);

        if ($account === null) {
            $customerId = $this->customerModel->insert(['name' => $phone, 'phone' => $phone], true);
            $accountId = $this->accountModel->insert([
                'customer_id' => $customerId,
                'login_phone' => $phone,
                'status'      => CustomerAccountModel::STATUS_PENDING,
            ], true);
            $account = $this->accountModel->find($accountId);
                }

        if ($this->accountModel->isSuspended($account)) {
            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status'  => 'error',
                    'code'    => 'account_suspended',
                    'title'   => 'Akun Dinonaktifkan',
                    'message' => 'Akun Anda sedang tidak dapat digunakan. Hubungi bantuan pelanggan.',
                ], 403);
            }

            return redirect()->back()->withInput()
                ->with('errors', ['phone' => 'Akun Anda sedang tidak dapat digunakan. Hubungi bantuan pelanggan.']);
        }

        $accountId = (int) $account['id'];

        $this->accountModel->update($accountId, [
            'phone_verified_at' => date('Y-m-d H:i:s'),
            'status'            => CustomerAccountModel::STATUS_ACTIVE,
            'last_login_at'     => date('Y-m-d H:i:s'),
            'last_login_ip'     => $ip,
            'failed_attempts'   => 0,
        ]);

        session()->regenerate(true);

        session()->set([
            'customer_logged_in'  => true,
            'customer_id'         => $account['customer_id'],
            'customer_account_id' => $accountId,
        ]);
        session()->remove(['customer_login_phone', 'customer_login_account_id', 'customer_login_otp_display_expires_at']);

        $this->activityLogModel->record(
            ActivityLogModel::EVENT_LOGIN_SUCCESS,
            $accountId,
            $ip,
            $userAgent,
            'Login tanpa OTP (bypass sementara)'
        );

        $freshAccount = $this->accountModel->find($accountId);
        $targetUrl = empty($freshAccount['onboarded_at'])
            ? site_url('account/onboarding')
            : site_url(ltrim(session()->get('customer_login_return_url') ?? 'account', '/'));

        session()->remove('customer_login_return_url');

        if ($this->wantsJson()) {
            return $this->jsonResponse([
                'status'   => 'success',
                'code'     => 'login_success',
                'title'    => 'Login Berhasil',
                'message'  => 'Mengalihkan ke dashboard...',
                'redirect' => $targetUrl,
            ]);
        }

        return redirect()->to($targetUrl);
    }

    /**
     * Kembali ke tujuan sebelum login (mis. checkout), atau beranda.
     */
    protected function redirectAfterLogin()
    {
        $returnUrl = session()->get('customer_login_return_url') ?? 'account';
        session()->remove('customer_login_return_url');

        return redirect()->to(site_url(ltrim($returnUrl, '/')));
    }

    protected function postOrNull(string $key): ?string
    {
        $v = trim((string) $this->request->getPost($key));
        return $v === '' ? null : $v;
    }

    /**
     * Cek rate limit gabungan per-phone dan per-IP.
     */
    protected function isRateLimited(string $phone, string $ip): bool
    {
        $byPhone = $this->otpModel->countRecentByPhone($phone, 3600);
        if ($byPhone >= OtpService::MAX_REQUEST_PER_PHONE_PER_HOUR) {
            return true;
        }

        $byIp = $this->otpModel->countRecentByIp($ip, 3600);
        if ($byIp >= OtpService::MAX_REQUEST_PER_IP_PER_HOUR) {
            return true;
        }

        return false;
    }

    /**
     * Validasi return_url agar hanya path relatif internal (cegah open redirect).
     */
    protected function isSafeReturnUrl(string $url): bool
    {
        return str_starts_with($url, '/') && !str_starts_with($url, '//');
    }
}