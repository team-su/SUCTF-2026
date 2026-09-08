<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['users']) || !is_array($_SESSION['users'])) {
    $_SESSION['users'] = [];
}

if (!isset($_SESSION['notes']) || !is_array($_SESSION['notes'])) {
    $_SESSION['notes'] = [];
}

initialize_system_admin();

/**
 * @param mixed $value
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * @return array{type: string, message: string}|null
 */
function pop_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    if (!is_array($flash) || !isset($flash['type'], $flash['message'])) {
        return null;
    }

    return [
        'type' => (string) $flash['type'],
        'message' => (string) $flash['message'],
    ];
}

function csrf_token(): string
{
    $token = $_SESSION['_csrf_token'] ?? null;
    if (is_string($token) && $token !== '') {
        return $token;
    }

    try {
        $token = bin2hex(random_bytes(32));
    } catch (Throwable) {
        $token = hash('sha256', (string) microtime(true) . (string) mt_rand());
    }

    $_SESSION['_csrf_token'] = $token;
    return $token;
}

function csrf_is_valid(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    $sessionToken = $_SESSION['_csrf_token'] ?? null;
    if (!is_string($sessionToken) || $sessionToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

function require_csrf_or_redirect(string $redirectPath): void
{
    $token = $_POST['_csrf'] ?? null;
    if (!csrf_is_valid(is_string($token) ? $token : null)) {
        set_flash('error', 'CSRF 校验失败，请重试。');
        redirect($redirectPath);
    }
}

function create_impersonated_session(string $username): string
{
    $currentSessionId = session_id();
    if ($currentSessionId === '') {
        session_start();
        $currentSessionId = session_id();
    }

    session_write_close();

    $originalUseCookies = ini_get('session.use_cookies');
    $originalUseOnlyCookies = ini_get('session.use_only_cookies');

    try {
        $newSessionId = bin2hex(random_bytes(16));
    } catch (Throwable) {
        $newSessionId = hash('sha256', (string) microtime(true) . (string) mt_rand());
    }

    try {
        $csrfToken = bin2hex(random_bytes(32));
    } catch (Throwable) {
        $csrfToken = hash('sha256', (string) microtime(true) . (string) mt_rand());
    }

    try {
        ini_set('session.use_cookies', '0');
        ini_set('session.use_only_cookies', '0');

        session_id($newSessionId);
        session_start();
        $_SESSION = [];
        $_SESSION['auth_user'] = $username;
        $_SESSION['_csrf_token'] = $csrfToken;
        session_write_close();
    } finally {
        ini_set('session.use_cookies', is_string($originalUseCookies) ? $originalUseCookies : '1');
        ini_set('session.use_only_cookies', is_string($originalUseOnlyCookies) ? $originalUseOnlyCookies : '1');
        session_id($currentSessionId);
        session_start();
    }

    return $newSessionId;
}

function current_user(): ?string
{
    $authUser = $_SESSION['auth_user'] ?? null;
    if (!is_string($authUser) || $authUser === '') {
        return null;
    }

    return $authUser;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function system_admin_username(): string
{
    $usernameFile = '/run/su-eznote/admin_user';
    if (is_readable($usernameFile)) {
        $fromFile = trim((string) file_get_contents($usernameFile));
        if ($fromFile !== '') {
            return $fromFile;
        }
    }

    $fromEnv = getenv('SU_EZNOTE_ADMIN_USER');
    if (is_string($fromEnv) && $fromEnv !== '') {
        return $fromEnv;
    }

    $fallback = $_SESSION['_sys_admin_user_fallback'] ?? null;
    if (is_string($fallback) && $fallback !== '') {
        return $fallback;
    }

    try {
        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
    } catch (Throwable) {
        $suffix = substr(hash('sha256', (string) microtime(true) . (string) mt_rand()), 0, 8);
    }

    $generated = 'admin_' . $suffix;
    $_SESSION['_sys_admin_user_fallback'] = $generated;

    return $generated;
}

function system_admin_password(): string
{
    $passwordFile = '/run/su-eznote/admin_password';
    if (is_readable($passwordFile)) {
        $fromFile = trim((string) file_get_contents($passwordFile));
        if ($fromFile !== '') {
            return $fromFile;
        }
    }

    $fromEnv = getenv('SU_EZNOTE_ADMIN_PASSWORD');
    if (is_string($fromEnv) && $fromEnv !== '') {
        return $fromEnv;
    }

    $fallback = $_SESSION['_sys_admin_pass_fallback'] ?? null;
    if (is_string($fallback) && $fallback !== '') {
        return $fallback;
    }

    try {
        $generated = substr(bin2hex(random_bytes(12)), 0, 18);
    } catch (Throwable) {
        $generated = substr(hash('sha256', (string) microtime(true) . (string) mt_rand()), 0, 18);
    }

    $_SESSION['_sys_admin_pass_fallback'] = $generated;

    return $generated;
}

function system_flag_content(): string
{
    $flagPath = getenv('SU_EZNOTE_FLAG_PATH');
    if (!is_string($flagPath) || $flagPath === '') {
        $flagPath = '/opt/su-eznote/flag';
    }

    if (is_readable($flagPath)) {
        $content = trim((string) file_get_contents($flagPath));
        if ($content !== '') {
            return $content;
        }
    }

    return 'flag{missing}';
}

function initialize_system_admin(): void
{
    $adminUser = system_admin_username();
    $adminPassword = system_admin_password();
    $passwordVersion = hash('sha256', $adminPassword);

    if (
        !isset($_SESSION['_sys_admin_password_v']) ||
        !is_string($_SESSION['_sys_admin_password_v']) ||
        $_SESSION['_sys_admin_password_v'] !== $passwordVersion ||
        !isset($_SESSION['_sys_admin_hash']) ||
        !is_string($_SESSION['_sys_admin_hash'])
    ) {
        $_SESSION['_sys_admin_password_v'] = $passwordVersion;
        $_SESSION['_sys_admin_hash'] = password_hash($adminPassword, PASSWORD_DEFAULT);
    }

    $_SESSION['users'][$adminUser] = $_SESSION['_sys_admin_hash'];

    if (!isset($_SESSION['notes'][$adminUser]) || !is_array($_SESSION['notes'][$adminUser])) {
        $_SESSION['notes'][$adminUser] = [];
    }

    if (!isset($_SESSION['_sys_flag_created_at']) || !is_string($_SESSION['_sys_flag_created_at'])) {
        $_SESSION['_sys_flag_created_at'] = date('Y-m-d H:i:s');
    }

    $flagNote = [
        'id' => '__system_flag_note__',
        'title' => 'flag',
        'content' => system_flag_content(),
        'created_at' => $_SESSION['_sys_flag_created_at'],
    ];

    $foundIndex = null;
    foreach ($_SESSION['notes'][$adminUser] as $index => $note) {
        if (is_array($note) && (($note['id'] ?? '') === '__system_flag_note__')) {
            $foundIndex = $index;
            break;
        }
    }

    if ($foundIndex === null) {
        array_unshift($_SESSION['notes'][$adminUser], $flagNote);
        return;
    }

    $_SESSION['notes'][$adminUser][$foundIndex] = $flagNote;
}
