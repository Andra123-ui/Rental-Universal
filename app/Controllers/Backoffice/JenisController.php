<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

class JenisController extends BaseController
{
    protected $helpers = ['idhash', 'url'];

    private function businessId(): int
    {
        return (int) (site_business()['id'] ?? 1);
    }

    public function index()
    {
        $rows = db_connect()->table('jenis j')
            ->select('j.id, j.name, j.slug, j.sort_order, j.is_active, c.name AS category_name,
                      (SELECT COUNT(*) FROM catalog_items i WHERE i.jenis_id = j.id AND i.deleted_at IS NULL) AS item_count', false)
            ->join('categories c', 'c.id = j.category_id')
            ->where('j.business_id', $this->businessId())
            ->orderBy('c.sort_order')->orderBy('j.sort_order')->orderBy('j.name')
            ->get()->getResultArray();

        return view('backoffice/jenis/index', ['title' => 'Jenis', 'nav' => 'jenis', 'rows' => $rows]);
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
        $row = $this->find($hash);

        db_connect()->table('jenis')->where('id', $row['id'])
            ->update(['is_active' => $row['is_active'] ? 0 : 1]);

        return redirect()->to(site_url('backoffice/jenis'))
            ->with('success', $row['is_active'] ? 'Jenis dinonaktifkan.' : 'Jenis diaktifkan.');
    }

    private function form(?array $row)
    {
        $categories = db_connect()->table('categories')
            ->where('is_active', 1)->orderBy('sort_order')->get()->getResultArray();

        $itemCount = 0;
        if ($row) {
            $itemCount = db_connect()->table('catalog_items')
                ->where('jenis_id', $row['id'])->where('deleted_at', null)->countAllResults();
        }

        return view('backoffice/jenis/form', [
            'title'      => $row ? 'Ubah Jenis' : 'Tambah Jenis',
            'nav'        => 'jenis',
            'row'        => $row,
            'categories' => $categories,
            'itemCount'  => $itemCount,
        ]);
    }

    private function save(?array $row)
    {
        $back = redirect()->back()->withInput();

        if (!$this->validate([
            'name'        => 'required|min_length[2]|max_length[120]',
            'category_id' => 'required|is_natural_no_zero',
            'sort_order'  => 'permit_empty|is_natural',
        ])) {
            return $back->with('errors', $this->validator->getErrors());
        }

        $db         = db_connect();
        $name       = trim((string) $this->request->getPost('name'));
        $categoryId = (int) $this->request->getPost('category_id');

        $cat = $db->table('categories')->where('id', $categoryId)->where('is_active', 1)->get()->getRowArray();
        if (!$cat) {
            return $back->with('errors', ['Kategori tidak valid.']);
        }

        $data = [
            'name'       => $name,
            'category_id' => $categoryId,
            'sort_order' => (int) $this->request->getPost('sort_order'),
        ];

        if ($row) {
            // Pindah kategori hanya boleh kalau belum dipakai item,
            // supaya item tidak berakhir di kombinasi kategori/jenis yang tidak cocok
            if ((int) $row['category_id'] !== $categoryId) {
                $used = $db->table('catalog_items')->where('jenis_id', $row['id'])
                    ->where('deleted_at', null)->countAllResults();
                if ($used > 0) {
                    return $back->with('errors', ["Kategori tidak bisa diubah karena jenis ini dipakai {$used} produk."]);
                }
            }

            $db->table('jenis')->where('id', $row['id'])->update($data);
            return redirect()->to(site_url('backoffice/jenis'))->with('success', 'Jenis diperbarui.');
        }

        $data['business_id'] = $this->businessId();
        $data['slug']        = $this->uniqueSlug($name);
        $data['is_active']   = 1;
        $db->table('jenis')->insert($data);

        return redirect()->to(site_url('backoffice/jenis'))->with('success', 'Jenis ditambahkan.');
    }

    /** Slug dibuat sekali saat tambah dan tidak berubah saat ubah nama (URL filter tetap stabil). */
    private function uniqueSlug(string $name): string
    {
        $base = url_title($name, '-', true) ?: 'jenis';
        $slug = $base;
        $i    = 2;

        while (db_connect()->table('jenis')->where('business_id', $this->businessId())->where('slug', $slug)->countAllResults() > 0) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function find(string $hash): array
    {
        $id  = id_decode($hash);
        $row = $id ? db_connect()->table('jenis')->where('id', $id)->where('business_id', $this->businessId())->get()->getRowArray() : null;

        if (!$row) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $row;
    }
}