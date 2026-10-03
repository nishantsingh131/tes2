<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$base = dirname(__DIR__);
$hostingerConfig = $base . DIRECTORY_SEPARATOR . 'hostinger-config.php';
if (is_file($hostingerConfig)) require_once $hostingerConfig;

// Usage: php scripts/import_json_to_mysql.php
// Environment variables take precedence over the private Hostinger config.
$dsn = getenv('DB_DSN') ?: (defined('HOSTINGER_DB_DSN') ? HOSTINGER_DB_DSN : 'mysql:host=127.0.0.1;dbname=tes2;charset=utf8mb4');
$user = getenv('DB_USER') ?: (defined('HOSTINGER_DB_USER') ? HOSTINGER_DB_USER : 'root');
$pass = getenv('DB_PASS') ?: (defined('HOSTINGER_DB_PASS') ? HOSTINGER_DB_PASS : '');

function read_json(string $path): array
{
    if (!is_file($path)) return [];
    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) throw new RuntimeException('Invalid JSON: ' . $path);
    return $data;
}

function sql_datetime(?string $value): string
{
    $time = $value === null ? false : strtotime($value);
    return date('Y-m-d H:i:s', $time === false ? time() : $time);
}

require_once $base . '/banking-catalog.php';
$builtIn = [];
foreach (banking_catalog() as $slug => $series) $builtIn[$slug] = [$series['title'], $series['stage'], $series['description']];

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $migration = file_get_contents($base . '/migrations/001_create_tables.sql');
    if ($migration === false) throw new RuntimeException('Migration file not found.');
    $pdo->exec($migration);
    $pdo->beginTransaction();

    $seriesStmt = $pdo->prepare('INSERT INTO series (id,slug,title,stage,description,active,created_at) VALUES (:id,:slug,:title,:stage,:description,:active,:created_at) ON DUPLICATE KEY UPDATE title=VALUES(title),stage=VALUES(stage),description=VALUES(description),active=VALUES(active)');
    $seriesIds = [];
    foreach ($builtIn as $slug => [$title, $stage, $description]) {
        $id = 'builtin-' . substr(hash('sha256', $slug), 0, 16);
        $seriesStmt->execute([':id' => $id, ':slug' => $slug, ':title' => $title, ':stage' => $stage, ':description' => $description, ':active' => 1, ':created_at' => date('Y-m-d H:i:s')]);
        $seriesIds[$slug] = $id;
    }
    $legacySlugs = ['ssc-cgl', 'ssc-chsl', 'rrb-ntpc', 'rrb-group-d', 'bpsc-prelims', 'state-psc-mains', 'nda-cds', 'ctet'];
    $legacyPlaceholders = implode(',', array_fill(0, count($legacySlugs), '?'));
    $pdo->prepare("UPDATE series SET active=0 WHERE slug IN ({$legacyPlaceholders})")->execute($legacySlugs);
    foreach (read_json($base . '/data/series.json') as $record) {
        if (empty($record['slug'])) continue;
        $id = (string) ($record['id'] ?? 'series-' . substr(hash('sha256', $record['slug']), 0, 16));
        $seriesStmt->execute([':id' => $id, ':slug' => $record['slug'], ':title' => $record['title'] ?? $record['slug'], ':stage' => $record['stage'] ?? 'Practice', ':description' => $record['description'] ?? '', ':active' => !empty($record['active']) ? 1 : 0, ':created_at' => sql_datetime($record['created_at'] ?? null)]);
        $seriesIds[$record['slug']] = $id;
    }

    $userStmt = $pdo->prepare('INSERT INTO users (id,name,email,password,role,active,created_at) VALUES (:id,:name,:email,:password,:role,:active,:created_at) ON DUPLICATE KEY UPDATE name=VALUES(name),password=VALUES(password),role=VALUES(role),active=VALUES(active)');
    $enrollStmt = $pdo->prepare('INSERT IGNORE INTO enrollments (user_id,series_id,source,enrolled_at) VALUES (:user_id,:series_id,:source,:enrolled_at)');
    $orderStmt = $pdo->prepare('INSERT INTO orders (id,user_id,plan,amount_paise,original_amount_paise,currency,status,provider,coupon_code,discount_paise,created_at) VALUES (:id,:user_id,:plan,:amount,:original_amount,:currency,:status,:provider,:coupon,:discount,:created_at) ON DUPLICATE KEY UPDATE status=VALUES(status),amount_paise=VALUES(amount_paise)');
    $itemStmt = $pdo->prepare('INSERT IGNORE INTO order_items (order_id,series_id,amount_paise) VALUES (:order_id,:series_id,:amount)');
    $attemptStmt = $pdo->prepare('INSERT IGNORE INTO attempts (id,user_id,series_id,test_id,score,total,percentage,time_taken_seconds,timed_out,submitted_at) VALUES (:id,:user_id,:series_id,:test_id,:score,:total,:percentage,:time_taken,:timed_out,:submitted_at)');
    $answerStmt = $pdo->prepare('INSERT IGNORE INTO attempt_answers (attempt_id,question_id,answer_option,is_correct) VALUES (:attempt_id,:question_id,:answer_option,:is_correct)');
    $users = read_json($base . '/data/users.json');
    foreach ($users as $record) {
        if (empty($record['id']) || empty($record['email'])) continue;
        $userStmt->execute([':id' => $record['id'], ':name' => $record['name'] ?? 'Student', ':email' => strtolower($record['email']), ':password' => $record['password'] ?? password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), ':role' => ($record['role'] ?? 'student') === 'admin' ? 'admin' : 'student', ':active' => !empty($record['active']) ? 1 : 0, ':created_at' => sql_datetime($record['created_at'] ?? null)]);
        foreach (array_unique((array) ($record['enrolled'] ?? [])) as $slug) if (isset($seriesIds[$slug])) $enrollStmt->execute([':user_id' => $record['id'], ':series_id' => $seriesIds[$slug], ':source' => 'free', ':enrolled_at' => sql_datetime($record['created_at'] ?? null)]);
        foreach ((array) ($record['orders'] ?? []) as $saved) {
            if (($saved['provider'] ?? '') === 'demo') continue;
            $product = (string) ($saved['product'] ?? $saved['plan'] ?? '');
            $amount = (int) round(((float) ($saved['amount'] ?? 0)) * 100);
            $original = (int) round(((float) ($saved['original_amount'] ?? $saved['amount'] ?? 0)) * 100);
            $discount = (int) round(((float) ($saved['discount'] ?? 0)) * 100);
            $orderId = (string) ($saved['id'] ?? 'import-' . bin2hex(random_bytes(8)));
            $orderStmt->execute([':id' => $orderId, ':user_id' => $record['id'], ':plan' => $product, ':amount' => $amount, ':original_amount' => $original, ':currency' => $saved['currency'] ?? 'INR', ':status' => $saved['status'] ?? 'paid', ':provider' => $saved['provider'] ?? 'import', ':coupon' => $saved['coupon'] ?? null, ':discount' => $discount, ':created_at' => sql_datetime($saved['created_at'] ?? null)]);
            if (isset($seriesIds[$product])) $itemStmt->execute([':order_id' => $orderId, ':series_id' => $seriesIds[$product], ':amount' => $amount]);
        }
    }

    $testIds = [];
    $testStmt = $pdo->prepare('INSERT INTO tests (id,series_id,test_key,title,duration_minutes,active,created_at) VALUES (:id,:series_id,:test_key,:title,:duration_minutes,:active,:created_at) ON DUPLICATE KEY UPDATE title=VALUES(title),duration_minutes=VALUES(duration_minutes),active=VALUES(active)');
    $questionStmt = $pdo->prepare('INSERT INTO questions (id,test_id,position,question_text,topic,section_title,direction_text,correct_option) VALUES (:id,:test_id,:position,:text,:topic,:section,:direction,:answer) ON DUPLICATE KEY UPDATE question_text=VALUES(question_text),topic=VALUES(topic),section_title=VALUES(section_title),direction_text=VALUES(direction_text),correct_option=VALUES(correct_option)');
    $optionStmt = $pdo->prepare('INSERT INTO question_options (question_id,position,option_text) VALUES (:question_id,:position,:text) ON DUPLICATE KEY UPDATE option_text=VALUES(option_text)');
    foreach (banking_catalog() as $slug => $series) foreach (banking_test_sets($slug, 15, 60) as $testKey => $test) {
        $testId = 'test-' . substr(hash('sha256', $slug . ':' . $testKey), 0, 20);
        $testIds[$slug . ':' . $testKey] = $testId;
        $testStmt->execute([':id' => $testId, ':series_id' => $seriesIds[$slug], ':test_key' => $testKey, ':title' => $test['title'], ':duration_minutes' => $test['duration_minutes'], ':active' => 1, ':created_at' => date('Y-m-d H:i:s')]);
        foreach ($test['questions'] as $position => $question) {
            $questionId = 'question-' . substr(hash('sha256', $slug . ':' . $testKey . ':' . $position), 0, 20);
            $questionStmt->execute([':id' => $questionId, ':test_id' => $testId, ':position' => $position, ':text' => $question['q'], ':topic' => $question['topic'], ':section' => $question['section'] ?? '', ':direction' => $question['direction'] ?? null, ':answer' => $question['answer']]);
            foreach ($question['options'] as $optionPosition => $option) $optionStmt->execute([':question_id' => $questionId, ':position' => $optionPosition, ':text' => $option]);
        }
    }
    foreach (glob($base . '/tests/*/test-*.json') ?: [] as $file) {
        $slug = basename(dirname($file));
        if (!isset($seriesIds[$slug])) continue;
        $test = read_json($file);
        $testKey = pathinfo($file, PATHINFO_FILENAME);
        $testId = 'test-' . substr(hash('sha256', $slug . ':' . $testKey), 0, 20);
        $testIds[$slug . ':' . $testKey] = $testId;
        $testStmt->execute([':id' => $testId, ':series_id' => $seriesIds[$slug], ':test_key' => $testKey, ':title' => $test['title'] ?? strtoupper($testKey), ':duration_minutes' => (int) ($test['duration_minutes'] ?? 10), ':active' => 1, ':created_at' => date('Y-m-d H:i:s')]);
        foreach ((array) ($test['questions'] ?? []) as $position => $question) {
            $questionId = 'question-' . substr(hash('sha256', $slug . ':' . $testKey . ':' . $position), 0, 20);
            $questionStmt->execute([':id' => $questionId, ':test_id' => $testId, ':position' => $position, ':text' => $question['q'] ?? '', ':topic' => $question['topic'] ?? '', ':section' => $question['section'] ?? '', ':direction' => $question['direction'] ?? null, ':answer' => (int) ($question['answer'] ?? 0)]);
            foreach ((array) ($question['options'] ?? []) as $optionPosition => $option) $optionStmt->execute([':question_id' => $questionId, ':position' => $optionPosition, ':text' => $option]);
        }
    }

    foreach ($users as $record) {
        foreach ((array) ($record['attempts'] ?? []) as $savedAttempt) {
            $slug = (string) ($savedAttempt['product'] ?? '');
            $testKey = (string) ($savedAttempt['test_id'] ?? 'test-01');
            $testId = $testIds[$slug . ':' . $testKey] ?? null;
            if ($testId === null || !isset($seriesIds[$slug])) continue;
            $attemptId = (string) ($savedAttempt['id'] ?? 'attempt-' . bin2hex(random_bytes(8)));
            $attemptStmt->execute([':id' => $attemptId, ':user_id' => $record['id'], ':series_id' => $seriesIds[$slug], ':test_id' => $testId, ':score' => (int) ($savedAttempt['score'] ?? 0), ':total' => (int) ($savedAttempt['total'] ?? 0), ':percentage' => (int) ($savedAttempt['percentage'] ?? 0), ':time_taken' => (int) ($savedAttempt['time_taken'] ?? 0), ':timed_out' => !empty($savedAttempt['timed_out']) ? 1 : 0, ':submitted_at' => sql_datetime($savedAttempt['submitted_at'] ?? null)]);
            foreach ((array) ($savedAttempt['answers'] ?? []) as $position => $answer) {
                $questionId = 'question-' . substr(hash('sha256', $slug . ':' . $testKey . ':' . $position), 0, 20);
                $answerStmt->execute([':attempt_id' => $attemptId, ':question_id' => $questionId, ':answer_option' => is_numeric($answer) ? (int) $answer : null, ':is_correct' => 0]);
            }
        }
    }

    $couponStmt = $pdo->prepare('INSERT INTO coupons (code,type,value,product,active,expires_at,max_uses,used) VALUES (:code,:type,:value,:product,:active,:expires_at,:max_uses,:used) ON DUPLICATE KEY UPDATE type=VALUES(type),value=VALUES(value),product=VALUES(product),active=VALUES(active),expires_at=VALUES(expires_at),max_uses=VALUES(max_uses),used=VALUES(used)');
    $emailStmt = $pdo->prepare('INSERT IGNORE INTO coupon_allowed_emails (coupon_code,email) VALUES (:code,:email)');
    foreach ($coupons = read_json($base . '/data/coupons.json') as $coupon) {
        if (empty($coupon['code'])) continue;
        $couponStmt->execute([':code' => strtoupper(trim($coupon['code'])), ':type' => ($coupon['type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent', ':value' => (int) ($coupon['value'] ?? 0), ':product' => $coupon['product'] ?? null, ':active' => !empty($coupon['active']) ? 1 : 0, ':expires_at' => !empty($coupon['expires_at']) ? sql_datetime($coupon['expires_at']) : null, ':max_uses' => isset($coupon['max_uses']) ? (int) $coupon['max_uses'] : null, ':used' => (int) ($coupon['used'] ?? 0)]);
        foreach ((array) ($coupon['emails'] ?? []) as $email) $emailStmt->execute([':code' => strtoupper(trim($coupon['code'])), ':email' => strtolower(trim($email))]);
    }

    $pdo->commit();
    echo "Import complete. Users: " . count($users) . ", series: " . count($seriesIds) . ", tests: " . count($testIds) . ", coupons: " . count($coupons) . PHP_EOL;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'Import failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
