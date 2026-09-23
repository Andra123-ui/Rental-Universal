<?php
// app/Filters/CustomerAuthFilter.php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CustomerAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('customer_logged_in')) {
            $redirectTo = current_url(true)->getPath();
            if ($query = $request->getUri()->getQuery()) {
                $redirectTo .= '?' . $query;
            }

            return redirect()
                ->to('/account/login?return_url=' . urlencode($redirectTo))
                ->with('info', 'Silakan masuk terlebih dahulu untuk melanjutkan checkout.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah request.
    }
}