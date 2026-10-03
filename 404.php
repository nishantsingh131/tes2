<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
http_response_code(404);
header('X-Robots-Tag: noindex');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found | FullMockTestSeries.com</title>
    <style>
        :root{--navy:#16233f;--paper:#efece2;--ink:#1c1a15;--muted:#625e50;--maroon:#9c3b2e;--gold:#a97a24;--line:#cfc7ac}*{box-sizing:border-box}body{margin:0;min-height:100vh;background:var(--paper);color:var(--ink);font:16px Arial,sans-serif}.error-wrap{width:min(820px,calc(100% - 32px));margin:0 auto;padding:72px 0 88px}.error-kicker{color:var(--maroon);font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.error-code{display:block;margin-top:16px;color:var(--gold);font:700 1rem Arial,sans-serif}.error-wrap h1{max-width:16ch;margin:8px 0 14px;color:var(--navy);font:700 2.5rem/1.12 Georgia,serif}.error-copy{max-width:58ch;color:var(--muted);font-size:1.05rem;line-height:1.7}.error-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:26px}.error-actions a{display:inline-flex;min-height:46px;align-items:center;justify-content:center;padding:12px 18px;border:1px solid var(--maroon);background:var(--maroon);color:#fff;font-weight:700;text-decoration:none}.error-actions a.secondary{background:transparent;color:var(--navy);border-color:var(--line)}.error-actions a:focus-visible{outline:3px solid var(--gold);outline-offset:3px}@media(max-width:520px){.error-wrap{padding:48px 0 60px}.error-wrap h1{font-size:2rem}.error-actions{align-items:stretch;flex-direction:column}.error-actions a{width:100%}}
    </style>
</head>
<body>
<main class="error-wrap">
    <p class="error-kicker">FullMockTestSeries.com</p>
    <span class="error-code">404 · PAGE NOT FOUND</span>
    <h1>This page is not here.</h1>
    <p class="error-copy">The address may be outdated, or the page may have moved. The published exam catalog is a good place to continue.</p>
    <div class="error-actions">
        <a href="index.php#exams">Browse available series</a>
        <a class="secondary" href="info.php?page=contact">Contact support</a>
    </div>
</main>
</body>
</html>