<?php

require __DIR__ . '/../app/Libraries/OtpService.php';

$svc = new \App\Libraries\OtpService();

$cases = [
    '' => 'empty',
    '   ' => 'empty',
    'abc' => 'invalid_format',
    '0812abc4567' => 'invalid_format',
    '1234567890' => 'invalid_format', // doesn't start with 8
    '081234567' => 'too_short', // 8 digits
    '812345678' => 'too_short', // 9 digits
    '8123456789' => 'valid (10 digits)',
    '08123456789' => 'valid (10 digits with 0)',
    '628123456789' => 'valid (10 digits with 62)',
    '+628123456789' => 'valid (10 digits with +62)',
    '8123456789012' => 'valid (13 digits)',
    '08123456789012' => 'valid (13 digits with 0)',
    '81234567890123' => 'too_long', // 14 digits
    '081234567890123' => 'too_long', // 14 digits
];

foreach ($cases as $input => $expected) {
    $res = $svc->validateAndNormalizePhone($input);
    $status = $res['valid'] ? 'valid' : $res['error_type'];
    echo "Input: '{$input}' => Result: {$status} (" . ($res['canonical'] ?? $res['message']) . ") | Expected: {$expected}\n";
}

echo "Masked: " . $svc->maskPhone('+6281234567890') . "\n";

