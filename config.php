<?php

declare(strict_types=1);

session_start();

const USER_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'users.json';
const SERIES_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'series.json';
const COUPON_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'coupons.json';

function razorpay_key_id(): string
{
    return (string) (getenv('RAZORPAY_KEY_ID') ?: '');
}

function razorpay_key_secret(): string
{
    return (string) (getenv('RAZORPAY_KEY_SECRET') ?: '');
}

function payment_mode(): string
{
    return strtolower((string) (getenv('PAYMENT_MODE') ?: 'demo'));
}

function coupons(): array
{
    if (!file_exists(COUPON_FILE)) file_put_contents(COUPON_FILE, "[]\n", LOCK_EX);
    $data = json_decode((string) file_get_contents(COUPON_FILE), true);
    return is_array($data) ? $data : [];
}

function save_coupons(array $coupons): void
{
    file_put_contents(COUPON_FILE, json_encode(array_values($coupons), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function valid_coupon(string $code, array $user, string $product, int $amount): ?array
{
    $email = strtolower((string) ($user['email'] ?? ''));
    foreach (coupons() as $coupon) {
        if (strtoupper((string) ($coupon['code'] ?? '')) !== strtoupper(trim($code)) || empty($coupon['active'])) continue;
        if (!empty($coupon['expires_at']) && strtotime((string) $coupon['expires_at']) < time()) continue;
        if (!empty($coupon['max_uses']) && (int) ($coupon['used'] ?? 0) >= (int) $coupon['max_uses']) continue;
        if (!empty($coupon['product']) && $coupon['product'] !== 'all' && $coupon['product'] !== $product) continue;
        $emails = array_filter(array_map('strtolower', $coupon['emails'] ?? []));
        if ($emails !== [] && !in_array($email, $emails, true)) continue;
        $discount = ($coupon['type'] ?? 'percent') === 'fixed' ? (int) ($coupon['value'] ?? 0) * 100 : (int) round($amount * ((int) ($coupon['value'] ?? 0) / 100));
        $discount = max(0, min($amount, $discount));
        return array_merge($coupon, ['discount_paise' => $discount, 'final_paise' => $amount - $discount]);
    }
    return null;
}

function create_razorpay_order(int $amount, string $receipt): array
{
    if (razorpay_key_id() === '' || razorpay_key_secret() === '') {
        throw new RuntimeException('Razorpay is not configured. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET before accepting payments.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL is required for Razorpay order creation.');
    }
    $curl = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => razorpay_key_id() . ':' . razorpay_key_secret(),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['amount' => $amount, 'currency' => 'INR', 'receipt' => $receipt, 'payment_capture' => 1]),
        CURLOPT_TIMEOUT => 15,
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $decoded = json_decode((string) $body, true);
    if ($status < 200 || $status >= 300 || !is_array($decoded) || empty($decoded['id'])) {
        throw new RuntimeException('Razorpay order creation failed. Check your keys and account settings.');
    }
    return $decoded;
}

function verify_razorpay_signature(string $orderId, string $paymentId, string $signature): bool
{
    if (razorpay_key_secret() === '') return false;
    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, razorpay_key_secret());
    return hash_equals($expected, $signature);
}

function ensure_user_store(): void
{
    $directory = dirname(USER_FILE);

    if (!is_dir($directory)) {
        mkdir($directory, 0700, true);
    }

    if (!file_exists(USER_FILE)) {
        file_put_contents(USER_FILE, json_encode([], JSON_PRETTY_PRINT), LOCK_EX);
    }
}

function ensure_series_store(): void
{
    $directory = dirname(SERIES_FILE);
    if (!is_dir($directory)) {
        mkdir($directory, 0700, true);
    }
    if (!file_exists(SERIES_FILE)) {
        file_put_contents(SERIES_FILE, json_encode([], JSON_PRETTY_PRINT), LOCK_EX);
    }
}

function series_records(): array
{
    ensure_series_store();
    $decoded = json_decode((string) file_get_contents(SERIES_FILE), true);
    return is_array($decoded) ? $decoded : [];
}

function save_series_records(array $series): void
{
    ensure_series_store();
    $temporaryFile = SERIES_FILE . '.tmp';
    $json = json_encode(array_values($series), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($temporaryFile, $json, LOCK_EX) === false || !rename($temporaryFile, SERIES_FILE)) {
        @unlink($temporaryFile);
        throw new RuntimeException('Unable to save series data.');
    }
}

function users(): array
{
    ensure_user_store();
    $contents = file_get_contents(USER_FILE);
    $decoded = json_decode($contents ?: '[]', true);
    return is_array($decoded) ? $decoded : [];
}

function save_users(array $users): void
{
    ensure_user_store();
    $temporaryFile = USER_FILE . '.tmp';
    $json = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($temporaryFile, $json, LOCK_EX) === false) {
        throw new RuntimeException('Unable to save user data.');
    }
    if (!rename($temporaryFile, USER_FILE)) {
        @unlink($temporaryFile);
        throw new RuntimeException('Unable to finalize user data.');
    }
}

function update_current_user(array $changes): array
{
    $sessionUser = current_user();
    if ($sessionUser === null) {
        return [];
    }

    $allUsers = users();
    foreach ($allUsers as &$storedUser) {
        if (($storedUser['id'] ?? '') === ($sessionUser['id'] ?? '')) {
            $storedUser = array_merge($storedUser, $changes);
            $sessionUser = array_merge($sessionUser, $changes);
            $_SESSION['user'] = $sessionUser;
            save_users($allUsers);
            return $sessionUser;
        }
    }

    return $sessionUser;
}

function current_user(): ?array
{
    if (!isset($_SESSION['user']['id']) || !is_string($_SESSION['user']['id'])) {
        return null;
    }

    $sessionId = $_SESSION['user']['id'];
    foreach (users() as $storedUser) {
        if (($storedUser['id'] ?? '') === $sessionId) {
            if (($storedUser['active'] ?? true) !== true) {
                unset($_SESSION['user']);
                return null;
            }
            unset($storedUser['password']);
            $_SESSION['user'] = $storedUser;
            return $storedUser;
        }
    }

    return null;
}

function require_login(): array
{
    $user = current_user();

    if ($user === null) {
        header('Location: auth.php?next=student.php');
        exit;
    }

    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if (($user['role'] ?? 'student') !== 'admin') {
        http_response_code(403);
        exit('Administrator access required.');
    }
    return $user;
}

function slugify(string $value): string
{
    $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value), '-'));
    return $slug !== '' ? $slug : 'series-' . bin2hex(random_bytes(3));
}

function safe_next(string $next): string
{
    if (in_array($next, ['student.php', 'checkout.php'], true)) {
        return $next;
    }

    if (preg_match('/^product\.php\?product=(ssc-cgl|ssc-chsl|rrb-ntpc|rrb-group-d|ibps-po|sbi-clerk|bpsc-prelims|state-psc-mains|nda-cds|ctet)$/', $next) === 1) {
        return $next;
    }

    return 'student.php';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
