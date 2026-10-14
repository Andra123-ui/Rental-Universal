<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index()
    {
        return view('backoffice/dashboard', ['title' => 'Dashboard', 'nav' => 'dashboard']);
    }
}