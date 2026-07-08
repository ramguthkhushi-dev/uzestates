<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/enquiry-form.php';

$cases = [
    ['+23058154042', true, '+23058154042'],
    ['+447700900123', true, '+447700900123'],
    ['+12025550123', true, '+12025550123'],
    ['+33123456789', true, '+33123456789'],
    ['58154042', true, '+23058154042'],
    ['123', false, ''],
    ['+1234', false, ''],
    ['2025550123', false, ''],
    ['', true, ''],
];

$failed = 0;

foreach ($cases as [$in, $valid, $expected]) {
    $norm = enquiry_normalize_phone($in);
    $err  = enquiry_validate_phone($in);
    $ok   = $in === ''
        ? $err === null
        : ($valid ? $err === null && $norm === $expected : $err !== null);

    if (!$ok) {
        $failed++;
        echo "FAIL in={$in} norm={$norm} err=" . ($err ?? 'null') . PHP_EOL;
    }
}

echo $failed === 0 ? "All phone validation tests passed.\n" : "{$failed} test(s) failed.\n";
exit($failed === 0 ? 0 : 1);
