<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
$user = current_user();
echo json_encode([
    'logged_in' => $user !== null,
    'name' => $user['name'] ?? null,
    'role' => $user['role'] ?? 'student',
]);
