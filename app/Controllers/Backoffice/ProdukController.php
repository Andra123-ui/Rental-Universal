<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

class ProdukController extends BaseController
{
    protected $helpers = ['idhash'];

    // Nilai dari data yang sudah ada. Kalau ada nilai lain di sistemmu, tambahkan di sini.
    private const ITEM_TYPES    = ['ITEM' => 'Barang', 'SERVICE' => 'Jasa', 'PACKAGE' => 'Paket'];
    private const RENTAL_MODES  = [
        'SERIALIZED' => 'Per unit (tiap unit punya identitas, mis. mobil)',
        'QUANTITY'   => 'Per jumlah (stok, mis. kursi)',
        'SCHEDULE'   => 'Per jadwal (mis. villa, jasa)',
    ];
    private const PRICING_UNITS = ['DAY' => 'Per hari', 'NIGHT' => 'Per malam', 'SESSION' => 'Per sesi'];

    private function businessId(): int
    {
        return (int) (site_business()['id'] ?? 1);
    }

    public function index()
    {
        $db      = db_connect();
        $q       = trim((string) $this->request->getGet('q'));
        $status  = (string) $this->request->getGet('status');
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 15;

        $base = $db->table('catalog_items i')
            ->join('categories c', 'c.id = i.category_id', 'left')
            ->join('jenis j', 'j.id = i.jenis_id', 'left')
            ->where('i.business_id', $this->businessId())
            ->where('i.deleted_at', null);

        if ($q !== '') {
            $base->groupStart()->like('i.name', $q)->orLike('i.sku', $q)->groupEnd();
        }
        if (in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $base->where('i.status', $status);
        }

        $total = (clone $base)->countAllResults();

        $rows = $base
            ->select('i.id, i.sku, i.name, i.item_type, i.base_price, i.unit_label, i.status,
                      c.name AS category_name, j.name AS jenis_name,
                      (SELECT COUNT(*) FROM resources r WHERE r.catalog_item_id = i.id AND r.deleted_at IS NULL) AS resource_count', false)
            ->orderBy('i.created_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()->getResultArray();

        return view('backoffice/produk/index', [
            'title'   => 'Produk',
            'nav'     => 'produk',
            'rows'    => $rows,
            'q'       => $q,
            'status'  => $status,
            'page'    => $page,
            'perPage' => $perPage,
            'total'   => $total,
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

    private function form(?array $item)
    {
        $db = db_connect();

        return view('backoffice/produk/form', [
            'title'       => $item ? 'Ubah Produk' : 'Tambah Produk',
            'nav'         => 'produk',
            'item'        => $item,
            'categories'  => $db->table('categories')->where('is_active', 1)->orderBy('sort_order')->get()->getResultArray(),
            'jenisList'   => $db->table('jenis')->where('business_id', $this->businessId())->orderBy('sort_order')->orderBy('name')->get()->getResultArray(),
            'itemTypes'   => self::ITEM_TYPES,
            'rentalModes' => self::RENTAL_MODES,
            'pricingUnits' => self::PRICING_UNITS,
        ]);
    }

    private function save(?array $item)
    {
        $back = redirect()->back()->withInput();

        if (!$this->validate([
            'name'            => 'required|min_length[3]|max_length[180]',
            'sku'             => 'permit_empty|max_length[60]',
            'category_id'     => 'required|is_natural_no_zero',
            'jenis_id'        => 'permit_empty|is_natural_no_zero',
            'item_type'       => 'required|in_list[' . implode(',', array_keys(self::ITEM_TYPES)) . ']',
            'rental_mode'     => 'required|in_list[' . implode(',', array_keys(self::RENTAL_MODES)) . ']',
            'unit_label'      => 'required|max_length[30]',
            'description'     => 'permit_empty|max_length[5000]',
            'base_price'      => 'required|numeric|greater_than_equal_to[0]',
            'pricing_unit'    => 'required|in_list[' . implode(',', array_keys(self::PRICING_UNITS)) . ']',
            'deposit_required' => 'required|in_list[0,1]',
            'deposit_amount'  => 'permit_empty|numeric|greater_than_equal_to[0]',
            'status'          => 'required|in_list[ACTIVE,INACTIVE]',
        ])) {
            return $back->with('errors', $this->validator->getErrors());
        }

        $db         = db_connect();
        $categoryId = (int) $this->request->getPost('category_id');
        $jenisId    = (int) $this->request->getPost('jenis_id') ?: null;
        $sku        = trim((string) $this->request->getPost('sku')) ?: null;

        if (!$db->table('categories')->where('id', $categoryId)->where('is_active', 1)->countAllResults()) {
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
            $dupe = $db->table('catalog_items')->where('sku', $sku)
                ->where('id !=', $item['id'] ?? 0)->countAllResults();
            if ($dupe) {
                return $back->with('errors', ["SKU '{$sku}' sudah dipakai produk lain."]);
            }
        }

        $depositRequired = (int) $this->request->getPost('deposit_required');

        $data = [
            'category_id'      => $categoryId,
            'jenis_id'         => $jenisId,
            'sku'              => $sku,
            'name'             => trim((string) $this->request->getPost('name')),
            'item_type'        => $this->request->getPost('item_type'),
            'rental_mode'      => $this->request->getPost('rental_mode'),
            'unit_label'       => trim((string) $this->request->getPost('unit_label')),
            'description'      => trim((string) $this->request->getPost('description')) ?: null,
            'base_price'       => (float) $this->request->getPost('base_price'),
            'pricing_unit'     => $this->request->getPost('pricing_unit'),
            'deposit_required' => $depositRequired,
            'deposit_amount'   => $depositRequired ? (float) $this->request->getPost('deposit_amount') : 0,
            'status'           => $this->request->getPost('status'),
        ];

        if ($item) {
            $db->table('catalog_items')->where('id', $item['id'])->update($data);
            return redirect()->to(site_url('backoffice/produk'))->with('success', 'Produk diperbarui.');
        }

        $data['business_id'] = $this->businessId();
        $db->table('catalog_items')->insert($data);

        return redirect()->to(site_url('backoffice/produk'))
            ->with('success', 'Produk ditambahkan. Produk baru belum punya unit/stok, jadi belum bisa dipesan sampai unitnya didaftarkan.');
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