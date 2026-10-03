<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$notice = '';

if ($token === '') {
    // token may be posted from form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = (string) ($_POST['token'] ?? '');
    }
}

if ($token === '') {
    http_response_code(400);
    header('Location: forgot.php?notice=' . rawurlencode('Open the reset link from your email to continue.'));
    exit;
}

$all = users();
$foundUser = null;
if (db_enabled()) {
    $foundUser = find_password_reset(db_pdo(), $token);
} else {
    foreach ($all as $u) {
        if (!empty($u['reset_token']) && hash_equals((string) $u['reset_token'], $token)) {
            $foundUser = $u;
            break;
        }
    }
}

if ($foundUser === null) {
    http_response_code(404);
    exit('Invalid or expired token.');
}

$expires = db_enabled() ? strtotime((string) ($foundUser['expires_at'] ?? '')) : (!empty($foundUser['reset_expires']) ? strtotime($foundUser['reset_expires']) : 0);
if ($expires < time()) {
    http_response_code(410);
    exit('This reset link has expired.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = (string) ($_POST['password'] ?? '');
    $pw2 = (string) ($_POST['password2'] ?? '');
    if (strlen($pw) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pw !== $pw2) {
        $error = 'Passwords do not match.';
    } else {
        if (db_enabled()) {
            complete_password_reset(db_pdo(), $token, (string) $foundUser['id'], $pw);
            $notice = 'Password updated. You may now log in.';
            header('Location: auth.php?notice=' . rawurlencode($notice));
            exit;
        }
        foreach ($all as &$u) {
            if (($u['id'] ?? '') === ($foundUser['id'] ?? '')) {
                $u['password'] = password_hash($pw, PASSWORD_DEFAULT);
                unset($u['reset_token'], $u['reset_expires']);
                save_users($all);
                $notice = 'Password updated. You may now log in.';
                header('Location: auth.php?notice=' . rawurlencode($notice));
                exit;
            }
        }
        unset($u);
        $error = 'Unable to update password.';
    }
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset password | RankSetu</title>
<style>body{font-family:Arial,Helvetica,sans-serif;background:#f6f6f6;padding:30px}main{max-width:520px;margin:0 auto}label{display:block;margin:14px 0 6px}input{width:100%;padding:10px;border:1px solid #ccc}button{margin-top:14px;padding:10px 14px;background:#16233f;color:#fff;border:0}div.err{background:#f7ded8;border:1px solid #c97968;padding:10px;margin-bottom:8px;color:#70251c}</style>
<style>
html,body{min-width:0;max-width:100%;overflow-x:hidden}
body{min-height:100vh;margin:0;padding:24px}
main{width:100%;max-width:520px;margin:0 auto}
h1{font-size:2rem;line-height:1.15;overflow-wrap:anywhere}
form,input,button{max-width:100%}
input{box-sizing:border-box;min-height:48px}
button{width:100%;min-height:48px;cursor:pointer}
.err{line-height:1.5;overflow-wrap:anywhere}
@media(max-width:380px){body{padding:16px}}
</style>
</head>
<body>
<main>
  <h1>Reset your password</h1>
  <p>Choose a new password for your account.</p>
  <?php if ($error !== ''): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <label for="password">New password</label>
    <input id="password" name="password" type="password" minlength="6" required>
    <label for="password2">Confirm password</label>
    <input id="password2" name="password2" type="password" minlength="6" required>
    <button type="submit">Set new password</button>
  </form>
  <p style="margin-top:18px"><a href="auth.php">Back to login</a></p>
</main>
</body>
</html>
