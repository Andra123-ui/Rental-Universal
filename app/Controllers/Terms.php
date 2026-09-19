<?php

namespace App\Controllers;

class Terms extends BaseController
{
    public function index()
    {
        $data = [
            'title' => 'Syarat & Ketentuan Rental - Rental Universal'
        ];

        // Memanggil view dari folder pub/terms.php
        return view('pub/terms', $data);
    }
}