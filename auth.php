<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

$next = safe_next((string) ($_GET['next'] ?? $_POST['next'] ?? 'student.php'));
if (current_user() !== null) {
    header('Location: ' . $next);
    exit;
}

$mode = ($_GET['mode'] ?? $_POST['mode'] ?? 'login') === 'signup' ? 'signup' : 'login';
$error = '';
$notice = (string) ($_GET['notice'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $allUsers = users();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $error = 'Enter a valid email and a password with at least 6 characters.';
    } elseif ($mode === 'signup') {
        foreach ($allUsers as $existingUser) {
            if (($existingUser['email'] ?? '') === $email) {
                $error = 'An account with this email already exists. Log in instead.';
                break;
            }
        }

        if ($error === '') {
            $user = [
                'id' => bin2hex(random_bytes(8)),
                'name' => $name !== '' ? $name : 'Student',
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => date('c'),
                    'role' => 'student',
                    'active' => true,
            ];
            $allUsers[] = $user;
            save_users($allUsers);
            $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']];
            claim_guest_attempts($user);
            header('Location: ' . $next);
            exit;
        }
    } else {
        foreach ($allUsers as $userIndex => $existingUser) {
            $storedEmail = strtolower(trim((string) ($existingUser['email'] ?? '')));
            $storedPassword = (string) ($existingUser['password'] ?? '');
            $passwordMatches = password_verify($password, $storedPassword);
            $legacyPasswordMatches = !str_starts_with($storedPassword, '$') && hash_equals($storedPassword, $password);

            if ($storedEmail === $email && (bool) ($existingUser['active'] ?? true) && ($passwordMatches || $legacyPasswordMatches)) {
                if ($legacyPasswordMatches) {
                    $allUsers[$userIndex]['password'] = password_hash($password, PASSWORD_DEFAULT);
                    save_users($allUsers);
                }
                $_SESSION['user'] = ['id' => $existingUser['id'], 'name' => $existingUser['name'], 'email' => $existingUser['email']];
                claim_guest_attempts($existingUser);
                header('Location: ' . $next);
                exit;
            }
        }
        $error = $allUsers === []
            ? 'No account exists yet. Create an account first, then log in.'
            : 'Email or password is incorrect.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $mode === 'signup' ? 'Create account' : 'Log in' ?> | RankSetu</title>
<style>
:root{--navy:#16233f;--maroon:#9c3b2e;--paper:#efece2;--card:#f7f5ec;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24}*{box-sizing:border-box}body{margin:0;min-height:100vh;background:var(--paper);color:var(--ink);font-family:Arial,sans-serif;display:flex;flex-direction:column;align-items:stretch;padding:0}.shell{width:min(440px,calc(100% - 40px));margin:56px auto}.brand{color:var(--navy);font:700 1.6rem Georgia,serif;text-decoration:none;display:inline-block;margin-bottom:28px}.card{background:var(--card);border:1px solid var(--line);padding:32px;box-shadow:0 18px 40px rgba(22,35,63,.12)}h1{font:600 2rem Georgia,serif;color:var(--navy);margin:0 0 8px}p{color:var(--muted);margin:0 0 24px}.error{background:#f7ded8;border:1px solid #c97968;padding:10px 12px;margin-bottom:18px;color:#70251c;font-size:.9rem}label{display:block;font-size:.85rem;font-weight:700;margin:16px 0 6px}input{width:100%;padding:12px;border:1px solid var(--line);background:#fffdf7;font:inherit}button{width:100%;margin-top:24px;padding:13px;background:var(--maroon);color:white;border:0;font-weight:700;cursor:pointer}button:hover{background:#832f24}.switch{margin:22px 0 0;text-align:center;font-size:.9rem}.switch a{color:var(--maroon);font-weight:700}
</style>
</head>
<body>
<main class="shell">
    <section class="card">
        <h1><?= $mode === 'signup' ? 'Create your account' : 'Welcome back' ?></h1>
        <p><?= $mode === 'signup' ? 'Register once, then continue to your student dashboard.' : 'Log in to continue to your student dashboard.' ?></p>
        <?php if ($notice !== ''): ?><div class="notice" style="background:#e9f7ef;border:1px solid #bfe6c8;padding:10px 12px;margin-bottom:18px;color:#0b6b2a;font-size:.95rem"><?= e($notice) ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="mode" value="<?= e($mode) ?>">
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <?php if ($mode === 'signup'): ?>
                <label for="name">Full name</label>
                <input id="name" name="name" required autocomplete="name">
            <?php endif; ?>
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" required autocomplete="email">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" minlength="6" required autocomplete="<?= $mode === 'signup' ? 'new-password' : 'current-password' ?>">
            <?php if ($mode !== 'signup'): ?><p style="margin-top:8px;text-align:right"><a href="forgot.php">Forgot password?</a></p><?php endif; ?>
            <button type="submit"><?= $mode === 'signup' ? 'Create account' : 'Log in' ?></button>
        </form>
        <p class="switch"><?= $mode === 'signup' ? 'Already registered?' : 'New to RankSetu?' ?> <a href="auth.php?mode=<?= $mode === 'signup' ? 'login' : 'signup' ?>&next=<?= e($next) ?>"><?= $mode === 'signup' ? 'Log in' : 'Create an account' ?></a></p>
    </section>
</main>
</body>
</html>

