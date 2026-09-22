<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

$pages = [
    'about' => [
        'title' => 'About RankSetu',
        'eyebrow' => 'Our purpose',
        'intro' => 'RankSetu helps exam aspirants prepare with focused, realistic practice.',
        'sections' => [
            ['title' => 'Built for consistent preparation', 'body' => 'We bring exam-style practice into one simple space for learners preparing for SSC, Railway, Banking, State PSC, Defence and teaching eligibility exams. Our goal is to make every attempt useful, measurable and easy to repeat.'],
            ['title' => 'What we believe', 'body' => 'Good preparation is built through a clear plan, regular practice and honest review. RankSetu is designed to help you build that routine without unnecessary complexity.'],
        ],
    ],
    'contact' => [
        'title' => 'Contact Us',
        'eyebrow' => 'We are here to help',
        'intro' => 'Have a question about your account, enrollment or a practice test? Send us a message.',
        'sections' => [
            ['title' => 'Support email', 'body' => 'Email us at support@ranksetu.example. We aim to reply within two business days.'],
            ['title' => 'Include these details', 'body' => 'Please include your registered email address, the series name and a short description of the issue. Never send your password or payment credentials.'],
        ],
    ],
    'disclaimer' => [
        'title' => 'Disclaimer',
        'eyebrow' => 'Important information',
        'intro' => 'RankSetu is an independent practice platform and is not affiliated with any government examination authority.',
        'sections' => [
            ['title' => 'Practice content', 'body' => 'Our tests and explanations are prepared for practice and learning. They do not guarantee selection, a particular score or a rank in any examination.'],
            ['title' => 'Exam updates', 'body' => 'Always verify dates, eligibility, syllabus and official instructions on the relevant examination authority website.'],
        ],
    ],
    'faq' => [
        'title' => 'Frequently Asked Questions',
        'eyebrow' => 'Help centre',
        'intro' => 'Quick answers about using RankSetu.',
        'sections' => [
            ['title' => 'How do I start a series?', 'body' => 'Open a series from the exam catalog, create or use your account, then select Enroll. The series will appear in your student dashboard.'],
            ['title' => 'Is payment available now?', 'body' => 'Not yet. Enrollment is currently free while the payment gateway is being prepared.'],
            ['title' => 'Can I practice on mobile?', 'body' => 'Yes. The pages are responsive and can be used from a modern mobile or desktop browser.'],
            ['title' => 'How do I get support?', 'body' => 'Use the Contact Us page and email support@ranksetu.example with your account email and issue details.'],
        ],
    ],
    'privacy' => [
        'title' => 'Privacy Policy',
        'eyebrow' => 'Your data',
        'intro' => 'This starter policy explains how RankSetu handles information while the platform is being developed.',
        'sections' => [
            ['title' => 'Information we store', 'body' => 'We store your name, email address, password hash and enrolled series to provide account and learning features. We do not store your plain-text password.'],
            ['title' => 'How we use it', 'body' => 'Account information is used to sign you in, show your dashboard, save enrollments and respond to support requests. We do not sell personal information.'],
            ['title' => 'Your choices', 'body' => 'Contact support to request an account correction or deletion. Production data storage, retention and security controls will be expanded before launch.'],
        ],
    ],
    'terms' => [
        'title' => 'Terms & Conditions',
        'eyebrow' => 'Using RankSetu',
        'intro' => 'By creating an account or using RankSetu, you agree to use the platform responsibly.',
        'sections' => [
            ['title' => 'Account responsibility', 'body' => 'Keep your login details private and provide accurate information. You are responsible for activity performed through your account.'],
            ['title' => 'Acceptable use', 'body' => 'Do not copy, resell, abuse, disrupt or attempt to gain unauthorized access to RankSetu or its content.'],
            ['title' => 'Service changes', 'body' => 'We may improve, change or temporarily suspend features as the platform develops. We will communicate important changes where practical.'],
        ],
    ],
    'refund' => [
        'title' => 'Refund & Cancellation Policy',
        'eyebrow' => 'Purchases and cancellations',
        'intro' => 'Enrollment is currently free and no payment gateway is active, so there are no paid orders to refund at this stage.',
        'sections' => [
            ['title' => 'When payments launch', 'body' => 'A detailed payment, refund and cancellation process will be published before paid plans are enabled. The checkout will show the applicable terms before confirmation.'],
            ['title' => 'Support for issues', 'body' => 'For an account or enrollment issue, contact support@ranksetu.example with your registered email and the relevant series name.'],
        ],
    ],
];

$key = (string) ($_GET['page'] ?? 'about');
if (!isset($pages[$key])) {
    $key = 'about';
}
$page = $pages[$key];
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page['title']) ?> | RankSetu</title>
<style>
:root{--navy:#16233f;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Arial,sans-serif}header{background:var(--navy);border-bottom:3px solid var(--gold);padding:18px 5%;display:flex;justify-content:space-between;align-items:center;gap:20px}.brand{font:700 1.5rem Georgia,serif;color:#fff;text-decoration:none}.nav{display:flex;align-items:center;gap:18px}.nav a{color:#f5ead2;text-decoration:none;font-size:.9rem}.user{color:#f5ead2;font-size:.9rem}.wrap{width:min(900px,calc(100% - 40px));margin:auto;padding:58px 0}.crumb{color:var(--maroon);font-weight:700;text-decoration:none;font-size:.88rem}.hero{background:var(--navy);color:#fff;padding:34px;margin-top:18px;border-top:4px solid var(--gold)}.eyebrow{font-size:.78rem;color:#e0b34f;font-weight:700;text-transform:uppercase;letter-spacing:.08em}.hero h1{font:600 clamp(2rem,5vw,3.5rem) Georgia,serif;margin:12px 0;color:#fff}.hero p{color:#e0dccd;font-size:1.05rem;line-height:1.6;margin:0;max-width:64ch}.content{display:grid;gap:16px;margin-top:20px}.section{background:var(--card);border:1px solid var(--line);padding:26px}.section h2{font:600 1.35rem Georgia,serif;color:var(--navy);margin:0 0 10px}.section p{color:var(--muted);line-height:1.75;margin:0}.quick{display:flex;flex-wrap:wrap;gap:10px;margin-top:26px;padding-top:20px;border-top:1px solid var(--line)}.quick a{color:var(--maroon);font-size:.88rem;font-weight:700;text-decoration:none}@media(max-width:650px){header{align-items:flex-start}.user{display:none}.nav{gap:10px;flex-wrap:wrap;justify-content:flex-end}.wrap{padding:38px 0}.hero{padding:25px}}
</style>
</head>
<body><header><a class="brand" href="index.php">RankSetu</a><nav class="nav"><span class="user"><?= $user !== null ? e($user['name']) : '' ?></span><?php if ($user !== null): ?><a href="student.php">Dashboard</a><a href="logout.php">Log out</a><?php else: ?><a href="auth.php">Log in</a><?php endif; ?></nav></header><main class="wrap"><a class="crumb" href="index.php">&larr; Back to RankSetu</a><section class="hero"><div class="eyebrow"><?= e($page['eyebrow']) ?></div><h1><?= e($page['title']) ?></h1><p><?= e($page['intro']) ?></p></section><section class="content"><?php foreach ($page['sections'] as $section): ?><article class="section"><h2><?= e($section['title']) ?></h2><p><?= e($section['body']) ?></p></article><?php endforeach; ?></section><nav class="quick" aria-label="Information pages"><a href="info.php?page=about">About</a><a href="info.php?page=contact">Contact</a><a href="info.php?page=disclaimer">Disclaimer</a><a href="info.php?page=faq">FAQ</a><a href="info.php?page=privacy">Privacy</a><a href="info.php?page=terms">Terms</a><a href="info.php?page=refund">Refunds</a></nav></main></body></html>

