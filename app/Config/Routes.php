<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// =====================================================
// PUBLIC
// =====================================================

$routes->get('/', 'Home::index');

// Katalog
$routes->get('/catalog', 'Katalog::index');
$routes->get('/item/cek-tersedia/(:any)', 'Katalog::cekTersedia/$1');
$routes->get('/item/(:any)', 'Katalog::detail/$1');

// =====================================================
// CART
// =====================================================

$routes->get('/cart', 'Cart::index');
$routes->post('/cart/tambah', 'Cart::tambah');
$routes->post('/cart/update', 'Cart::update');

// Key cart berbentuk: 4_9608d5630bb5d1885f30e8510d4b0527
// Jadi gunakan (:segment), bukan (:num)
$routes->get('/cart/hapus/(:segment)', 'Cart::hapus/$1');

// =====================================================
// CHECKOUT
// =====================================================

// Wajib login untuk masuk checkout
$routes->get('/checkout', 'Checkout::index', [
    'filter' => 'customerAuth'
]);

// Simpan data customer
$routes->post('/checkout/simpan-customer', 'Checkout::simpanCustomer', [
    'filter' => 'customerAuth'
]);

// Fulfillment
$routes->get('/checkout/fulfillment', 'Checkout::fulfillment', [
    'filter' => 'customerAuth'
]);

$routes->post('/checkout/fulfillment', 'Checkout::simpanFulfillment', [
    'filter' => 'customerAuth'
]);

// Review
$routes->get('/checkout/review', 'Checkout::review', [
    'filter' => 'customerAuth'
]);

// Proses checkout
$routes->post('/checkout/proses', 'Checkout::proses', [
    'filter' => 'customerAuth'
]);

// Berhasil
$routes->get('/checkout/berhasil/(:segment)', 'Checkout::berhasil/$1', [
    'filter' => 'customerAuth'
]);

// =====================================================
// CEK BOOKING (guest, tanpa login)
// =====================================================

$routes->get('/cek-booking', 'CekBooking::index');

// TERMS & CONDITIONS
$routes->get('/terms', 'Terms::index');

// =====================================================
// BANTUAN / FAQ
// =====================================================

$routes->get('/help', 'Help::index');

// =====================================================
// TRACKING & PAYMENT
// =====================================================

$routes->get('/track/(:segment)', 'Tracking::index/$1');

$routes->get(
    '/track/(:segment)/payment',
    'Payment::index/$1'
);

$routes->post(
    '/track/(:segment)/payment/process',
    'Payment::process/$1'
);

// =====================================================
// CUSTOMER PORTAL & AUTH (OTP WhatsApp)
// =====================================================

$routes->get('account/booking', 'Customer\Bookings::index', ['filter' => 'customerAuth']);

$routes->group('account', [
    'namespace' => 'App\Controllers\Customer'
], static function ($routes) {

    // CAUTH-01
    $routes->get('login', 'AuthController::login');
    $routes->post('login/send-otp', 'AuthController::sendOtp');

    // CAUTH-02
    $routes->get('verify', 'AuthController::verifyForm');
    $routes->post('verify', 'AuthController::verifyOtp');
    $routes->post('verify/resend', 'AuthController::resendOtp');
    $routes->get('verify/change-phone', 'AuthController::changePhone');

    // CAUTH-03 — hanya boleh diakses yang sudah login
    $routes->get('onboarding', 'AuthController::onboardingForm', ['filter' => 'customerAuth']);
    $routes->post('onboarding', 'AuthController::onboardingSubmit', ['filter' => 'customerAuth']);

    // CAUTH-04
    $routes->post('logout', 'AuthController::logout');

    // -----------------------------------------------------
    // CUS-01: Dashboard Customer
    // -----------------------------------------------------
    $routes->get('/', 'Dashboard::index', ['filter' => 'customerAuth']);

    // -----------------------------------------------------
    // CUS-02: Riwayat Booking
    // -----------------------------------------------------
    $routes->get('bookings', 'Bookings::index', ['filter' => 'customerAuth']);

    // -----------------------------------------------------
    // CUS-03: Detail Booking & Cetak Invoice
    // -----------------------------------------------------
    $routes->get('bookings/(:segment)/print', 'BookingDetail::print/$1', ['filter' => 'customerAuth']);
    $routes->get('bookings/(:segment)', 'BookingDetail::show/$1', ['filter' => 'customerAuth']);

    // -----------------------------------------------------
    // CUS-04: Pembayaran Saya
    // -----------------------------------------------------
    $routes->get('payments', 'Payments::index', ['filter' => 'customerAuth']);
    $routes->post('payments/upload-proof/(:segment)', 'Payments::uploadProof/$1', ['filter' => 'customerAuth']);
    $routes->get('payments/receipt/(:segment)', 'Payments::receipt/$1', ['filter' => 'customerAuth']);

    // -----------------------------------------------------
    // CUS-05: Invoice & Dokumen
    // -----------------------------------------------------
    $routes->get('documents', 'Documents::index', ['filter' => 'customerAuth']);
    $routes->get('documents/download/(:num)', 'Documents::download/$1', ['filter' => 'customerAuth']);

    // -----------------------------------------------------
    // CUS-06: Profil Saya
    // -----------------------------------------------------
    $routes->get('profile', 'Profile::index', ['filter' => 'customerAuth']);
    $routes->post('profile/update', 'Profile::update', ['filter' => 'customerAuth']);
    $routes->get('profile/photo', 'Profile::photo', ['filter' => 'customerAuth']);

    // -----------------------------------------------------
    // CUS-07: Dokumen Identitas
    // -----------------------------------------------------
    $routes->get('identity-documents', 'IdentityDocuments::index', ['filter' => 'customerAuth']);
    $routes->post('identity-documents/upload', 'IdentityDocuments::upload', ['filter' => 'customerAuth']);
    $routes->post('identity-documents/delete/(:num)', 'IdentityDocuments::delete/$1', ['filter' => 'customerAuth']);
    $routes->get('identity-documents/file/(:num)', 'IdentityDocuments::file/$1', ['filter' => 'customerAuth']);

    // -----------------------------------------------------
    // CUS-08: Keamanan Akun
    // -----------------------------------------------------
    $routes->get('security', 'Security::index', ['filter' => 'customerAuth']);
    $routes->post('security/phone/request-otp', 'Security::requestPhoneOtp', ['filter' => 'customerAuth']);
    $routes->post('security/phone/verify-otp', 'Security::verifyPhoneOtp', ['filter' => 'customerAuth']);
    $routes->post('security/phone/cancel-otp', 'Security::cancelPhoneOtp', ['filter' => 'customerAuth']);
});

// =====================================================
// BACKOFFICE
// =====================================================

$routes->group('backoffice', ['namespace' => 'App\Controllers\Backoffice'], static function ($routes) {
    $routes->get('/', 'AuthController::login');
    $routes->post('login', 'AuthController::attempt');
    $routes->post('logout', 'AuthController::logout');

    $routes->get('dashboard', 'DashboardController::index', ['filter' => 'backofficeAuth']);

    // Produk: lihat daftar
    $routes->get('produk', 'ProdukController::index', ['filter' => 'backofficeAuth:product.view,product.manage']);

    // Produk: tambah / ubah / aktif-nonaktif
    $routes->group('produk', ['filter' => 'backofficeAuth:product.manage'], static function ($routes) {
        $routes->get('baru', 'ProdukController::create');
        $routes->post('simpan', 'ProdukController::store');
        $routes->get('ubah/(:segment)', 'ProdukController::edit/$1');
        $routes->post('ubah/(:segment)', 'ProdukController::update/$1');
        $routes->post('status/(:segment)', 'ProdukController::status/$1');
    });

    // Jenis
    $routes->group('jenis', ['filter' => 'backofficeAuth:jenis.manage'], static function ($routes) {
        $routes->get('/', 'JenisController::index');
        $routes->get('baru', 'JenisController::create');
        $routes->post('simpan', 'JenisController::store');
        $routes->get('ubah/(:segment)', 'JenisController::edit/$1');
        $routes->post('ubah/(:segment)', 'JenisController::update/$1');
        $routes->post('status/(:segment)', 'JenisController::status/$1');
    });

    // Verifikasi identitas customer
    $routes->group('verifikasi-identitas', ['filter' => 'backofficeAuth:customer.verify'], static function ($routes) {
        $routes->get('/', 'CustomerVerification::index');
        $routes->get('(:segment)', 'CustomerVerification::show/$1');
        $routes->get('(:segment)/file', 'CustomerVerification::file/$1');
        $routes->post('(:segment)/approve', 'CustomerVerification::approve/$1');
        $routes->post('(:segment)/reject', 'CustomerVerification::reject/$1');
    });

    $routes->get('customer-photo/(:num)', 'CustomerVerification::profilePhoto/$1', ['filter' => 'backofficeAuth:customer.verify']);
});

// =====================================================
// 404 CUSTOM OVERRIDE
// =====================================================
$routes->set404Override(static function () {
    return view('errors/html/error_404');
});
