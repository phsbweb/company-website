<?php

function adminIsHttps(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        return strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
    }

    if (isset($_SERVER['HTTP_X_FORWARDED_SSL'])) {
        return strtolower((string) $_SERVER['HTTP_X_FORWARDED_SSL']) === 'on';
    }

    return false;
}

function adminStartSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'path' => '/',
        'secure' => adminIsHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function adminLog(string $message, array $context = []): void
{
    $payload = [
        'message' => $message,
        'session_id' => session_id(),
        'admin_logged_in' => $_SESSION['admin_logged_in'] ?? false,
        'host' => $_SERVER['HTTP_HOST'] ?? null,
        'uri' => $_SERVER['REQUEST_URI'] ?? null,
    ];

    if ($context) {
        $payload['context'] = $context;
    }

    error_log('[admin-auth] ' . json_encode($payload));
}

function adminCookieSecret(): string
{
    $secret = getenv('ADMIN_AUTH_SECRET')
        ?: getenv('APP_KEY')
        ?: getenv('DB_PASS')
        ?: 'phsb-admin-fallback-secret';

    return hash('sha256', $secret);
}

function adminCookieOptions(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => adminIsHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function adminIssueAuthCookie(array $user): bool
{
    $payload = [
        'id' => (int) ($user['id'] ?? 0),
        'username' => (string) ($user['username'] ?? ''),
        'exp' => time() + (86400 * 30),
    ];

    $json = json_encode($payload);
    $b64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $b64, adminCookieSecret());
    $token = $b64 . '.' . $sig;

    return setcookie('admin_auth', $token, adminCookieOptions($payload['exp']));
}

function adminClearAuthCookie(): void
{
    setcookie('admin_auth', '', adminCookieOptions(time() - 3600));
}

function adminReadAuthCookie(): ?array
{
    $token = $_COOKIE['admin_auth'] ?? '';
    if (!$token || !str_contains($token, '.')) {
        return null;
    }

    [$b64, $sig] = explode('.', $token, 2);
    $expected = hash_hmac('sha256', $b64, adminCookieSecret());
    if (!hash_equals($expected, $sig)) {
        return null;
    }

    $json = base64_decode(strtr($b64, '-_', '+/'), true);
    if ($json === false) {
        return null;
    }

    $payload = json_decode($json, true);
    if (!is_array($payload) || empty($payload['id']) || empty($payload['username']) || empty($payload['exp'])) {
        return null;
    }

    if ((int) $payload['exp'] < time()) {
        return null;
    }

    return $payload;
}

function adminRestoreSessionFromCookie(): bool
{
    if (!empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_user_id'])) {
        return true;
    }

    $payload = adminReadAuthCookie();
    if (!$payload) {
        return false;
    }

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user_id'] = (int) $payload['id'];
    $_SESSION['admin_username'] = (string) $payload['username'];

    adminLog('Rehydrated admin session from auth cookie', [
        'admin_user_id' => $_SESSION['admin_user_id'],
        'username' => $_SESSION['admin_username'],
    ]);

    return true;
}
