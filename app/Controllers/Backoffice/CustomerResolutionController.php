<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;

/**
 * CUST-03 — Customer Account / Claim Resolution
 *
 * Kasus (issue):
 *  DUP    : beberapa record customer dengan nomor yang sama
 *  ORPHAN : customer lama TANPA akun, nomornya = login_phone akun milik record customer lain
 *  CLAIM  : akun login dengan status diblokir / perlu review
 *
 * Aturan: tidak pernah merge hanya berdasar nama; nomor harus terverifikasi;
 * merge transaksional dan diaudit (customer_resolutions + activity_logs).
 */
class CustomerResolutionController extends BaseController
{
    private const TYPES = [
        'DUP'    => 'Duplicate phone',
        'ORPHAN' => 'Customer lama tanpa akun',
        'CLAIM'  => 'Claim diblokir',
    ];
    private const LIMIT = 200;

    private $conn;
    private array $cf = [];
    private array $af = [];

    // ===================================================================
    // GET /backoffice/customers/account-resolution   (issue queue)
    // ===================================================================
    public function index()
    {
        $this->boot();

        $type   = (string) $this->request->getGet('type');
        $status = (string) $this->request->getGet('status');
        if (!isset(self::TYPES[$type])) {
            $type = '';
        }
        if (!in_array($status, ['open', 'hold', 'closed'], true)) {
            $status = '';
        }

        $hasTable = $this->conn->tableExists('customer_resolutions');
        $decided  = $hasTable ? $this->latestDecisions() : [];

        $issues = [];
        foreach ($this->collectIssues() as $i) {
            if ($type !== '' && $i['type'] !== $type) {
                continue;
            }

            $d      = $decided[$i['key']] ?? null;
            $act    = $d['action'] ?? null;
            $closed = in_array($act, ['REJECT', 'MERGE', 'LINK'], true);

            if ($status === 'closed') {
                if (!$closed) {
                    continue;
                }
            } elseif ($closed) {
                continue;
            } elseif ($status === 'open' && $act !== null) {
                continue;
            } elseif ($status === 'hold' && $act !== 'HOLD') {
                continue;
            }

            $i['decision']   = $act;
            $i['decided_at'] = $d['created_at'] ?? null;
            $i['case_url']   = site_url('backoffice/customers/account-resolution/case') . '?key=' . rawurlencode($i['key']);
            $issues[] = $i;
        }

        return view('backoffice/customer/resolution_index', [
            'title'    => 'Account Resolution',
            'nav'      => 'customer',
            'issues'   => $issues,
            'types'    => self::TYPES,
            'type'     => $type,
            'status'   => $status,
            'hasTable' => $hasTable,
            'see'      => $this->canSeePhone(),
        ]);
    }

    // ===================================================================
    // GET /backoffice/customers/account-resolution/case?key=...
    // ===================================================================
    public function show()
    {
        $this->boot();

        $key  = (string) $this->request->getGet('key');
        $cand = $this->parseKey($key);
        if (!$cand) {
            return redirect()->to($this->queueUrl())->with('error', 'Kasus tidak valid.');
        }

        $people = $this->loadPeople($this->candidateIds($cand));
        if (!$people) {
            return redirect()->to($this->queueUrl())->with('error', 'Kasus ini sudah tidak relevan (record tidak ditemukan).');
        }

        // Saran record utama: yang punya akun, lalu paling banyak booking
        $sorted = $people;
        usort($sorted, static function ($a, $b) {
            return [(int) $b['has_account'], (int) $b['bk_count'], $a['id']] <=> [(int) $a['has_account'], (int) $a['bk_count'], $b['id']];
        });
        $suggestTarget = $sorted[0]['id'];
        $suggestSource = 0;
        foreach ($sorted as $p) {
            if ($p['id'] !== $suggestTarget) {
                $suggestSource = $p['id'];
                break;
            }
        }

        $hasTable = $this->conn->tableExists('customer_resolutions');
        $history  = $hasTable
            ? $this->conn->table('customer_resolutions r')
                ->select('r.*, u.name AS operator')
                ->join('users u', 'u.id = r.operator_user_id', 'left')
                ->where('r.issue_key', $key)->orderBy('r.id', 'DESC')->limit(20)->get()->getResultArray()
            : [];

        return view('backoffice/customer/resolution_case', [
            'title'         => 'Tinjau Kasus',
            'nav'           => 'customer',
            'key'           => $key,
            'type'          => $cand['type'],
            'typeLabel'     => self::TYPES[$cand['type']],
            'people'        => $people,
            'history'       => $history,
            'hasTable'      => $hasTable,
            'suggestTarget' => $suggestTarget,
            'suggestSource' => $suggestSource,
            'queueUrl'      => $this->queueUrl(),
            'formUrl'       => site_url('backoffice/customers/account-resolution/resolve'),
        ]);
    }

    // ===================================================================
    // POST /backoffice/customers/account-resolution/resolve
    // ===================================================================
    public function resolve()
    {
        $this->boot();

        if (!$this->request->is('post')) {
            return redirect()->to($this->queueUrl());
        }

        $key  = (string) $this->request->getPost('key');
        $cand = $this->parseKey($key);
        if (!$cand) {
            return redirect()->to($this->queueUrl())->with('error', 'Kasus tidak valid.');
        }

        $caseUrl = site_url('backoffice/customers/account-resolution/case') . '?key=' . rawurlencode($key);

        if (!$this->conn->tableExists('customer_resolutions')) {
            return redirect()->to($caseUrl)->with('error', 'Tabel customer_resolutions belum dibuat. Jalankan SQL pembuatan tabel terlebih dahulu.');
        }

        $action = (string) $this->request->getPost('action');
        $reason = trim((string) $this->request->getPost('reason'));

        $this->conn->transBegin();
        try {
            if (!in_array($action, ['MERGE', 'LINK', 'REJECT', 'HOLD'], true)) {
                throw new \DomainException('Aksi tidak valid.');
            }
            if (mb_strlen($reason) < 10) {
                throw new \DomainException('Alasan wajib diisi (minimal 10 karakter) sebagai jejak audit.');
            }
            $reason = mb_substr($reason, 0, 900);
            $ids    = $this->candidateIds($cand);

            switch ($action) {
                case 'MERGE':
                    [$primary, $secondary, $msg] = $this->doMerge($ids, $reason);
                    break;
                case 'LINK':
                    [$primary, $secondary, $msg] = $this->doLink($ids, $reason);
                    break;
                default:
                    $primary   = (int) $this->request->getPost('customer_id') ?: ($ids[0] ?? null);
                    $secondary = null;
                    $msg       = $action === 'REJECT' ? 'Kasus ditolak dan ditutup.' : 'Kasus ditahan untuk ditinjau lagi.';
                    $this->audit($primary ?: 0, strtolower($action), ucfirst(strtolower($action)) . " kasus {$key}: {$reason}");
            }

            $this->conn->table('customer_resolutions')->insert([
                'issue_key'             => $key,
                'issue_type'            => $cand['type'],
                'action'                => $action,
                'primary_customer_id'   => $primary,
                'secondary_customer_id' => $secondary,
                'reason'                => $reason,
                'operator_user_id'      => $this->uid(),
            ]);

            $this->conn->transCommit();
        } catch (\DomainException $e) {
            $this->conn->transRollback();
            return redirect()->to($caseUrl)->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->conn->transRollback();
            log_message('error', 'Account resolution gagal: ' . $e->getMessage());
            return redirect()->to($caseUrl)->with('error', 'Terjadi kesalahan; tidak ada data yang diubah.');
        }

        return redirect()->to($this->queueUrl())->with('success', $msg);
    }

    // ===================================================================
    // Aksi
    // ===================================================================

    /** Gabungkan record SOURCE ke TARGET. Return [primary, secondary, pesan]. */
    private function doMerge(array $ids, string $reason): array
    {
        $tid = (int) $this->request->getPost('target_id');
        $sid = (int) $this->request->getPost('source_id');

        if (!$tid || !$sid || $tid === $sid) {
            throw new \DomainException('Pilih dua record yang berbeda (utama dan yang digabung).');
        }
        if (!in_array($tid, $ids, true) || !in_array($sid, $ids, true)) {
            throw new \DomainException('Record yang dipilih bukan bagian dari kasus ini.');
        }
        foreach (['confirm_same', 'confirm_verified', 'confirm_backup'] as $chk) {
            if (!$this->request->getPost($chk)) {
                throw new \DomainException('Centang ketiga konfirmasi sebelum merge.');
            }
        }
        if (!in_array('deleted_at', $this->cf, true)) {
            throw new \DomainException('Tabel customers perlu kolom deleted_at (soft delete) agar record lama tidak dihapus permanen. '
                . 'Contoh: ALTER TABLE customers ADD COLUMN deleted_at DATETIME NULL;');
        }

        // Kunci baris dengan urutan id tetap (hindari deadlock)
        $rows = [];
        foreach ([min($tid, $sid), max($tid, $sid)] as $x) {
            $rows[$x] = $this->conn->query('SELECT * FROM customers WHERE id = ? FOR UPDATE', [$x])->getRowArray();
        }
        $t = $rows[$tid];
        $s = $rows[$sid];
        if (!$t || !$s || !empty($t['deleted_at']) || !empty($s['deleted_at'])) {
            throw new \DomainException('Salah satu record sudah tidak aktif atau tidak ditemukan.');
        }

        $ta = $this->conn->query('SELECT * FROM customer_accounts WHERE customer_id = ? FOR UPDATE', [$tid])->getRowArray();
        $sa = $this->conn->query('SELECT * FROM customer_accounts WHERE customer_id = ? FOR UPDATE', [$sid])->getRowArray();

        // Bukti: nomor sama, atau login_phone akun cocok dengan salah satu record. Nama saja TIDAK cukup.
        $samePhone = (string) $t['phone'] !== '' && (string) $t['phone'] === (string) $s['phone'];
        $accProof  = false;
        foreach ([$ta, $sa] as $a) {
            if ($a && in_array((string) $a['login_phone'], [(string) $t['phone'], (string) $s['phone']], true)) {
                $accProof = true;
            }
        }
        if (!$samePhone && !$accProof) {
            throw new \DomainException('Merge ditolak: kedua record tidak berbagi nomor yang sama. Merge tidak boleh hanya berdasar nama.');
        }

        if ($ta && $sa) {
            throw new \DomainException('Kedua record sudah punya akun login. Selesaikan dulu akun mana yang sah (tahan atau tolak kasus ini).');
        }

        $keep = $ta ?: $sa;
        if ($keep && in_array('verified_at', $this->af, true) && empty($keep['verified_at'])) {
            throw new \DomainException('Akun login belum terverifikasi (verified_at kosong). Nomor harus berhasil diverifikasi sebelum merge.');
        }

        // Pindahkan data
        $moved = [];
        foreach (['bookings', 'customer_documents', 'notifications'] as $tbl) {
            if ($this->conn->tableExists($tbl) && $this->conn->fieldExists('customer_id', $tbl)) {
                $this->conn->table($tbl)->where('customer_id', $sid)->update(['customer_id' => $tid]);
                $moved[$tbl] = (int) $this->conn->affectedRows();
            }
        }
        if ($sa && !$ta) {
            $this->conn->table('customer_accounts')->where('customer_id', $sid)->update(['customer_id' => $tid]);
            $moved['akun'] = 1;
        }

        // Record lama: soft delete + jejak
        $now = date('Y-m-d H:i:s');
        $upd = ['deleted_at' => $now];
        if (in_array('merged_into_id', $this->cf, true)) {
            $upd['merged_into_id'] = $tid;
        }
        if (in_array('updated_at', $this->cf, true)) {
            $upd['updated_at'] = $now;
        }
        $this->conn->table('customers')->where('id', $sid)->update($upd);

        $who = (string) session()->get('admin_name');
        $this->appendNote($sid, "[{$now} · {$who}] Digabung ke customer #{$tid}. Alasan: {$reason}");
        $this->appendNote($tid, "[{$now} · {$who}] Menerima merge dari customer #{$sid} ({$s['name']}).");

        $detail = [];
        foreach ($moved as $k => $n) {
            $detail[] = "{$k}: {$n}";
        }
        $this->audit($tid, 'merge', "Merge customer #{$sid} ({$s['name']}) ke #{$tid} ({$t['name']}); dipindah [" . implode(', ', $detail) . "]. Alasan: {$reason}");
        $this->audit($sid, 'merged_out', "Customer #{$sid} digabung ke #{$tid}. Alasan: {$reason}");

        return [$tid, $sid, "Merge selesai: customer #{$sid} digabung ke #{$tid} (" . implode(', ', $detail) . ').'];
    }

    /** Pindahkan akun login dari record OWNER ke record TARGET (customer lama yang punya riwayat). */
    private function doLink(array $ids, string $reason): array
    {
        $oid = (int) $this->request->getPost('owner_id');
        $tid = (int) $this->request->getPost('target_id');

        if (!$oid || !$tid || $oid === $tid) {
            throw new \DomainException('Pilih record pemilik akun dan record tujuan yang berbeda.');
        }
        if (!in_array($oid, $ids, true) || !in_array($tid, $ids, true)) {
            throw new \DomainException('Record yang dipilih bukan bagian dari kasus ini.');
        }
        if (!$this->request->getPost('confirm_verified')) {
            throw new \DomainException('Centang konfirmasi bahwa nomor sudah terverifikasi.');
        }

        $rows = [];
        foreach ([min($oid, $tid), max($oid, $tid)] as $x) {
            $rows[$x] = $this->conn->query('SELECT * FROM customers WHERE id = ? FOR UPDATE', [$x])->getRowArray();
        }
        $o = $rows[$oid];
        $t = $rows[$tid];
        if (!$o || !$t || !empty($o['deleted_at']) || !empty($t['deleted_at'])) {
            throw new \DomainException('Salah satu record sudah tidak aktif atau tidak ditemukan.');
        }

        $acc = $this->conn->query('SELECT * FROM customer_accounts WHERE customer_id = ? FOR UPDATE', [$oid])->getRowArray();
        if (!$acc) {
            throw new \DomainException('Record pemilik akun tidak punya akun login.');
        }
        $has = $this->conn->query('SELECT 1 FROM customer_accounts WHERE customer_id = ? FOR UPDATE', [$tid])->getRowArray();
        if ($has) {
            throw new \DomainException('Record tujuan sudah punya akun login.');
        }
        if (in_array('verified_at', $this->af, true) && empty($acc['verified_at'])) {
            throw new \DomainException('Akun login belum terverifikasi (verified_at kosong).');
        }
        if (preg_replace('/\D+/', '', (string) $acc['login_phone']) !== preg_replace('/\D+/', '', (string) $t['phone'])) {
            throw new \DomainException('Nomor login akun tidak sama dengan nomor record tujuan. Link ditolak.');
        }

        $upd = ['customer_id' => $tid];
        if (in_array('updated_at', $this->af, true)) {
            $upd['updated_at'] = date('Y-m-d H:i:s');
        }
        $this->conn->table('customer_accounts')->where('customer_id', $oid)->update($upd);

        $this->audit($tid, 'link_account', "Link akun {$acc['login_phone']} dari customer #{$oid} ke #{$tid}. Alasan: {$reason}");
        $this->audit($oid, 'unlink_account', "Akun {$acc['login_phone']} dipindah ke customer #{$tid}. Alasan: {$reason}");

        return [$tid, $oid, "Akun {$acc['login_phone']} ditautkan ke customer #{$tid}."];
    }

    // ===================================================================
    // Pengumpulan kasus
    // ===================================================================

    private function collectIssues(): array
    {
        $issues = [];
        $del    = in_array('deleted_at', $this->cf, true);
        $delP   = $del ? ' AND deleted_at IS NULL' : '';
        $delC   = $del ? ' AND c.deleted_at IS NULL' : '';

        // 1) Duplicate phone
        $rows = $this->conn->query(
            "SELECT phone, GROUP_CONCAT(id ORDER BY id) AS ids
               FROM customers
              WHERE phone IS NOT NULL AND phone <> ''{$delP}
              GROUP BY phone HAVING COUNT(*) > 1
              ORDER BY MAX(id) DESC LIMIT " . self::LIMIT
        )->getResultArray();
        foreach ($rows as $r) {
            $issues[] = ['key' => 'DUP|' . $r['phone'], 'type' => 'DUP', 'phone' => $r['phone'],
                'ids' => array_map('intval', explode(',', $r['ids'])), 'note' => 'Nomor sama dipakai beberapa record.'];
        }

        // 2) Customer lama tanpa akun, nomornya dipakai akun milik record lain
        $rows = $this->conn->query(
            "SELECT c.id AS cid, ca.customer_id AS aid, ca.login_phone AS phone
               FROM customers c
               JOIN customer_accounts ca ON ca.login_phone = c.phone AND ca.customer_id <> c.id
               JOIN customers x ON x.id = ca.customer_id
              WHERE c.phone <> '' AND x.phone <> c.phone
                AND NOT EXISTS (SELECT 1 FROM customer_accounts o WHERE o.customer_id = c.id){$delC}
              ORDER BY c.id DESC LIMIT " . self::LIMIT
        )->getResultArray();
        foreach ($rows as $r) {
            $issues[] = ['key' => 'ORPHAN|' . $r['cid'] . '|' . $r['aid'], 'type' => 'ORPHAN', 'phone' => $r['phone'],
                'ids' => [(int) $r['cid'], (int) $r['aid']], 'note' => 'Record lama tanpa akun; akun dengan nomor ini ada di record lain.'];
        }

        // 3) Akun dengan status diblokir / perlu review
        $rows = $this->conn->query(
            "SELECT ca.customer_id AS cid, ca.login_phone AS phone, ca.status
               FROM customer_accounts ca
              WHERE ca.status LIKE '%BLOCK%' OR ca.status LIKE '%CLAIM%' OR ca.status LIKE '%REVIEW%' OR ca.status LIKE '%HOLD%'
              ORDER BY ca.customer_id DESC LIMIT " . self::LIMIT
        )->getResultArray();
        foreach ($rows as $r) {
            $issues[] = ['key' => 'CLAIM|' . $r['cid'], 'type' => 'CLAIM', 'phone' => $r['phone'],
                'ids' => [(int) $r['cid']], 'note' => 'Status akun: ' . $r['status']];
        }

        // Ringkasan orang per kasus
        $all = [];
        foreach ($issues as $i) {
            foreach ($i['ids'] as $id) {
                $all[$id] = true;
            }
        }
        $brief = $this->peopleBrief(array_keys($all));
        $see   = $this->canSeePhone();
        foreach ($issues as &$i) {
            $i['people'] = array_map(static fn ($id) => $brief[$id] ?? ['id' => $id, 'name' => '#' . $id, 'bookings' => 0, 'acc_status' => null], $i['ids']);
            $i['phone_view'] = $see ? $i['phone'] : $this->mask($i['phone'], 5, 3);
            $i['type_label'] = self::TYPES[$i['type']];
        }
        unset($i);

        return $issues;
    }

    private function peopleBrief(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $rows = $this->conn->table('customers c')
            ->select('c.id, c.name, (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id) AS bookings, '
                . '(SELECT a.status FROM customer_accounts a WHERE a.customer_id = c.id LIMIT 1) AS acc_status', false)
            ->whereIn('c.id', $ids)->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['id']] = ['id' => (int) $r['id'], 'name' => $r['name'], 'bookings' => (int) $r['bookings'], 'acc_status' => $r['acc_status']];
        }
        return $out;
    }

    private function latestDecisions(): array
    {
        $rows = $this->conn->query(
            'SELECT r.* FROM customer_resolutions r
               JOIN (SELECT issue_key, MAX(id) AS mid FROM customer_resolutions GROUP BY issue_key) m ON m.mid = r.id'
        )->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[$r['issue_key']] = $r;
        }
        return $out;
    }

    // ===================================================================
    // Detail kasus
    // ===================================================================

    private function parseKey(string $key): ?array
    {
        if (preg_match('/^DUP\|(\+?\d{6,20})$/', $key, $m)) {
            return ['type' => 'DUP', 'phone' => $m[1]];
        }
        if (preg_match('/^ORPHAN\|(\d+)\|(\d+)$/', $key, $m)) {
            return ['type' => 'ORPHAN', 'a' => (int) $m[1], 'b' => (int) $m[2]];
        }
        if (preg_match('/^CLAIM\|(\d+)$/', $key, $m)) {
            return ['type' => 'CLAIM', 'a' => (int) $m[1]];
        }
        return null;
    }

    private function candidateIds(array $c): array
    {
        $delP = in_array('deleted_at', $this->cf, true) ? ' AND deleted_at IS NULL' : '';

        if ($c['type'] === 'DUP') {
            $rows = $this->conn->query("SELECT id FROM customers WHERE phone = ?{$delP} ORDER BY id LIMIT 10", [$c['phone']])->getResultArray();
            return array_map('intval', array_column($rows, 'id'));
        }

        if ($c['type'] === 'ORPHAN') {
            return [$c['a'], $c['b']];
        }

        // CLAIM: record itu + record lain yang memakai nomor yang sama
        $ids  = [$c['a']];
        $acc  = $this->conn->table('customer_accounts')->where('customer_id', $c['a'])->get()->getRowArray();
        $cust = $this->conn->table('customers')->select('phone')->where('id', $c['a'])->get()->getRowArray();
        $phones = array_values(array_unique(array_filter([$acc['login_phone'] ?? null, $cust['phone'] ?? null])));
        if ($phones) {
            $q = $this->conn->table('customers')->select('id')->whereIn('phone', $phones);
            if (in_array('deleted_at', $this->cf, true)) {
                $q->where('deleted_at', null);
            }
            foreach ($q->limit(10)->get()->getResultArray() as $r) {
                $ids[] = (int) $r['id'];
            }
        }
        return array_values(array_unique($ids));
    }

    private function loadPeople(array $ids): array
    {
        $see = $this->canSeePhone();
        $out = [];

        foreach ($ids as $id) {
            $q = $this->conn->table('customers')->where('id', (int) $id);
            if (in_array('deleted_at', $this->cf, true)) {
                $q->where('deleted_at', null);
            }
            $c = $q->get()->getRowArray();
            if (!$c) {
                continue;
            }

            $acc = $this->conn->table('customer_accounts')->where('customer_id', (int) $id)->get()->getRowArray();
            if ($acc) {
                $login = $this->firstExisting(['last_login_at', 'last_login'], $this->af);
                $acc['_last_login'] = $login ? ($acc[$login] ?? null) : null;
                $acc['_phone_view'] = $see ? (string) $acc['login_phone'] : $this->mask($acc['login_phone'], 5, 3);
            }

            $stat = $this->conn->query(
                "SELECT COUNT(*) AS n,
                        COALESCE(SUM(CASE WHEN status NOT IN ('CANCELLED','EXPIRED') THEN grand_total END), 0) AS total
                   FROM bookings WHERE customer_id = ?",
                [(int) $id]
            )->getRowArray();

            $bks = $this->conn->table('bookings')
                ->select('id, invoice_no, created_at, status, grand_total')
                ->where('customer_id', (int) $id)->orderBy('id', 'DESC')->limit(8)->get()->getResultArray();
            foreach ($bks as &$b) {
                $b['url'] = site_url('backoffice/bookings/' . id_encode((int) $b['id']));
            }
            unset($b);

            $docs = $this->conn->tableExists('customer_documents')
                ? (int) $this->conn->table('customer_documents')->where('customer_id', (int) $id)->countAllResults()
                : 0;

            $code = 'CUS-' . str_pad((string) $c['id'], 5, '0', STR_PAD_LEFT);
            foreach (['customer_code', 'code'] as $k) {
                if (!empty($c[$k])) {
                    $code = (string) $c[$k];
                    break;
                }
            }

            $out[] = [
                'id'          => (int) $c['id'],
                'enc'         => id_encode((int) $c['id']),
                'code'        => $code,
                'name'        => $c['name'],
                'phone_view'  => $see ? (string) $c['phone'] : $this->mask($c['phone'], 5, 3),
                'email'       => $c['email'] ?? '',
                'created_at'  => $c['created_at'] ?? null,
                'acc'         => $acc,
                'has_account' => (bool) $acc,
                'doc_count'   => $docs,
                'bk_count'    => (int) $stat['n'],
                'bk_total'    => (float) $stat['total'],
                'bookings'    => $bks,
            ];
        }

        return $out;
    }

    // ===================================================================
    // Helper
    // ===================================================================

    private function boot(): void
    {
        helper('backoffice');

        $this->conn = db_connect();
        $this->cf   = $this->conn->getFieldNames('customers');
        $this->af   = $this->conn->tableExists('customer_accounts') ? $this->conn->getFieldNames('customer_accounts') : [];
    }

    private function queueUrl(): string
    {
        return site_url('backoffice/customers/account-resolution');
    }

    private function canSeePhone(): bool
    {
        return admin_can('customer.manage') || admin_can('customer.view_phone') || admin_can('booking.manage');
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

    private function appendNote(int $id, string $line): void
    {
        $col = $this->firstExisting(['internal_notes', 'notes'], $this->cf);
        if (!$col) {
            return;
        }
        $cur = (string) $this->conn->table('customers')->select($col)->where('id', $id)->get()->getRow($col);
        $this->conn->table('customers')->where('id', $id)->update([$col => trim($cur . "\n" . $line)]);
    }

    private function audit(int $customerId, string $action, string $desc): void
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