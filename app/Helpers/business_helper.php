<?php

if (!function_exists('business_name')) {
    function business_name(?int $businessId = null): string
    {
        static $cache = [];
        $businessId = $businessId ?: 1;

        if (!isset($cache[$businessId])) {
            $row = db_connect()->table('business_profiles')
                ->select('business_name')
                ->where('id', $businessId)
                ->get()->getRowArray();
            $cache[$businessId] = (string) ($row['business_name'] ?? '');
        }

        return $cache[$businessId];
    }
}

if (!function_exists('branch_name')) {
    function branch_name(?int $branchId): string
    {
        if (!$branchId) {
            return '';
        }

        static $cache = [];
        if (!isset($cache[$branchId])) {
            $row = db_connect()->table('branches')
                ->select('branch_name')
                ->where('id', $branchId)
                ->get()->getRowArray();
            $cache[$branchId] = (string) ($row['branch_name'] ?? '');
        }

        return $cache[$branchId];
    }
}