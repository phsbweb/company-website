<?php

function attendanceIsHttps(): bool
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

function attendanceStartSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'path' => '/',
        'secure' => attendanceIsHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function attendanceLog(string $message, array $context = []): void
{
    $payload = [
        'message' => $message,
        'session_id' => session_id(),
        'has_session_user' => isset($_SESSION['user_id']),
        'has_device_cookie' => isset($_COOKIE['device_token']),
        'host' => $_SERVER['HTTP_HOST'] ?? null,
        'uri' => $_SERVER['REQUEST_URI'] ?? null,
    ];

    if ($context) {
        $payload['context'] = $context;
    }

    error_log('[attendance] ' . json_encode($payload));
}

function attendanceCookieSecret(): string
{
    $secret = getenv('ATTENDANCE_AUTH_SECRET')
        ?: getenv('APP_KEY')
        ?: getenv('DB_PASS')
        ?: 'phsb-attendance-fallback-secret';

    return hash('sha256', $secret);
}

function attendanceCookieOptions(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => attendanceIsHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function attendanceIssueAuthCookie(array $user): bool
{
    $payload = [
        'id' => (int) ($user['id'] ?? 0),
        'full_name' => (string) ($user['full_name'] ?? ''),
        'exp' => time() + (86400 * 30),
    ];

    $json = json_encode($payload);
    $b64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $b64, attendanceCookieSecret());
    $token = $b64 . '.' . $sig;

    return setcookie('attendance_auth', $token, attendanceCookieOptions($payload['exp']));
}

function attendanceClearAuthCookie(): void
{
    setcookie('attendance_auth', '', attendanceCookieOptions(time() - 3600));
}

function attendanceReadAuthCookie(): ?array
{
    $token = $_COOKIE['attendance_auth'] ?? '';
    if (!$token || !str_contains($token, '.')) {
        return null;
    }

    [$b64, $sig] = explode('.', $token, 2);
    $expected = hash_hmac('sha256', $b64, attendanceCookieSecret());
    if (!hash_equals($expected, $sig)) {
        return null;
    }

    $json = base64_decode(strtr($b64, '-_', '+/'), true);
    if ($json === false) {
        return null;
    }

    $payload = json_decode($json, true);
    if (!is_array($payload) || empty($payload['id']) || empty($payload['full_name']) || empty($payload['exp'])) {
        return null;
    }

    if ((int) $payload['exp'] < time()) {
        return null;
    }

    return $payload;
}

function attendanceRestoreSessionFromCookie(): bool
{
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['full_name'])) {
        return true;
    }

    $payload = attendanceReadAuthCookie();
    if (!$payload) {
        return false;
    }

    $_SESSION['user_id'] = (int) $payload['id'];
    $_SESSION['full_name'] = (string) $payload['full_name'];

    attendanceLog('Rehydrated user session from signed auth cookie', [
        'user_id' => $_SESSION['user_id'],
    ]);

    return true;
}

function attendanceClearDeviceTokenCookie(): void
{
    setcookie('device_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => attendanceIsHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function attendanceSetDeviceTokenCookie(string $token): bool
{
    return setcookie('device_token', $token, [
        'expires' => time() + (86400 * 30),
        'path' => '/',
        'secure' => attendanceIsHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
