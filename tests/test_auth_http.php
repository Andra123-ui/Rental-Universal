<?php

ob_start();
require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

// Route tests group to default MySQL database
config('Database')->defaultGroup = 'default';
config('Database')->tests = config('Database')->default;

use App\Controllers\Customer\AuthController;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use Config\App;

$logs = [];
$allPassed = true;

function assertHttp($condition, $name) {
    global $allPassed, $logs;
    if ($condition) {
        $logs[] = " [PASS] {$name}";
    } else {
        $logs[] = " [FAIL] {$name}";
        $allPassed = false;
    }
}

// Helper to simulate request
function createRequest(string $method, string $uri, array $post = [], array $headers = []) {
    $config = new App();
    $uriObj = new URI('http://localhost/' . ltrim($uri, '/'));
    $userAgent = new UserAgent();
    $request = new IncomingRequest($config, $uriObj, null, $userAgent);
    $request->setMethod($method);
    
    if (!empty($post)) {
        $request->setGlobal('post', $post);
    }
    
    foreach ($headers as $k => $v) {
        $request->setHeader($k, $v);
    }
    
    return $request;
}

$logs[] = "--- 1. Testing AuthController::login (GET) ---";
try {
    $req = createRequest('GET', 'account/login');
    $ctrl = new AuthController();
    $ctrl->initController($req, service('response'), service('logger'));
    $res = $ctrl->login();
    $html = is_string($res) ? $res : (string) $res->getBody();
    
    assertHttp(strpos($html, 'Masuk ke Akun Anda') !== false, "GET /account/login mengembalikan halaman login");
    assertHttp(strpos($html, 'Kirim Kode OTP') !== false, "Halaman login memiliki tombol 'Kirim Kode OTP'");
} catch (\Throwable $e) {
    $logs[] = "Error GET login: " . $e->getMessage();
    assertHttp(false, "GET /account/login berhasil");
}

$logs[] = "\n--- 2. Testing AuthController::sendOtp Validation via AJAX (POST) ---";
try {
    // Test empty
    $req = createRequest('POST', 'account/login/send-otp', ['phone' => '', 'ajax' => '1'], [
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'application/json'
    ]);
    $ctrl = new AuthController();
    $ctrl->initController($req, service('response'), service('logger'));
    $res = $ctrl->sendOtp();
    $data = json_decode($res->getBody(), true);
    assertHttp($res->getStatusCode() === 422 && $data['code'] === 'empty_phone', "Submit nomor kosong mengembalikan HTTP 422 'empty_phone'");

    // Test too short
    $req = createRequest('POST', 'account/login/send-otp', ['phone' => '81234567', 'ajax' => '1'], [
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'application/json'
    ]);
    $ctrl = new AuthController();
    $ctrl->initController($req, service('response'), service('logger'));
    $res = $ctrl->sendOtp();
    $data = json_decode($res->getBody(), true);
    assertHttp($res->getStatusCode() === 422 && $data['code'] === 'phone_too_short', "Submit nomor <10 digit mengembalikan HTTP 422 'phone_too_short'");

    // Test too long
    $req = createRequest('POST', 'account/login/send-otp', ['phone' => '81234567890123', 'ajax' => '1'], [
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'application/json'
    ]);
    $ctrl = new AuthController();
    $ctrl->initController($req, service('response'), service('logger'));
    $res = $ctrl->sendOtp();
    $data = json_decode($res->getBody(), true);
    assertHttp($res->getStatusCode() === 422 && $data['code'] === 'phone_too_long', "Submit nomor >13 digit mengembalikan HTTP 422 'phone_too_long'");

    // Test invalid format
    $req = createRequest('POST', 'account/login/send-otp', ['phone' => 'abc8123', 'ajax' => '1'], [
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'application/json'
    ]);
    $ctrl = new AuthController();
    $ctrl->initController($req, service('response'), service('logger'));
    $res = $ctrl->sendOtp();
    $data = json_decode($res->getBody(), true);
    assertHttp($res->getStatusCode() === 422 && $data['code'] === 'invalid_format', "Submit nomor tidak valid mengembalikan HTTP 422 'invalid_format'");

} catch (\Throwable $e) {
    $logs[] = "Error sendOtp AJAX: " . $e->getMessage();
    assertHttp(false, "AJAX sendOtp validasi berhasil");
}

$logs[] = "\n--- 3. Testing AuthController::verifyOtp (POST) ---";
try {
    // Test without session
    session()->remove('customer_login_phone');
    $req = createRequest('POST', 'account/verify', ['code' => '123456', 'ajax' => '1'], [
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'application/json'
    ]);
    $ctrl = new AuthController();
    $ctrl->initController($req, service('response'), service('logger'));
    $res = $ctrl->verifyOtp();
    $data = json_decode($res->getBody(), true);
    assertHttp($res->getStatusCode() === 401 && $data['code'] === 'session_expired', "Verifikasi tanpa sesi mengembalikan HTTP 401 'session_expired'");

    // Test with invalid code length
    session()->set('customer_login_phone', '+6281234567890');
    $req = createRequest('POST', 'account/verify', ['code' => '12', 'ajax' => '1'], [
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'application/json'
    ]);
    $ctrl = new AuthController();
    $ctrl->initController($req, service('response'), service('logger'));
    $res = $ctrl->verifyOtp();
    $data = json_decode($res->getBody(), true);
    assertHttp($res->getStatusCode() === 422 && $data['code'] === 'otp_invalid', "Verifikasi kode < 6 digit mengembalikan HTTP 422 'otp_invalid'");

} catch (\Throwable $e) {
    $logs[] = "Error verifyOtp: " . $e->getMessage();
    assertHttp(false, "verifyOtp test berhasil");
}

ob_end_clean();

echo "=== HASIL TEST HTTP AUTHCONTROLLER ===\n\n";
foreach ($logs as $log) {
    echo $log . "\n";
}

echo "\n============================================\n";
if ($allPassed) {
    echo "🎉 SEMUA HTTP TEST BERHASIL (ALL PASSED)!\n";
} else {
    echo "❌ ADA HTTP TEST YANG GAGAL!\n";
}
echo "============================================\n";
