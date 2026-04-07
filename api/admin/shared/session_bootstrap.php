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
