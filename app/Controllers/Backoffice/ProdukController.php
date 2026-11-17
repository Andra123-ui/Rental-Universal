<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;
use App\Libraries\CategoryTree;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * CAT-01 Daftar Katalog, CAT-02 Tambah/Edit Item Katalog.
 * Item & resource yang pernah dipakai transaksi tidak pernah di-hard-delete.
 */
class ProdukController extends BaseController
{
    protected $helpers = ['idhash'];

    private const ITEM_TYPES = ['ITEM' => 'Barang', 'SERVICE' => 'Jasa', 'PACKAGE' => 'Paket'];

    private const RENTAL_MODES = [
        'SERIALIZED' => 'Per unit (tiap unit punya identitas, mis. mobil)',
        'QUANTITY'   => 'Per jumlah (stok, mis. kursi)',
        'CAPACITY'   => 'Per kapasitas (kuota, mis. peserta / studio)',
        'SCHEDULE'   => 'Per jadwal (mis. villa, jasa)',
        'UNLIMITED'  => 'Tanpa batas stok (mis. layanan online)',
    ];

    private const PRICING_UNITS = ['DAY' => 'Per hari', 'NIGHT' => 'Per malam', 'SESSION' => 'Per sesi'];

    private const PER_PAGE         = 15;
    private const MEDIA_EXT        = ['jpg', 'jpeg', 'png', 'webp'];
    private const MEDIA_MIME       = ['image/jpeg', 'image/png', 'image/webp'];
    private const MEDIA_MAX_KB     = 3072;
    private const MEDIA_MAX_ITEM   = 12;
    private const MEDIA_DIR        = 'uploads/items';
    private const MAX_UNITS_BRANCH = 500;

    private function businessId(): int
    {
        return (int) (site_business()['id'] ?? 1);
    }

    // ===================================================================
    // CAT-01: Daftar
    // ===================================================================
    public function index()
    {
        $db = db_connect();

        $type = (string) $this->request->getGet('type');
        $f = [
            'q'        => trim((string) $this->request->getGet('q')),
            'status'   => in_array((string) $this->request->getGet('status'), ['ACTIVE', 'INACTIVE'], true) ? (string) $this->request->getGet('status') : '',
            'category' => (int) $this->request->getGet('category'),
            'type'     => isset(self::ITEM_TYPES[$type]) ? $type : '',
            'online'   => in_array((string) $this->request->getGet('online'), ['1', '0'], true) ? (string) $this->request->getGet('online') : '',
        ];
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));

        $allCats = $db->table('categories')->orderBy('sort_order')->get()->getResultArray();

        $base = $db->table('catalog_items i')
            ->join('categories c', 'c.id = i.category_id', 'left')
            ->join('jenis j', 'j.id = i.jenis_id', 'left')
            ->where('i.business_id', $this->businessId())
            ->where('i.deleted_at', null);

        if ($f['q'] !== '') {
            $base->groupStart()->like('i.name', $f['q'])->orLike('i.sku', $f['q'])->groupEnd();
        }
        if ($f['status'] !== '') {
            $base->where('i.status', $f['status']);
        }
        if ($f['type'] !== '') {
            $base->where('i.item_type', $f['type']);
        }
        if ($f['online'] !== '') {
            $base->where('i.allow_online_booking', (int) $f['online']);
        }
        if ($f['category'] > 0) {
            // Kategori + semua turunannya
            $ids = array_merge([$f['category']], CategoryTree::descendantIds($allCats, $f['category']));
            $base->whereIn('i.category_id', $ids);
        }

        $total = (clone $base)->countAllResults();

        $rows = $base
            ->select('i.id, i.sku, i.name, i.item_type, i.rental_mode, i.pricing_unit, i.base_price, i.unit_label,
                      i.allow_online_booking, i.status,
                      c.name AS category_name, j.name AS jenis_name,
                      (SELECT m.file_path FROM item_media m WHERE m.catalog_item_id = i.id AND m.media_type = "IMAGE"
                        ORDER BY m.is_primary DESC, m.sort_order ASC, m.id ASC LIMIT 1) AS thumb,
                      (SELECT COUNT(*) FROM resources r WHERE r.catalog_item_id = i.id AND r.deleted_at IS NULL) AS resource_count,
                      (SELECT GROUP_CONCAT(DISTINCT b.branch_name SEPARATOR ", ") FROM resources r2 JOIN branches b ON b.id = r2.branch_id WHERE r2.catalog_item_id = i.id AND r2.deleted_at IS NULL) AS branch_names', false)
            ->orderBy('i.created_at', 'DESC')
            ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)
            ->get()->getResultArray();

        return view('backoffice/produk/index', [
            'title'           => 'Produk',
            'nav'             => 'produk',
            'rows'            => $rows,
            'f'               => $f,
            'page'            => $page,
            'perPage'         => self::PER_PAGE,
            'total'           => $total,
            'categoryOptions' => CategoryTree::flatten($allCats),
            'itemTypes'       => self::ITEM_TYPES,
        ]);
    }

    public function create()
    {
        return $this->form(null);
    }

    public function edit(string $hash)
    {
        return $this->form($this->find($hash));
    }

    public function store()
    {
        return $this->save(null);
    }

    public function update(string $hash)
    {
        return $this->save($this->find($hash));
    }

    public function status(string $hash)
    {
        $item = $this->find($hash);
        $new  = $item['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        db_connect()->table('catalog_items')->where('id', $item['id'])->update(['status' => $new]);

        return redirect()->back()->with(
            'success',
            $new === 'ACTIVE' ? 'Produk diaktifkan dan tampil di katalog.' : 'Produk dinonaktifkan dan disembunyikan dari katalog.'
        );
    }

    // ===================================================================
    // Duplicate: salin item + komponen paket + aturan harga (TANPA media & resource)
    // ===================================================================
    public function duplicate(string $hash)
    {
        $src = $this->find($hash);
        $db  = db_connect();
        $now = date('Y-m-d H:i:s');

        $db->transBegin();
        try {
            $copy = $src;
            unset($copy['id'], $copy['created_at'], $copy['updated_at']);
            $copy['name']       = mb_substr($src['name'], 0, 165) . ' (Salinan)';
            $copy['sku']        = null;            // SKU harus unik
            $copy['status']     = 'INACTIVE';      // tidak langsung tampil di katalog
            $copy['deleted_at'] = null;
            $db->table('catalog_items')->insert($copy);
            $newId = (int) $db->insertID();

            foreach (['package_components' => 'package_item_id', 'pricing_rules' => 'catalog_item_id'] as $tbl => $fk) {
                if (!$db->tableExists($tbl)) {
                    continue;
                }
                foreach ($db->table($tbl)->where($fk, (int) $src['id'])->get()->getResultArray() as $r) {
                    unset($r['id']);
                    $r[$fk] = $newId;
                    if (array_key_exists('created_at', $r)) {
                        $r['created_at'] = $now;
                    }
                    if (array_key_exists('updated_at', $r)) {
                        $r['updated_at'] = $now;
                    }
                    $db->table($tbl)->insert($r);
                }
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Duplikat produk gagal: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menduplikasi produk.');
        }

        return redirect()->to(site_url('backoffice/produk/ubah/' . id_encode($newId)) . '#cabang-unit')
            ->with('success', 'Salinan dibuat (nonaktif). Atur cabang & unit, tambah media, lalu aktifkan.');
    }

    // ===================================================================
    // Media: upload / primary / up / down / delete / alt
    // ===================================================================
    public function media(string $hash)
    {
        $item = $this->find($hash);
        $db   = db_connect();
        $id   = (int) $item['id'];
        $back = redirect()->to(site_url('backoffice/produk/ubah/' . $hash) . '#media');

        $action  = (string) $this->request->getPost('action');
        $mediaId = (int) $this->request->getPost('media_id');
        $now     = date('Y-m-d H:i:s');

        if ($action === 'upload') {
            $files = $this->request->getFileMultiple('images') ?: [];
            if (!$files) {
                return $back->with('error', 'Pilih minimal satu gambar.');
            }

            $count      = (int) $db->table('item_media')->where('catalog_item_id', $id)->countAllResults();
            $maxSort    = (int) ($db->table('item_media')->selectMax('sort_order', 'm')->where('catalog_item_id', $id)->get()->getRow('m') ?? 0);
            $hasPrimary = $db->table('item_media')->where('catalog_item_id', $id)->where('is_primary', 1)->countAllResults() > 0;

            $added = 0;
            $skipped = [];
            foreach ($files as $file) {
                if (!$file->isValid() || $file->hasMoved()) {
                    continue; // input kosong
                }
                if ($count + $added >= self::MEDIA_MAX_ITEM) {
                    $skipped[] = 'batas ' . self::MEDIA_MAX_ITEM . ' gambar per produk tercapai';
                    break;
                }
                if ($file->getSizeByUnit('kb') > self::MEDIA_MAX_KB
                    || !in_array(strtolower($file->getExtension()), self::MEDIA_EXT, true)
                    || !in_array($file->getMimeType(), self::MEDIA_MIME, true)) {
                    $skipped[] = $file->getClientName() . ' (harus JPG/PNG/WEBP, maks 3 MB)';
                    continue;
                }

                $name = $file->getRandomName();
                $file->move(FCPATH . self::MEDIA_DIR, $name);

                $db->table('item_media')->insert([
                    'catalog_item_id' => $id,
                    'media_type'      => 'IMAGE',
                    'file_path'       => self::MEDIA_DIR . '/' . $name,
                    'is_primary'      => $hasPrimary ? 0 : 1,
                    'sort_order'      => ++$maxSort,
                    'alt_text'        => mb_substr($item['name'], 0, 255),
                    'created_at'      => $now,
                ]);
                $hasPrimary = true;
                $added++;
            }

            if ($added === 0) {
                return $back->with('error', 'Tidak ada gambar yang diunggah' . ($skipped ? ': ' . implode('; ', $skipped) : '.'));
            }
            $msg = $added . ' gambar diunggah.' . ($skipped ? ' Dilewati: ' . implode('; ', $skipped) . '.' : '');
            return $back->with('success', $msg);
        }

        // Aksi lain butuh media milik item ini
        $m = $db->table('item_media')->where('id', $mediaId)->where('catalog_item_id', $id)->get()->getRowArray();
        if (!$m) {
            return $back->with('error', 'Media tidak ditemukan.');
        }

        switch ($action) {
            case 'primary':
                $db->table('item_media')->where('catalog_item_id', $id)->update(['is_primary' => 0]);
                $db->table('item_media')->where('id', $mediaId)->update(['is_primary' => 1]);
                return $back->with('success', 'Foto utama diganti.');

            case 'up':
            case 'down':
                $ids = array_map('intval', array_column(
                    $db->table('item_media')->select('id')->where('catalog_item_id', $id)->orderBy('sort_order')->orderBy('id')->get()->getResultArray(),
                    'id'
                ));
                $pos  = array_search($mediaId, $ids, true);
                $swap = $action === 'up' ? $pos - 1 : $pos + 1;
                if ($pos !== false && isset($ids[$swap])) {
                    [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
                    foreach ($ids as $i => $mid) {
                        $db->table('item_media')->where('id', $mid)->update(['sort_order' => $i + 1]);
                    }
                }
                return $back->with('success', 'Urutan media diperbarui.');

            case 'alt':
                $db->table('item_media')->where('id', $mediaId)
                    ->update(['alt_text' => mb_substr(trim((string) $this->request->getPost('alt_text')), 0, 255)]);
                return $back->with('success', 'Teks alternatif disimpan.');

            case 'delete':
                $db->table('item_media')->where('id', $mediaId)->delete();

                // File fisik dihapus hanya kalau tidak dipakai baris lain
                $path = (string) $m['file_path'];
                $used = $db->table('item_media')->where('file_path', $path)->countAllResults();
                if ($used === 0 && strpos($path, self::MEDIA_DIR . '/') === 0 && strpos($path, '..') === false) {
                    @unlink(FCPATH . $path);
                }

                // Pastikan selalu ada foto utama
                $hasPrimary = $db->table('item_media')->where('catalog_item_id', $id)->where('is_primary', 1)->countAllResults();
                if (!$hasPrimary) {
                    $first = $db->table('item_media')->select('id')->where('catalog_item_id', $id)->orderBy('sort_order')->orderBy('id')->get(1)->getRowArray();
                    if ($first) {
                        $db->table('item_media')->where('id', $first['id'])->update(['is_primary' => 1]);
                    }
                }
                return $back->with('success', 'Media dihapus.');
        }

        return $back->with('error', 'Aksi media tidak valid.');
    }

    // ===================================================================
    // Form
    // ===================================================================
    private function form(?array $item)
    {
        $db = db_connect();

        $branches = $db->table('branches')->where('is_active', 1)->orderBy('branch_name')->get()->getResultArray();

        $selectedBranches = [];
        $media = [];
        if ($item) {
            $selectedBranches = $db->table('resources')
                ->select('branch_id, COUNT(id) as total_unit')
                ->where('catalog_item_id', $item['id'])
                ->where('deleted_at', null)
                ->groupBy('branch_id')
                ->get()->getResultArray();

            $media = $db->table('item_media')->where('catalog_item_id', $item['id'])
                ->orderBy('sort_order')->orderBy('id')->get()->getResultArray();
        }

        // Kategori aktif (atau kategori item ini sendiri) dalam bentuk pohon
        $cats = CategoryTree::flatten($db->table('categories')->orderBy('sort_order')->get()->getResultArray());
        $cats = array_values(array_filter($cats, static fn ($c) => (int) $c['is_active'] === 1
            || ($item && (int) $item['category_id'] === (int) $c['id'])));

        return view('backoffice/produk/form', [
            'title'            => $item ? 'Ubah Produk' : 'Tambah Produk',
            'nav'              => 'produk',
            'item'             => $item,
            'categories'       => $cats,
            'jenisList'        => $db->table('jenis')->where('business_id', $this->businessId())->orderBy('sort_order')->orderBy('name')->get()->getResultArray(),
            'itemTypes'        => self::ITEM_TYPES,
            'rentalModes'      => self::RENTAL_MODES,
            'pricingUnits'     => self::PRICING_UNITS,
            'branches'         => $branches,
            'selectedBranches' => $selectedBranches,
            'media'            => $media,
            // Isi setelah halamannya dibuat, mis. site_url('backoffice/pricing-rules?item=' . $hash)
            'priceUrl'         => null,
            'packageUrl'       => null,
        ]);
    }

    // ===================================================================
    // Simpan
    // ===================================================================
    private function save(?array $item)
    {
        $back = redirect()->back()->withInput();

        if (!$this->validate([
            'name'                  => 'required|min_length[3]|max_length[180]',
            'sku'                   => 'permit_empty|max_length[60]',
            'category_id'           => 'required|is_natural_no_zero',
            'jenis_id'              => 'permit_empty|is_natural_no_zero',
            'item_type'             => 'required|in_list[' . implode(',', array_keys(self::ITEM_TYPES)) . ']',
            'rental_mode'           => 'required|in_list[' . implode(',', array_keys(self::RENTAL_MODES)) . ']',
            'unit_label'            => 'required|max_length[30]',
            'description'           => 'permit_empty|max_length[5000]',
            'base_price'            => 'required|numeric|greater_than_equal_to[0]',
            'pricing_unit'          => 'required|in_list[' . implode(',', array_keys(self::PRICING_UNITS)) . ']',
            'min_duration'          => 'permit_empty|numeric|greater_than_equal_to[0]',
            'max_duration'          => 'permit_empty|numeric|greater_than_equal_to[0]',
            'buffer_before_minutes' => 'permit_empty|is_natural|less_than_equal_to[1440]',
            'buffer_after_minutes'  => 'permit_empty|is_natural|less_than_equal_to[1440]',
            'default_capacity'      => 'permit_empty|numeric|greater_than[0]',
            'track_stock'           => 'permit_empty|in_list[0,1]',
            'allow_online_booking'  => 'required|in_list[0,1]',
            'deposit_required'      => 'required|in_list[0,1]',
            'deposit_amount'        => 'permit_empty|numeric|greater_than_equal_to[0]',
            'status'                => 'required|in_list[ACTIVE,INACTIVE]',
        ])) {
            return $back->with('errors', $this->validator->getErrors());
        }

        $db         = db_connect();
        $type       = (string) $this->request->getPost('item_type');
        $mode       = (string) $this->request->getPost('rental_mode');
        $unit       = (string) $this->request->getPost('pricing_unit');
        $categoryId = (int) $this->request->getPost('category_id');
        $jenisId    = (int) $this->request->getPost('jenis_id') ?: null;
        $sku        = trim((string) $this->request->getPost('sku')) ?: null;

        $capacity = $this->nullableNumber($this->request->getPost('default_capacity'));
        $minD     = $this->nullableNumber($this->request->getPost('min_duration'));
        $maxD     = $this->nullableNumber($this->request->getPost('max_duration'));

        if ($minD !== null && $maxD !== null && $minD > $maxD) {
            return $back->with('errors', ['Durasi minimum tidak boleh lebih besar dari durasi maksimum.']);
        }
        if ($err = $this->comboError($type, $mode, $unit, $capacity)) {
            return $back->with('errors', [$err]);
        }

        // Kategori: aktif, atau tetap kategori lama item ini
        $cat = $db->table('categories')->where('id', $categoryId)->get()->getRowArray();
        $sameAsBefore = $item && (int) $item['category_id'] === $categoryId;
        if (!$cat || ((int) $cat['is_active'] !== 1 && !$sameAsBefore)) {
            return $back->with('errors', ['Kategori tidak valid.']);
        }

        if ($jenisId !== null) {
            $okJenis = $db->table('jenis')->where('id', $jenisId)
                ->where('business_id', $this->businessId())->where('category_id', $categoryId)->countAllResults();
            if (!$okJenis) {
                return $back->with('errors', ['Jenis tidak sesuai dengan kategori yang dipilih.']);
            }
        }

        if ($sku !== null) {
            $dupe = $db->table('catalog_items')->where('sku', $sku)->where('id !=', $item['id'] ?? 0)->countAllResults();
            if ($dupe) {
                return $back->with('errors', ["SKU '{$sku}' sudah dipakai produk lain."]);
            }
        }

        // Distribusi cabang & unit
        $dist = [];
        $branchesPost = $this->request->getPost('branches') ?? [];
        $unitsPost    = $this->request->getPost('units') ?? [];
        if (is_array($branchesPost)) {
            foreach ($branchesPost as $i => $bid) {
                $bid = (int) $bid;
                $n   = (int) ($unitsPost[$i] ?? 0);
                if ($bid <= 0 || $n <= 0) {
                    continue;
                }
                if (isset($dist[$bid])) {
                    return $back->with('errors', ['Cabang yang sama dipilih lebih dari sekali. Gabungkan jumlah unitnya jadi satu baris.']);
                }
                if ($n > self::MAX_UNITS_BRANCH) {
                    return $back->with('errors', ['Jumlah unit per cabang maksimal ' . self::MAX_UNITS_BRANCH . '.']);
                }
                $dist[$bid] = $n;
            }
        }
        if ($dist) {
            $okBranch = $db->table('branches')->whereIn('id', array_keys($dist))->where('is_active', 1)->countAllResults();
            if ($okBranch !== count($dist)) {
                return $back->with('errors', ['Ada cabang yang tidak valid atau nonaktif.']);
            }
        }

        $depositRequired = (int) $this->request->getPost('deposit_required');

        $data = [
            'category_id'           => $categoryId,
            'jenis_id'              => $jenisId,
            'sku'                   => $sku,
            'name'                  => trim((string) $this->request->getPost('name')),
            'item_type'             => $type,
            'rental_mode'           => $mode,
            'unit_label'            => trim((string) $this->request->getPost('unit_label')),
            'description'           => trim((string) $this->request->getPost('description')) ?: null,
            'base_price'            => (float) $this->request->getPost('base_price'),
            'pricing_unit'          => $unit,
            'min_duration'          => $minD,
            'max_duration'          => $maxD,
            'buffer_before_minutes' => (int) $this->request->getPost('buffer_before_minutes'),
            'buffer_after_minutes'  => (int) $this->request->getPost('buffer_after_minutes'),
            'default_capacity'      => $mode === 'CAPACITY' ? $capacity : null,
            'track_stock'           => $mode === 'UNLIMITED' ? 0 : (int) ($this->request->getPost('track_stock') ?? 1),
            'allow_online_booking'  => (int) $this->request->getPost('allow_online_booking'),
            'deposit_required'      => $depositRequired,
            'deposit_amount'        => $depositRequired ? (float) $this->request->getPost('deposit_amount') : 0,
            'status'                => $this->request->getPost('status'),
        ];

        $db->transBegin();
        try {
            if ($item) {
                $db->table('catalog_items')->where('id', $item['id'])->update($data);
                $productId = (int) $item['id'];
            } else {
                $data['business_id'] = $this->businessId();
                $db->table('catalog_items')->insert($data);
                $productId = (int) $db->insertID();
            }

            // Paket memakai package_components; mode tanpa batas tidak butuh unit
            if ($type !== 'PACKAGE' && $mode !== 'UNLIMITED') {
                $this->syncResources($db, $productId, $data, $dist, $sku ?: ('ITM' . $productId));
            }

            $db->transCommit();
        } catch (\DomainException $e) {
            $db->transRollback();
            return $back->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Simpan produk gagal: ' . $e->getMessage());
            return $back->with('error', 'Gagal menyimpan produk dan unit.');
        }

        $msg = $item ? 'Produk diperbarui.' : 'Produk ditambahkan.';

        if ((string) $this->request->getPost('save_action') === 'resource') {
            return redirect()->to(site_url('backoffice/produk/ubah/' . id_encode($productId)) . '#cabang-unit')
                ->with('success', $msg . ' Atur cabang & unit di bawah.');
        }
        if (!$item) {
            // Produk baru: lanjut ke edit supaya bisa unggah media
            return redirect()->to(site_url('backoffice/produk/ubah/' . id_encode($productId)) . '#media')
                ->with('success', $msg . ' Tambahkan foto produk di bawah.');
        }

        return redirect()->to(site_url('backoffice/produk'))->with('success', $msg);
    }

    /**
     * Samakan jumlah unit aktif per cabang dengan permintaan form.
     * Tambah jika kurang; kurangi dengan soft delete (tidak pernah hard delete),
     * dan tolak jika unit masih dipakai booking aktif.
     */
    private function syncResources($db, int $itemId, array $data, array $dist, string $prefix): void
    {
        $now = date('Y-m-d H:i:s');

        $existing = $db->table('resources')->where('catalog_item_id', $itemId)->where('deleted_at', null)
            ->orderBy('id')->get()->getResultArray();
        $byBranch = [];
        foreach ($existing as $r) {
            $byBranch[(int) $r['branch_id']][] = $r;
        }

        $capacity = $data['rental_mode'] === 'CAPACITY' ? (float) $data['default_capacity'] : 1;
        $resType  = $data['item_type'] === 'SERVICE' ? 'PERSON' : 'EQUIPMENT';

        foreach (array_unique(array_merge(array_keys($dist), array_keys($byBranch))) as $bid) {
            if ($bid === 0) {
                continue; // resource tanpa cabang tidak disentuh
            }

            $want = $dist[$bid] ?? 0;
            $have = $byBranch[$bid] ?? [];
            $diff = $want - count($have);

            if ($diff > 0) {
                $n = 1;
                for ($i = 0; $i < $diff; $i++) {
                    do {
                        $code = $prefix . '-' . $bid . '-' . $n++;
                    } while ($db->table('resources')->where('resource_code', $code)->countAllResults() > 0);

                    $db->table('resources')->insert([
                        'catalog_item_id' => $itemId,
                        'branch_id'       => $bid,
                        'resource_code'   => $code,
                        'resource_name'   => $data['name'] . ' - ' . $code,
                        'resource_type'   => $resType,
                        'capacity'        => $capacity,
                        'status'          => 'AVAILABLE',
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
                }
            } elseif ($diff < 0) {
                $need  = -$diff;
                $flags = [];
                foreach ($have as $r) {
                    $flags[(int) $r['id']] = $this->allocCount($db, (int) $r['id']) > 0;
                }
                // Utamakan unit tanpa riwayat, lalu yang terbaru
                usort($have, static fn ($a, $b) => [$flags[(int) $a['id']], -(int) $a['id']] <=> [$flags[(int) $b['id']], -(int) $b['id']]);

                $removed = 0;
                foreach ($have as $r) {
                    if ($removed >= $need) {
                        break;
                    }
                    if ($this->inUse($db, (int) $r['id'])) {
                        continue;
                    }
                    $db->table('resources')->where('id', $r['id'])
                        ->update(['deleted_at' => $now, 'status' => 'INACTIVE', 'updated_at' => $now]);
                    $removed++;
                }

                if ($removed < $need) {
                    $bn = $db->table('branches')->where('id', $bid)->get()->getRow('branch_name') ?: ('#' . $bid);
                    throw new \DomainException("Jumlah unit di cabang {$bn} tidak bisa dikurangi sebanyak itu: sisanya masih dipakai booking aktif. "
                        . 'Selesaikan atau batalkan booking tersebut dulu.');
                }
            }
        }

        // Mode kapasitas: semua unit aktif ikut kapasitas default terbaru
        if ($data['rental_mode'] === 'CAPACITY') {
            $db->table('resources')->where('catalog_item_id', $itemId)->where('deleted_at', null)
                ->update(['capacity' => $capacity, 'updated_at' => $now]);
        }
    }

    private function inUse($db, int $resourceId): bool
    {
        return $db->table('booking_resource_allocations')
            ->where('resource_id', $resourceId)
            ->whereNotIn('allocation_status', ['CANCELLED', 'RELEASED', 'RETURNED'])
            ->where('end_at >', date('Y-m-d H:i:s'))
            ->countAllResults() > 0;
    }

    private function allocCount($db, int $resourceId): int
    {
        return (int) $db->table('booking_resource_allocations')->where('resource_id', $resourceId)->countAllResults();
    }

    /** Validasi kombinasi item_type / rental_mode / pricing_unit. */
    private function comboError(string $type, string $mode, string $unit, ?float $capacity): ?string
    {
        if ($type === 'PACKAGE' && !in_array($mode, ['SCHEDULE', 'UNLIMITED'], true)) {
            return 'Paket harus memakai mode "Per jadwal" atau "Tanpa batas stok"; isi paket diatur lewat komponen paket.';
        }
        if ($mode === 'QUANTITY' && $type !== 'ITEM') {
            return 'Mode "Per jumlah" hanya untuk Barang.';
        }
        if ($unit === 'NIGHT' && $mode === 'QUANTITY') {
            return 'Satuan "Per malam" tidak cocok dengan mode "Per jumlah". Pakai Per unit, Per jadwal, atau Per kapasitas.';
        }
        if ($mode === 'CAPACITY' && ($capacity === null || $capacity < 1)) {
            return 'Mode "Per kapasitas" wajib mengisi kapasitas default minimal 1.';
        }
        return null;
    }

    private function nullableNumber($v): ?float
    {
        if ($v === null || $v === '' || !is_numeric($v)) {
            return null;
        }
        return (float) $v;
    }

    private function find(string $hash): array
    {
        $id   = id_decode($hash);
        $item = $id ? db_connect()->table('catalog_items')
            ->where('id', $id)->where('business_id', $this->businessId())->where('deleted_at', null)
            ->get()->getRowArray() : null;

        if (!$item) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $item;
    }
}