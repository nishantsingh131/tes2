<?php

declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$admin = require_admin();
$userId = trim((string) ($_GET['user'] ?? $_POST['user'] ?? ''));
$target = null;
foreach (users() as $candidate) if (($candidate['id'] ?? '') === $userId) { $target = $candidate; break; }
if ($target === null) { http_response_code(404); exit('User not found.'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'deactivate') {
    try {
        admin_deactivate_user_account($userId, (string) $admin['id'], (string) ($_POST['reason'] ?? 'User account deactivated by administrator'));
        header('Location: admin-course-control.php?notice=' . rawurlencode('User account deactivated, credentials removed and all course access revoked.'));
        exit;
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Deactivate user | Admin</title><style>body{margin:0;background:#efece2;color:#1c1a15;font:16px Arial}.box{width:min(600px,calc(100% - 32px));margin:55px auto;background:#f8f6ee;border:1px solid #cfc7ac;padding:30px}h1{font:600 2.2rem Georgia;color:#16233f}.muted{color:#625e50}.warning{background:#fff3ed;border-left:5px solid #9c3b2e;color:#70251c;padding:15px;margin:20px 0;line-height:1.55}.error{background:#f7ded8;color:#70251c;padding:12px;margin:15px 0}.btn{border:0;background:#70251c;color:#fff;padding:13px 18px;font-weight:700;cursor:pointer}.back{display:block;color:#9c3b2e;margin-top:20px}</style></head><body><main class="box"><h1>Deactivate user account</h1><p><strong><?= e((string)$target['name']) ?></strong><br><span class="muted"><?= e((string)$target['email']) ?></span></p><?php if($error!==''): ?><div class="error"><?= e($error) ?></div><?php endif; ?><div class="warning"><strong>This action removes login credentials.</strong><br>The user will be unable to log in, premium subscriptions will be suspended, and every course enrollment will be revoked. Orders, invoices, payments, submitted attempts and audit history will be preserved and the account will be anonymized rather than physically deleted.</div><form method="post" onsubmit="return confirm('Deactivate this user, remove credentials and revoke every course?')"><input type="hidden" name="user" value="<?= e($userId) ?>"><input type="hidden" name="action" value="deactivate"><input type="hidden" name="reason" value="User account deactivated by administrator"><button class="btn" type="submit">Deactivate user and remove credentials</button></form><a class="back" href="admin-user-detail.php?user=<?= e($userId) ?>">Back to user details</a></main></body></html>
