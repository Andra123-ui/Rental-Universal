<?php

if (! function_exists('site_business')) {
    function site_business()
    {
        static $business = null;
        if ($business === null) {
            $model    = new \App\Models\BusinessProfileModel();
            $business = $model->where('is_active', 1)->first() ?? [];
        }
        return $business;
    }
}

if (! function_exists('unit_labels')) {
    function unit_labels()
    {
        return [
            'HOUR'    => 'jam',
            'DAY'     => 'hari',
            'NIGHT'   => 'malam',
            'SESSION' => 'sesi',
            'TRIP'    => 'trip',
            'UNIT'    => 'unit',
            'FIXED'   => 'paket',
        ];
    }
}