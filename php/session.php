<?php
function startStudentHubSession()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $cookie = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

function clearStudentHubSession()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    $_SESSION = [];
    session_unset();
    $destroyed = session_destroy();

    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax'
        ]);
    }

    return $destroyed;
}

function refreshStudentHubSession()
{
    if (!is_array($_SESSION['student'] ?? null)) {
        return false;
    }

    $lastActivity = $_SESSION['last_activity'] ?? null;
    if (!is_int($lastActivity) || time() - $lastActivity > 1800) {
        if (!clearStudentHubSession()) {
            error_log('Could not destroy an expired StudentHub session.');
        }
        return true;
    }

    $_SESSION['last_activity'] = time();
    return false;
}
