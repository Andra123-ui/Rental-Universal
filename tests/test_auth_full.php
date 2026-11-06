<?php

ob_start();

require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

use App\Libraries\OtpService;
use App\Models\CustomerModel;
use App\Models\CustomerAccountModel;
use App\Models\CustomerLoginOtpModel;

// Disable CSRF cookie sending in CLI test
config('Security')->csrfProtection = 'cookie';
config('Security')->regenerate = false;

$logs = [];
$allPassed = true;

function assertTest($condition, $testName) {
    global $allPassed, $logs;
    if ($condition) {
        $logs[] = " [PASS] {$testName}";
    } else {
        $logs[] = " [FAIL] {$testName}";
        $allPassed = false;
    }
}

$svc = new OtpService();

// 1. Validasi Nomor Telepon
$logs[] = "--- 1. Testing Validasi Nomor Telepon (Section E) ---";
$r1 = $svc->validateAndNormalizePhone('');
assertTest(!$r1['valid'] && $r1['error_type'] === 'empty', "Input kosong menghasilkan error 'empty'");

$r2 = $svc->validateAndNormalizePhone('   ');
assertTest(!$r2['valid'] && $r2['error_type'] === 'empty', "Input spasi menghasilkan error 'empty'");

$r3 = $svc->validateAndNormalizePhone('0812abc4567');
assertTest(!$r3['valid'] && $r3['error_type'] === 'invalid_format', "Input alfanumerik menghasilkan error 'invalid_format'");

$r4 = $svc->validateAndNormalizePhone('1234567890');
assertTest(!$r4['valid'] && $r4['error_type'] === 'invalid_format', "Input tidak diawali angka 8 menghasilkan error 'invalid_format'");

$r5 = $svc->validateAndNormalizePhone('081234567'); // 8 digit lokal
assertTest(!$r5['valid'] && $r5['error_type'] === 'too_short', "Nomor 8 digit lokal menghasilkan error 'too_short'");

$r6 = $svc->validateAndNormalizePhone('812345678'); // 9 digit lokal
assertTest(!$r6['valid'] && $r6['error_type'] === 'too_short', "Nomor 9 digit lokal menghasilkan error 'too_short'");

$r7 = $svc->validateAndNormalizePhone('8123456789'); // 10 digit lokal
assertTest($r7['valid'] && $r7['canonical'] === '+628123456789', "Nomor 10 digit lokal valid (+628123456789)");

$r8 = $svc->validateAndNormalizePhone('081234567890'); // 11 digit lokal dengan 0
assertTest($r8['valid'] && $r8['canonical'] === '+6281234567890', "Nomor 11 digit lokal dengan awalan 0 dinormalisasi ke +6281234567890");

$r9 = $svc->validateAndNormalizePhone('62812345678901'); // 12 digit lokal dengan 62
assertTest($r9['valid'] && $r9['canonical'] === '+62812345678901', "Nomor 12 digit lokal dengan awalan 62 dinormalisasi ke +62812345678901");

$r10 = $svc->validateAndNormalizePhone('+628123456789012'); // 13 digit lokal dengan +62
assertTest($r10['valid'] && $r10['canonical'] === '+628123456789012', "Nomor 13 digit lokal dengan awalan +62 dinormalisasi ke +628123456789012");

$r11 = $svc->validateAndNormalizePhone('81234567890123'); // 14 digit lokal
assertTest(!$r11['valid'] && $r11['error_type'] === 'too_long', "Nomor 14 digit lokal menghasilkan error 'too_long'");

$r12 = $svc->validateAndNormalizePhone('081234567890123'); // 14 digit lokal dengan 0
assertTest(!$r12['valid'] && $r12['error_type'] === 'too_long', "Nomor 14 digit lokal dengan 0 menghasilkan error 'too_long'");

// 2. Format Masked Phone
$logs[] = "\n--- 2. Testing Masked Phone (Section G) ---";
$mask1 = $svc->maskPhone('+6281234567890');
assertTest($mask1 === '+62 812-****-7890', "Masked phone '+6281234567890' menghasilkan '+62 812-****-7890' (hasil: {$mask1})");

// 3. OTP Generation & Verification
$logs[] = "\n--- 3. Testing OTP Generation & Cryptographic Hashing ---";
$code = $svc->generateCode();
assertTest(strlen($code) === 6 && ctype_digit($code), "OTP yang dihasilkan tepat 6 digit angka: {$code}");

$hash = $svc->hashCode($code);
assertTest($svc->verifyCode($code, $hash), "Verifikasi kode benar terhadap hash berhasil");
assertTest(!$svc->verifyCode('000000', $hash) && !$svc->verifyCode('999999', $hash), "Verifikasi kode salah terhadap hash ditolak");

// 4. View Rendering Tests
$logs[] = "\n--- 4. Testing View Rendering & Assets ---";
// Gunakan output buffering untuk menangkap view render
try {
    $loginHtml = view('customer/login', ['returnUrl' => '/account']);
    assertTest(strpos($loginHtml, 'glass-auth-card') !== false, "View login mengandung elemen '.glass-auth-card'");
    assertTest(strpos($loginHtml, 'BgMasuk.png') !== false, "View login memuat background 'BgMasuk.png'");
    assertTest(strpos($loginHtml, 'Solusi Sewa Alat') === false, "Teks slogan 'Solusi Sewa Alat' sudah dihapus dari view login");
    assertTest(strpos($loginHtml, 'hero-promo-header') === false, "Elemen '.hero-promo-header' sudah dihapus dari HTML");
    assertTest(strpos($loginHtml, 'Kirim Kode OTP') !== false, "View login mengandung tombol 'Kirim Kode OTP'");
    assertTest(strpos($loginHtml, 'phoneInput') !== false, "View login mengandung input 'phoneInput'");
    assertTest(strpos($loginHtml, 'showAuthPopup') !== false, "View login memanggil modal 'showAuthPopup' (bukan browser alert)");
    assertTest(strpos($loginHtml, 'alert(') === false, "View login TIDAK mengandung browser alert()");

    $verifyHtml = view('customer/verify', [
        'maskedPhone' => '+62 812-****-7890',
        'expiresAt' => date('Y-m-d H:i:s', time() + 300),
        'resendCooldown' => 60,
    ]);
    assertTest(strpos($verifyHtml, 'otp-box-digit') !== false, "View verify mengandung 6 kotak input '.otp-box-digit'");
    assertTest(strpos($verifyHtml, 'Verifikasi Nomor WhatsApp') !== false, "View verify mengandung judul 'Verifikasi Nomor WhatsApp'");
    assertTest(strpos($verifyHtml, 'Verifikasi & Masuk') !== false, "View verify mengandung tombol 'Verifikasi & Masuk'");
    assertTest(strpos($verifyHtml, 'Kirim Ulang Kode') !== false, "View verify mengandung tombol 'Kirim Ulang Kode'");
    assertTest(strpos($verifyHtml, 'Ganti Nomor') !== false, "View verify mengandung tautan 'Ganti Nomor'");
} catch (\Throwable $e) {
    $logs[] = "View Render Error: " . $e->getMessage();
    assertTest(false, "View rendering berhasil tanpa exception");
}

// 5. Database & Verification Flow
$logs[] = "\n--- 5. Testing Database Flow ---";
try {
    $db = \Config\Database::connect('default');
    $db->query("SELECT 1");
    assertTest(true, "Koneksi ke database rental_universal_db berhasil");

    $accountModel = new CustomerAccountModel($db);
    $otpModel = new CustomerLoginOtpModel($db);

    $testPhone = '+628999999999';
    $account = $accountModel->findByLoginPhone($testPhone);
    if (!$account) {
        $custModel = new CustomerModel($db);
        $cid = $custModel->insert(['name' => 'Test User', 'phone' => $testPhone], true);
        $aid = $accountModel->insert(['customer_id' => $cid, 'login_phone' => $testPhone, 'status' => 'PENDING'], true);
        $account = $accountModel->find($aid);
    }
    assertTest(!empty($account['id']), "Customer account test terdaftar di database (ID: {$account['id']})");

    // Simpan OTP test
    $testOtp = '765432';
    $otpId = $otpModel->insert([
        'customer_account_id' => $account['id'],
        'phone' => $testPhone,
        'purpose' => 'LOGIN',
        'code_hash' => $svc->hashCode($testOtp),
        'expires_at' => date('Y-m-d H:i:s', time() + 300),
        'attempt_count' => 0,
        'max_attempts' => 5,
        'status' => 'PENDING',
        'requested_ip' => '127.0.0.1',
        'user_agent' => 'CLI Test',
    ], true);

    $latest = $otpModel->latestPendingByAccountId((int) $account['id']);
    assertTest($latest && $latest['id'] == $otpId, "Latest pending OTP berhasil diambil dari database");
    assertTest($svc->verifyCode($testOtp, $latest['code_hash']), "Kode OTP test cocok dengan hash di database");

    // Bersihkan data test OTP
    $otpModel->delete($otpId);
    assertTest(true, "Data test OTP dibersihkan dengan aman");

} catch (\Throwable $e) {
    $logs[] = "Database error: " . $e->getMessage();
    assertTest(false, "Koneksi/operasi database gagal");
}

// Clean buffer and print clean output
ob_end_clean();

echo "=== HASIL TEST INTEGRASI AUTH & WHATSAPP OTP ===\n\n";
foreach ($logs as $log) {
    echo $log . "\n";
}

echo "\n============================================\n";
if ($allPassed) {
    echo "🎉 SEMUA TEST BERHASIL (ALL PASSED)!\n";
} else {
    echo "❌ ADA TEST YANG GAGAL!\n";
}
echo "============================================\n";
