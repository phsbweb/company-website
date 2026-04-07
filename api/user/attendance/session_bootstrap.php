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

