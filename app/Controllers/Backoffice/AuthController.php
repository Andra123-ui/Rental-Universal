<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;

class AuthController extends BaseController
{
    private const MAX_ATTEMPTS   = 5;    // percobaan
    private const WINDOW_SECONDS = 300;  // per 5 menit

    /**
     * GET /backoffice
     */
    public function login()
    {
        if (session()->get('admin_logged_in')) {
            return redirect()->to(site_url('backoffice/dashboard'));
        }

        return view('backoffice/login');
    }

    /**
     * POST /backoffice/login
     */
    public function attempt()
    {
        if (!$this->validate([
            'username' => 'required|max_length[80]',
            'password' => 'required|max_length[255]',
        ])) {
            return redirect()->to(site_url('backoffice'))->withInput()
                ->with('error', 'Username dan password wajib diisi.');
        }

        $username  = trim((string) $this->request->getPost('username'));
        $password  = (string) $this->request->getPost('password');
        $ip        = $this->request->getIPAddress();
        $userAgent = (string) $this->request->getUserAgent();

        // Batasi percobaan per kombinasi IP + username (anti tebak password)
        $throttler = service('throttler');
        $key = 'bo-login-' . md5($ip . '|' . strtolower($username));
        if ($throttler->check($key, self::MAX_ATTEMPTS, self::WINDOW_SECONDS) === false) {
            $wait = max(1, (int) $throttler->getTokenTime());
            return redirect()->to(site_url('backoffice'))->withInput()
                ->with('error', "Terlalu banyak percobaan. Coba lagi dalam {$wait} detik.");
        }

        $db   = db_connect();
        $user = $db->table('users u')
            ->select('u.id, u.name, u.branch_id, u.password_hash, u.status, r.slug AS role_slug')
            ->join('roles r', 'r.id = u.role_id')
            ->where('u.username', $username)
            ->where('u.deleted_at', null)
            ->get()->getRowArray();

        // Tetap hitung hash walau user tidak ada, supaya waktu respons tidak membocorkan
        // apakah username-nya terdaftar
        $hash  = $user['password_hash'] ?? password_hash('dummy', PASSWORD_DEFAULT);
        $valid = password_verify($password, $hash) && $user && $user['status'] === 'ACTIVE';

        if (!$valid) {
            $this->log($user['id'] ?? null, 'ADMIN_LOGIN_FAILED', "Login gagal untuk username '{$username}'", $ip, $userAgent);

            return redirect()->to(site_url('backoffice'))->withInput()
                ->with('error', 'Username atau password salah.');
        }

        // Upgrade hash otomatis kalau algoritma default berubah
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $db->table('users')->where('id', $user['id'])
                ->update(['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        $db->table('users')->where('id', $user['id'])
            ->update(['last_login_at' => date('Y-m-d H:i:s')]);

        // Ganti session ID setelah login (cegah session fixation)
        session()->regenerate(true);

        session()->set([
            'admin_logged_in' => true,
            'admin_id'        => (int) $user['id'],
            'admin_name'      => $user['name'],
            'admin_role'      => $user['role_slug'],
            'admin_branch_id' => $user['branch_id'] ? (int) $user['branch_id'] : null,
        ]);

        $this->log((int) $user['id'], 'ADMIN_LOGIN_SUCCESS', 'Login backoffice berhasil', $ip, $userAgent);

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

        session()->remove(['admin_logged_in', 'admin_id', 'admin_name', 'admin_role', 'admin_branch_id', 'admin_permissions']);
        session()->regenerate(true);

        return redirect()->to(site_url('backoffice'))->with('success', 'Anda sudah keluar.');
    }

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