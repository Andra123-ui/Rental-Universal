<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;
use App\Libraries\CategoryTree;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * CAT-03 — Kategori bertingkat (categories.parent_id).
 * Tidak ada aksi hapus: kategori terpakai di-nonaktifkan, bukan dihapus.
 */
class CategoryController extends BaseController
{
    protected $helpers = ['idhash', 'url'];

    // ===================================================================
    // GET /backoffice/kategori  (tree/table + usage count + reorder)
    // ===================================================================
    public function index()
    {
        $db   = db_connect();
        $rows = $db->table('categories')->orderBy('sort_order')->orderBy('name')->get()->getResultArray();

        $items = [];
        foreach ($db->query('SELECT category_id, COUNT(*) AS n FROM catalog_items WHERE deleted_at IS NULL GROUP BY category_id')->getResultArray() as $r) {
            $items[(int) $r['category_id']] = (int) $r['n'];
        }
        $jenis = [];
        if ($db->tableExists('jenis')) {
            foreach ($db->query('SELECT category_id, COUNT(*) AS n FROM jenis GROUP BY category_id')->getResultArray() as $r) {
                $jenis[(int) $r['category_id']] = (int) $r['n'];
            }
        }

        $names = [];
        $kids  = [];
        foreach ($rows as $r) {
            $names[(int) $r['id']] = $r['name'];
            if (!empty($r['parent_id'])) {
                $kids[(int) $r['parent_id']] = ($kids[(int) $r['parent_id']] ?? 0) + 1;
            }
        }

        $tree = CategoryTree::flatten($rows);

        // Posisi di antara saudara (untuk tombol naik/turun)
        $sibs = [];
        foreach ($tree as $t) {
            $sibs[(int) ($t['parent_id'] ?? 0)][] = (int) $t['id'];
        }

        foreach ($tree as &$t) {
            $id  = (int) $t['id'];
            $pid = (int) ($t['parent_id'] ?? 0);
            $t['enc']         = id_encode($id);
            $t['parent_name'] = $pid ? ($names[$pid] ?? '-') : null;
            $t['item_count']  = $items[$id] ?? 0;
            $t['jenis_count'] = $jenis[$id] ?? 0;
            $t['child_count'] = $kids[$id] ?? 0;
            $list             = $sibs[$pid] ?? [];
            $t['is_first']    = ($list[0] ?? null) === $id;
            $t['is_last']     = (end($list) ?: null) === $id;
        }
        unset($t);

        return view('backoffice/kategori/index', [
            'title' => 'Kategori',
            'nav'   => 'kategori',
            'rows'  => $tree,
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

    // ===================================================================
    // POST status (aktif/nonaktif)
    // ===================================================================
    public function status(string $hash)
    {
        $row = $this->find($hash);
        $db  = db_connect();
        $new = (int) $row['is_active'] ? 0 : 1;

        $db->table('categories')->where('id', $row['id'])->update(['is_active' => $new, 'updated_at' => date('Y-m-d H:i:s')]);

        if ($new === 1) {
            return redirect()->to(site_url('backoffice/kategori'))->with('success', 'Kategori diaktifkan.');
        }

        $items = (int) $db->table('catalog_items')->where('category_id', $row['id'])->where('deleted_at', null)->countAllResults();
        $kids  = (int) $db->table('categories')->where('parent_id', $row['id'])->where('is_active', 1)->countAllResults();

        $msg = 'Kategori dinonaktifkan.';
        if ($items > 0) {
            $msg .= " {$items} produk tetap ada, tetapi kategori ini tidak muncul sebagai pilihan baru.";
        }
        if ($kids > 0) {
            $msg .= " {$kids} subkategori masih aktif; nonaktifkan juga bila perlu.";
        }

        return redirect()->to(site_url('backoffice/kategori'))->with('success', $msg);
    }

    // ===================================================================
    // POST urutan (naik / turun di antara saudara)
    // ===================================================================
    public function move(string $hash)
    {
        $row = $this->find($hash);
        $dir = (string) $this->request->getPost('dir');
        $db  = db_connect();

        $q = $db->table('categories')->select('id')->where('parent_id', $row['parent_id'] ?: null);
        $ids = array_map('intval', array_column($q->orderBy('sort_order')->orderBy('name')->orderBy('id')->get()->getResultArray(), 'id'));

        $pos  = array_search((int) $row['id'], $ids, true);
        $swap = $dir === 'up' ? $pos - 1 : $pos + 1;

        if ($pos !== false && in_array($dir, ['up', 'down'], true) && isset($ids[$swap])) {
            [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
            foreach ($ids as $i => $cid) {
                $db->table('categories')->where('id', $cid)->update(['sort_order' => $i + 1]);
            }
        }

        return redirect()->to(site_url('backoffice/kategori'));
    }

    // ===================================================================
    // Form & simpan
    // ===================================================================
    private function form(?array $row)
    {
        $db  = db_connect();
        $all = $db->table('categories')->orderBy('sort_order')->get()->getResultArray();
        $flat = CategoryTree::flatten($all);

        // Induk tidak boleh diri sendiri / turunannya (mencegah loop)
        $exclude = $row ? array_merge([(int) $row['id']], CategoryTree::descendantIds($all, (int) $row['id'])) : [];
        $parents = array_values(array_filter($flat, static function ($c) use ($exclude, $row) {
            if (in_array((int) $c['id'], $exclude, true)) {
                return false;
            }
            return (int) $c['is_active'] === 1 || ($row && (int) $row['parent_id'] === (int) $c['id']);
        }));

        $itemCount = $childCount = 0;
        if ($row) {
            $itemCount  = (int) $db->table('catalog_items')->where('category_id', $row['id'])->where('deleted_at', null)->countAllResults();
            $childCount = (int) $db->table('categories')->where('parent_id', $row['id'])->countAllResults();
        }

        return view('backoffice/kategori/form', [
            'title'      => $row ? 'Ubah Kategori' : 'Tambah Kategori',
            'nav'        => 'kategori',
            'row'        => $row,
            'parents'    => $parents,
            'itemCount'  => $itemCount,
            'childCount' => $childCount,
        ]);
    }

    private function save(?array $row)
    {
        $back = redirect()->back()->withInput();

        if (!$this->validate([
            'name'        => 'required|min_length[2]|max_length[120]',
            'slug'        => 'permit_empty|max_length[140]|regex_match[/^[a-z0-9]+(?:-[a-z0-9]+)*$/]',
            'description' => 'permit_empty|max_length[2000]',
            'sort_order'  => 'permit_empty|is_natural',
            'parent_id'   => 'permit_empty|is_natural_no_zero',
            'is_active'   => 'permit_empty|in_list[0,1]',
        ])) {
            return $back->with('errors', $this->validator->getErrors());
        }

        $db       = db_connect();
        $all      = $db->table('categories')->get()->getResultArray();
        $name     = trim((string) $this->request->getPost('name'));
        $parentId = (int) $this->request->getPost('parent_id') ?: null;
        $selfId   = $row ? (int) $row['id'] : 0;

        // ---- induk ----
        if ($parentId !== null) {
            $parent = null;
            foreach ($all as $c) {
                if ((int) $c['id'] === $parentId) {
                    $parent = $c;
                    break;
                }
            }
            if (!$parent) {
                return $back->with('errors', ['Kategori induk tidak ditemukan.']);
            }
            if ($row) {
                $bad = array_merge([$selfId], CategoryTree::descendantIds($all, $selfId));
                if (in_array($parentId, $bad, true)) {
                    return $back->with('errors', ['Kategori induk tidak boleh dirinya sendiri atau turunannya (membentuk loop).']);
                }
            }
            if ((int) $parent['is_active'] !== 1 && (!$row || (int) $row['parent_id'] !== $parentId)) {
                return $back->with('errors', ['Kategori induk sedang nonaktif.']);
            }
        }

        // ---- slug ----
        $slugIn = trim((string) $this->request->getPost('slug'));
        if ($slugIn !== '') {
            $dupe = $db->table('categories')->where('slug', $slugIn)->where('id !=', $selfId)->countAllResults();
            if ($dupe) {
                return $back->with('errors', ["Slug '{$slugIn}' sudah dipakai kategori lain."]);
            }
            $slug = $slugIn;
        } else {
            $slug = $row ? $row['slug'] : $this->uniqueSlug($name);
        }

        // ---- urutan ----
        $sortRaw = $this->request->getPost('sort_order');
        if ($sortRaw === null || $sortRaw === '') {
            $siblings = $db->table('categories')->selectMax('sort_order', 'm')->where('parent_id', $parentId)->get()->getRow('m');
            $sort     = $row ? (int) $row['sort_order'] : ((int) $siblings + 1);
        } else {
            $sort = (int) $sortRaw;
        }

        $now  = date('Y-m-d H:i:s');
        $data = [
            'parent_id'   => $parentId,
            'name'        => $name,
            'slug'        => $slug,
            'description' => trim((string) $this->request->getPost('description')) ?: null,
            'sort_order'  => $sort,
            'is_active'   => $this->request->getPost('is_active') === null ? ($row ? (int) $row['is_active'] : 1) : (int) $this->request->getPost('is_active'),
            'updated_at'  => $now,
        ];

        if ($row) {
            $db->table('categories')->where('id', $row['id'])->update($data);

            $msg = 'Kategori diperbarui.';
            if ($slug !== $row['slug']) {
                $msg .= ' Slug berubah, link katalog lama yang memakai slug sebelumnya tidak berlaku lagi.';
            }
            return redirect()->to(site_url('backoffice/kategori'))->with('success', $msg);
        }

        $data['created_at'] = $now;
        $db->table('categories')->insert($data);

        return redirect()->to(site_url('backoffice/kategori'))->with('success', 'Kategori ditambahkan.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = url_title($name, '-', true) ?: 'kategori';
        $slug = $base;
        $i    = 2;

        while (db_connect()->table('categories')->where('slug', $slug)->countAllResults() > 0) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function find(string $hash): array
    {
        $id  = id_decode($hash);
        $row = $id ? db_connect()->table('categories')->where('id', $id)->get()->getRowArray() : null;

        if (!$row) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $row;
    }
}