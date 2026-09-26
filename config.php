<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . DIRECTORY_SEPARATOR . 'layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'banking-catalog.php';
ob_start('site_layout_output_filter');

$hostingerConfig = __DIR__ . DIRECTORY_SEPARATOR . 'hostinger-config.php';
if (is_file($hostingerConfig)) require_once $hostingerConfig;

const APP_BRAND_NAME = 'FullMockTestSeries.com';
const APP_BRAND_TAGLINE = 'Practice Today | Score Tomorrow';
const APP_CANONICAL_URL = 'https://fullmocktestseries.com';
const USER_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'users.json';
const SERIES_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'series.json';
const COUPON_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'coupons.json';
const SUBSCRIPTION_PLAN_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'subscription-plans.json';
const SUBSCRIPTION_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'subscriptions.json';
const CONTACT_MESSAGE_FILE = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'ranksetu-private' . DIRECTORY_SEPARATOR . 'contact-messages.json';
const SERIES_IMAGE_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'series';

// Database configuration (optional). If USE_DB=1 and DB_DSN is set, PDO MySQL will be used.
function db_dsn(): string
{
    return (string) (getenv('DB_DSN') ?: (defined('HOSTINGER_DB_DSN') ? HOSTINGER_DB_DSN : ''));
}

function db_user(): string
{
    return (string) (getenv('DB_USER') ?: (defined('HOSTINGER_DB_USER') ? HOSTINGER_DB_USER : ''));
}

function db_pass(): string
{
    return (string) (getenv('DB_PASS') ?: (defined('HOSTINGER_DB_PASS') ? HOSTINGER_DB_PASS : ''));
}

function db_enabled(): bool
{
    return ((string) (getenv('USE_DB') ?: (defined('HOSTINGER_USE_DB') ? HOSTINGER_USE_DB : ''))) === '1' && db_dsn() !== '';
}

function app_base_url(): string
{
    $configured = (string) (getenv('APP_URL') ?: (defined('HOSTINGER_APP_URL') ? HOSTINGER_APP_URL : ''));
    return rtrim($configured !== '' ? $configured : 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
}

function mail_from_address(): string
{
    return (string) (getenv('MAIL_FROM') ?: (defined('HOSTINGER_MAIL_FROM') ? HOSTINGER_MAIL_FROM : ''));
}

function mail_from_name(): string
{
    return (string) (getenv('MAIL_FROM_NAME') ?: (defined('HOSTINGER_MAIL_FROM_NAME') ? HOSTINGER_MAIL_FROM_NAME : APP_BRAND_NAME));
}

function send_password_reset_email(string $email, string $link): bool
{
    $from = mail_from_address();
    if (!filter_var($from, FILTER_VALIDATE_EMAIL) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log('Password reset email skipped: MAIL_FROM is not configured.');
        return false;
    }

    $subject = 'Reset your FullMockTestSeries.com password';
    $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
    $body = "<p>We received a request to reset your FullMockTestSeries.com password.</p>"
        . "<p><a href=\"{$safeLink}\">Reset your password</a></p>"
        . '<p>This link expires in one hour. If you did not request this, you can ignore this email.</p>';
    $encodedName = function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader(mail_from_name(), 'UTF-8') : mail_from_name();
    $headers = [
        'From: ' . $encodedName . ' <' . $from . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
    ];

    return mail($email, $subject, $body, implode("\r\n", $headers));
}

function db_datetime(?string $value = null): string
{
    $timestamp = $value !== null ? strtotime($value) : false;
    return $timestamp === false ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', $timestamp);
}

/** @var PDO|null */
function db_pdo(): ?PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    if (!db_enabled()) return null;
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO(db_dsn(), db_user(), db_pass(), $options);
        return $pdo;
    } catch (Throwable $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        throw new RuntimeException('Database connection failed. Check DB_DSN, DB_USER, DB_PASS and the MySQL service.', 0, $e);
    }
}

function store_contact_message(array $message): void
{
    $record = [
        'id' => (string) ($message['id'] ?? bin2hex(random_bytes(16))),
        'name' => (string) ($message['name'] ?? ''),
        'email' => (string) ($message['email'] ?? ''),
        'subject' => (string) ($message['subject'] ?? ''),
        'message' => (string) ($message['message'] ?? ''),
        'created_at' => db_datetime(isset($message['created_at']) ? (string) $message['created_at'] : null),
    ];

    $pdo = db_pdo();
    if ($pdo !== null) {
        $stmt = $pdo->prepare('INSERT INTO contact_messages (id,name,email,subject,message,created_at) VALUES (:id,:name,:email,:subject,:message,:created_at)');
        $stmt->execute([
            ':id' => $record['id'],
            ':name' => $record['name'],
            ':email' => $record['email'],
            ':subject' => $record['subject'],
            ':message' => $record['message'],
            ':created_at' => $record['created_at'],
        ]);
        return;
    }

    $directory = dirname(CONTACT_MESSAGE_FILE);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create private contact-message storage.');
    }
    $handle = @fopen(CONTACT_MESSAGE_FILE, 'c+');
    if ($handle === false) throw new RuntimeException('Unable to open private contact-message storage.');
    try {
        @chmod(CONTACT_MESSAGE_FILE, 0600);
        if (!flock($handle, LOCK_EX)) throw new RuntimeException('Unable to lock contact-message storage.');
        rewind($handle);
        $contents = stream_get_contents($handle);
        if ($contents === false) throw new RuntimeException('Unable to read contact-message storage.');
        try {
            $messages = $contents === '' ? [] : json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Contact-message storage contains invalid JSON.', 0, $exception);
        }
        if (!is_array($messages)) {
            throw new RuntimeException('Contact-message storage has an invalid format.');
        }
        if ($messages !== [] && array_keys($messages) !== range(0, count($messages) - 1)) throw new RuntimeException('Contact-message storage has an invalid format.');
        $messages[] = $record;
        try {
            $json = json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode the contact message.', 0, $exception);
        }
        rewind($handle);
        if (!ftruncate($handle, 0)) throw new RuntimeException('Unable to update contact-message storage.');
        $offset = 0;
        $length = strlen($json);
        while ($offset < $length) {
            $written = fwrite($handle, substr($json, $offset));
            if ($written === false || $written === 0) throw new RuntimeException('Unable to write contact-message storage.');
            $offset += $written;
        }
        if (!fflush($handle)) throw new RuntimeException('Unable to flush contact-message storage.');
        if (function_exists('fsync')) fsync($handle);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }
}

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
    $pdo = db_pdo();
    if ($pdo !== null) {
        $stmt = $pdo->query('SELECT * FROM coupons');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $emails = $pdo->query('SELECT coupon_code,email FROM coupon_allowed_emails')->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
        foreach ($rows as &$row) {
            $row['active'] = (bool) $row['active'];
            $row['emails'] = $emails[$row['code']] ?? [];
        }
        return is_array($rows) ? $rows : [];
    }

    if (!file_exists(COUPON_FILE)) file_put_contents(COUPON_FILE, "[]\n", LOCK_EX);
    $data = json_decode((string) file_get_contents(COUPON_FILE), true);
    return is_array($data) ? $data : [];
}

function save_coupons(array $coupons): void
{
    $pdo = db_pdo();
    if ($pdo !== null) {
        $pdo->beginTransaction();
        try {
            $existingCodes = $pdo->query('SELECT code FROM coupons')->fetchAll(PDO::FETCH_COLUMN);
            $incomingCodes = array_map(static fn(array $coupon): string => (string) ($coupon['code'] ?? ''), $coupons);
            $delete = $pdo->prepare('DELETE FROM coupons WHERE code=:code');
            foreach (array_diff($existingCodes, $incomingCodes) as $code) $delete->execute([':code' => $code]);
            $insert = $pdo->prepare('INSERT INTO coupons (code,type,value,product,active,expires_at,max_uses,used) VALUES (:code,:type,:value,:product,:active,:expires_at,:max_uses,:used) ON DUPLICATE KEY UPDATE type=VALUES(type),value=VALUES(value),product=VALUES(product),active=VALUES(active),expires_at=VALUES(expires_at),max_uses=VALUES(max_uses),used=VALUES(used)');
            $deleteEmails = $pdo->prepare('DELETE FROM coupon_allowed_emails WHERE coupon_code=:code');
            $insertEmail = $pdo->prepare('INSERT INTO coupon_allowed_emails (coupon_code,email) VALUES (:code,:email)');
            foreach ($coupons as $c) {
                $insert->execute([
                    ':code' => $c['code'] ?? '',
                    ':type' => $c['type'] ?? 'percent',
                    ':value' => (int) ($c['value'] ?? 0),
                    ':product' => $c['product'] ?? null,
                    ':active' => !empty($c['active']) ? 1 : 0,
                    ':expires_at' => !empty($c['expires_at']) ? db_datetime($c['expires_at']) : null,
                    ':max_uses' => isset($c['max_uses']) ? (int) $c['max_uses'] : null,
                    ':used' => (int) ($c['used'] ?? 0),
                ]);
                $deleteEmails->execute([':code' => $c['code']]);
                foreach ((array) ($c['emails'] ?? []) as $email) $insertEmail->execute([':code' => $c['code'], ':email' => strtolower(trim($email))]);
            }
            $pdo->commit();
            return;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

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

function subscription_plans(bool $activeOnly = false): array
{
    $pdo = db_pdo();
    if ($pdo !== null) {
        $sql = 'SELECT * FROM subscription_plans' . ($activeOnly ? ' WHERE active=1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW())' : '') . ' ORDER BY display_order, created_at';
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$plan) {
            $plan['active'] = (bool) $plan['active'];
            $plan['featured'] = (bool) $plan['featured'];
            $plan['all_access'] = (bool) $plan['all_access'];
            $plan['benefits'] = json_decode((string) $plan['benefits'], true) ?: [];
            $plan['covered_groups'] = json_decode((string) $plan['covered_groups'], true) ?: [];
            $covered = $pdo->prepare('SELECT s.slug FROM subscription_plan_courses pc JOIN series s ON s.id=pc.series_id WHERE pc.plan_id=:plan_id');
            $covered->execute([':plan_id' => $plan['id']]);
            $plan['covered_courses'] = $covered->fetchAll(PDO::FETCH_COLUMN);
        }
        return $rows;
    }
    if (!file_exists(SUBSCRIPTION_PLAN_FILE)) file_put_contents(SUBSCRIPTION_PLAN_FILE, "[]\n", LOCK_EX);
    $plans = json_decode((string) file_get_contents(SUBSCRIPTION_PLAN_FILE), true);
    if (!is_array($plans)) return [];
    return $activeOnly ? array_values(array_filter($plans, static fn(array $plan): bool => !empty($plan['active']) && (empty($plan['starts_at']) || strtotime((string) $plan['starts_at']) <= time()) && (empty($plan['ends_at']) || strtotime((string) $plan['ends_at']) >= time()))) : $plans;
}

function save_subscription_plans(array $plans): void
{
    $pdo = db_pdo();
    if ($pdo !== null) {
        $pdo->beginTransaction();
        try {
            $save = $pdo->prepare('INSERT INTO subscription_plans (id,name,slug,description,price_paise,original_price_paise,currency,duration,duration_unit,active,featured,display_order,all_access,max_enrollments,starts_at,ends_at,benefits,covered_groups,terms,created_at) VALUES (:id,:name,:slug,:description,:price,:original,:currency,:duration,:unit,:active,:featured,:order,:all_access,:max_enrollments,:starts_at,:ends_at,:benefits,:groups,:terms,:created_at) ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),price_paise=VALUES(price_paise),original_price_paise=VALUES(original_price_paise),currency=VALUES(currency),duration=VALUES(duration),duration_unit=VALUES(duration_unit),active=VALUES(active),featured=VALUES(featured),display_order=VALUES(display_order),all_access=VALUES(all_access),max_enrollments=VALUES(max_enrollments),starts_at=VALUES(starts_at),ends_at=VALUES(ends_at),benefits=VALUES(benefits),covered_groups=VALUES(covered_groups),terms=VALUES(terms)');
            $deleteCourses = $pdo->prepare('DELETE FROM subscription_plan_courses WHERE plan_id=:plan_id');
            $addCourse = $pdo->prepare('INSERT IGNORE INTO subscription_plan_courses (plan_id,series_id) SELECT :plan_id,id FROM series WHERE slug=:slug');
            foreach ($plans as $plan) {
                $save->execute([':id'=>$plan['id'],':name'=>$plan['name'],':slug'=>$plan['slug'],':description'=>$plan['description'] ?? '',':price'=>max(0,(int)$plan['price_paise']),':original'=>max(0,(int)($plan['original_price_paise'] ?? 0)),':currency'=>$plan['currency'] ?? 'INR',':duration'=>max(1,(int)$plan['duration']),':unit'=>$plan['duration_unit'] ?? 'day',':active'=>!empty($plan['active'])?1:0,':featured'=>!empty($plan['featured'])?1:0,':order'=>(int)($plan['display_order']??0),':all_access'=>!empty($plan['all_access'])?1:0,':max_enrollments'=>isset($plan['max_enrollments'])&&$plan['max_enrollments']!==''?(int)$plan['max_enrollments']:null,':starts_at'=>!empty($plan['starts_at'])?db_datetime($plan['starts_at']):null,':ends_at'=>!empty($plan['ends_at'])?db_datetime($plan['ends_at']):null,':benefits'=>json_encode(array_values((array)($plan['benefits']??[])),JSON_THROW_ON_ERROR),':groups'=>json_encode(array_values((array)($plan['covered_groups']??[])),JSON_THROW_ON_ERROR),':terms'=>$plan['terms']??'',':created_at'=>db_datetime($plan['created_at']??null)]);
                $deleteCourses->execute([':plan_id'=>$plan['id']]);
                foreach (array_unique((array)($plan['covered_courses']??[])) as $slug) $addCourse->execute([':plan_id'=>$plan['id'],':slug'=>$slug]);
            }
            $pdo->commit(); return;
        } catch (Throwable $exception) { $pdo->rollBack(); throw $exception; }
    }
    $json = json_encode(array_values($plans), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    file_put_contents(SUBSCRIPTION_PLAN_FILE, $json, LOCK_EX);
}

function user_subscriptions(string $userId): array
{
    $pdo = db_pdo();
    if ($pdo !== null) {
        $stmt = $pdo->prepare('SELECT s.*, p.name AS plan_name, p.slug AS plan_slug, p.benefits, p.covered_groups, p.all_access FROM subscriptions s JOIN subscription_plans p ON p.id=s.plan_id WHERE s.user_id=:user_id ORDER BY s.created_at DESC');
        $stmt->execute([':user_id'=>$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $courses = $pdo->prepare('SELECT pc.plan_id, s.slug FROM subscription_plan_courses pc JOIN series s ON s.id=pc.series_id JOIN subscriptions sub ON sub.plan_id=pc.plan_id WHERE sub.user_id=:user_id');
        $courses->execute([':user_id'=>$userId]);
        $byPlan = [];
        foreach ($courses->fetchAll(PDO::FETCH_ASSOC) as $course) $byPlan[(string)$course['plan_id']][] = $course['slug'];
        foreach ($rows as &$row) $row['covered_courses'] = $byPlan[(string)$row['plan_id']] ?? [];
        return $rows;
    }
    $all = file_exists(SUBSCRIPTION_FILE) ? json_decode((string)file_get_contents(SUBSCRIPTION_FILE), true) : [];
    return is_array($all) ? array_values(array_filter($all, static fn(array $row): bool => ($row['user_id'] ?? '') === $userId)) : [];
}

function create_subscription_record(array $subscription): void
{
    $pdo = db_pdo();
    if ($pdo !== null) {
        $stmt = $pdo->prepare('INSERT INTO subscriptions (id,user_id,plan_id,status,start_at,expires_at,amount_paise,discount_paise,tax_paise,final_amount_paise,coupon_code,order_id,payment_id,created_at) VALUES (:id,:user_id,:plan_id,:status,:start_at,:expires_at,:amount,:discount,:tax,:final,:coupon,:order_id,:payment_id,:created_at)');
        $stmt->execute([':id'=>$subscription['id'],':user_id'=>$subscription['user_id'],':plan_id'=>$subscription['plan_id'],':status'=>$subscription['status'],':start_at'=>$subscription['start_at'],':expires_at'=>$subscription['expires_at'],':amount'=>$subscription['amount_paise'],':discount'=>$subscription['discount_paise']??0,':tax'=>$subscription['tax_paise']??0,':final'=>$subscription['final_amount_paise'],':coupon'=>$subscription['coupon_code']??null,':order_id'=>$subscription['order_id']??null,':payment_id'=>$subscription['payment_id']??null,':created_at'=>$subscription['created_at']]);
        return;
    }
    $all = file_exists(SUBSCRIPTION_FILE) ? json_decode((string)file_get_contents(SUBSCRIPTION_FILE), true) : [];
    if (!is_array($all)) $all = [];
    foreach ($all as $saved) if (($saved['order_id'] ?? null) !== null && ($saved['order_id'] ?? null) === ($subscription['order_id'] ?? null)) return;
    $all[] = $subscription;
    file_put_contents(SUBSCRIPTION_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), LOCK_EX);
}

function enroll_user_in_course(array $user, string $courseSlug, string $source = 'free', ?string $subscriptionId = null): array
{
    $enrolled = array_values(array_unique(array_map('strval', (array)($user['enrolled'] ?? []))));
    if (!in_array($courseSlug, $enrolled, true)) $enrolled[] = $courseSlug;
    $sources = (array)($user['enrollment_sources'] ?? []);
    if (!isset($sources[$courseSlug])) $sources[$courseSlug] = ['source'=>$source,'subscription_id'=>$subscriptionId,'enrolled_at'=>date('c')];
    $updated = update_current_user(['enrolled'=>$enrolled,'enrollment_sources'=>$sources]);
    $pdo = db_pdo();
    if ($pdo !== null) {
        $series = $pdo->prepare('SELECT id FROM series WHERE slug=:slug');
        $series->execute([':slug'=>$courseSlug]);
        $seriesId = $series->fetchColumn();
        if ($seriesId !== false) {
            $insert = $pdo->prepare('INSERT IGNORE INTO enrollments (user_id,series_id,source,enrollment_source,subscription_id,enrolled_at) VALUES (:user_id,:series_id,:source,:enrollment_source,:subscription_id,:enrolled_at)');
            $insert->execute([':user_id'=>$user['id'],':series_id'=>$seriesId,':source'=>$source === 'subscription' ? 'paid' : $source,':enrollment_source'=>$source,':subscription_id'=>$subscriptionId,':enrolled_at'=>date('Y-m-d H:i:s')]);
        }
    }
    return $updated;
}

function user_has_course_access(array $user, string $courseSlug): bool
{
    $revoked = (array) ($user['revoked_courses'] ?? []);
    if (!empty($revoked[$courseSlug])) return false;
    return in_array($courseSlug, (array) ($user['enrolled'] ?? []), true);
}

function admin_set_course_access(string $userId, string $courseSlug, bool $active, string $adminId, string $reason = ''): void
{
    $reason = trim(substr($reason, 0, 500));
    $pdo = db_pdo();
    if ($pdo !== null) {
        $series = $pdo->prepare('SELECT id FROM series WHERE slug=:slug');
        $series->execute([':slug' => $courseSlug]);
        $seriesId = $series->fetchColumn();
        if ($seriesId === false) throw new RuntimeException('Course was not found.');
        $update = $pdo->prepare('UPDATE enrollments SET access_status=:status,deactivated_at=:deactivated_at,deactivated_by=:deactivated_by,deactivation_reason=:reason WHERE user_id=:user_id AND series_id=:series_id');
        $update->execute([':status' => $active ? 'active' : 'revoked', ':deactivated_at' => $active ? null : date('Y-m-d H:i:s'), ':deactivated_by' => $active ? null : $adminId, ':reason' => $active ? null : $reason, ':user_id' => $userId, ':series_id' => $seriesId]);
        if ($update->rowCount() === 0 && !$active) {
            $insert = $pdo->prepare('INSERT INTO enrollments (user_id,series_id,source,enrollment_source,subscription_id,access_status,deactivated_at,deactivated_by,deactivation_reason,enrolled_at) VALUES (:user_id,:series_id,\'admin\',\'admin\',NULL,\'revoked\',:deactivated_at,:deactivated_by,:reason,:enrolled_at)');
            $insert->execute([':user_id' => $userId, ':series_id' => $seriesId, ':deactivated_at' => date('Y-m-d H:i:s'), ':deactivated_by' => $adminId, ':reason' => $reason, ':enrolled_at' => date('Y-m-d H:i:s')]);
        } elseif ($update->rowCount() === 0) throw new RuntimeException('This user has no recorded access to that course.');
        return;
    }
    $allUsers = users();
    $found = false;
    foreach ($allUsers as &$user) {
        if (($user['id'] ?? '') !== $userId) continue;
        $found = true;
        $revoked = (array) ($user['revoked_courses'] ?? []);
        if ($active) unset($revoked[$courseSlug]);
        else $revoked[$courseSlug] = ['deactivated_at' => date('c'), 'deactivated_by' => $adminId, 'reason' => $reason];
        $user['revoked_courses'] = $revoked;
        break;
    }
    unset($user);
    if (!$found) throw new RuntimeException('User was not found.');
    save_users($allUsers);
}

function admin_set_subscription_status(string $userId, bool $active, string $adminId, string $reason = ''): void
{
    $reason = trim(substr($reason, 0, 500));
    $pdo = db_pdo();
    if ($pdo !== null) {
        $stmt = $pdo->prepare('UPDATE subscriptions SET status=:status,updated_at=NOW() WHERE user_id=:user_id AND status=:current_status');
        $stmt->execute([':status' => $active ? 'ACTIVE' : 'SUSPENDED', ':current_status' => $active ? 'SUSPENDED' : 'ACTIVE', ':user_id' => $userId]);
        if ($stmt->rowCount() === 0) throw new RuntimeException('No matching subscription status was found.');
        return;
    }
    $file = file_exists(SUBSCRIPTION_FILE) ? json_decode((string) file_get_contents(SUBSCRIPTION_FILE), true) : [];
    if (!is_array($file)) $file = [];
    $changed = false;
    foreach ($file as &$subscription) {
        if (($subscription['user_id'] ?? '') !== $userId || ($subscription['status'] ?? '') !== ($active ? 'SUSPENDED' : 'ACTIVE')) continue;
        $subscription['status'] = $active ? 'ACTIVE' : 'SUSPENDED';
        $subscription['status_changed_at'] = date('c');
        $subscription['status_changed_by'] = $adminId;
        $subscription['status_change_reason'] = $reason;
        $changed = true;
    }
    unset($subscription);
    if (!$changed) throw new RuntimeException('No matching subscription status was found.');
    file_put_contents(SUBSCRIPTION_FILE, json_encode($file, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), LOCK_EX);
}

function admin_deactivate_user_account(string $userId, string $adminId, string $reason = ''): void
{
    if ($userId === $adminId) throw new RuntimeException('You cannot deactivate your own administrator account.');
    $reason = trim(substr($reason, 0, 500));
    $anonymousEmail = 'deleted-' . preg_replace('/[^a-zA-Z0-9]/', '', $userId) . '@invalid.local';
    $pdo = db_pdo();
    if ($pdo !== null) {
        $pdo->beginTransaction();
        try {
            $updateUser = $pdo->prepare('UPDATE users SET name=:name,email=:email,password=:password,active=0 WHERE id=:id AND role <> \'admin\'');
            $updateUser->execute([':name' => 'Deleted user', ':email' => $anonymousEmail, ':password' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), ':id' => $userId]);
            if ($updateUser->rowCount() === 0) throw new RuntimeException('Student account was not found or is an administrator.');
            $pdo->prepare('UPDATE enrollments SET access_status=\'revoked\',deactivated_at=NOW(),deactivated_by=:admin_id,deactivation_reason=:reason WHERE user_id=:user_id')->execute([':admin_id' => $adminId, ':reason' => $reason !== '' ? $reason : 'User account deactivated', ':user_id' => $userId]);
            $pdo->prepare('UPDATE subscriptions SET status=\'SUSPENDED\',updated_at=NOW() WHERE user_id=:user_id AND status=\'ACTIVE\'')->execute([':user_id' => $userId]);
            $pdo->commit();
            return;
        } catch (Throwable $exception) { $pdo->rollBack(); throw $exception; }
    }
    $allUsers = users();
    $found = false;
    foreach ($allUsers as &$user) {
        if (($user['id'] ?? '') !== $userId || (($user['role'] ?? 'student') === 'admin')) continue;
        $found = true;
        $revoked = (array) ($user['revoked_courses'] ?? []);
        foreach ((array) ($user['enrolled'] ?? []) as $slug) $revoked[$slug] = ['deactivated_at' => date('c'), 'deactivated_by' => $adminId, 'reason' => $reason !== '' ? $reason : 'User account deactivated'];
        $user['revoked_courses'] = $revoked;
        $user['enrolled'] = [];
        $user['active'] = false;
        $user['name'] = 'Deleted user';
        $user['email'] = $anonymousEmail;
        $user['password'] = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $user['deleted_at'] = date('c');
        $user['deleted_by'] = $adminId;
        break;
    }
    unset($user);
    if (!$found) throw new RuntimeException('Student account was not found or is an administrator.');
    save_users($allUsers);
    $subscriptions = file_exists(SUBSCRIPTION_FILE) ? json_decode((string) file_get_contents(SUBSCRIPTION_FILE), true) : [];
    if (is_array($subscriptions)) {
        foreach ($subscriptions as &$subscription) if (($subscription['user_id'] ?? '') === $userId && ($subscription['status'] ?? '') === 'ACTIVE') { $subscription['status'] = 'SUSPENDED'; $subscription['status_changed_at'] = date('c'); $subscription['status_changed_by'] = $adminId; }
        unset($subscription);
        file_put_contents(SUBSCRIPTION_FILE, json_encode($subscriptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), LOCK_EX);
    }
}

function admin_course_control_rows(): array
{
    $rows = [];
    foreach (users() as $user) {
        $attemptsByCourse = [];
        foreach ((array) ($user['attempts'] ?? []) as $attempt) {
            $slug = (string) ($attempt['product'] ?? '');
            if ($slug === '') continue;
            if (!isset($attemptsByCourse[$slug])) $attemptsByCourse[$slug] = ['count' => 0, 'total' => 0, 'last' => null];
            $attemptsByCourse[$slug]['count']++;
            $attemptsByCourse[$slug]['total'] += (int) ($attempt['percentage'] ?? 0);
            $attemptsByCourse[$slug]['last'] = $attempt['submitted_at'] ?? $attemptsByCourse[$slug]['last'];
        }
        $subscriptions = user_subscriptions((string) ($user['id'] ?? ''));
        $premium = null;
        foreach ($subscriptions as $subscription) if (($subscription['status'] ?? '') === 'ACTIVE' && (empty($subscription['expires_at']) || strtotime((string) $subscription['expires_at']) > time())) { $premium = $subscription; break; }
        $courses = [];
        foreach (array_unique(array_merge((array) ($user['enrolled'] ?? []), array_keys((array) ($user['revoked_courses'] ?? [])), array_keys($attemptsByCourse))) as $slug) {
            $stats = $attemptsByCourse[$slug] ?? ['count' => 0, 'total' => 0, 'last' => null];
            $courses[] = ['slug' => $slug, 'active' => user_has_course_access($user, $slug), 'attempts' => $stats['count'], 'average' => $stats['count'] ? (int) round($stats['total'] / $stats['count']) : 0, 'last' => $stats['last']];
        }
        $rows[] = ['id' => (string) ($user['id'] ?? ''), 'name' => (string) ($user['name'] ?? 'Student'), 'email' => (string) ($user['email'] ?? ''), 'active' => !empty($user['active']), 'premium' => $premium, 'courses' => $courses, 'orders' => count((array) ($user['orders'] ?? []))];
    }
    return $rows;
}

function course_entitlement(?array $user, string $courseSlug): array
{
    if ($user === null) return ['eligible'=>false,'source'=>'guest','price_paise'=>null,'message'=>'Please log in to continue.'];
    if (!empty(((array) ($user['revoked_courses'] ?? []))[$courseSlug])) return ['eligible'=>false,'source'=>'revoked','price_paise'=>null,'message'=>'Course access was disabled by an administrator.'];
    if (in_array($courseSlug, (array)($user['enrolled'] ?? []), true)) return ['eligible'=>true,'source'=>'owned','price_paise'=>0,'message'=>'You already have access to this course.'];
    $catalog = published_banking_catalog();
    $group = (string)($catalog[$courseSlug]['group'] ?? '');
    foreach (user_subscriptions((string)$user['id']) as $subscription) {
        $status = (string)($subscription['status'] ?? '');
        $expires = strtotime((string)($subscription['expires_at'] ?? ''));
        if ($status !== 'ACTIVE' || ($expires !== false && $expires <= time())) continue;
        $coveredCourses = (array)($subscription['covered_courses'] ?? []);
        $coveredGroups = is_string($subscription['covered_groups'] ?? null) ? (json_decode($subscription['covered_groups'], true) ?: []) : (array)($subscription['covered_groups'] ?? []);
        $allAccess = !empty($subscription['all_access']);
        if ($allAccess || in_array($courseSlug, $coveredCourses, true) || in_array($group, $coveredGroups, true)) return ['eligible'=>true,'source'=>'subscription','price_paise'=>0,'subscription_id'=>$subscription['id'],'message'=>'Included with your active subscription.'];
    }
    return ['eligible'=>false,'source'=>'paid','price_paise'=>isset($catalog[$courseSlug]['price_paise'])?(int)$catalog[$courseSlug]['price_paise']:null,'message'=>''];
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
    $pdo = db_pdo();
    if ($pdo !== null) {
        $stmt = $pdo->query('SELECT * FROM series');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['active'] = (bool) $row['active'];
            $row['group'] = (string) ($row['parent_group'] ?? 'Other exams');
        }
        return is_array($rows) ? $rows : [];
    }

    ensure_series_store();
    $decoded = json_decode((string) file_get_contents(SERIES_FILE), true);
    return is_array($decoded) ? $decoded : [];
}

function save_series_records(array $series): void
{
    $pdo = db_pdo();
    if ($pdo !== null) {
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare('INSERT INTO series (id,slug,title,stage,description,parent_group,price_paise,image_path,active,created_at) VALUES (:id,:slug,:title,:stage,:description,:parent_group,:price_paise,:image_path,:active,:created_at) ON DUPLICATE KEY UPDATE title=VALUES(title),stage=VALUES(stage),description=VALUES(description),parent_group=VALUES(parent_group),price_paise=VALUES(price_paise),image_path=VALUES(image_path),active=VALUES(active)');
            foreach ($series as $s) {
                $insert->execute([
                    ':id' => $s['id'] ?? bin2hex(random_bytes(6)),
                    ':slug' => $s['slug'] ?? '',
                    ':title' => $s['title'] ?? '',
                    ':stage' => $s['stage'] ?? 'Practice',
                    ':description' => $s['description'] ?? '',
                    ':parent_group' => $s['group'] ?? 'Other exams',
                    ':price_paise' => max(0, (int) ($s['price_paise'] ?? 25000)),
                    ':image_path' => $s['image_path'] ?? null,
                    ':active' => !empty($s['active']) ? 1 : 0,
                    ':created_at' => db_datetime($s['created_at'] ?? null),
                ]);
            }
            $pdo->commit();
            return;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    ensure_series_store();
    $temporaryFile = SERIES_FILE . '.tmp';
    $json = json_encode(array_values($series), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($temporaryFile, $json, LOCK_EX) === false || !rename($temporaryFile, SERIES_FILE)) {
        @unlink($temporaryFile);
        throw new RuntimeException('Unable to save series data.');
    }
}

function delete_series_image(?string $imagePath): void
{
    if ($imagePath === null || $imagePath === '') return;
    $fullPath = __DIR__ . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagePath), DIRECTORY_SEPARATOR);
    $root = realpath(SERIES_IMAGE_DIR);
    $target = realpath($fullPath);
    if ($root !== false && $target !== false && str_starts_with($target, $root . DIRECTORY_SEPARATOR)) @unlink($target);
}

function save_series_image_upload(array $file, string $slug, ?string $previousPath = null): ?string
{
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError === UPLOAD_ERR_NO_FILE) return $previousPath;
    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) throw new RuntimeException('The image exceeds the upload size limit configured by the server.');
    if ($uploadError !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) throw new RuntimeException('The image upload failed. Please try again.');
    $temporaryPath = (string) $file['tmp_name'];
    $actualSize = filesize($temporaryPath);
    if ($actualSize === false || $actualSize === 0) throw new RuntimeException('The uploaded image is empty or unreadable.');
    if ($actualSize > 3 * 1024 * 1024) throw new RuntimeException('Series images must be 3 MB or smaller.');
    $imageInfo = @getimagesize($temporaryPath);
    if ($imageInfo === false) throw new RuntimeException('The uploaded file is not a valid image.');
    $mime = (string) ($imageInfo['mime'] ?? '');
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Use a JPG, PNG or WebP image for the series.');
    if (class_exists('finfo') && (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath) !== $mime) throw new RuntimeException('The uploaded file is not a valid image.');
    $width = (int) ($imageInfo[0] ?? 0);
    $height = (int) ($imageInfo[1] ?? 0);
    if ($width < 1 || $height < 1 || $width > 8000 || $height > 8000 || $width * $height > 25000000) throw new RuntimeException('Use an image no larger than 8,000 pixels per side or 25 megapixels.');
    if (!is_dir(SERIES_IMAGE_DIR) && !mkdir(SERIES_IMAGE_DIR, 0750, true) && !is_dir(SERIES_IMAGE_DIR)) throw new RuntimeException('Unable to create the series image folder.');
    $filename = slugify($slug) . '-' . bin2hex(random_bytes(5)) . '.' . $extensions[$mime];
    $destination = SERIES_IMAGE_DIR . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($temporaryPath, $destination)) throw new RuntimeException('Unable to save the series image.');
    return 'uploads/series/' . $filename;
}

function price_to_paise(string $value): int
{
    $value = trim($value);
    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) throw new RuntimeException('Enter a valid price with up to two decimal places.');
    $paise = (int) round((float) $value * 100);
    if ($paise > 100000000) throw new RuntimeException('Price cannot exceed ₹1,000,000.');
    return $paise;
}

function published_banking_catalog(): array
{
    $catalog = banking_catalog();
    foreach ($catalog as &$definition) $definition['price_paise'] = 25000;
    unset($definition);
    foreach (series_records() as $record) {
        $recordSlug = (string) ($record['slug'] ?? '');
        if ($recordSlug === '') continue;
        if (array_key_exists('active', $record) && empty($record['active'])) {
            unset($catalog[$recordSlug]);
            continue;
        }
        if (isset($catalog[$recordSlug])) {
            $catalog[$recordSlug] = array_merge($catalog[$recordSlug], array_filter([
                'title' => $record['title'] ?? null,
                'stage' => $record['stage'] ?? null,
                'description' => $record['description'] ?? null,
                'group' => $record['group'] ?? null,
                'price_paise' => isset($record['price_paise']) ? max(0, (int) $record['price_paise']) : null,
                'image_path' => $record['image_path'] ?? null,
            ], static fn($value): bool => $value !== null));
            continue;
        }
        if (!isset($catalog[$recordSlug])) {
            $catalog[$recordSlug] = [
                'title' => (string) ($record['title'] ?? $recordSlug),
                'stage' => (string) ($record['stage'] ?? 'Practice'),
                'description' => (string) ($record['description'] ?? ''),
                'price_paise' => max(0, (int) ($record['price_paise'] ?? 25000)),
                'group' => (string) ($record['group'] ?? 'Other exams'),
                'image_path' => $record['image_path'] ?? null,
            ];
        }
    }
    return $catalog;
}

function delete_series_content(string $slug): void
{
    $pdo = db_pdo();
    if ($pdo !== null) {
        $pdo->beginTransaction();
        try {
            $series = $pdo->prepare('UPDATE series SET active=0 WHERE slug=:slug');
            $series->execute([':slug' => $slug]);
            $tests = $pdo->prepare('UPDATE tests t JOIN series s ON s.id=t.series_id SET t.active=0 WHERE s.slug=:slug');
            $tests->execute([':slug' => $slug]);
            $pdo->commit();
            return;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    $folder = __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . $slug;
    foreach (glob($folder . DIRECTORY_SEPARATOR . 'test-*.json') ?: [] as $file) @unlink($file);
    if (is_dir($folder)) @rmdir($folder);
    foreach (glob(SERIES_IMAGE_DIR . DIRECTORY_SEPARATOR . slugify($slug) . '-*') ?: [] as $file) @unlink($file);
}

function test_records(string $slug): array
{
    $pdo = db_pdo();
    if ($pdo === null) {
        $records = [];
        foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'test-*.json') ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (is_array($data)) $records[pathinfo($file, PATHINFO_FILENAME)] = $data;
        }
        uksort($records, static fn(string $a, string $b): int => (int) substr($a, 5) <=> (int) substr($b, 5));
        if ($records === [] && isset(published_banking_catalog()[$slug])) $records = banking_test_sets($slug);
        return $records;
    }

    $stmt = $pdo->prepare('SELECT t.id,t.test_key,t.title,t.duration_minutes,q.id AS question_id,q.position,q.question_text,q.topic,q.correct_option,o.position AS option_position,o.option_text FROM tests t JOIN series s ON s.id=t.series_id LEFT JOIN questions q ON q.test_id=t.id LEFT JOIN question_options o ON o.question_id=q.id WHERE s.slug=:slug AND t.active=1 ORDER BY t.test_key,q.position,o.position');
    $stmt->execute([':slug' => $slug]);
    $tests = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $key = $row['test_key'];
        if (!isset($tests[$key])) $tests[$key] = ['title' => $row['title'], 'duration_minutes' => (int) $row['duration_minutes'], 'questions' => []];
        if ($row['question_id'] === null) continue;
        $position = (int) $row['position'];
        if (!isset($tests[$key]['questions'][$position])) $tests[$key]['questions'][$position] = ['q' => $row['question_text'], 'topic' => $row['topic'], 'answer' => (int) $row['correct_option'], 'options' => []];
        if ($row['option_position'] !== null) $tests[$key]['questions'][$position]['options'][(int) $row['option_position']] = $row['option_text'];
    }
    foreach ($tests as &$test) $test['questions'] = array_values($test['questions']);
    if ($tests === [] && isset(published_banking_catalog()[$slug])) $tests = banking_test_sets($slug);
    return $tests;
}

function save_test_record(string $slug, string $testKey, array $test): void
{
    $pdo = db_pdo();
    if ($pdo === null) return;
    $series = $pdo->prepare('SELECT id FROM series WHERE slug=:slug');
    $series->execute([':slug' => $slug]);
    $seriesId = $series->fetchColumn();
    if ($seriesId === false && isset(banking_catalog()[$slug])) {
        $definition = banking_catalog()[$slug];
        $seriesId = 'builtin-' . substr(hash('sha256', $slug), 0, 16);
        $createSeries = $pdo->prepare('INSERT INTO series (id,slug,title,stage,description,active,created_at) VALUES (:id,:slug,:title,:stage,:description,1,:created_at)');
        $createSeries->execute([':id' => $seriesId, ':slug' => $slug, ':title' => $definition['title'], ':stage' => $definition['stage'], ':description' => $definition['description'], ':created_at' => date('Y-m-d H:i:s')]);
    }
    if ($seriesId === false) throw new RuntimeException('Series is not configured in the database.');
    $testId = 'test-' . substr(hash('sha256', $slug . ':' . $testKey), 0, 20);
    $pdo->beginTransaction();
    try {
        $save = $pdo->prepare('INSERT INTO tests (id,series_id,test_key,title,duration_minutes,active,created_at) VALUES (:id,:series_id,:test_key,:title,:duration,1,:created_at) ON DUPLICATE KEY UPDATE title=VALUES(title),duration_minutes=VALUES(duration_minutes),active=1');
        $save->execute([':id' => $testId, ':series_id' => $seriesId, ':test_key' => $testKey, ':title' => $test['title'] ?? $testKey, ':duration' => max(1, (int) ($test['duration_minutes'] ?? 10)), ':created_at' => date('Y-m-d H:i:s')]);
        $pdo->prepare('DELETE qo FROM question_options qo JOIN questions q ON q.id=qo.question_id WHERE q.test_id=:test_id')->execute([':test_id' => $testId]);
        $pdo->prepare('DELETE FROM questions WHERE test_id=:test_id')->execute([':test_id' => $testId]);
        $question = $pdo->prepare('INSERT INTO questions (id,test_id,position,question_text,topic,correct_option) VALUES (:id,:test_id,:position,:text,:topic,:answer)');
        $option = $pdo->prepare('INSERT INTO question_options (question_id,position,option_text) VALUES (:question_id,:position,:text)');
        foreach ((array) ($test['questions'] ?? []) as $position => $row) {
            $questionId = 'question-' . substr(hash('sha256', $slug . ':' . $testKey . ':' . $position), 0, 20);
            $question->execute([':id' => $questionId, ':test_id' => $testId, ':position' => $position, ':text' => $row['q'] ?? '', ':topic' => $row['topic'] ?? '', ':answer' => (int) ($row['answer'] ?? 0)]);
            foreach ((array) ($row['options'] ?? []) as $optionPosition => $text) $option->execute([':question_id' => $questionId, ':position' => $optionPosition, ':text' => $text]);
        }
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
}

function delete_test_record(string $slug, string $testKey): void
{
    $pdo = db_pdo();
    if ($pdo === null) return;
    $stmt = $pdo->prepare('DELETE t FROM tests t JOIN series s ON s.id=t.series_id WHERE s.slug=:slug AND t.test_key=:test_key');
    $stmt->execute([':slug' => $slug, ':test_key' => $testKey]);
}

function test_record(string $slug, string $testKey): ?array
{
    if ($slug === 'sample') {
        $sample = test_records('sbi-po')['test-01'] ?? null;
        if (is_array($sample)) $sample['title'] = APP_BRAND_NAME . ' Free Sample Mock';
        return $sample;
    }
    $tests = test_records($slug);
    return $tests[$testKey] ?? null;
}

function save_attempt_record(array $user, string $slug, string $testKey, array $attempt): void
{
    $pdo = db_pdo();
    if ($pdo === null) return;
    $series = $pdo->prepare('SELECT id FROM series WHERE slug=:slug');
    $series->execute([':slug' => $slug]);
    $seriesId = $series->fetchColumn();
    $test = $pdo->prepare('SELECT id FROM tests WHERE series_id=:series_id AND test_key=:test_key');
    $test->execute([':series_id' => $seriesId, ':test_key' => $testKey]);
    $testId = $test->fetchColumn();
    if ($seriesId === false || $testId === false) throw new RuntimeException('Test is not configured in the database.');
    $insert = $pdo->prepare('INSERT INTO attempts (id,user_id,series_id,test_id,score,total,percentage,time_taken_seconds,timed_out,submitted_at) VALUES (:id,:user_id,:series_id,:test_id,:score,:total,:percentage,:time_taken,:timed_out,:submitted_at)');
    $insert->execute([':id' => $attempt['id'], ':user_id' => $user['id'], ':series_id' => $seriesId, ':test_id' => $testId, ':score' => $attempt['score'], ':total' => $attempt['total'], ':percentage' => $attempt['percentage'], ':time_taken' => $attempt['time_taken'], ':timed_out' => !empty($attempt['timed_out']) ? 1 : 0, ':submitted_at' => db_datetime($attempt['submitted_at'])]);
    $questions = $pdo->prepare('SELECT id,position FROM questions WHERE test_id=:test_id ORDER BY position');
    $questions->execute([':test_id' => $testId]);
    $answer = $pdo->prepare('INSERT INTO attempt_answers (attempt_id,question_id,answer_option,is_correct) VALUES (:attempt_id,:question_id,:answer_option,:is_correct)');
        $testData = test_record($slug, $testKey);
        if (!is_array($testData)) throw new RuntimeException('Test questions are not available.');
    foreach ($questions->fetchAll(PDO::FETCH_ASSOC) as $question) {
        $position = (int) $question['position'];
        $selected = $attempt['answers'][$position] ?? null;
            $correct = (int) ($testData['questions'][$position]['answer'] ?? -1);
        $answer->execute([':attempt_id' => $attempt['id'], ':question_id' => $question['id'], ':answer_option' => $selected, ':is_correct' => $selected !== null && (int) $selected === $correct ? 1 : 0]);
    }
}

function claim_guest_attempts(array $user): void
{
    if (!db_enabled() || empty($_SESSION['guest_attempts']) || !is_array($_SESSION['guest_attempts'])) return;
    foreach ($_SESSION['guest_attempts'] as $attempt) {
        save_attempt_record($user, (string) ($attempt['product'] ?? 'sbi-po'), (string) ($attempt['test_id'] ?? 'test-01'), $attempt);
    }
    unset($_SESSION['guest_attempts']);
}

function users(): array
{
    $pdo = db_pdo();
    if ($pdo !== null) {
        $stmt = $pdo->query('SELECT * FROM users');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) $r = hydrate_db_user($pdo, $r);
        return $rows;
    }

    ensure_user_store();
    $contents = file_get_contents(USER_FILE);
    $decoded = json_decode($contents ?: '[]', true);
    return is_array($decoded) ? $decoded : [];
}

function hydrate_db_user(PDO $pdo, array $user): array
{
    $enrollment = $pdo->prepare('SELECT s.slug,e.access_status,e.deactivated_at,e.deactivated_by,e.deactivation_reason FROM enrollments e JOIN series s ON s.id = e.series_id WHERE e.user_id = :user_id');
    $enrollment->execute([':user_id' => $user['id']]);
    $user['enrolled'] = [];
    $user['revoked_courses'] = [];
    foreach ($enrollment->fetchAll(PDO::FETCH_ASSOC) as $savedEnrollment) {
        if (($savedEnrollment['access_status'] ?? 'active') === 'revoked') $user['revoked_courses'][$savedEnrollment['slug']] = $savedEnrollment;
        else $user['enrolled'][] = $savedEnrollment['slug'];
    }

    $orders = $pdo->prepare('SELECT o.*, c.code AS coupon_code FROM orders o LEFT JOIN coupons c ON c.code = o.coupon_code WHERE o.user_id = :user_id ORDER BY o.created_at DESC');
    $orders->execute([':user_id' => $user['id']]);
    $user['orders'] = [];
    foreach ($orders->fetchAll(PDO::FETCH_ASSOC) as $order) {
        $user['orders'][] = [
            'id' => $order['id'], 'plan' => $order['plan'], 'product' => $order['plan'],
            'amount' => number_format(((int) $order['amount_paise']) / 100, 2, '.', ''),
            'original_amount' => number_format(((int) $order['original_amount_paise']) / 100, 2, '.', ''),
            'coupon' => $order['coupon_code'], 'discount' => number_format(((int) $order['discount_paise']) / 100, 2, '.', ''),
            'currency' => $order['currency'], 'status' => $order['status'], 'provider' => $order['provider'], 'created_at' => $order['created_at'],
        ];
    }

    $attempts = $pdo->prepare('SELECT a.*, s.slug AS product, t.title FROM attempts a JOIN series s ON s.id = a.series_id JOIN tests t ON t.id = a.test_id WHERE a.user_id = :user_id ORDER BY a.submitted_at DESC');
    $attempts->execute([':user_id' => $user['id']]);
    $user['attempts'] = [];
    foreach ($attempts->fetchAll(PDO::FETCH_ASSOC) as $attempt) {
        $answerStmt = $pdo->prepare('SELECT question_id, answer_option FROM attempt_answers WHERE attempt_id = :attempt_id ORDER BY question_id');
        $answerStmt->execute([':attempt_id' => $attempt['id']]);
        $user['attempts'][] = [
            'id' => $attempt['id'], 'product' => $attempt['product'], 'test_id' => $attempt['test_id'], 'title' => $attempt['title'],
            'score' => (int) $attempt['score'], 'total' => (int) $attempt['total'], 'percentage' => (int) $attempt['percentage'],
            'answers' => array_map(static fn(array $row): ?int => $row['answer_option'] === null ? null : (int) $row['answer_option'], $answerStmt->fetchAll(PDO::FETCH_ASSOC)),
            'time_taken' => (int) $attempt['time_taken_seconds'], 'timed_out' => (bool) $attempt['timed_out'], 'submitted_at' => $attempt['submitted_at'],
        ];
    }
    $user['active'] = (bool) $user['active'];
    return $user;
}

function sync_user_relational_data(PDO $pdo, array $user): void
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') return;

    if (array_key_exists('enrolled', $user)) {
        $lookup = $pdo->prepare('SELECT id FROM series WHERE slug = :slug');
        $insert = $pdo->prepare('INSERT IGNORE INTO enrollments (user_id,series_id,source,enrolled_at) VALUES (:user_id,:series_id,:source,:enrolled_at)');
        foreach (array_unique((array) $user['enrolled']) as $slug) {
            $lookup->execute([':slug' => $slug]);
            $seriesId = $lookup->fetchColumn();
            if ($seriesId !== false) $insert->execute([':user_id' => $userId, ':series_id' => $seriesId, ':source' => 'free', ':enrolled_at' => date('Y-m-d H:i:s')]);
        }
    }

    if (array_key_exists('orders', $user)) {
        $lookup = $pdo->prepare('SELECT id FROM series WHERE slug = :slug');
        $order = $pdo->prepare('INSERT IGNORE INTO orders (id,user_id,plan,amount_paise,original_amount_paise,currency,status,provider,coupon_code,discount_paise,created_at) VALUES (:id,:user_id,:plan,:amount,:original_amount,:currency,:status,:provider,:coupon,:discount,:created_at)');
        $item = $pdo->prepare('INSERT IGNORE INTO order_items (order_id,series_id,amount_paise) VALUES (:order_id,:series_id,:amount)');
        foreach ((array) $user['orders'] as $saved) {
            $product = (string) ($saved['product'] ?? $saved['plan'] ?? '');
            $amount = (int) round(((float) ($saved['amount'] ?? 0)) * 100);
            $original = (int) round(((float) ($saved['original_amount'] ?? $saved['amount'] ?? 0)) * 100);
            $discount = (int) round(((float) ($saved['discount'] ?? 0)) * 100);
            $order->execute([':id' => $saved['id'] ?? bin2hex(random_bytes(8)), ':user_id' => $userId, ':plan' => $product, ':amount' => $amount, ':original_amount' => $original, ':currency' => $saved['currency'] ?? 'INR', ':status' => $saved['status'] ?? 'paid', ':provider' => $saved['provider'] ?? 'demo', ':coupon' => $saved['coupon'] ?? null, ':discount' => $discount, ':created_at' => db_datetime($saved['created_at'] ?? null)]);
            if ($product !== 'all-access') {
                $lookup->execute([':slug' => $product]);
                $seriesId = $lookup->fetchColumn();
                if ($seriesId !== false) {
                    $item->execute([':order_id' => $saved['id'], ':series_id' => $seriesId, ':amount' => $amount]);
                }
            }
        }
    }
}

function save_users(array $users): void
{
    $pdo = db_pdo();
    if ($pdo !== null) {
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare('INSERT INTO users (id,name,email,password,created_at,role,active) VALUES (:id,:name,:email,:password,:created_at,:role,:active) ON DUPLICATE KEY UPDATE name=VALUES(name),email=VALUES(email),password=VALUES(password),role=VALUES(role),active=VALUES(active)');
            foreach ($users as $u) {
                $insert->execute([
                    ':id' => $u['id'] ?? bin2hex(random_bytes(8)),
                    ':name' => $u['name'] ?? '',
                    ':email' => $u['email'] ?? '',
                    ':password' => $u['password'] ?? '',
                    ':created_at' => $u['created_at'] ?? date('c'),
                    ':role' => $u['role'] ?? 'student',
                    ':active' => !empty($u['active']) ? 1 : 0,
                ]);
                sync_user_relational_data($pdo, $u);
            }
            $pdo->commit();
            return;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

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

function set_user_active(string $userId, bool $active): void
{
    $pdo = db_pdo();
    if ($pdo !== null) {
        $stmt = $pdo->prepare('UPDATE users SET active=:active WHERE id=:id');
        $stmt->execute([':active' => $active ? 1 : 0, ':id' => $userId]);
        return;
    }

    $allUsers = users();
    foreach ($allUsers as &$user) {
        if (($user['id'] ?? '') === $userId) $user['active'] = $active;
    }
    unset($user);
    save_users($allUsers);
}

function update_current_user(array $changes): array
{
    $sessionUser = current_user();
    if ($sessionUser === null) {
        return [];
    }

    $pdo = db_pdo();
    if ($pdo !== null) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $sessionUser['id']]);
        $stored = $stmt->fetch(PDO::FETCH_ASSOC) ?: $sessionUser;
        $updated = array_merge(hydrate_db_user($pdo, $stored), $changes);
        $save = $pdo->prepare('UPDATE users SET name=:name,email=:email,role=:role,active=:active WHERE id=:id');
        $save->execute([':name' => $updated['name'], ':email' => $updated['email'], ':role' => $updated['role'] ?? 'student', ':active' => !empty($updated['active']) ? 1 : 0, ':id' => $updated['id']]);
        sync_user_relational_data($pdo, $updated);
        $updated = hydrate_db_user($pdo, $stored);
        unset($updated['password']);
        $updated = array_merge($updated, $changes);
        $_SESSION['user'] = $updated;
        return $updated;
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

function create_password_reset(PDO $pdo, string $userId, string $token, int $expires): void
{
    $stmt = $pdo->prepare('INSERT INTO password_reset_tokens (token_hash,user_id,expires_at) VALUES (:token_hash,:user_id,:expires_at)');
    $stmt->execute([':token_hash' => hash('sha256', $token), ':user_id' => $userId, ':expires_at' => date('Y-m-d H:i:s', $expires)]);
}

function find_password_reset(PDO $pdo, string $token): ?array
{
    $stmt = $pdo->prepare('SELECT u.id,u.email,r.expires_at FROM password_reset_tokens r JOIN users u ON u.id=r.user_id WHERE r.token_hash=:token_hash AND r.used_at IS NULL AND r.expires_at > NOW()');
    $stmt->execute([':token_hash' => hash('sha256', $token)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function complete_password_reset(PDO $pdo, string $token, string $userId, string $password): void
{
    $pdo->beginTransaction();
    try {
        $user = $pdo->prepare('UPDATE users SET password=:password WHERE id=:id');
        $user->execute([':password' => password_hash($password, PASSWORD_DEFAULT), ':id' => $userId]);
        $reset = $pdo->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE token_hash=:token_hash AND user_id=:user_id AND used_at IS NULL');
        $reset->execute([':token_hash' => hash('sha256', $token), ':user_id' => $userId]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function current_user(): ?array
{
    if (!isset($_SESSION['user']['id']) || !is_string($_SESSION['user']['id'])) {
        return null;
    }

    $sessionId = $_SESSION['user']['id'];
    foreach (users() as $storedUser) {
        if (($storedUser['id'] ?? '') === $sessionId) {
            if (empty($storedUser['active'])) {
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
        $next = basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'blog-submit.php' ? 'blog-submit.php' : 'student.php';
        header('Location: auth.php?next=' . rawurlencode($next));
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
    if (in_array($next, ['student.php', 'checkout.php', 'blog-submit.php'], true)) {
        return $next;
    }

    if (preg_match('/^product\.php\?product=([a-z0-9-]+)$/', $next, $matches) === 1 && isset(banking_catalog()[$matches[1]])) {
        return $next;
    }

    return 'student.php';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
