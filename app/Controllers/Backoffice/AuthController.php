<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;

class AuthController extends BaseController
{
    // Batas percobaan login per 5 menit
    private const MAX_ATTEMPTS   = 5;    
    private const WINDOW_SECONDS = 300;  

    /**
     * GET /backoffice
     * Menampilkan halaman login internal.
     */
    public function login()
    {
        // Jika sudah login, langsung ke dashboard
        if (session()->get('admin_logged_in')) {
            return redirect()->to(site_url('backoffice/dashboard'));
        }

        // Cek apakah ada cookie 'remember_me' (opsional, untuk implementasi mendatang)
        // if (isset($_COOKIE['remember_me'])) { ... }

        return view('backoffice/login');
    }

    /**
     * POST /backoffice/login
     * Memproses autentikasi.
     */
    public function attempt()
    {
        // 1. Validasi Input
        if (!$this->validate([
            'username' => 'required|max_length[80]',
            'password' => 'required|max_length[255]',
        ])) {
            return redirect()->to(site_url('backoffice'))->withInput()
                ->with('error', 'Username dan password wajib diisi.');
        }

        $username  = trim((string) $this->request->getPost('username'));
        $password  = (string) $this->request->getPost('password');
        $remember  = $this->request->getPost('remember') ? true : false;
        $ip        = $this->request->getIPAddress();
        $userAgent = (string) $this->request->getUserAgent();

        // 2. Rate Limiting (Anti Brute Force)
        $throttler = service('throttler');
        $key = 'bo-login-' . md5($ip . '|' . strtolower($username));
        
        if ($throttler->check($key, self::MAX_ATTEMPTS, self::WINDOW_SECONDS) === false) {
            $wait = max(1, (int) $throttler->getTokenTime());
            // Log aktivitas gagal karena throttle
            $this->log(null, 'ADMIN_LOGIN_THROTTLED', "Percobaan login melebihi batas untuk username '{$username}'", $ip, $userAgent);
            return redirect()->to(site_url('backoffice'))->withInput()
                ->with('error', "Terlalu banyak percobaan. Coba lagi dalam {$wait} detik.");
        }

        // 3. Cek User ke Database
        $db   = db_connect();
        $user = $db->table('users u')
            ->select('u.id, u.name, u.branch_id, u.password_hash, u.status, r.slug AS role_slug')
            ->join('roles r', 'r.id = u.role_id')
            ->where('u.username', $username)
            ->where('u.deleted_at', null)
            ->get()->getRowArray();

        // 4. Verifikasi Password (Timing Attack Safe)
        // Selalu hitung hash walau user tidak ada, supaya waktu respons tidak membocorkan
        $hash  = $user['password_hash'] ?? password_hash('dummy', PASSWORD_DEFAULT);
        $valid = password_verify($password, $hash) && $user && $user['status'] === 'ACTIVE';

        if (!$valid) {
            // Log kegagalan
            $this->log($user['id'] ?? null, 'ADMIN_LOGIN_FAILED', "Login gagal untuk username '{$username}'", $ip, $userAgent);

            // Pesan error generik (keamanan)
            return redirect()->to(site_url('backoffice'))->withInput()
                ->with('error', 'Username atau password salah.');
        }

        // 5. Upgrade Hash jika diperlukan
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $db->table('users')->where('id', $user['id'])
                ->update(['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        // 6. Update Last Login
        $db->table('users')->where('id', $user['id'])
            ->update(['last_login_at' => date('Y-m-d H:i:s')]);

        // 7. Set Session (Regenerate ID untuk keamanan)
        session()->regenerate(true);

        session()->set([
            'admin_logged_in' => true,
            'admin_id'        => (int) $user['id'],
            'admin_name'      => $user['name'],
            'admin_role'      => $user['role_slug'],
            'admin_branch_id' => $user['branch_id'] ? (int) $user['branch_id'] : null,
        ]);

        // 8. Log Sukses
        $this->log((int) $user['id'], 'ADMIN_LOGIN_SUCCESS', 'Login backoffice berhasil', $ip, $userAgent);

        // 9. Redirect
        return redirect()->to(site_url('backoffice/dashboard'));
    }

    /**
     * POST /backoffice/logout
     */
    public function logout()
    {
        $userId = session()->get('admin_id');
        if ($userId) {
            $this->log((int) $userId, 'ADMIN_LOGOUT', 'Logout backoffice',
                $this->request->getIPAddress(), (string) $this->request->getUserAgent());
        }

        // Hapus session
        session()->remove(['admin_logged_in', 'admin_id', 'admin_name', 'admin_role', 'admin_branch_id', 'admin_permissions']);
        session()->regenerate(true);

        return redirect()->to(site_url('backoffice'))->with('success', 'Anda sudah keluar.');
    }

    /**
     * GET /backoffice/forgot-password
     * (Placeholder untuk fitur Lupa Password)
     */
    public function forgotPassword()
    {
        // Implementasi reset password bisa ditambahkan di sini
        return redirect()->to(site_url('backoffice'))->with('error', 'Fitur lupa password belum tersedia. Hubungi Administrator.');
    }

    /**
     * Helper untuk mencatat log aktivitas.
     */
    private function log(?int $userId, string $event, string $desc, string $ip, string $ua): void
    {
        db_connect()->table('activity_logs')->insert([
            'user_id'     => $userId,
            'event_type'  => $event,
            'module'      => 'backoffice',
            'action'      => 'auth',
            'description' => $desc,
            'ip_address'  => $ip,
            'user_agent'  => mb_substr($ua, 0, 500),
        ]);
    }
}