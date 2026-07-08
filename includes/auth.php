<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

function app_cookie_path(): string
{
    return rtrim(BASE_URL, '/') . '/';
}

function session_cookie_params(int $lifetime = SESSION_LIFETIME): array
{
    return [
        'lifetime' => $lifetime,
        'path'     => app_cookie_path(),
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => request_is_https(),
    ];
}

function configure_session_storage(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    if (defined('SESSION_SAVE_PATH')) {
        if (!is_dir(SESSION_SAVE_PATH)) {
            @mkdir(SESSION_SAVE_PATH, 0755, true);
        }

        if (is_dir(SESSION_SAVE_PATH) && is_writable(SESSION_SAVE_PATH)) {
            ini_set('session.save_path', SESSION_SAVE_PATH);
        }
    }
}

function purge_remember_cookie(): void
{
    $paths = array_unique([
        app_cookie_path(),
        rtrim(BASE_URL, '/'),
        BASE_URL . '/',
        '/',
    ]);

    foreach ($paths as $path) {
        setcookie(REMEMBER_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => $path,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    unset($_COOKIE[REMEMBER_COOKIE]);
}

function purge_session_cookie(): void
{
    $paths = array_unique([
        app_cookie_path(),
        rtrim(BASE_URL, '/'),
        BASE_URL . '/',
        '/',
    ]);

    foreach ($paths as $path) {
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $path,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function begin_fresh_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    configure_session_storage();
    session_name(SESSION_NAME);
    session_set_cookie_params(session_cookie_params());
    session_start();
}

function clear_legacy_auth_cookies(): void
{
    $paths = array_unique([
        app_cookie_path(),
        rtrim(BASE_URL, '/'),
        BASE_URL . '/',
        app_cookie_path() . 'admin/',
        rtrim(BASE_URL, '/') . '/admin',
        '/',
    ]);

    foreach (['uz_estates_admin', SESSION_NAME, REMEMBER_COOKIE] as $name) {
        foreach ($paths as $path) {
            setcookie($name, '', time() - 42000, $path);
        }
    }
}

function start_session(): void
{
    static $bootstrapped = false;

    force_https_if_enabled();

    if (session_status() === PHP_SESSION_NONE) {
        configure_session_storage();
        session_name(SESSION_NAME);
        session_set_cookie_params(session_cookie_params());
        session_start();
    }

    if (!$bootstrapped) {
        $bootstrapped = true;
        if (isset($_GET['logged_out'])) {
            purge_remember_cookie();
        } elseif (!user_logged_in()) {
            try_remember_login();
        }
    }
}

function csrf_token(): string
{
    start_session();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    start_session();

    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function user_role(): ?string
{
    start_session();
    $role = $_SESSION['user_role'] ?? null;

    return in_array($role, ['admin', 'client'], true) ? $role : null;
}

function admin_logged_in(): bool
{
    start_session();

    return user_role() === 'admin' && !empty($_SESSION['admin_id']);
}

function client_logged_in(): bool
{
    start_session();

    return user_role() === 'client'
        && !empty($_SESSION['client_id'])
        && !empty($_SESSION['client_email']);
}

function user_logged_in(): bool
{
    return admin_logged_in() || client_logged_in();
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        header('Location: ' . admin_url('login.php'));
        exit;
    }
}

function redirect_if_logged_in(): void
{
    if (admin_logged_in()) {
        header('Location: ' . admin_url('index.php'));
        exit;
    }

    if (client_logged_in()) {
        logout_user();
    }
}

function current_admin(): ?array
{
    if (!admin_logged_in()) {
        return null;
    }

    return [
        'id'       => (int) $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'name'     => $_SESSION['admin_name'] ?? '',
        'email'    => $_SESSION['admin_email'] ?? '',
    ];
}

function login_admin(array $admin, bool $remember = false): void
{
    start_session();
    session_regenerate_id(true);

    $_SESSION['user_role']      = 'admin';
    $_SESSION['admin_id']       = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_name']     = $admin['full_name'] ?? '';
    $_SESSION['admin_email']    = $admin['email'] ?? '';

    clear_legacy_auth_cookies();
    persist_remember_me('admin', (int) $admin['id'], $remember);
    refresh_session_cookie($remember ? REMEMBER_LIFETIME : SESSION_LIFETIME);
}

function logout_user(): void
{
    start_session();

    $role = user_role();
    $adminId = (int) ($_SESSION['admin_id'] ?? 0);
    $clientId = (int) ($_SESSION['client_id'] ?? 0);

    if ($role === 'admin' && $adminId > 0) {
        clear_remember_tokens('admin', $adminId);
    } elseif ($role === 'client' && $clientId > 0) {
        clear_remember_tokens('client', $clientId);
    }

    $_SESSION = [];
    session_unset();
    purge_remember_cookie();
    purge_session_cookie();
    clear_legacy_auth_cookies();

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function attempt_login(string $login, string $password, bool $remember = false): array
{
    require_once __DIR__ . '/../config/database.php';

    $login = trim($login);

    if ($login === '' || $password === '') {
        return ['success' => false, 'error' => 'Please enter your username/email and password.'];
    }

    if (strlen($login) > 150) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    $normalizedLogin = strtolower($login);

    $stmt = db()->prepare(
        'SELECT id, username, email, password_hash, full_name, is_active
         FROM admins
         WHERE LOWER(email) = :email_login OR LOWER(username) = :username_login
         LIMIT 1'
    );
    $stmt->execute([
        'email_login'    => $normalizedLogin,
        'username_login' => $normalizedLogin,
    ]);
    $admin = $stmt->fetch();

    if (!$admin) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    if (!(bool) $admin['is_active']) {
        return ['success' => false, 'error' => 'This admin account has been deactivated.'];
    }

    if (!password_verify($password, $admin['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    db()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = :id')
        ->execute(['id' => $admin['id']]);

    login_admin($admin, $remember);

    return ['success' => true, 'role' => 'admin'];
}

function validate_email_address(string $email): ?string
{
    $email = trim($email);

    if ($email === '') {
        return 'Email is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }

    if (strlen($email) > 150) {
        return 'Email is too long.';
    }

    return null;
}

function validate_password_pair(string $password, string $confirm): ?string
{
    if ($password === '' || $confirm === '') {
        return 'Please enter and confirm your password.';
    }

    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }

    if (strlen($password) > 128) {
        return 'Password is too long.';
    }

    if (!hash_equals($password, $confirm)) {
        return 'Passwords do not match.';
    }

    return null;
}

function attempt_forgot_password(string $email): array
{
    require_once __DIR__ . '/../config/database.php';

    $emailError = validate_email_address($email);
    if ($emailError !== null) {
        return ['success' => false, 'error' => $emailError];
    }

    $email = strtolower(trim($email));
    $userType = null;

    $admin = db()->prepare('SELECT id, email FROM admins WHERE LOWER(email) = :email AND is_active = 1 LIMIT 1');
    $admin->execute(['email' => $email]);
    if ($admin->fetch()) {
        $userType = 'admin';
    } else {
        $client = db()->prepare('SELECT id, email FROM clients WHERE LOWER(email) = :email AND is_active = 1 LIMIT 1');
        $client->execute(['email' => $email]);
        if ($client->fetch()) {
            $userType = 'client';
        }
    }

    // Always show the same message to avoid email enumeration
    $genericMessage = 'If an account exists for that email, password reset instructions have been sent.';

    if ($userType === null) {
        return ['success' => true, 'message' => $genericMessage, 'dev_reset_url' => null];
    }

    $token     = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expires   = date('Y-m-d H:i:s', time() + RESET_TOKEN_LIFETIME);

    db()->prepare('DELETE FROM password_resets WHERE email = :email AND user_type = :user_type')
        ->execute(['email' => $email, 'user_type' => $userType]);

    db()->prepare(
        'INSERT INTO password_resets (user_type, email, token_hash, expires_at)
         VALUES (:user_type, :email, :token_hash, :expires_at)'
    )->execute([
        'user_type'  => $userType,
        'email'      => $email,
        'token_hash' => $tokenHash,
        'expires_at' => $expires,
    ]);

    $resetUrl = absolute_url(
        ($userType === 'admin' ? 'admin/' : '') . 'reset-password.php?token=' . urlencode($token)
    );
    send_password_reset_email($email, $resetUrl);

    $devUrl = is_local_request() ? $resetUrl : null;

    return ['success' => true, 'message' => $genericMessage, 'dev_reset_url' => $devUrl];
}

function attempt_reset_password(string $token, string $password, string $confirm): array
{
    require_once __DIR__ . '/../config/database.php';

    $passwordError = validate_password_pair($password, $confirm);
    if ($passwordError !== null) {
        return ['success' => false, 'error' => $passwordError];
    }

    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return ['success' => false, 'error' => 'Invalid or expired reset link.'];
    }

    $tokenHash = hash('sha256', $token);

    $stmt = db()->prepare(
        'SELECT id, user_type, email FROM password_resets
         WHERE token_hash = :token_hash AND expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute(['token_hash' => $tokenHash]);
    $reset = $stmt->fetch();

    if (!$reset) {
        return ['success' => false, 'error' => 'Invalid or expired reset link. Please request a new one.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($reset['user_type'] === 'admin') {
        db()->prepare('UPDATE admins SET password_hash = :hash WHERE LOWER(email) = :email')
            ->execute(['hash' => $hash, 'email' => strtolower($reset['email'])]);
    } else {
        db()->prepare('UPDATE clients SET password_hash = :hash WHERE LOWER(email) = :email')
            ->execute(['hash' => $hash, 'email' => strtolower($reset['email'])]);
    }

    db()->prepare('DELETE FROM password_resets WHERE id = :id')->execute(['id' => $reset['id']]);

    return ['success' => true];
}

function refresh_session_cookie(int $lifetime): void
{
    $params = session_cookie_params($lifetime);

    setcookie(session_name(), session_id(), [
        'expires'  => time() + $lifetime,
        'path'     => $params['path'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'],
        'secure'   => $params['secure'],
    ]);
}

function persist_remember_me(string $userType, int $userId, bool $remember): void
{
    if (!$remember) {
        setcookie(REMEMBER_COOKIE, '', time() - 42000, app_cookie_path());

        return;
    }

    try {
        require_once __DIR__ . '/../config/database.php';

        clear_remember_tokens($userType, $userId);

        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires   = date('Y-m-d H:i:s', time() + REMEMBER_LIFETIME);

        db()->prepare(
            'INSERT INTO remember_tokens (user_type, user_id, token_hash, expires_at)
             VALUES (:user_type, :user_id, :token_hash, :expires_at)'
        )->execute([
            'user_type'  => $userType,
            'user_id'    => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expires,
        ]);

        setcookie(
            REMEMBER_COOKIE,
            $userType . ':' . $userId . ':' . $token,
            [
                'expires'  => time() + REMEMBER_LIFETIME,
                'path'     => app_cookie_path(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    } catch (Throwable $e) {
        // Login still succeeds even if remember-me storage is unavailable.
    }
}

function clear_remember_tokens(string $userType, int $userId): void
{
    try {
        require_once __DIR__ . '/../config/database.php';

        db()->prepare('DELETE FROM remember_tokens WHERE user_type = :user_type AND user_id = :user_id')
            ->execute(['user_type' => $userType, 'user_id' => $userId]);
    } catch (Throwable $e) {
        // Ignore missing table or DB errors during cleanup.
    }
}

function try_remember_login(): void
{
    $raw = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if ($raw === '' || substr_count($raw, ':') !== 2) {
        return;
    }

    [$userType, $userId, $token] = explode(':', $raw, 3);
    if (!in_array($userType, ['client', 'admin'], true) || !ctype_digit($userId) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        setcookie(REMEMBER_COOKIE, '', time() - 42000, app_cookie_path());

        return;
    }

    try {
        require_once __DIR__ . '/../config/database.php';

        $tokenHash = hash('sha256', $token);
        $stmt = db()->prepare(
            'SELECT id FROM remember_tokens
             WHERE user_type = :user_type AND user_id = :user_id
               AND token_hash = :token_hash AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([
            'user_type'  => $userType,
            'user_id'    => (int) $userId,
            'token_hash' => $tokenHash,
        ]);

        if (!$stmt->fetch()) {
            setcookie(REMEMBER_COOKIE, '', time() - 42000, app_cookie_path());

            return;
        }
    } catch (Throwable $e) {
        return;
    }

    if ($userType === 'admin') {
        $user = db()->prepare(
            'SELECT id, username, email, full_name, is_active FROM admins WHERE id = :id LIMIT 1'
        );
        $user->execute(['id' => (int) $userId]);
        $admin = $user->fetch();

        if (!$admin || !(bool) $admin['is_active']) {
            clear_remember_tokens('admin', (int) $userId);
            setcookie(REMEMBER_COOKIE, '', time() - 42000, app_cookie_path());

            return;
        }

        login_admin($admin, true);

        return;
    }

    clear_remember_tokens($userType, (int) $userId);
    setcookie(REMEMBER_COOKIE, '', time() - 42000, app_cookie_path());
}

function admin_update_profile(int $adminId, string $fullName, string $email, string $username): array
{
    require_once __DIR__ . '/../config/database.php';

    $fullName = trim($fullName);
    $email    = strtolower(trim($email));
    $username = trim($username);

    if ($fullName === '') {
        return ['success' => false, 'error' => 'Please enter your name.'];
    }

    $emailError = validate_email_address($email);
    if ($emailError !== null) {
        return ['success' => false, 'error' => $emailError];
    }

    if ($username === '' || strlen($username) > 50) {
        return ['success' => false, 'error' => 'Please enter a valid username.'];
    }

    $dup = db()->prepare(
        'SELECT id FROM admins WHERE (LOWER(email) = :email OR username = :username) AND id != :id LIMIT 1'
    );
    $dup->execute(['email' => $email, 'username' => $username, 'id' => $adminId]);
    if ($dup->fetch()) {
        return ['success' => false, 'error' => 'That email or username is already in use.'];
    }

    db()->prepare(
        'UPDATE admins SET full_name = :full_name, email = :email, username = :username, updated_at = NOW() WHERE id = :id'
    )->execute([
        'full_name' => $fullName,
        'email'     => $email,
        'username'  => $username,
        'id'        => $adminId,
    ]);

    $_SESSION['admin_name']     = $fullName;
    $_SESSION['admin_email']    = $email;
    $_SESSION['admin_username'] = $username;

    return ['success' => true];
}

function admin_change_password(int $adminId, string $current, string $new, string $confirm): array
{
    require_once __DIR__ . '/../config/database.php';

    $passwordError = validate_password_pair($new, $confirm);
    if ($passwordError !== null) {
        return ['success' => false, 'error' => $passwordError];
    }

    $stmt = db()->prepare('SELECT password_hash FROM admins WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute(['id' => $adminId]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($current, $row['password_hash'])) {
        return ['success' => false, 'error' => 'Current password is incorrect.'];
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    db()->prepare('UPDATE admins SET password_hash = :hash, updated_at = NOW() WHERE id = :id')
        ->execute(['hash' => $hash, 'id' => $adminId]);

    return ['success' => true];
}

/** @return list<array<string, mixed>> */
function admin_list_all(): array
{
    require_once __DIR__ . '/../config/database.php';

    return db()->query(
        'SELECT id, username, email, full_name, is_active, last_login_at, created_at
         FROM admins
         ORDER BY id ASC'
    )->fetchAll();
}

function admin_create_user(string $fullName, string $email, string $username, string $password, string $confirm): array
{
    require_once __DIR__ . '/../config/database.php';

    $fullName = trim($fullName);
    $email    = strtolower(trim($email));
    $username = trim($username);

    if ($fullName === '') {
        return ['success' => false, 'error' => 'Please enter a full name.'];
    }

    $emailError = validate_email_address($email);
    if ($emailError !== null) {
        return ['success' => false, 'error' => $emailError];
    }

    if ($username === '' || strlen($username) > 50 || !preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        return ['success' => false, 'error' => 'Username must be 1–50 characters (letters, numbers, . _ - only).'];
    }

    $passwordError = validate_password_pair($password, $confirm);
    if ($passwordError !== null) {
        return ['success' => false, 'error' => $passwordError];
    }

    $dup = db()->prepare(
        'SELECT id FROM admins WHERE LOWER(email) = :email OR username = :username LIMIT 1'
    );
    $dup->execute(['email' => $email, 'username' => $username]);
    if ($dup->fetch()) {
        return ['success' => false, 'error' => 'That email or username is already in use.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    db()->prepare(
        'INSERT INTO admins (username, email, password_hash, full_name, is_active)
         VALUES (:username, :email, :hash, :full_name, 1)'
    )->execute([
        'username'  => $username,
        'email'     => $email,
        'hash'      => $hash,
        'full_name' => $fullName,
    ]);

    return ['success' => true, 'id' => (int) db()->lastInsertId()];
}

require_once __DIR__ . '/mail.php';

function auth_form_data(): array
{
    start_session();
    $old = $_SESSION['auth_old'] ?? [];
    unset($_SESSION['auth_old']);

    return is_array($old) ? $old : [];
}

function flash_set(string $key, string $message): void
{
    start_session();
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    start_session();

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}
