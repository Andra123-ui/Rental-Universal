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

    // Pesan generik untuk error verifikasi — tidak membedakan salah/expired/blocked
    // secara teknis ke user, tapi cukup jelas supaya user tahu harus apa.
    protected const ERR_INVALID_CODE = 'Kode OTP salah. Silakan coba lagi.';
    protected const ERR_EXPIRED      = 'Kode OTP sudah tidak berlaku. Silakan minta kode baru.';
    protected const ERR_BLOCKED      = 'Terlalu banyak percobaan salah. Silakan minta kode baru.';
    protected const ERR_NO_SESSION   = 'Sesi login tidak ditemukan. Silakan mulai ulang dari halaman login.';

    public function __construct()
    {
        $this->customerModel     = new CustomerModel();
        $this->accountModel      = new CustomerAccountModel();
        $this->otpModel          = new CustomerLoginOtpModel();
        $this->otpService        = new OtpService();
        $this->activityLogModel  = new ActivityLogModel();
    }

    /**
     * GET /account/login
     * Menampilkan form login (nomor HP) — CAUTH-01.
     */
    public function login()
    {
        // Simpan return_url ke session bila ada & valid, supaya customer
        // dikembalikan ke proses sebelumnya (mis. checkout) setelah login.
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
     * Memproses submit form: validasi, normalisasi nomor, rate limit,
     * generate & kirim OTP. Selalu balas pesan generik.
     */
    public function sendOtp()
    {
        $rules = [
            'phone' => 'required|min_length[8]|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $rawPhone = (string) $this->request->getPost('phone');
        $ip       = $this->request->getIPAddress();
        $userAgent = (string) $this->request->getUserAgent();

        $phone = $this->otpService->normalizePhone($rawPhone);

        if ($phone === null) {
            // Format tidak valid tetap dibalas pesan yang tidak spesifik
            return redirect()->back()->withInput()
                ->with('errors', ['phone' => 'Format nomor HP tidak valid.']);
        }

        // --- Rate limit per phone + IP ---
        if ($this->isRateLimited($phone, $ip)) {
            return redirect()->back()->withInput()
                ->with('notice', self::GENERIC_MESSAGE);
            // Sengaja tidak membedakan pesan rate-limit dari pesan sukses,
            // supaya tidak jadi sinyal enumerasi nomor.
        }

        // Cegah kirim ulang terlalu cepat selagi OTP sebelumnya masih aktif
        $pending = $this->otpModel->latestPendingByPhone($phone);
        if ($pending !== null) {
            return redirect()->back()->withInput()
                ->with('notice', self::GENERIC_MESSAGE);
        }

        $account = $this->accountModel->findByLoginPhone($phone);

        // Nomor belum pernah dipakai login -> auto-registrasi customer + account baru.
        // Nama diisi placeholder (nomor HP itu sendiri) dan dilengkapi nanti di CAUTH-03.
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

        session()->set('customer_login_phone', $phone);
        session()->set('customer_login_otp_display_expires_at', date('Y-m-d H:i:s', time() + OtpService::OTP_TTL_SECONDS));

        // Catatan: customer berstatus SUSPENDED tetap boleh sampai di sini
        // (tetap dapat OTP), tapi WAJIB ditolak saat verifikasi di CAUTH-02.
        // Jangan tambahkan pengecekan suspended di titik ini.

        $code     = $this->otpService->generateCode();
        $codeHash = $this->otpService->hashCode($code);

        $this->otpModel->insert([
            'customer_account_id' => $account['id'],
            'phone'                => $phone,
            'purpose'              => CustomerLoginOtpModel::PURPOSE_LOGIN,
            'code_hash'            => $codeHash,
            'expires_at'           => date('Y-m-d H:i:s', time() + OtpService::OTP_TTL_SECONDS),
            'attempt_count'        => 0,
            'max_attempts'         => 5,
            'status'               => CustomerLoginOtpModel::STATUS_PENDING,
            'requested_ip'         => $ip,
            'user_agent'           => $userAgent,
        ]);

        $sent = $this->otpService->sendViaWhatsapp($phone, $code);

        if (!$sent) {
            log_message('error', "Gagal mengirim OTP WhatsApp ke {$phone}");
            // Tetap balas pesan generik ke user walau pengiriman gagal di sisi kita,
            // supaya tidak membocorkan detail infrastruktur ke publik.
        } else {
            $this->activityLogModel->record(
                ActivityLogModel::EVENT_LOGIN_OTP_SENT,
                $account['id'],
                $ip,
                $userAgent
            );
        }

        // Simpan account_id di session HANYA untuk kenyamanan (tidak wajib —
        // verify akan tetap cari ulang berdasarkan phone kalau ini kosong)
        session()->set('customer_login_account_id', $account['id']);

        return redirect()->to(site_url('account/verify'))
            ->with('notice', self::GENERIC_MESSAGE);
    }

    /**
     * GET /account/verify
     * Tampilkan form verifikasi OTP — CAUTH-02.
     */
    public function verifyForm()
    {
        $phone = session()->get('customer_login_phone');

        if (!$phone) {
            return redirect()->to(site_url('account/login'))
                ->with('errors', ['session' => self::ERR_NO_SESSION]);
        }

        $account = $this->accountModel->findByLoginPhone($phone);
        $otp     = $account ? $this->otpModel->latestPendingByAccountId((int) $account['id']) : null;

        // Kalau tidak ada OTP nyata (akun tidak ditemukan / OTP sudah tidak pending),
        // pakai waktu display dari session supaya countdown tetap konsisten
        // secara visual dan tidak jadi sinyal enumerasi nomor.
        $expiresAt = $otp['expires_at'] ?? session()->get('customer_login_otp_display_expires_at');

        return view('customer/verify', [
            'maskedPhone'    => $this->otpService->maskPhone($phone),
            'expiresAt'      => $expiresAt,
            'resendCooldown' => OtpService::RESEND_COOLDOWN_SECONDS,
        ]);
    }

    /**
     * POST /account/verify
     * Verifikasi kode OTP, bentuk session customer, rotate session ID.
     */
    public function verifyOtp()
    {
        $phone     = session()->get('customer_login_phone');
        $ip        = $this->request->getIPAddress();
        $userAgent = (string) $this->request->getUserAgent();

        if (!$phone) {
            return redirect()->to(site_url('account/login'))
                ->with('errors', ['session' => self::ERR_NO_SESSION]);
        }

        if (!$this->validate(['code' => 'required|exact_length[6]|numeric'])) {
            return redirect()->to(site_url('account/verify'))
                ->with('errors', ['code' => self::ERR_INVALID_CODE]);
        }

        $code    = (string) $this->request->getPost('code');
        $account = $this->accountModel->findByLoginPhone($phone);

        // Nomor tidak terdaftar -> tidak pernah ada OTP asli yang cocok,
        // jadi kode apa pun otomatis dianggap salah (pesan generik, sama
        // seperti kode salah biasa — tidak membocorkan status terdaftar).
        if ($account === null) {
            return redirect()->to(site_url('account/verify'))
                ->with('errors', ['code' => self::ERR_INVALID_CODE]);
        }

        $accountId = (int) $account['id'];
        $otp       = $this->otpModel->latestPendingByAccountId($accountId);

        if ($otp === null) {
            return redirect()->to(site_url('account/verify'))
                ->with('errors', ['code' => self::ERR_EXPIRED]);
        }

        // Cek expiry
        if (strtotime($otp['expires_at']) < time()) {
            $this->otpModel->markExpired($otp['id']);
            $this->activityLogModel->record(
                ActivityLogModel::EVENT_LOGIN_OTP_EXPIRED,
                (int) $accountId,
                $ip,
                $userAgent
            );

            return redirect()->to(site_url('account/verify'))
                ->with('errors', ['code' => self::ERR_EXPIRED]);
        }

        // Cek max attempts
        if ($otp['attempt_count'] >= $otp['max_attempts']) {
            $this->otpModel->markBlocked($otp['id']);
            $this->activityLogModel->record(
                ActivityLogModel::EVENT_LOGIN_OTP_BLOCKED,
                (int) $accountId,
                $ip,
                $userAgent
            );

            return redirect()->to(site_url('account/verify'))
                ->with('errors', ['code' => self::ERR_BLOCKED]);
        }

        // Cek kode OTP (hash compare)
        if (!$this->otpService->verifyCode($code, $otp['code_hash'])) {
            $this->otpModel->incrementAttempt($otp['id']);
            $this->activityLogModel->record(
                ActivityLogModel::EVENT_LOGIN_OTP_FAILED,
                (int) $accountId,
                $ip,
                $userAgent
            );

            return redirect()->to(site_url('account/verify'))
                ->with('errors', ['code' => self::ERR_INVALID_CODE]);
        }

        // --- Sukses ---
        $this->otpModel->markUsed($otp['id']); // one-time use

        // Customer suspended: OTP boleh benar, tapi login TETAP DITOLAK di sini.
        if ($account && $this->accountModel->isSuspended($account)) {
            $this->activityLogModel->record(
                ActivityLogModel::EVENT_LOGIN_OTP_FAILED,
                (int) $accountId,
                $ip,
                $userAgent,
                'Login ditolak: akun suspended'
            );

            session()->remove(['customer_login_phone', 'customer_login_account_id', 'customer_login_otp_display_expires_at']);

            return redirect()->to(site_url('account/login'))
                ->with('errors', ['code' => 'Akun Anda sedang tidak dapat digunakan. Hubungi bantuan pelanggan.']);
        }

        $this->accountModel->update((int) $accountId, [
            'phone_verified_at' => date('Y-m-d H:i:s'),
            'status'            => CustomerAccountModel::STATUS_ACTIVE,
            'last_login_at'     => date('Y-m-d H:i:s'),
            'last_login_ip'     => $ip,
            'failed_attempts'   => 0,
        ]);

        // Rotate session ID setelah login berhasil (cegah session fixation)
        session()->regenerate(true);

        session()->set([
            'customer_logged_in' => true,
            'customer_id'         => $account['customer_id'],
            'customer_account_id' => $account['id'],
        ]);
        session()->remove(['customer_login_phone', 'customer_login_account_id', 'customer_login_otp_display_expires_at']);

        $this->activityLogModel->record(
            ActivityLogModel::EVENT_LOGIN_SUCCESS,
            (int) $accountId,
            $ip,
            $userAgent
        );

        $returnUrl = session()->get('customer_login_return_url') ?? '/';
        session()->remove('customer_login_return_url');

        return redirect()->to(site_url(ltrim($returnUrl, '/')));
    }

    /**
     * POST /account/verify/resend
     * Kirim ulang OTP setelah cooldown terpenuhi.
     */
    public function resendOtp()
    {
        $phone     = session()->get('customer_login_phone');
        $ip        = $this->request->getIPAddress();
        $userAgent = (string) $this->request->getUserAgent();

        if (!$phone) {
            return redirect()->to(site_url('account/login'))
                ->with('errors', ['session' => self::ERR_NO_SESSION]);
        }

        // Refresh display expiry supaya countdown di halaman tetap konsisten
        // baik akun terdaftar maupun tidak.
        session()->set('customer_login_otp_display_expires_at', date('Y-m-d H:i:s', time() + OtpService::OTP_TTL_SECONDS));

        if ($this->isRateLimited($phone, $ip)) {
            return redirect()->to(site_url('account/verify'))
                ->with('notice', self::GENERIC_MESSAGE);
        }

        $pending = $this->otpModel->latestPendingByPhone($phone);
        if ($pending !== null) {
            $createdAt = strtotime($pending['created_at']);
            if (time() - $createdAt < OtpService::RESEND_COOLDOWN_SECONDS) {
                // Masih dalam cooldown — jangan buat OTP baru
                return redirect()->to(site_url('account/verify'))
                    ->with('notice', self::GENERIC_MESSAGE);
            }
            // Cooldown lewat: OTP lama dianggap kadaluarsa begitu ada request baru
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
            $accountId = $account['id'];
        }

        $code     = $this->otpService->generateCode();
        $codeHash = $this->otpService->hashCode($code);

        $this->otpModel->insert([
            'customer_account_id' => $accountId,
            'phone'                => $phone,
            'purpose'              => CustomerLoginOtpModel::PURPOSE_LOGIN,
            'code_hash'            => $codeHash,
            'expires_at'           => date('Y-m-d H:i:s', time() + OtpService::OTP_TTL_SECONDS),
            'attempt_count'        => 0,
            'max_attempts'         => 5,
            'status'               => CustomerLoginOtpModel::STATUS_PENDING,
            'requested_ip'         => $ip,
            'user_agent'           => $userAgent,
        ]);

        $sent = $this->otpService->sendViaWhatsapp($phone, $code);
        if ($sent) {
            $this->activityLogModel->record(
                ActivityLogModel::EVENT_LOGIN_OTP_SENT,
                (int) $accountId,
                $ip,
                $userAgent,
                'Resend'
            );
        }

        return redirect()->to(site_url('account/verify'))
            ->with('notice', self::GENERIC_MESSAGE);
    }

    /**
     * GET /account/verify/change-phone
     * Kembali ke input nomor — hapus session verifikasi yang sedang berjalan.
     */
    public function changePhone()
    {
        session()->remove(['customer_login_phone', 'customer_login_account_id', 'customer_login_otp_display_expires_at']);

        return redirect()->to(site_url('account/login'));
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