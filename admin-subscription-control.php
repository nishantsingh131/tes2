<?php

declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$admin = require_admin();
$userId = trim((string) ($_GET['user'] ?? $_POST['user'] ?? ''));
$target = null;
foreach (users() as $candidate) if (($candidate['id'] ?? '') === $userId) { $target = $candidate; break; }
if ($target === null) { http_response_code(404); exit('User not found.'); }
$active = null;
foreach (user_subscriptions($userId) as $subscription) if (subscription_is_current($subscription)) { $active = $subscription; break; }
$action = (string) ($_POST['action'] ?? '');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['suspend','restore'], true)) {
    try {
        admin_set_subscription_status($userId, $action === 'restore', (string) $admin['id'], (string) ($_POST['reason'] ?? 'Admin premium status change'));
        header('Location: admin-user-detail.php?user=' . rawurlencode($userId) . '&notice=' . rawurlencode($action === 'restore' ? 'Premium subscription restored.' : 'Premium subscription suspended.'));
        exit;
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Premium status | Admin</title><style>body{margin:0;background:#efece2;color:#1c1a15;font:16px Arial}.box{width:min(560px,calc(100% - 32px));margin:55px auto;background:#f8f6ee;border:1px solid #cfc7ac;padding:28px}h1{font:600 2.2rem Georgia;color:#16233f}.muted{color:#625e50}.warning{background:#fff3ed;border-left:5px solid #9c3b2e;padding:14px;color:#70251c;margin:18px 0}.success{background:#e0f0e6;border-left:5px solid #286447;padding:14px;color:#174b32;margin:18px 0}.btn{border:0;background:#9c3b2e;color:#fff;padding:13px 17px;font-weight:700;cursor:pointer}.restore{background:#286447}.back{display:block;color:#9c3b2e;margin-top:20px}</style></head><body><main class="box"><h1>Premium status</h1><p><strong><?= e((string)$target['name']) ?></strong><br><span class="muted"><?= e((string)$target['email']) ?></span></p><?php if($error!==''): ?><div class="warning"><?= e($error) ?></div><?php endif; ?><?php if($active!==null): ?><div class="warning"><strong>Bank Premium is active.</strong><br>Suspending it removes premium entitlement immediately. Existing orders, invoices, course history and submitted attempts remain preserved.</div><form method="post" onsubmit="return confirm('Suspend this user premium subscription?')"><input type="hidden" name="user" value="<?= e($userId) ?>"><input type="hidden" name="action" value="suspend"><input type="hidden" name="reason" value="Suspended by administrator"><button class="btn" type="submit">Deactivate premium</button></form><?php else: ?><div class="success">No active premium subscription is currently granting access.</div><form method="post"><input type="hidden" name="user" value="<?= e($userId) ?>"><input type="hidden" name="action" value="restore"><input type="hidden" name="reason" value="Restored by administrator"><button class="btn restore" type="submit">Restore premium</button></form><?php endif; ?><a class="back" href="admin-user-detail.php?user=<?= e($userId) ?>">Back to user details</a></main></body></html>
