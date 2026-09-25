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
$routes->get('item/kalender-tersedia/(:segment)', 'Katalog::kalenderTersedia/$1');

// =====================================================
// AVAILABILITY 
// =====================================================

$routes->get('availability', 'Availability::index');
$routes->get('availability/cek/(:num)', 'Availability::cek/$1');

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
// SYARAT & KETENTUAN
// =====================================================

$routes->get('terms', 'Terms::index');

// =====================================================
// CUSTOMER AUTH (OTP WhatsApp)
// =====================================================

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

    // CAUTH-03 — hanya boleh diakses yang sudah login (bukan yang sudah lengkap profil)
    $routes->get('onboarding', 'AuthController::onboardingForm', ['filter' => 'customerAuth']);
    $routes->post('onboarding', 'AuthController::onboardingSubmit', ['filter' => 'customerAuth']);

    // CAUTH-04
    $routes->post('logout', 'AuthController::logout');
});