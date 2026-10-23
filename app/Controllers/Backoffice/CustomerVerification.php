<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;
use App\Libraries\CustomerUploads;

/**
 * Verifikasi file identitas customer di back office.
 * Data dokumen ada di tabel customer_documents. Akses dicek lewat admin_can('customer.verify') di setiap method.
 */
class CustomerVerification extends BaseController
{
    private const STATUSES = ['PENDING', 'REJECTED', 'VERIFIED', 'UNVERIFIED'];

    public function index()
    {
        if ($r = $this->deny()) {
            return $r;
        }
        $status = strtoupper((string) $this->request->getGet('status'));
        if (! in_array($status, self::STATUSES, true)) {
            $status = 'PENDING';
        }

        $db   = db_connect();
        $rows = $db->table('customer_documents d')
            ->select('c.id, c.name, c.phone, c.profile_photo, d.document_type AS id_type, d.document_number AS id_number, d.expires_at AS id_expires_at, d.verification_status AS id_verification_status, d.updated_at')
            ->join('customers c', 'c.id = d.customer_id')
            ->where('d.verification_status', $status)
            ->orderBy('d.updated_at', $status === 'PENDING' ? 'ASC' : 'DESC')
            ->get()->getResultArray();

        $counts = [];
        foreach ($db->query('SELECT verification_status AS s, COUNT(*) AS c FROM customer_documents GROUP BY verification_status')->getResultArray() as $r) {
            $counts[$r['s']] = (int) $r['c'];
        }

        return view('backoffice/customer_verification/index', [
            'title'  => 'Verifikasi Identitas',
            'nav'    => 'verifikasi',
            'rows'   => $rows,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function show(string $enc)
    {
        if ($r = $this->deny()) {
            return $r;
        }
        $customer = $this->findCustomer($enc);
        if (! $customer) {
            return redirect()->to(base_url('backoffice/verifikasi-identitas'))->with('error', 'Dokumen identitas tidak ditemukan.');
        }

        // Peringatan: nomor identitas yang sama dipakai akun lain
        $duplicates = [];
        if (! empty($customer['id_number'])) {
            $duplicates = db_connect()->table('customer_documents d')
                ->select('c.id, c.name, c.phone')
                ->join('customers c', 'c.id = d.customer_id')
                ->where('d.document_number', $customer['id_number'])
                ->where('d.customer_id !=', $customer['id'])
                ->get()->getResultArray();
        }

        $path = (new CustomerUploads())->identityPath($customer['id_file_path'] ?? null);

        return view('backoffice/customer_verification/show', [
            'title'      => 'Tinjau Identitas',
            'nav'        => 'verifikasi',
            'customer'   => $customer,
            'duplicates' => $duplicates,
            'fileMime'   => $path ? (mime_content_type($path) ?: '') : '',
            'fileExists' => $path !== null,
            'enc'        => $enc,
        ]);
    }

    /** Tampilkan file identitas ke staff yang berhak. */
    public function file(string $enc)
    {
        if ($r = $this->deny()) {
            return $r;
        }
        $customer = $this->findCustomer($enc);
        if (! $customer) {
            return $this->response->setStatusCode(404);
        }

        return (new CustomerUploads())->streamIdentity($customer['id_file_path'] ?? null);
    }

    public function approve(string $enc)
    {
        if ($r = $this->deny()) {
            return $r;
        }
        $customer = $this->findCustomer($enc);
        if (! $customer || $customer['id_verification_status'] !== 'PENDING' || empty($customer['id_file_path'])) {
            return redirect()->back()->with('error', 'Dokumen ini tidak bisa disetujui.');
        }

        // Staff wajib mencentang semua poin pemeriksaan (dikirim sebagai checks[])
        $checks = (array) $this->request->getPost('checks');
        if (count($checks) < 4) {
            return redirect()->back()->with('error', 'Centang semua poin pemeriksaan sebelum menyetujui.');
        }

        db_connect()->table('customer_documents')->where('id', $customer['doc_id'])->update([
            'verification_status' => 'VERIFIED',
            'is_verified'         => 1,
            'verified_at'         => date('Y-m-d H:i:s'),
            'verified_by'         => $this->staffId(),
            'reject_reason'       => null,
        ]);

        return redirect()->to(base_url('backoffice/verifikasi-identitas'))->with('success', 'Identitas ' . $customer['name'] . ' diverifikasi.');
    }

    public function reject(string $enc)
    {
        if ($r = $this->deny()) {
            return $r;
        }
        $customer = $this->findCustomer($enc);
        if (! $customer || ! in_array($customer['id_verification_status'], ['PENDING', 'VERIFIED'], true)) {
            return redirect()->back()->with('error', 'Dokumen ini tidak bisa ditolak.');
        }

        $reason = trim((string) $this->request->getPost('reason'));
        if ($reason === '' || mb_strlen($reason) > 255) {
            return redirect()->back()->with('error', 'Alasan penolakan wajib diisi (maksimal 255 karakter).');
        }

        db_connect()->table('customer_documents')->where('id', $customer['doc_id'])->update([
            'verification_status' => 'REJECTED',
            'is_verified'         => 0,
            'verified_at'         => date('Y-m-d H:i:s'),
            'verified_by'         => $this->staffId(),
            'reject_reason'       => $reason,
        ]);

        return redirect()->to(base_url('backoffice/verifikasi-identitas'))->with('success', 'Dokumen ' . $customer['name'] . ' ditolak.');
    }

        /**
     * Tampilkan foto profil customer (khusus admin backoffice)
     */
    public function profilePhoto(int $customerId)
    {
        if ($r = $this->deny()) {
            return $r;
        }

        $customer = db_connect()->table('customers')
            ->select('profile_photo')
            ->where('id', $customerId)
            ->get(1)->getRowArray();

        if (! $customer || empty($customer['profile_photo'])) {
            // Jika foto kosong, kirim gambar transparan 1x1 pixel
            return $this->response->setHeader('Content-Type', 'image/png')
                ->setBody(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        }

        // Gunakan library untuk mengirimkan file gambar yang ada di writable/uploads/profile/
        return (new CustomerUploads())->streamPhoto($customer['profile_photo']);
    }

    /** $enc = id customer terenkripsi; hasilnya data customer + dokumen identitasnya. */
    private function findCustomer(string $enc): ?array
    {
        helper('id'); // sesuaikan dengan nama helper id_encode/id_decode di projectmu (hapus baris ini jika sudah autoload)
        $id = id_decode($enc);
        if (! $id) {
            return null;
        }

        return db_connect()->table('customers c')
            ->select('c.*, d.id AS doc_id, d.document_type AS id_type, d.document_number AS id_number, d.file_path AS id_file_path, d.expires_at AS id_expires_at, d.verification_status AS id_verification_status, d.reject_reason AS id_reject_reason')
            ->join('customer_documents d', 'd.customer_id = c.id')
            ->where('c.id', $id)
            ->orderBy('d.id', 'DESC')
            ->get(1)->getRowArray();
    }

    /** Cek permission lewat helper backoffice yang sama dengan menu produk/jenis. */
    private function deny()
    {
        helper('backoffice');
        if (! admin_can('customer.verify')) {
            return redirect()->to(site_url('backoffice/dashboard'))->with('error', 'Kamu tidak punya akses ke verifikasi identitas.');
        }

        return null;
    }

    private function staffId(): ?int
    {
        // Sesuaikan key session id admin kalau berbeda (layout memakai admin_name & admin_role)
        $id = session()->get('admin_id') ?? session()->get('user_id');

        return $id ? (int) $id : null;
    }
}