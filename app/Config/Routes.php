<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('/katalog', 'Katalog::index');
$routes->get('/katalog/(:num)', 'Katalog::detail/$1');

$routes->get('/keranjang', 'Cart::index');
$routes->post('/keranjang/tambah', 'Cart::tambah');
$routes->post('/keranjang/update', 'Cart::update');
$routes->get('/keranjang/hapus/(:num)', 'Cart::hapus/$1');

$routes->get('/checkout', 'Checkout::index');
$routes->get('/checkout/fulfillment', 'Checkout::fulfillment');
$routes->post('/checkout/fulfillment', 'Checkout::simpanFulfillment');
$routes->get('/checkout/review', 'Checkout::review');
$routes->post('/checkout/proses', 'Checkout::proses');
$routes->get('/checkout/berhasil/(:segment)', 'Checkout::berhasil/$1');

$routes->get('/cek-booking', 'CekBooking::index');
