<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;

/**
 * CUST-01 Daftar Customer, CUST-02 Detail Customer.
 * Customer dengan booking TIDAK pernah di-hard-delete (tidak ada aksi hapus di sini).
 * Admin cabang hanya melihat customer yang pernah booking di cabangnya.
 */
class CustomerController extends BaseController
{
    private const PER_PAGE   = 20;
    private const ACCOUNTS   = ['ACTIVE', 'SUSPENDED', 'NONE', 'OTHER'];
    private const DOC_TYPES  = ['KTP' => 'KTP', 'SIM' => 'SIM', 'PASSPORT' => 'Paspor'];
    private const DOC_EXT    = ['jpg', 'jpeg', 'png', 'pdf'];
    private const DOC_MIME   = ['image/jpeg', 'image/png', 'application/pdf'];
    private const DOC_MAX_KB = 5120;

    private $conn;
    private ?int $branchId = null;
    private array $cf = []; // kolom customers
    private array $af = []; // kolom customer_accounts
    private array $df = []; // kolom customer_documents

    // ===================================================================
    // GET /backoffice/customers   (CUST-01)
    // ===================================================================
    public function index()
    {
        $this->boot();

        $g = $this->request->getGet();
        $account = strtoupper((string) ($g['account'] ?? ''));
        $f = [
            'q'        => mb_substr(trim((string) ($g['q'] ?? '')), 0, 100),
            'account'  => in_array($account, self::ACCOUNTS, true) ? $account : '',
            'verified' => in_array(($g['verified'] ?? ''), ['yes', 'no'], true) ? $g['verified'] : '',
            'flag'     => in_array(($g['flag'] ?? ''), ['dup', 'noacc'], true) ? $g['flag'] : '',
            'from'     => $this->dateOrEmpty($g['from'] ?? ''),
            'to'       => $this->dateOrEmpty($g['to'] ?? ''),
        ];
        $page = max(1, (int) ($g['page'] ?? 1));

        $cnt = $this->conn->table('customers c')->join('customer_accounts ca', 'ca.customer_id = c.id', 'left');
        $this->applyFilters($cnt, $f);
        $total = (int) $cnt->countAllResults();

        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($page, $pages);

        $qb = $this->conn->table('customers c')->select($this->listSelect(), false)
            ->join('customer_accounts ca', 'ca.customer_id = c.id', 'left');
        $this->applyFilters($qb, $f);
        $rows = $qb->orderBy('c.id', 'DESC')->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)->get()->getResultArray();

        $see = $this->canSeePhone();
        foreach ($rows as &$r) {
            $r['enc']        = id_encode((int) $r['id']);
            $r['code']       = $this->codeOf($r);
            $r['phone_view'] = $see ? (string) $r['phone'] : $this->mask($r['phone'], 5, 3);
        }
        unset($r);

        return view('backoffice/customer/index', [
            'title'      => 'Customer',
            'nav'        => 'customer',
            'rows'       => $rows,
            'f'          => $f,
            'page'       => $page,
            'pages'      => $pages,
            'total'      => $total,
            'canManage'  => $this->canManage(),
            'canCreate'  => $this->canCreate(),
            'canSeePhone' => $see,
            'resolveUrl' => $this->canResolve() ? site_url('backoffice/customers/account-resolution') : null,
        ]);
    }

    // ===================================================================
    // GET /backoffice/customers/{id}   (CUST-02)
    // ===================================================================
    public function show(string $seg)
    {
        $this->boot();

        $c = $this->loadCustomer($seg);
        if (!$c) {
            return $this->gone();
        }

        $id  = (int) $c['id'];
        $see = $this->canSeePhone();

        // Akun login. Sengaja TIDAK membaca tabel OTP: admin tidak boleh melihat OTP aktif.
        $account = $this->conn->table('customer_accounts')->where('customer_id', $id)->get()->getRowArray();
        if ($account) {
            $loginCol = $this->firstExisting(['last_login_at', 'last_login'], $this->af);
            $account['_last_login'] = $loginCol ? ($account[$loginCol] ?? null) : null;
            $account['_phone_view'] = $see ? (string) $account['login_phone'] : $this->mask($account['login_phone'], 5, 3);
        }

        // Dokumen identitas
        $docs = [];
        if ($this->df) {
            $typeCol   = $this->firstExisting(['document_type', 'doc_type', 'type'], $this->df);
            $numCol    = $this->firstExisting(['document_number', 'id_number', 'number'], $this->df);
            $statusCol = $this->firstExisting(['verification_status', 'status'], $this->df);

            $q = $this->conn->table('customer_documents')->where('customer_id', $id);
            if (in_array('deleted_at', $this->df, true)) {
                $q->where('deleted_at', null);
            }
            foreach ($q->orderBy('id', 'DESC')->limit(30)->get()->getResultArray() as $d) {
                $docs[] = [
                    'type'    => $typeCol ? (string) ($d[$typeCol] ?? '-') : '-',
                    'number'  => $numCol ? ($see ? (string) ($d[$numCol] ?? '') : $this->mask($d[$numCol] ?? '', 3, 3)) : '',
                    'status'  => $statusCol ? strtoupper((string) ($d[$statusCol] ?? '')) : '',
                    'created' => $d['created_at'] ?? null,
                    'url'     => admin_can('customer.verify') ? site_url('backoffice/verifikasi-identitas/' . id_encode((int) $d['id'])) : null,
                ];
            }
        }

        // Riwayat booking (admin cabang: hanya booking cabangnya)
        $limit = $this->request->getGet('all') ? 500 : 25;
        $bq = $this->conn->table('bookings')
            ->select('id, invoice_no, created_at, start_at, end_at, status, payment_status, grand_total')
            ->where('customer_id', $id);
        if ($this->branchId) {
            $bq->where('branch_id', $this->branchId);
        }
        $bookings = $bq->orderBy('id', 'DESC')->limit($limit)->get()->getResultArray();
        foreach ($bookings as &$b) {
            $b['url'] = site_url('backoffice/bookings/' . id_encode((int) $b['id']));
        }
        unset($b);

        $sb = $this->branchId ? ' AND branch_id = ' . (int) $this->branchId : '';
        $stat = $this->conn->query(
            "SELECT COUNT(*) AS n,
                    COALESCE(SUM(CASE WHEN status NOT IN ('CANCELLED','EXPIRED') THEN grand_total END), 0) AS total,
                    MAX(created_at) AS last_at
               FROM bookings WHERE customer_id = ?" . $sb,
            [$id]
        )->getRowArray();

        // Nomor kembar
        $dups = [];
        if (!empty($c['phone'])) {
            $dq = $this->conn->table('customers')->select('id, name')->where('phone', $c['phone'])->where('id !=', $id);
            if (in_array('deleted_at', $this->cf, true)) {
                $dq->where('deleted_at', null);
            }
            if ($this->branchId) {
                $dq->where($this->inBranch('customers'), null, false);
            }
            $dups = $dq->limit(5)->get()->getResultArray();
        }

        // Aktivitas
        $log = $this->conn->table('activity_logs a')
            ->select('a.description, a.created_at, u.name AS who')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->where('a.entity_type', 'customer')->where('a.entity_id', $id)
            ->orderBy('a.id', 'DESC')->limit(15)->get()->getResultArray();

        $noteCol = $this->firstExisting(['internal_notes', 'notes'], $this->cf);
        $addrCol = $this->firstExisting(['address', 'alamat'], $this->cf);

        return view('backoffice/customer/show', [
            'title'       => $c['name'],
            'nav'         => 'customer',
            'seg'         => $seg,
            'c'           => $c,
            'code'        => $this->codeOf($c),
            'phoneView'   => $see ? (string) $c['phone'] : $this->mask($c['phone'], 5, 3),
            'address'     => $addrCol ? (string) ($c[$addrCol] ?? '') : null,
            'notes'       => $noteCol ? (string) ($c[$noteCol] ?? '') : null,
            'account'     => $account,
            'docs'        => $docs,
            'bookings'    => $bookings,
            'stat'        => $stat,
            'dups'        => $dups,
            'log'         => $log,
            'showAll'     => (bool) $this->request->getGet('all'),
            'edit'        => (bool) $this->request->getGet('edit'),
            'canManage'   => $this->canManage(),
            'canCreate'   => $this->canCreate(),
            'resolveUrl'  => $this->canResolve() ? site_url('backoffice/customers/account-resolution') : null,
            'docTypes'    => self::DOC_TYPES,
            'bookingUrl'  => site_url('backoffice/bookings/baru?customer_id=' . (int) $c['id']),
        ]);
    }

    // ===================================================================
    // POST /backoffice/customers/{id}/update   (edit profil; nomor HP tidak bisa diubah di sini)
    // ===================================================================
    public function update(string $seg)
    {
        $this->boot();
        $c = $this->loadCustomer($seg);
        if (!$c) {
            return $this->gone();
        }

        return $this->tx($seg, '', function () use ($c) {
            $name  = trim((string) $this->request->getPost('name'));
            $email = trim((string) $this->request->getPost('email'));
            $addr  = trim((string) $this->request->getPost('address'));

            if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
                throw new \DomainException('Nama wajib diisi (2–150 karakter).');
            }
            if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150)) {
                throw new \DomainException('Format email tidak valid.');
            }

            $data = ['name' => $name, 'email' => $email === '' ? null : $email];
            $addrCol = $this->firstExisting(['address', 'alamat'], $this->cf);
            if ($addrCol) {
                $data[$addrCol] = $addr === '' ? null : mb_substr($addr, 0, 500);
            }
            if (in_array('updated_at', $this->cf, true)) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }

            $changed = [];
            if ($name !== (string) $c['name']) {
                $changed[] = 'nama';
            }
            if ((string) $c['email'] !== $email) {
                $changed[] = 'email';
            }
            if ($addrCol && (string) ($c[$addrCol] ?? '') !== $addr) {
                $changed[] = 'alamat';
            }

            $this->conn->table('customers')->where('id', (int) $c['id'])->update($data);
            $this->logActivity((int) $c['id'], 'profile_update',
                'Ubah profil customer #' . $c['id'] . ($changed ? ' (' . implode(', ', $changed) . ')' : ' (tanpa perubahan)'));

            return 'Profil customer diperbarui.';
        });
    }

    // ===================================================================
    // POST /backoffice/customers/{id}/account-status   (suspend / reactivate)
    // Suspend TIDAK membatalkan booking apa pun.
    // ===================================================================
    public function accountStatus(string $seg)
    {
        $this->boot();
        $c = $this->loadCustomer($seg);
        if (!$c) {
            return $this->gone();
        }

        return $this->tx($seg, '#akun', function () use ($c) {
            $id     = (int) $c['id'];
            $action = (string) $this->request->getPost('action');
            $reason = trim((string) $this->request->getPost('reason'));

            if (!in_array($action, ['suspend', 'reactivate'], true)) {
                throw new \DomainException('Aksi tidak valid.');
            }
            if (mb_strlen($reason) < 3) {
                throw new \DomainException('Alasan wajib diisi (minimal 3 karakter).');
            }

            $acc = $this->conn->query('SELECT * FROM customer_accounts WHERE customer_id = ? FOR UPDATE', [$id])->getRowArray();
            if (!$acc) {
                throw new \DomainException('Customer ini belum punya akun login.');
            }

            if ($action === 'suspend') {
                if ($acc['status'] !== 'ACTIVE') {
                    throw new \DomainException('Hanya akun ACTIVE yang bisa di-suspend (status sekarang ' . $acc['status'] . ').');
                }
                $new = 'SUSPENDED';
            } else {
                if ($acc['status'] !== 'SUSPENDED') {
                    throw new \DomainException('Hanya akun SUSPENDED yang bisa diaktifkan kembali (status sekarang ' . $acc['status'] . ').');
                }
                $new = 'ACTIVE';
            }

            $upd = ['status' => $new];
            if (in_array('updated_at', $this->af, true)) {
                $upd['updated_at'] = date('Y-m-d H:i:s');
            }
            if (in_array('suspended_at', $this->af, true)) {
                $upd['suspended_at'] = $new === 'SUSPENDED' ? date('Y-m-d H:i:s') : null;
            }

            $this->conn->table('customer_accounts')->where('customer_id', $id)->update($upd);
            $this->logActivity($id, 'account_' . $action,
                ($action === 'suspend' ? 'Suspend' : 'Aktifkan kembali') . ' akun customer #' . $id . ': ' . mb_substr($reason, 0, 300));

            return $action === 'suspend'
                ? 'Akun di-suspend. Booking yang sudah ada tidak berubah.'
                : 'Akun diaktifkan kembali.';
        });
    }

    // ===================================================================
    // POST /backoffice/customers/{id}/note   (catatan internal, hanya menambah)
    // ===================================================================
    public function addNote(string $seg)
    {
        $this->boot();
        $c = $this->loadCustomer($seg);
        if (!$c) {
            return $this->gone();
        }

        return $this->tx($seg, '#catatan', function () use ($c) {
            $col  = $this->firstExisting(['internal_notes', 'notes'], $this->cf);
            $text = $this->str('note', 1000);
            if (!$col) {
                throw new \DomainException('Kolom catatan (internal_notes / notes) tidak ditemukan di tabel customers.');
            }
            if ($text === null) {
                throw new \DomainException('Catatan tidak boleh kosong.');
            }

            $line = '[' . date('d/m/Y H:i') . ' · ' . (string) session()->get('admin_name') . '] ' . $text;
            $this->conn->table('customers')->where('id', (int) $c['id'])
                ->update([$col => trim(((string) ($c[$col] ?? '')) . "\n" . $line)]);
            $this->logActivity((int) $c['id'], 'note', 'Catatan internal pada customer #' . $c['id']);

            return 'Catatan disimpan.';
        });
    }

    // ===================================================================
    // POST /backoffice/customers/{id}/document   (tambah dokumen identitas oleh staf)
    // ===================================================================
    public function addDocument(string $seg)
    {
        $this->boot();
        $c = $this->loadCustomer($seg);
        if (!$c) {
            return $this->gone();
        }

        return $this->tx($seg, '#dokumen', function () use ($c) {
            if (!$this->df) {
                throw new \DomainException('Tabel customer_documents tidak ditemukan.');
            }

            $type = strtoupper((string) $this->request->getPost('document_type'));
            if (!isset(self::DOC_TYPES[$type])) {
                throw new \DomainException('Pilih jenis dokumen.');
            }
            $number = $this->str('document_number', 60);

            $file = $this->request->getFile('document');
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                throw new \DomainException('Pilih file dokumen yang valid.');
            }
            if ($file->getSizeByUnit('kb') > self::DOC_MAX_KB) {
                throw new \DomainException('Ukuran file maksimal 5 MB.');
            }
            if (!in_array(strtolower($file->getExtension()), self::DOC_EXT, true)
                || !in_array($file->getMimeType(), self::DOC_MIME, true)) {
                throw new \DomainException('Format file harus JPG, PNG, atau PDF.');
            }

            $fileCol = $this->firstExisting(['file_path', 'document_path', 'path', 'file'], $this->df);
            if (!$fileCol) {
                throw new \DomainException('Kolom file di customer_documents tidak ditemukan.');
            }

            $dir  = 'uploads/customer_documents/' . (int) $c['id'];
            $name = $file->getRandomName();
            $file->move(WRITEPATH . $dir, $name);

            $now  = date('Y-m-d H:i:s');
            $data = ['customer_id' => (int) $c['id'], $fileCol => $dir . '/' . $name];
            $put  = function (array $cols, $val) use (&$data) {
                $col = $this->firstExisting($cols, $this->df);
                if ($col) {
                    $data[$col] = $val;
                }
            };
            $put(['document_type', 'doc_type', 'type'], $type);
            $put(['document_number', 'id_number', 'number'], $number);
            $put(['verification_status', 'status'], 'PENDING');
            $put(['original_name', 'file_name'], mb_substr($file->getClientName(), 0, 200));
            $put(['mime_type'], $file->getMimeType());
            $put(['file_size'], (int) $file->getSize());
            $put(['uploaded_by_user_id', 'created_by_user_id'], $this->uid());
            $put(['created_at'], $now);
            $put(['updated_at'], $now);

            try {
                $this->conn->table('customer_documents')->insert($data);
            } catch (\Throwable $e) {
                @unlink(WRITEPATH . $dir . '/' . $name);
                throw $e;
            }

            $this->logActivity((int) $c['id'], 'document_add', 'Tambah dokumen ' . $type . ' untuk customer #' . $c['id']);

            return 'Dokumen ' . self::DOC_TYPES[$type] . ' ditambahkan dan menunggu verifikasi.';
        });
    }

    // ===================================================================
    // Query list
    // ===================================================================

    private function listSelect(): string
    {
        $del = in_array('deleted_at', $this->cf, true) ? ' AND d.deleted_at IS NULL' : '';

        $s = 'c.*, ca.customer_id AS acc_owner, ca.status AS acc_status, ca.login_phone AS acc_phone';
        $s .= in_array('verified_at', $this->af, true) ? ', ca.verified_at AS acc_verified_at' : ', NULL AS acc_verified_at';

        $login = $this->firstExisting(['last_login_at', 'last_login'], $this->af);
        $s .= $login ? ", ca.{$login} AS acc_last_login" : ', NULL AS acc_last_login';

        // Admin cabang: hitung booking di cabangnya saja
        $bw  = $this->branchId ? ' AND b.branch_id = '  . (int) $this->branchId : '';
        $bw2 = $this->branchId ? ' AND b2.branch_id = ' . (int) $this->branchId : '';
        $s .= ', (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id' . $bw . ') AS booking_count';
        $s .= ', (SELECT MAX(b2.created_at) FROM bookings b2 WHERE b2.customer_id = c.id' . $bw2 . ') AS last_booking_at';
        $s .= ", (SELECT COUNT(*) FROM customers d WHERE d.phone = c.phone AND d.phone <> '' AND d.id <> c.id{$del}) AS dup_count";

        return $s;
    }

    private function applyFilters($qb, array $f): void
    {
        $del = in_array('deleted_at', $this->cf, true);
        if ($del) {
            $qb->where('c.deleted_at', null);
        }

        // Admin cabang: hanya customer yang pernah booking di cabangnya
        if ($this->branchId) {
            $qb->where($this->inBranch('c'), null, false);
        }

        if ($f['q'] !== '') {
            $q = $f['q'];
            $qb->groupStart()->like('c.name', $q)->orLike('c.email', $q);
            if (ctype_digit($q)) {
                $qb->orWhere('c.id', (int) $q);
            }
            foreach (['customer_code', 'code'] as $col) {
                if (in_array($col, $this->cf, true)) {
                    $qb->orLike('c.' . $col, $q);
                }
            }
            // Pencarian nomor hanya untuk role yang boleh melihat nomor lengkap
            $digits = preg_replace('/\D+/', '', $q);
            if ($this->canSeePhone() && strlen($digits) >= 3) {
                $qb->orLike('c.phone', ltrim($digits, '0'));
            }
            $qb->groupEnd();
        }

        switch ($f['account']) {
            case 'NONE':
                $qb->where('ca.customer_id IS NULL', null, false);
                break;
            case 'OTHER':
                $qb->where('ca.customer_id IS NOT NULL', null, false)->whereNotIn('ca.status', ['ACTIVE', 'SUSPENDED']);
                break;
            case 'ACTIVE':
            case 'SUSPENDED':
                $qb->where('ca.status', $f['account']);
                break;
        }

        if (in_array('verified_at', $this->af, true)) {
            if ($f['verified'] === 'yes') {
                $qb->where('ca.verified_at IS NOT NULL', null, false);
            } elseif ($f['verified'] === 'no') {
                $qb->where('ca.customer_id IS NOT NULL', null, false)->where('ca.verified_at IS NULL', null, false);
            }
        }

        if (in_array('created_at', $this->cf, true)) {
            if ($f['from'] !== '') {
                $qb->where('c.created_at >=', $f['from'] . ' 00:00:00');
            }
            if ($f['to'] !== '') {
                $qb->where('c.created_at <', date('Y-m-d', strtotime($f['to'] . ' +1 day')) . ' 00:00:00');
            }
        }

        if ($f['flag'] === 'dup') {
            $dd = $del ? ' AND d.deleted_at IS NULL' : '';
            $qb->where("EXISTS (SELECT 1 FROM customers d WHERE d.phone = c.phone AND d.phone <> '' AND d.id <> c.id{$dd})", null, false);
        } elseif ($f['flag'] === 'noacc') {
            $qb->where('ca.customer_id IS NULL', null, false);
        }
    }

    // ===================================================================
    // Helper
    // ===================================================================

    private function boot(): void
    {
        helper('backoffice'); // id_encode / id_decode / admin_can

        $this->conn     = db_connect();
        $this->branchId = session()->get('admin_branch_id') ? (int) session()->get('admin_branch_id') : null;
        $this->cf       = $this->conn->getFieldNames('customers');
        $this->af       = $this->conn->tableExists('customer_accounts') ? $this->conn->getFieldNames('customer_accounts') : [];
        $this->df       = $this->conn->tableExists('customer_documents') ? $this->conn->getFieldNames('customer_documents') : [];
    }

    /** Potongan SQL: customer pernah booking di cabang admin. */
    private function inBranch(string $alias): string
    {
        return "EXISTS (SELECT 1 FROM bookings bx WHERE bx.customer_id = {$alias}.id AND bx.branch_id = " . (int) $this->branchId . ')';
    }

    private function canSeePhone(): bool
    {
        return admin_can('customer.manage') || admin_can('customer.view_phone') || admin_can('booking.manage');
    }

    private function canManage(): bool
    {
        return admin_can('customer.manage') || admin_can('booking.manage');
    }

    private function canCreate(): bool
    {
        return admin_can('booking.create') || admin_can('booking.manage');
    }

    /** Resolution (merge/link) lintas cabang: hanya admin lintas cabang (Owner). */
    private function canResolve(): bool
    {
        return $this->branchId === null && (admin_can('customer.resolve') || admin_can('customer.manage'));
    }

    private function uid(): int
    {
        return (int) session()->get('admin_id');
    }

    private function firstExisting(array $candidates, array $fields): ?string
    {
        foreach ($candidates as $c) {
            if (in_array($c, $fields, true)) {
                return $c;
            }
        }
        return null;
    }

    private function codeOf(array $row): string
    {
        foreach (['customer_code', 'code'] as $k) {
            if (!empty($row[$k])) {
                return (string) $row[$k];
            }
        }
        return 'CUS-' . str_pad((string) $row['id'], 5, '0', STR_PAD_LEFT);
    }

    private function mask($text, int $keepStart, int $keepEnd): string
    {
        $text = (string) $text;
        if ($text === '') {
            return '-';
        }
        $len = mb_strlen($text);
        if ($len <= $keepStart + $keepEnd) {
            return str_repeat('*', $len);
        }
        return mb_substr($text, 0, $keepStart) . str_repeat('*', min(8, $len - $keepStart - $keepEnd)) . mb_substr($text, -$keepEnd);
    }

    private function dateOrEmpty($v): string
    {
        $v = (string) $v;
        $d = \DateTime::createFromFormat('Y-m-d', $v);
        return ($d && $d->format('Y-m-d') === $v) ? $v : '';
    }

    private function str(string $field, int $max): ?string
    {
        $v = $this->request->getPost($field);
        $v = is_string($v) ? trim($v) : '';
        return $v === '' ? null : mb_substr($v, 0, $max);
    }

    private function loadCustomer(string $seg): ?array
    {
        $id = id_decode($seg);
        if (!$id) {
            return null;
        }
        $q = $this->conn->table('customers c')->select('c.*')->where('c.id', (int) $id);
        if (in_array('deleted_at', $this->cf, true)) {
            $q->where('c.deleted_at', null);
        }
        if ($this->branchId) {
            $q->where($this->inBranch('c'), null, false);
        }
        return $q->get()->getRowArray() ?: null;
    }

    private function gone()
    {
        return redirect()->to(site_url('backoffice/customers'))->with('error', 'Customer tidak ditemukan.');
    }

    private function tx(string $seg, string $anchor, callable $fn)
    {
        $this->conn->transBegin();
        try {
            $msg = $fn();
            $this->conn->transCommit();
            return $this->done($seg, $anchor, 'success', (string) $msg);
        } catch (\DomainException $e) {
            $this->conn->transRollback();
            return $this->done($seg, $anchor, 'error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->conn->transRollback();
            log_message('error', 'Customer gagal: ' . $e->getMessage());
            return $this->done($seg, $anchor, 'error', 'Terjadi kesalahan saat menyimpan. Coba lagi.');
        }
    }

    private function done(string $seg, string $anchor, string $type, string $msg)
    {
        return redirect()->to(site_url('backoffice/customers/' . $seg) . $anchor)->with($type, $msg);
    }

    private function logActivity(int $customerId, string $action, string $desc): void
    {
        $this->conn->table('activity_logs')->insert([
            'user_id'     => $this->uid(),
            'event_type'  => 'CUSTOMER_' . strtoupper($action),
            'module'      => 'customer',
            'entity_type' => 'customer',
            'entity_id'   => $customerId,
            'action'      => $action,
            'description' => mb_substr($desc, 0, 1000),
            'ip_address'  => $this->request->getIPAddress(),
            'user_agent'  => mb_substr((string) $this->request->getUserAgent(), 0, 500),
        ]);
    }
}