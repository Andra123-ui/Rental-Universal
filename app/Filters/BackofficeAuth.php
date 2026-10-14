<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class BackofficeAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('backoffice');
        $session = session();

        if (!$session->get('admin_logged_in')) {
            return redirect()->to(site_url('backoffice'))
                ->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $db = db_connect();

        $user = $db->table('users u')
            ->select('u.status, u.role_id, r.slug AS role_slug')
            ->join('roles r', 'r.id = u.role_id')
            ->where('u.id', (int) $session->get('admin_id'))
            ->where('u.deleted_at', null)
            ->get()->getRowArray();

        if (!$user || $user['status'] !== 'ACTIVE') {
            $session->remove([
                'admin_logged_in', 'admin_id', 'admin_name',
                'admin_role', 'admin_branch_id', 'admin_permissions',
            ]);
            return redirect()->to(site_url('backoffice'))
                ->with('error', 'Akun tidak aktif. Hubungi administrator.');
        }

        // Muat ulang role & hak akses di setiap request, jadi perubahan
        // di tabel role_permissions langsung berlaku tanpa login ulang
        $perms = $db->table('role_permissions rp')
            ->select('p.code')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('rp.role_id', (int) $user['role_id'])
            ->where('rp.allowed', 1)
            ->get()->getResultArray();

        $session->set([
            'admin_role'        => $user['role_slug'],
            'admin_permissions' => array_column($perms, 'code'),
        ]);

        // Pembatasan hak akses (opsional): 'backofficeAuth:product.view,product.manage'
        if (!empty($arguments)) {
            foreach ($arguments as $code) {
                if (admin_can(trim($code))) {
                    return; // lolos
                }
            }

            return redirect()->to(site_url('backoffice/dashboard'))
                ->with('error', 'Anda tidak memiliki akses ke halaman itu.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}