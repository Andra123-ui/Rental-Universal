<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// =====================================================
// PUBLIC
// =====================================================

$routes->get('/', 'Home::index');

// Katalog
$routes->get('/catalog', 'Katalog::index');
$routes->get('/item/(:num)', 'Katalog::detail/$1');

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

$routes->get('/checkout', 'Checkout::index');

$routes->get('/checkout/fulfillment', 'Checkout::fulfillment');
$routes->post('/checkout/fulfillment', 'Checkout::simpanFulfillment');

$routes->get('/checkout/review', 'Checkout::review');

$routes->post('/checkout/proses', 'Checkout::proses');

$routes->get('/checkout/berhasil/(:segment)', 'Checkout::berhasil/$1');

// =====================================================
// CEK BOOKING
// =====================================================

$routes->get('/cek-booking', 'CekBooking::index');

// =====================================================
// CUSTOMER AUTH
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
});