<?php

declare(strict_types=1);

/**
 * HTTP smoke tests for Ajax enquiry API endpoints.
 * Run: php scripts/verify-enquiry-api.php
 */

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/enquiries.php';

$passed = 0;
$failed = 0;

function check(string $label, bool $ok): void
{
    global $passed, $failed;
    if ($ok) {
        echo "PASS  {$label}\n";
        $passed++;
    } else {
        echo "FAIL  {$label}\n";
        $failed++;
    }
}

function http_request(string $method, string $url, ?array $post = null, ?string $cookieFile = null): array
{
    if (!function_exists('curl_init')) {
        return ['code' => 0, 'body' => '', 'error' => 'curl extension unavailable'];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    if ($cookieFile !== null) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'code'  => $code,
        'body'  => is_string($body) ? $body : '',
        'error' => $error,
    ];
}

$baseUrl    = 'http://localhost' . BASE_URL;
$cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uz5-api-test-' . getmypid() . '.txt';

$getApi = http_request('GET', $baseUrl . '/api/enquiry.php');
check('public api GET returns 405', $getApi['code'] === 405);

$contactPage = http_request('GET', $baseUrl . '/contact.php', null, $cookieFile);
check('contact page loads for session', $contactPage['code'] === 200);

$csrf = null;
if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $contactPage['body'], $matches)) {
    $csrf = $matches[1];
}
check('csrf token extracted from contact page', $csrf !== null && $csrf !== '');

$postResult = http_request('POST', $baseUrl . '/api/enquiry.php', [
    'csrf_token'   => $csrf,
    'name'         => 'HTTP API Test',
    'phone'        => '+23051234567',
    'email'        => 'http-api-test@example.com',
    'message'      => 'Automated HTTP Ajax API verification message.',
    'enquiry_type' => 'General',
], $cookieFile);

$postJson = json_decode($postResult['body'], true);
check('public api POST returns 200', $postResult['code'] === 200);
check('public api POST success JSON', ($postJson['ok'] ?? false) === true && !empty($postJson['message']));

$enquiryId = (int) db()->query(
    "SELECT id FROM enquiries WHERE email = 'http-api-test@example.com' ORDER BY id DESC LIMIT 1"
)->fetchColumn();
check('public api enquiry stored in database', $enquiryId > 0);

$badCsrf = http_request('POST', $baseUrl . '/api/enquiry.php', [
    'csrf_token' => 'invalid-token',
    'name'       => 'Bad CSRF',
    'phone'      => '+23051234567',
    'email'      => 'bad-csrf@example.com',
    'message'    => 'This should fail CSRF validation check.',
], $cookieFile);
$badJson = json_decode($badCsrf['body'], true);
check('public api rejects invalid csrf', $badCsrf['code'] === 403 && ($badJson['ok'] ?? true) === false);

if ($enquiryId > 0) {
    enquiry_delete($enquiryId);
}

$adminGet = http_request('GET', $baseUrl . '/admin/api/enquiry.php');
check('admin api GET returns 401 without session', $adminGet['code'] === 401);

check('enquiry-form.js exists', is_file(__DIR__ . '/../js/enquiry-form.js'));
check('admin enquiries.js exists', is_file(__DIR__ . '/../admin/assets/enquiries.js'));

check('honeypot helper rejects bots', enquiry_is_honeypot(['website' => 'http://spam.test']));
check('honeypot allows real users', !enquiry_is_honeypot(['website' => '']));

$honeypotResult = enquiry_save([
    'name'    => 'Spam Bot',
    'phone'   => '+23051234567',
    'email'   => 'spam-bot@example.com',
    'message' => 'This honeypot test should not be stored in the database.',
    'website' => 'http://spam.test',
]);
check('honeypot save returns success', ($honeypotResult['success'] ?? false) === true);
$spamRow = (int) db()->query(
    "SELECT COUNT(*) FROM enquiries WHERE email = 'spam-bot@example.com'"
)->fetchColumn();
check('honeypot enquiry not stored', $spamRow === 0);

if (is_file($cookieFile)) {
    @unlink($cookieFile);
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
