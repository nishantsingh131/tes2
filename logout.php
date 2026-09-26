<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

// Clear session data and destroy session cookie robustly
$_SESSION = [];
session_unset();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    $path = $params['path'] ?: '/';
    $domain = $params['domain'] ?: '';
    $secure = !empty($params['secure']);
    $httponly = !empty($params['httponly']);

    // Remove the session cookie (primary) and provide a fallback delete on '/'
    setcookie(session_name(), '', time() - 42000, $path, $domain, $secure, $httponly);
    setcookie(session_name(), '', time() - 42000, '/');
}

session_destroy();

header('Location: index.php');
exit;

