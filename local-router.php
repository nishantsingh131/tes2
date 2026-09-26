<?php

declare(strict_types=1);

$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
$requestPath = str_replace('\\', '/', $requestPath);
if (preg_match('~(?:^|/)(?:data|\.git)(?:/|$)|(?:^|/)\.~i', $requestPath) === 1) {
    http_response_code(404);
    require __DIR__ . DIRECTORY_SEPARATOR . '404.php';
    return true;
}

if ($requestPath === '/') return false;

$root = realpath(__DIR__);
$candidate = realpath(__DIR__ . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $requestPath), DIRECTORY_SEPARATOR));
if ($root !== false && $candidate !== false && str_starts_with($candidate, $root . DIRECTORY_SEPARATOR) && (is_file($candidate) || is_dir($candidate))) return false;

http_response_code(404);
require __DIR__ . DIRECTORY_SEPARATOR . '404.php';
return true;