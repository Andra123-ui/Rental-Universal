<?php

namespace App\Controllers\Customer;

use App\Libraries\OtpService;
use App\Models\ActivityLogModel;
use App\Models\CustomerAccountModel;
use App\Models\CustomerLoginOtpModel;
use App\Models\CustomerModel;
use CodeIgniter\HTTP\RedirectResponse;

class Security extends BaseCustomerController
{
    protected CustomerLoginOtpModel $otpModel;
    protected OtpService $otpService;
    protected ActivityLogModel $activityLogModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->otpModel         = new CustomerLoginOtpModel();
        $this->otpService       = new OtpService();
        $this->activityLogModel = new ActivityLogModel();
    }

    /**
     * GET /account/security (CUS-08: Keamanan Akun)
     */
    public function index()
    {
        $customerId = $this->customerId();
        $accountId  = $this->customerAccountId();

        if ($customerId <= 0 || $accountId <= 0) {
            return redirect()->to('/account/login')->with('info', 'Silakan masuk terlebih dahulu.');
        }

        // Ambil riwayat log aktivitas keamanan akun
        $logs = $this->activityLogModel->getCustomerSecurityLogs($accountId, 15);

        // Ambil status pending OTP pergantian nomor jika ada
        $pendingPhone = session()->get('change_phone_target');
        $otpExpiresAt = session()->get('change_phone_expires_at');

        return $this->renderCustomerView('customer/security/index', [
            'activeNav'    => 'security',
            'pageTitle'    => 'Keamanan Akun',
            'pageSubtitle' => 'Kelola nomor telepon WhatsApp login, status verifikasi, dan pantau aktivitas akun Anda.',
            'breadcrumbs'  => ['Keamanan Akun' => null],
            'account'      => $this->customerAccount,
            'logs'         => $logs,
            'pendingPhone' => $pendingPhone,
            'otpExpiresAt' => $otpExpiresAt,
        ]);
    }

    /**
     * POST /account/security/phone/request-otp
     * Minta kode OTP untuk perubahan nomor WhatsApp
     */
    public function requestPhoneOtp(): RedirectResponse
    {
        $customerId = $this->customerId();
        $accountId  = $this->customerAccountId();

        if ($customerId <= 0 || $accountId <= 0) {
            return redirect()->to('/account/login');
        }

        $rules = [
            'new_phone' => 'required|min_length[8]|max_length[25]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $rawPhone  = (string) $this->request->getPost('new_phone');
        $normPhone = $this->otpService->normalizePhone($rawPhone);

        if (!$normPhone) {
            return redirect()->back()->withInput()->with('error', 'Format nomor WhatsApp tidak valid. Gunakan format contoh 08123456789 atau +628123456789.');
        }

        // Cek apakah sama dengan nomor saat ini
        if ($normPhone === ($this->customerAccount['login_phone'] ?? '')) {
            return redirect()->back()->withInput()->with('error', 'Nomor baru tidak boleh sama dengan nomor login yang sedang aktif.');
        }

        // Cek apakah nomor baru sudah digunakan akun lain yang aktif
        $conflict = $this->accountModel
            ->where('login_phone', $normPhone)
            ->where('id !=', $accountId)
            ->first();

        if ($conflict) {
            return redirect()->back()->withInput()->with('error', 'Nomor WhatsApp tersebut sudah terdaftar pada akun lain.');
        }

        $ip        = $this->request->getIPAddress();
        $userAgent = (string) $this->request->getUserAgent();

        // Generate OTP
        $code     = $this->otpService->generateCode();
        $codeHash = $this->otpService->hashCode($code);
        $ttl      = OtpService::OTP_TTL_SECONDS;

        $this->otpModel->insert([
            'customer_account_id' => $accountId,
            'phone'               => $normPhone,
            'purpose'             => 'PHONE_CHANGE',
            'code_hash'           => $codeHash,
            'expires_at'          => date('Y-m-d H:i:s', time() + $ttl),
            'attempt_count'       => 0,
            'max_attempts'        => 5,
            'status'              => CustomerLoginOtpModel::STATUS_PENDING,
            'requested_ip'        => $ip,
            'user_agent'          => mb_substr($userAgent, 0, 500),
        ]);

        // Simpan sesi sementara untuk form verifikasi OTP
        session()->set('change_phone_target', $normPhone);
        session()->set('change_phone_expires_at', date('Y-m-d H:i:s', time() + $ttl));

        // Kirim OTP via OtpService WhatsApp (atau bypass bila dev)
        $bypass = (bool) env('otp.bypass', false);
        if (!$bypass) {
            $msg = "Kode OTP perubahan nomor WhatsApp Rental Universal Anda adalah: *{$code}*. Berlaku 5 menit. JANGAN bagikan kode ini kepada siapa pun.";
            $this->otpService->sendWhatsapp($normPhone, $msg);
        }

        return redirect()->to(site_url('account/security'))
            ->with('success', 'Kode OTP berhasil dikirimkan ke nomor WhatsApp baru ' . esc($normPhone) . '. Silakan masukkan kode untuk mengonfirmasi.');
    }

    /**
     * POST /account/security/phone/verify-otp
     * Verifikasi kode OTP dan selesaikan perubahan nomor
     */
    public function verifyPhoneOtp(): RedirectResponse
    {
        $customerId = $this->customerId();
        $accountId  = $this->customerAccountId();

        if ($customerId <= 0 || $accountId <= 0) {
            return redirect()->to('/account/login');
        }

        $newPhone = session()->get('change_phone_target');
        if (!$newPhone) {
            return redirect()->to(site_url('account/security'))
                ->with('error', 'Sesi perubahan nomor tidak ditemukan atau sudah kedaluwarsa. Silakan ajukan ulang.');
        }

        $code = trim((string) $this->request->getPost('otp_code'));
        if ($code === '' || strlen($code) < 4) {
            return redirect()->back()->with('error', 'Kode OTP wajib diisi lengkap.');
        }

        $ip        = $this->request->getIPAddress();
        $userAgent = (string) $this->request->getUserAgent();

        // Cari OTP pending untuk customer ini
        $otp = $this->otpModel
            ->where('customer_account_id', $accountId)
            ->where('purpose', 'PHONE_CHANGE')
            ->where('status', CustomerLoginOtpModel::STATUS_PENDING)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$otp) {
            return redirect()->to(site_url('account/security'))
                ->with('error', 'Permintaan OTP tidak ditemukan. Silakan kirim ulang kode OTP.');
        }

        // Cek kedaluwarsa
        if (strtotime($otp['expires_at']) < time()) {
            $this->otpModel->markExpired($otp['id']);
            return redirect()->to(site_url('account/security'))
                ->with('error', 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.');
        }

        // Cek percobaan maksimum
        if ($otp['attempt_count'] >= $otp['max_attempts']) {
            $this->otpModel->markBlocked($otp['id']);
            return redirect()->to(site_url('account/security'))
                ->with('error', 'Terlalu banyak percobaan salah. Silakan minta kode baru.');
        }

        // Verifikasi kecocokan kode hash
        if (!$this->otpService->verifyCode($code, $otp['code_hash'])) {
            $this->otpModel->incrementAttempt($otp['id']);
            return redirect()->back()->with('error', 'Kode OTP yang Anda masukkan salah. Silakan coba lagi.');
        }

        // OTP Valid!
        $this->otpModel->markUsed($otp['id']);

        // Update kredensial akun
        $this->accountModel->update($accountId, [
            'login_phone'       => $newPhone,
            'phone_verified_at' => date('Y-m-d H:i:s'),
        ]);

        // Update kontak di tabel customers
        $this->customerModel->update($customerId, [
            'phone' => $newPhone,
        ]);

        // Catat di activity_logs
        $this->activityLogModel->record(
            ActivityLogModel::EVENT_PHONE_CHANGE,
            $accountId,
            $ip,
            $userAgent,
            "Nomor WhatsApp login diubah ke {$newPhone}"
        );

        // Hapus sesi temporary
        session()->remove(['change_phone_target', 'change_phone_expires_at']);

        // Rotasi session ID demi keamanan
        session()->regenerate(true);

        return redirect()->to(site_url('account/security'))
            ->with('success', 'Nomor WhatsApp login Anda berhasil diperbarui menjadi ' . esc($newPhone) . '.');
    }

    /**
     * POST /account/security/phone/cancel-otp
     * Batalkan alur pergantian nomor
     */
    public function cancelPhoneOtp(): RedirectResponse
    {
        session()->remove(['change_phone_target', 'change_phone_expires_at']);
        return redirect()->to(site_url('account/security'))->with('info', 'Permintaan pergantian nomor dibatalkan.');
    }
}

