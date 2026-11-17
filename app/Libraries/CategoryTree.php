<?php

namespace App\Libraries;

/**
 * Helper pohon kategori (categories.parent_id).
 */
class CategoryTree
{
    /**
     * Urutan depth-first. Tiap baris ditambah 'depth' dan 'path' ("Kendaraan › Mobil").
     * Aman terhadap data loop / induk yatim.
     */
    public static function flatten(array $rows): array
    {
        $byId     = [];
        $byParent = [];

        foreach ($rows as $r) {
            $byId[(int) $r['id']] = $r;
        }
        foreach ($rows as $r) {
            $pid = (int) ($r['parent_id'] ?? 0);
            if ($pid && !isset($byId[$pid])) {
                $pid = 0;
            }
            $byParent[$pid][] = $r;
        }
        foreach ($byParent as &$list) {
            usort($list, static fn ($a, $b) => [(int) $a['sort_order'], $a['name'], (int) $a['id']]
                <=> [(int) $b['sort_order'], $b['name'], (int) $b['id']]);
        }
        unset($list);

        $out  = [];
        $seen = [];
        $walk = static function (int $pid, int $depth, string $path) use (&$walk, &$out, &$seen, $byParent) {
            foreach ($byParent[$pid] ?? [] as $r) {
                $id = (int) $r['id'];
                if (isset($seen[$id])) {
                    continue;
                }
                $seen[$id]  = true;
                $p          = $path === '' ? $r['name'] : $path . ' › ' . $r['name'];
                $r['depth'] = $depth;
                $r['path']  = $p;
                $out[]      = $r;
                $walk($id, $depth + 1, $p);
            }
        };
        $walk(0, 0, '');

        // Sisa yang tidak terjangkau (data loop lama) tampil sebagai root
        foreach ($byId as $id => $r) {
            if (!isset($seen[$id])) {
                $r['depth'] = 0;
                $r['path']  = $r['name'];
                $out[]      = $r;
            }
        }

        return $out;
    }

    /** Semua id turunan (anak, cucu, ...) dari $id. */
    public static function descendantIds(array $rows, int $id): array
    {
        $children = [];
        foreach ($rows as $r) {
            $p = (int) ($r['parent_id'] ?? 0);
            if ($p) {
                $children[$p][] = (int) $r['id'];
            }
        }

        $out   = [];
        $seen  = [$id => true];
        $stack = [$id];
        while ($stack) {
            $cur = array_pop($stack);
            foreach ($children[$cur] ?? [] as $c) {
                if (isset($seen[$c])) {
                    continue;
                }
                $seen[$c] = true;
                $out[]    = $c;
                $stack[]  = $c;
            }
        }

        return $out;
    }
}