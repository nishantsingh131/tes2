<?php

declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$catalog = ['ssc-cgl'=>['SSC CGL','Tier I & II','Combined Graduate Level practice with quant, reasoning, English and GK sets'],'ssc-chsl'=>['SSC CHSL','Tier I & II','Higher Secondary Level descriptive-ready mocks'],'rrb-ntpc'=>['RRB NTPC','CBT 1 & 2','Non-Technical Popular Categories timed mocks'],'rrb-group-d'=>['RRB Group D','CBT','Maths, reasoning, science and GK practice'],'ibps-po'=>['IBPS PO','Prelims & Mains','Reasoning, quant, English and banking awareness'],'sbi-clerk'=>['SBI Clerk','Prelims & Mains','Full-length prelims and mains practice'],'bpsc-prelims'=>['BPSC Prelims','Prelims','General studies sets on the current syllabus'],'state-psc-mains'=>['State PSC Mains','Mains','Descriptive general studies papers'],'nda-cds'=>['NDA & CDS','Written exam','Maths and general ability mocks'],'ctet'=>['CTET','Paper I & II','Subject-wise teaching eligibility practice']];
foreach (series_records() as $record) if (!empty($record['active']) && isset($record['slug'],$record['title'])) $catalog[$record['slug']]=[$record['title'],$record['stage']??'Practice',$record['description']??'Admin-published practice series.'];
$slug=(string)($_GET['product']??''); if(!isset($catalog[$slug])){header('Location: test-series-landing.html#exams');exit;}$user=require_login();[$title,$stage,$description]=$catalog[$slug];$enrolled=in_array($slug,$user['enrolled']??[],true);
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?=e($title)?> | RankSetu</title><style>:root{--navy:#16233f;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24}*{box-sizing:border-box}body{margin:0;background:var(--paper);font-family:Arial;color:#1c1a15}header{background:var(--navy);border-bottom:3px solid var(--gold);padding:18px 5%;display:flex;justify-content:space-between}header a{color:#f5ead2;text-decoration:none;margin-left:18px}.brand{font:700 1.5rem Georgia;color:#fff;margin:0}.wrap{width:min(980px,calc(100% - 40px));margin:auto;padding:58px 0}.hero{display:grid;grid-template-columns:1.4fr .8fr;gap:24px}.intro,.buy,.feature{background:var(--card);border:1px solid var(--line);padding:30px}.intro{border-top:5px solid var(--maroon)}.eyebrow{color:var(--maroon);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em}h1,h2{font-family:Georgia;color:var(--navy)}h1{font-size:clamp(2.2rem,5vw,4rem);margin:12px 0}.intro p,.buy p,.feature p{color:var(--muted);line-height:1.7}.buy{background:var(--navy);color:#fff}.buy h2{color:#fff}.price{font:700 2.2rem Georgia;color:#e0b34f;margin:20px 0}.btn{display:block;text-align:center;background:var(--maroon);color:#fff;padding:13px 18px;text-decoration:none;font-weight:700;border:0;width:100%;cursor:pointer}.btn.light{background:#f5ead2;color:var(--navy);margin-top:10px}.features{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:18px}.notice{margin-top:18px;padding:14px;background:#a97a241c;border-left:4px solid var(--gold);color:var(--muted)}@media(max-width:700px){.hero,.features{grid-template-columns:1fr}.wrap{padding:35px 0}}
</style></head><body><header><a class="brand" href="test-series-landing.html">RankSetu</a><nav><a href="dashboard.php">Dashboard</a><a href="logout.php">Log out</a></nav></header><main class="wrap"><section class="hero"><div class="intro"><div class="eyebrow"><?=e($stage)?></div><h1><?=e($title)?></h1><p><?=e($description)?></p><a class="btn light" href="practice.php?product=<?=e($slug)?>">View practice area</a></div><aside class="buy"><h2><?= $enrolled?'Continue learning':'Enroll and unlock this series' ?></h2><p><?= $enrolled?'You already have access to this series.':'Secure payment is handled by Razorpay. UPI, cards, net banking and wallets are supported.' ?></p><?php if($enrolled): ?><a class="btn light" href="instructions.php?product=<?=e($slug)?>">Start test</a><?php else: ?><div class="price">₹250.00</div><a class="btn" href="checkout.php?plan=<?=e($slug)?>">Enroll now</a><?php endif; ?></aside></section><section class="features"><article class="feature"><h2>Real exam practice</h2><p>Timed questions built for focused preparation and measurable progress.</p></article><article class="feature"><h2>Secure checkout</h2><p>Payment confirmation is verified on the server before access is granted.</p></article><article class="feature"><h2>Order receipt</h2><p>After payment, download your order invoice from your student account.</p></article></section></main></body></html>


$catalog = [
    'ssc-cgl' => ['SSC CGL', 'Tier I & II', 'Combined Graduate Level practice with quant, reasoning, English and GK sets.', '15 full-length tests', '60-90 min each'],
    'ssc-chsl' => ['SSC CHSL', 'Tier I & II', 'Higher Secondary Level mocks with descriptive-ready practice.', '15 full-length tests', '60 min each'],
    'rrb-ntpc' => ['RRB NTPC', 'CBT 1 & 2', 'Non-Technical Popular Categories timed mocks.', '15 full-length tests', '90 min each'],
    'rrb-group-d' => ['RRB Group D', 'CBT', 'Maths, reasoning, science and GK sets built to the level-1 pattern.', '15 full-length tests', '90 min each'],
    'ibps-po' => ['IBPS PO', 'Prelims & Mains', 'Reasoning, quant, English and banking awareness practice.', '15 full-length tests', '60 min each'],
    'sbi-clerk' => ['SBI Clerk', 'Prelims & Mains', 'Full-length prelims and mains practice sets.', '15 full-length tests', '60 min each'],
    'bpsc-prelims' => ['BPSC Prelims', 'Prelims', 'General studies sets built on the current Bihar PSC syllabus.', '15 full-length tests', '120 min each'],
    'state-psc-mains' => ['State PSC Mains', 'Mains', 'Descriptive-style general studies papers for state PSC preparation.', '12 full-length tests', '180 min each'],
    'nda-cds' => ['NDA & CDS', 'Written exam', 'Maths and general ability mocks for the written stage.', '15 full-length tests', '150 min each'],
    'ctet' => ['CTET', 'Paper I & II', 'Subject-wise sets for the Central Teacher Eligibility Test.', '15 full-length tests', '150 min each'],
];
foreach (series_records() as $record) {
    if (!empty($record['active']) && isset($record['slug'], $record['title'])) {
        $catalog[$record['slug']] = [$record['title'], $record['stage'] ?? 'Practice', $record['description'] ?? 'Admin-published practice series.', '10 full-length questions', ((string) ($record['duration_minutes'] ?? 10)) . ' min each'];
    }
}

$slug = (string) ($_GET['product'] ?? $_POST['product'] ?? 'ssc-cgl');
if (!isset($catalog[$slug])) {
    header('Location: test-series-landing.html#exams');
    exit;
}

$user = current_user();
if ($user === null) {
    header('Location: auth.php?next=' . rawurlencode('product.php?product=' . $slug));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enroll') {
    $enrolled = $user['enrolled'] ?? [];
    if (!in_array($slug, $enrolled, true)) {
        $enrolled[] = $slug;
    }
    update_current_user(['enrolled' => array_values($enrolled)]);
    header('Location: product.php?product=' . rawurlencode($slug) . '&enrolled=1');
    exit;
}

[$title, $stage, $description, $tests, $duration] = $catalog[$slug];
$isEnrolled = in_array($slug, $user['enrolled'] ?? [], true);
$justEnrolled = ($_GET['enrolled'] ?? '') === '1';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> test series | RankSetu</title>
<style>
:root{--navy:#16233f;--navy2:#243a64;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Arial,sans-serif}header{background:var(--navy);border-bottom:3px solid var(--gold);padding:18px 5%;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{font:700 1.5rem Georgia,serif;color:#fff;text-decoration:none}.nav{display:flex;gap:20px;align-items:center}.nav a{color:#f5ead2;text-decoration:none;font-size:.92rem}.user{color:#f5ead2;font-size:.9rem}.wrap{width:min(1050px,calc(100% - 40px));margin:auto;padding:60px 0}.crumb{color:var(--maroon);font-size:.85rem;font-weight:700;text-decoration:none}.hero{margin-top:18px;display:grid;grid-template-columns:1.3fr .7fr;gap:28px;align-items:stretch}.intro,.summary,.feature{background:var(--card);border:1px solid var(--line);padding:32px}.intro{border-top:5px solid var(--maroon)}.kicker{color:var(--maroon);font-size:.78rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}h1,h2{font-family:Georgia,serif;color:var(--navy)}h1{font-size:clamp(2.2rem,5vw,4rem);margin:12px 0}h2{margin-top:0}.intro p{font-size:1.08rem;line-height:1.7;color:var(--muted);max-width:58ch}.summary{background:var(--navy);color:#fff}.summary h2{color:#fff;margin-top:0}.price{font:700 2.4rem Georgia,serif;color:#e0b34f}.summary p{color:#d5d0c0}.btn{display:inline-block;border:0;background:var(--maroon);color:white;text-decoration:none;padding:13px 20px;font-weight:700;cursor:pointer;font-size:.95rem}.btn.secondary{background:transparent;border:1px solid var(--gold);color:var(--navy)}.summary .btn{width:100%;text-align:center;margin-top:12px}.summary .btn.secondary{background:#f5ead2;color:var(--navy);border-color:#f5ead2}.notice{margin-top:20px;padding:14px 16px;background:rgba(169,122,36,.14);border-left:4px solid var(--gold);color:var(--muted)}.features{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:22px}.feature h2{font-size:1.1rem}.feature p{color:var(--muted);font-size:.92rem;line-height:1.6}@media(max-width:720px){.hero,.features{grid-template-columns:1fr}.wrap{padding:36px 0}.nav{gap:10px}.user{display:none}}
</style>
</head>
<body>
<header><a class="brand" href="test-series-landing.html">RankSetu</a><nav class="nav"><span class="user"><?= e($user['name']) ?></span><a href="student.php">Dashboard</a><a href="logout.php">Log out</a></nav></header>
<main class="wrap"><a class="crumb" href="test-series-landing.html#exams">&larr; All test series</a><section class="hero"><div class="intro"><div class="kicker"><?= e($stage) ?></div><h1><?= e($title) ?></h1><p><?= e($description) ?></p><a class="btn secondary" href="student.php">View my learning</a></div><aside class="summary"><h2>Start preparing today</h2><p><?= e($tests) ?> &bull; <?= e($duration) ?></p><div class="price">Free to enroll</div><?php if ($isEnrolled): ?><a class="btn secondary" href="practice.php?product=<?= e($slug) ?>">Start practice</a><?php else: ?><form method="post"><input type="hidden" name="product" value="<?= e($slug) ?>"><input type="hidden" name="action" value="enroll"><button class="btn" type="submit">Enroll in this series</button></form><?php endif; ?><a class="btn" href="checkout.php?plan=<?= e($slug) ?>">View demo checkout</a></aside></section><?php if ($justEnrolled): ?><div class="notice"><strong>You are enrolled.</strong> This series is now available in your student dashboard.</div><?php endif; ?><section class="features"><article class="feature"><h2>Exam-style tests</h2><p>Timed practice sets designed around the latest pattern and marking approach.</p></article><article class="feature"><h2>Track your progress</h2><p>Keep every attempt in one place and build a steady preparation habit.</p></article><article class="feature"><h2>Practice first</h2><p>Free enrollment is available now. The demo checkout lets you test the future order flow without charging money.</p></article></section></main>
</body></html>
