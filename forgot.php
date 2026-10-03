<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

$notice = (string) ($_GET['notice'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $all = users();
        $found = false;
        foreach ($all as &$u) {
            if (strtolower((string) ($u['email'] ?? '')) === $email) {
                $found = true;
                $token = bin2hex(random_bytes(16));
                $expires = time() + 3600; // 1 hour
                if (db_enabled()) {
                    create_password_reset(db_pdo(), $u['id'], $token, $expires);
                } else {
                    $u['reset_token'] = $token;
                    $u['reset_expires'] = date('c', $expires);
                    save_users($all);
                }

                $link = app_base_url() . '/reset.php?token=' . rawurlencode($token);
                send_password_reset_email($email, $link);
                $notice = 'If this email exists, a password reset link has been sent. Check your inbox.';
                break;
            }
        }
        unset($u);
        if (!$found) {
            // still show the same notice to avoid email enumeration
            $notice = 'If this email exists, a password reset link has been sent. Check your inbox.';
        }
    }
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot password | RankSetu</title>
<style>:root{--navy:#16233f;--maroon:#9c3b2e;--paper:#efece2;--card:#f7f5ec;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24}body{margin:0;min-height:100vh;background:var(--paper);color:var(--ink);font-family:Arial,sans-serif;display:flex;flex-direction:column}main{width:min(520px,calc(100% - 40px));margin:56px auto 0;background:var(--card);border:1px solid var(--line);padding:34px;box-shadow:0 18px 40px rgba(22,35,63,.12)}h1{font:600 2.2rem Georgia,serif;color:var(--navy);margin:0 0 10px}p{color:var(--muted);line-height:1.5}label{display:block;font-size:.85rem;font-weight:700;margin:20px 0 7px}input{width:100%;box-sizing:border-box;padding:12px;border:1px solid var(--line);background:#fffdf7;font:inherit}button{margin-top:22px;padding:13px 18px;background:var(--maroon);color:#fff;border:0;font-weight:700;cursor:pointer}.notice{background:#e9f7ef;border:1px solid #bfe6c8;padding:10px 12px;color:#0b6b2a}.error{background:#f7ded8;border:1px solid #c97968;padding:10px 12px;color:#70251c}main a{color:var(--maroon);font-weight:700}</style>
<style>
html,body{min-width:0;max-width:100%;overflow-x:hidden}
main{width:min(520px,calc(100% - 32px));margin:clamp(24px,8vh,56px) auto 24px;padding:clamp(20px,6vw,34px)}
h1{font-size:2rem;line-height:1.15;overflow-wrap:anywhere}
form,input,button{max-width:100%}
input{min-height:48px}
button{width:100%;min-height:48px}
.notice,.error{line-height:1.5;overflow-wrap:anywhere}
</style>
</head>
<body>
<main>
  <h1>Forgot password</h1>
    <p>Enter your account email and we will send a secure password reset link if the account exists.</p>
  <?php if ($notice !== ''): ?><p class="notice"><?= e($notice) ?></p><?php endif; ?>
  <?php if ($error !== ''): ?><div style="background:#f7ded8;border:1px solid #c97968;padding:10px;margin-bottom:8px;color:#70251c"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <label for="email">Email address</label>
    <input id="email" name="email" type="email" required autocomplete="email">
    <button type="submit">Send reset link</button>
  </form>
  <p style="margin-top:18px"><a href="auth.php">Back to login</a></p>
</main>
</body>
</html>
