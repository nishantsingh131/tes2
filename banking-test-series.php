<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$user = current_user();
$catalog = [];
$testsBySeries = [];
foreach (published_banking_catalog() as $slug => $series) {
	if (preg_match('/^(test|demo|sample|untitled)$/i', trim((string) ($series['title'] ?? ''))) === 1) continue;
	$populatedTests = [];
	foreach (test_records($slug) as $testKey => $test) {
		$questions = $test['questions'] ?? [];
		if (is_array($questions) && $questions !== []) $populatedTests[$testKey] = $test;
	}
	if ($populatedTests === []) continue;
	$catalog[$slug] = $series;
	$testsBySeries[$slug] = $populatedTests;
}
$groups = [];
foreach ($catalog as $slug => $exam) $groups[$exam['group']][$slug] = $exam;
$setCounts = [];
$testCount = 0;
$questionCounts = [];
foreach ($catalog as $slug => $exam) {
	$tests = $testsBySeries[$slug] ?? [];
	$setCounts[$slug] = 0;
	foreach ($tests as $test) {
		$questions = $test['questions'] ?? [];
		$questionCount = is_array($questions) ? count($questions) : 0;
		if ($questionCount > 0) {
			$setCounts[$slug]++;
			$testCount++;
			$questionCounts[] = $questionCount;
		}
	}
}
$examCount = count($catalog);
if ($questionCounts === []) {
	$questionsPerSet = '—';
} else {
	sort($questionCounts, SORT_NUMERIC);
	$minimumQuestions = $questionCounts[0];
	$maximumQuestions = $questionCounts[count($questionCounts) - 1];
	$questionsPerSet = $minimumQuestions === $maximumQuestions ? (string) $minimumQuestions : $minimumQuestions . '-' . $maximumQuestions;
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>RankSetu - Banking Test Series</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:Arial,sans-serif;background:#fff;color:#000;line-height:1.6}.container{max-width:1200px;margin:0 auto;padding:0 20px}header{background:#667eea;padding:15px 0;position:sticky;top:0;z-index:100}nav{display:flex;justify-content:space-between;align-items:center;gap:20px}.logo{font-size:24px;font-weight:bold;color:#fff;text-decoration:none}nav a{color:#fff;margin:0 10px;text-decoration:none}.nav-links{display:flex;align-items:center;gap:5px;flex-wrap:wrap}.btn{display:inline-block;padding:10px 20px;border:0;border-radius:5px;cursor:pointer;font-weight:bold;text-decoration:none}.btn-white{background:#fff;color:#667eea}.btn-white:hover{background:#f0f0f0}.btn-primary{background:#667eea;color:#fff}.btn-primary:hover{background:#556bd4}.hero{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:80px 0;text-align:center}.hero h1{font-size:clamp(2rem,5vw,3em);margin-bottom:20px;color:#fff}.hero p{font-size:1.2em;margin-bottom:30px;color:#fff}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin:40px auto 0;max-width:650px}.stat{background:rgba(0,0,0,.2);padding:20px;border-radius:8px;color:#fff}.stat .number{font-size:2em;font-weight:bold}.section{padding:60px 0}.section:nth-of-type(even){background:#f5f5f5}h2{font-size:2.2em;color:#000;text-align:center;margin-bottom:40px}.features{display:grid;grid-template-columns:repeat(3,1fr);gap:30px}.feature{background:#fff;padding:30px;border-radius:8px;box-shadow:0 5px 15px rgba(0,0,0,.1);text-align:center;border:1px solid #e0e0e0}.feature .icon{font-size:2.5em;margin-bottom:15px}.feature h3{color:#000;margin-bottom:10px}.feature p{color:#333}.exams{display:grid;grid-template-columns:repeat(3,1fr);gap:25px}.exam-card{background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 5px 20px rgba(0,0,0,.1);border:1px solid #e0e0e0}.exam-header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:22px;text-align:center;font-size:1.7em;font-weight:bold}.exam-body{padding:20px}.exam-body h3{color:#000;margin-bottom:10px}.exam-body p{color:#333;min-height:72px}.exam-meta{display:flex;justify-content:space-between;gap:10px;margin-top:15px;padding-top:15px;border-top:1px solid #eee;color:#000;align-items:center}.exam-price{color:#667eea;font-weight:bold;font-size:1em}.exam-link{margin-top:16px;width:100%;text-align:center}.benefits{display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:900px;margin:0 auto}.benefit-item{background:#fff;padding:20px;border-radius:8px;border-left:4px solid #667eea;box-shadow:0 3px 10px rgba(0,0,0,.08);border-top:1px solid #e0e0e0;border-right:1px solid #e0e0e0;border-bottom:1px solid #e0e0e0}.benefit-item h4{color:#667eea;margin-bottom:8px}.benefit-item p{color:#333}.faq-container{max-width:800px;margin:0 auto}.faq-item{background:#fff;margin-bottom:12px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #e0e0e0}.faq-q{padding:18px;display:flex;justify-content:space-between;align-items:center;background:#f9f9f9;font-weight:600;color:#000}.faq-a{padding:0 18px 18px;color:#333}.cta{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;text-align:center;padding:60px 20px;border-radius:10px;margin:60px 0}.cta h2{color:#fff}.cta p{font-size:1.1em;margin-bottom:20px;color:#fff}footer{background:#1a1a1a;color:#fff;padding:40px 0 20px;margin-top:60px}.footer-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:30px;margin-bottom:30px}.footer-col h4{margin-bottom:15px;color:#fff}.footer-col ul{list-style:none}.footer-col li{margin:7px 0}.footer-col a{color:#ccc;text-decoration:none}.footer-col a:hover{color:#fff}.footer-bottom{text-align:center;padding-top:20px;border-top:1px solid #333;color:#ccc}@media(max-width:768px){.features,.exams{grid-template-columns:1fr}.stats,.benefits{grid-template-columns:1fr}.footer-grid{grid-template-columns:repeat(2,1fr)}.hero h1{font-size:1.8em}h2{font-size:1.5em}.nav-links{justify-content:flex-end}}@media(max-width:480px){.footer-grid{grid-template-columns:1fr}nav{align-items:flex-start;flex-direction:column}.nav-links{justify-content:flex-start}}
</style>
<style>
.nav-toggle{display:none;border:1px solid rgba(255,255,255,.45);background:transparent;color:#fff;border-radius:5px;padding:8px 11px;font:700 .9rem Arial,sans-serif;cursor:pointer}
.nav-toggle:hover,.nav-toggle:focus-visible{background:rgba(255,255,255,.12)}
header.site-header{display:block;background:#fff;border-bottom:1px solid #e4e7ef;padding:0;box-shadow:0 2px 12px rgba(25,35,55,.06)}header.site-header>.container{width:100%;flex:1 1 auto;padding-left:24px;padding-right:24px}header.site-header nav{min-height:76px;gap:22px}.logo{color:#667eea;font-size:24px;padding-right:24px;border-right:1px solid #e4e7ef;white-space:nowrap}.nav-links{gap:20px;flex:1;justify-content:center}.nav-links a{color:#202733;font-size:.92rem;margin:0;padding:9px 0}.nav-links a:hover{color:#667eea;text-decoration:none}.nav-tools{display:flex;align-items:center;gap:12px}.nav-search{width:42px;height:42px;padding:0;border:1px solid #dce1ea;border-radius:50%;background:#f7f8fa;color:#202733;display:grid;place-items:center;cursor:pointer}.nav-search svg{width:19px;height:19px}.nav-offline{color:#202733;border:1px solid #dce1ea;border-radius:24px;padding:10px 18px!important;white-space:nowrap}.nav-offline:hover{border-color:#667eea!important;background:#f6f7ff}.nav-login{background:#303740;color:#fff!important;border-radius:24px;padding:11px 22px!important;white-space:nowrap}.nav-login:hover{background:#202733!important;color:#fff!important}.nav-toggle{color:#202733;border-color:#dce1ea}
.search-control{position:relative}.search-panel{display:none;position:absolute;z-index:120;top:52px;right:0;width:min(330px,calc(100vw - 28px));padding:12px;background:#fff;border:1px solid #dce1ea;border-radius:8px;box-shadow:0 12px 28px rgba(25,35,55,.16)}.search-control.is-open .search-panel{display:block}.search-form{display:flex;align-items:center;gap:8px}.search-input{width:100%;min-width:0;padding:10px 11px;border:1px solid #cfd6e2;border-radius:5px;color:#202733;font:400 .9rem Arial,sans-serif}.search-input:focus{outline:2px solid rgba(102,126,234,.3);border-color:#667eea}.search-clear{border:0;background:transparent;color:#667eea;font:700 .8rem Arial,sans-serif;cursor:pointer;padding:6px}.search-status{margin:8px 2px 0;color:#667085;font-size:.78rem}
.main-search{width:min(680px,100%);margin:28px auto 0}.main-search-form{display:flex;align-items:center;gap:8px;background:#fff;padding:7px;border-radius:7px;box-shadow:0 10px 25px rgba(25,35,55,.2)}.main-search-form svg{flex:0 0 auto;width:21px;height:21px;margin-left:10px;color:#667eea}.main-search-input{flex:1;min-width:0;border:0;outline:0;padding:12px 6px;color:#202733;font:400 1rem Arial,sans-serif}.main-search-submit{border:0;border-radius:5px;background:#667eea;color:#fff;padding:12px 20px;font:700 .9rem Arial,sans-serif;cursor:pointer}.main-search-submit:hover{background:#556bd4}.search-suggestions{display:flex;justify-content:center;flex-wrap:wrap;gap:8px;margin-top:12px;color:#fff;font-size:.82rem}.search-suggestion{border:1px solid rgba(255,255,255,.45);border-radius:20px;background:transparent;color:#fff;padding:5px 10px;cursor:pointer}.search-suggestion:hover{background:rgba(255,255,255,.14)}
.hero .main-search + .btn-white{margin-top:24px}
@media(max-width:980px){.nav-links{gap:12px}.nav-offline{display:none}}
@media(max-width:768px){header{padding:0}.container{padding-left:14px;padding-right:14px}header nav{min-height:68px;gap:10px;flex-wrap:wrap}.logo{font-size:21px;padding-right:14px}.nav-tools{margin-left:auto}.nav-search{width:38px;height:38px}.nav-toggle{display:block;margin-left:0}.nav-links{display:none;width:100%;flex-direction:column;align-items:stretch;gap:4px;margin:0;padding:10px 0 12px;border-top:1px solid #e4e7ef}.nav-links a{margin:0;padding:10px 12px;border-radius:4px}.nav-links a:hover{background:#f6f7ff}.nav-links .btn{padding:10px 12px;text-align:center}.nav-links span{padding:8px 12px;margin:0!important}nav.is-open{flex-wrap:wrap}nav.is-open .nav-links{display:flex}}
@media(max-width:768px){header.site-header nav{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;justify-content:normal}header.site-header .logo{grid-column:1;grid-row:1;display:flex;align-self:center;min-width:max-content;visibility:visible;opacity:1;font-size:20px}.nav-tools{grid-column:2;grid-row:1;gap:7px;margin-left:0}.nav-links{grid-column:1/-1;grid-row:2;flex:none;width:100%}.nav-offline{display:none!important}.nav-login{padding:9px 14px!important;font-size:.86rem}.nav-search{width:36px;height:36px}.nav-toggle{padding:7px 9px;font-size:.82rem}}
@media(max-width:768px){.search-panel{position:fixed;top:68px;left:14px;right:14px;width:auto}}
@media(max-width:600px){.main-search-form{padding:6px}.main-search-input{font-size:.9rem}.main-search-submit{padding:11px 13px}.search-suggestions{justify-content:flex-start}}
</style>
<style>
.feature{transition:transform .2s ease,box-shadow .2s ease}.feature:hover{transform:translateY(-4px);box-shadow:0 12px 24px rgba(102,126,234,.16)}.feature h3{font-size:1.15rem}.feature p{line-height:1.65}.feature:nth-child(3n+1){border-top:3px solid #667eea}.feature:nth-child(3n+2){border-top:3px solid #764ba2}.feature:nth-child(3n){border-top:3px solid #9b8df0}.benefit-panel{display:grid;grid-template-columns:repeat(2,1fr);gap:24px}.benefit-box{padding:30px;border-radius:8px;border:1px solid #e0e0e0;background:#fff;box-shadow:0 5px 15px rgba(0,0,0,.08)}.benefit-box h3{margin-bottom:18px;font-size:1.4rem}.benefit-box ul{list-style:none;display:grid;gap:14px}.benefit-box li{position:relative;padding-left:28px;color:#333}.benefit-box li::before{position:absolute;left:0;font-weight:bold;font-size:1.1rem}.benefit-box.positive{border-top:4px solid #37a169}.benefit-box.positive h3{color:#207849}.benefit-box.positive li::before{content:"+";color:#37a169}.benefit-box.caution{border-top:4px solid #d18a35}.benefit-box.caution h3{color:#a6631d}.benefit-box.caution li::before{content:"!";color:#d18a35}.faq-container{max-width:820px;margin:0 auto}.faq-item{background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px 22px;margin-bottom:12px}.faq-q{font-weight:bold;color:#202733}.faq-a{color:#555;margin-top:8px;line-height:1.65}@media(max-width:700px){.benefit-panel{grid-template-columns:1fr}.benefit-box{padding:24px}.faq-item{padding:17px}}
<style>.exam-visual{height:120px;overflow:hidden;background:#667eea}.exam-visual img{width:100%;height:100%;object-fit:cover;display:block}.exam-meta small{font-size:.78rem}.exam-price{white-space:nowrap}</style>
<style>
.hero .hero-title{display:flex;flex-wrap:nowrap;align-items:baseline;justify-content:center;gap:0 .22em;width:100%;min-height:1.1em;margin:0 0 20px;font-size:3rem;line-height:1.08;white-space:nowrap}
.hero-prefix,.hero-category,.hero-suffix{display:inline-block}
.hero-category{width:auto;min-height:0;text-align:center;opacity:1;transform:translateY(0);transition:opacity .24s ease,transform .24s ease}
.hero-category.is-changing{opacity:0;transform:translateY(.18em)}
@media(max-width:600px){.hero{padding:52px 0 40px}.hero .container{width:100%;max-width:100%;padding:0 16px}.hero .hero-title{max-width:100%;flex-wrap:wrap;white-space:normal;overflow-wrap:anywhere;font-size:clamp(1.4rem,7vw,1.9rem);line-height:1.15}.hero p{max-width:100%;font-size:1rem;line-height:1.55;overflow-wrap:anywhere}.main-search{max-width:100%;margin-top:22px}}
@media(max-width:360px){.hero .hero-title{font-size:1.35rem}}
@media(prefers-reduced-motion:reduce){.hero-category{transition:none}}
</style>
</head>
<body>
<header><div class="container"><nav id="primary-navigation"><a class="logo" href="index.php">RankSetu</a><div class="nav-links" id="primary-links"><a href="index.php">Home</a><a href="#features">Features</a><a href="#exams">Exams</a><a href="subscription.php">Subscriptions</a><a href="#faq">FAQ</a><a href="info.php?page=about">About Us</a><a href="director.php">Director</a><a href="info.php?page=contact">Contact</a><?php if ($user !== null): ?><a href="student.php">Dashboard</a><?php if (($user['role'] ?? '') === 'admin'): ?><a href="admin.php">Admin</a><?php endif; ?><a href="logout.php">Log out</a><span style="color:#667eea;font-weight:bold;margin-left:8px"><?= e((string) $user['name']) ?></span><?php endif; ?></div><div class="nav-tools"><?php if ($user === null): ?><a class="nav-offline" href="info.php?page=contact">Contact support</a><a class="nav-login" href="auth.php">Log in</a><?php endif; ?><button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-links">Menu</button></div></nav></div></header>
<section class="hero"><div class="container"><h1 class="hero-title"><span class="hero-prefix">Crack</span><span class="hero-category" id="hero-category">Banking</span><span class="hero-suffix">Exams</span></h1><p>Build a focused preparation plan for India's leading public-sector exams. Explore the active series below as new categories are published.</p><div class="main-search"><form class="main-search-form" id="main-search-form"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="6.5"></circle><path d="m16 16 4 4"></path></svg><input class="main-search-input" id="main-search-input" type="search" placeholder="Search SBI PO, IBPS Clerk, RBI..." aria-label="Search banking exams"><button class="main-search-submit" type="submit">Search</button></form><div class="search-suggestions" aria-label="Popular searches">Popular: <button class="search-suggestion" type="button" data-search="SBI PO">SBI PO</button><button class="search-suggestion" type="button" data-search="IBPS Clerk">IBPS Clerk</button><button class="search-suggestion" type="button" data-search="RBI">RBI</button></div></div><a class="btn btn-white" href="instructions.php?sample=1">Try Free Sample</a><div class="stats"><div class="stat"><div class="number"><?= number_format($examCount) ?></div><div>Published Exams</div></div><div class="stat"><div class="number"><?= number_format($testCount) ?></div><div>Full-Length Tests</div></div><div class="stat"><div class="number"><?= e($questionsPerSet) ?></div><div>Questions Per Set</div></div></div></div></section>
<section id="features" class="section"><div class="container"><h2>✨ Why Choose RankSetu?</h2><div class="features"><div class="feature"><div class="icon">📊</div><h3>Progress Analytics</h3><p>Track every attempt with scores, timings and performance history.</p></div><div class="feature"><div class="icon">🎯</div><h3>Exam-Focused Sets</h3><p>Practice structured child test sets for the exam you actually target.</p></div><div class="feature"><div class="icon">⚡</div><h3>Timed Practice</h3><p>Build speed and accuracy with a dedicated timer for every test.</p></div><div class="feature"><div class="icon">🏆</div><h3>Performance History</h3><p>Compare attempts and identify the areas that need more work.</p></div><div class="feature"><div class="icon">📱</div><h3>Mobile Ready</h3><p>Study comfortably on desktop, tablet or phone.</p></div><div class="feature"><div class="icon">🔄</div><h3>Unlimited Attempts</h3><p>Reattempt enrolled tests and measure your improvement.</p></div><div class="feature"><div class="icon">🧠</div><h3>Smart Revision</h3><p>Return to difficult topics and turn every mistake into a focused revision goal.</p></div><div class="feature"><div class="icon">🔒</div><h3>Secure Accounts</h3><p>Keep your enrollments, attempts and progress organized in one protected account.</p></div><div class="feature"><div class="icon">📝</div><h3>Exam-Like Practice</h3><p>Build confidence with structured question sets designed for realistic practice.</p></div></div></div></section>
<section id="exams" class="section"><div class="container"><h2>📚 Choose Your Banking Exam</h2><?php foreach ($groups as $group => $exams): ?><h3 style="font-size:1.45em;margin:32px 0 18px;color:#667eea"><?= e($group) ?></h3><div class="exams"><?php foreach ($exams as $slug => $exam): ?><article class="exam-card"><div class="exam-visual"><img src="<?= e($exam['image_path'] ?? 'assets/exam-card.svg') ?>" alt="<?= e($exam['title']) ?> practice overview" loading="lazy"></div><div class="exam-header"><?= e($exam['title']) ?></div><div class="exam-body"><h3><?= e($exam['stage']) ?></h3><p><?= e($exam['description']) ?></p><div class="exam-meta"><span><?= (int) ($setCounts[$slug] ?? 0) ?> Test Sets<br><small>Questions and timing per set</small></span><span class="exam-price">₹<?= number_format(((int) ($exam['price_paise'] ?? 25000)) / 100, 2) ?></span></div><a class="btn btn-primary exam-link" href="product.php?product=<?= e($slug) ?>">View test series</a></div></article><?php endforeach; ?></div><?php endforeach; ?></div></section>
<section class="section"><div class="container"><h2>💡 Built for Consistent Preparation</h2><div class="benefits"><div class="benefit-item"><h4>✓ Structured Practice</h4><p>Move from one set to the next while keeping every result in your history.</p></div><div class="benefit-item"><h4>✓ Admin-Controlled Content</h4><p>Every question, option, answer and duration is maintained through the production admin panel.</p></div><div class="benefit-item"><h4>✓ Secure Accounts</h4><p>Your enrollments, attempts and purchases are associated with your account.</p></div><div class="benefit-item"><h4>✓ Banking-First Catalog</h4><p>One focused destination for SBI, IBPS, RBI, NABARD, SEBI, LIC and other banking exams.</p></div></div></div></section>
<section class="section"><div class="container"><h2>⚖️ Know What You’re Getting</h2><p style="text-align:center;color:#555;max-width:680px;margin:-22px auto 34px">A focused practice platform is powerful when you use it consistently. Here is the honest picture before you begin.</p><div class="benefit-panel"><article class="benefit-box positive"><h3>What works in your favor</h3><ul><li>Practice on your phone, tablet or desktop whenever you have time.</li><li>Track scores, timing and progress after every saved attempt.</li><li>Start with free sample tests before committing to a full routine.</li><li>Use focused banking series instead of searching across scattered resources.</li></ul></article><article class="benefit-box caution"><h3>What to keep in mind</h3><ul><li>Practice tests support preparation but cannot guarantee selection.</li><li>Content quality depends on regular updates and honest review.</li><li>Internet access is needed to load tests and save your progress.</li><li>Real improvement still requires a consistent study plan outside the platform.</li></ul></article></div></div></section>
<section id="faq" class="section"><div class="container"><h2>❓ Frequently Asked Questions</h2><div class="faq-container"><div class="faq-item"><div class="faq-q">How many tests does each series include?<span>+</span></div><div class="faq-a">Each banking series is designed around 15 full-length child tests, with 10 questions per starter set. Admins can update the content and duration.</div></div><div class="faq-item"><div class="faq-q">Can I try a test before creating an account?<span>+</span></div><div class="faq-a">Yes. Use the free sample test from this page. Guest results are temporary; logged-in attempts are saved to your dashboard.</div></div><div class="faq-item"><div class="faq-q">Where can I see my performance?<span>+</span></div><div class="faq-a">Your dashboard and history pages store scores, time used and every submitted test attempt.</div></div><div class="faq-item"><div class="faq-q">Can I practice on a phone?</div><div class="faq-a">Yes. The catalog, test instructions and practice flow are designed to work across modern phones, tablets and desktop browsers.</div></div><div class="faq-item"><div class="faq-q">What happens after I enroll in a series?</div><div class="faq-a">The series becomes available from your student dashboard, where you can open tests, submit attempts and review your history.</div></div><div class="faq-item"><div class="faq-q">Are the tests timed?</div><div class="faq-a">Each test can use its configured duration. Follow the instructions before starting so you know how much time is available.</div></div><div class="faq-item"><div class="faq-q">Can I retake a test?</div><div class="faq-a">Yes. Retaking a set helps you compare attempts and measure whether your speed and accuracy are improving.</div></div><div class="faq-item"><div class="faq-q">How can I get help with an account issue?</div><div class="faq-a">Use the Contact link and include your registered email, series name and a short description. Never send your password or payment credentials.</div></div></div></div></section>
<section class="container"><div class="cta"><h2>Ready to Start?</h2><p>Choose your banking exam and build a stronger preparation routine.</p><a class="btn btn-white" href="#exams">Explore Test Series</a></div></section>
<footer><div class="container"><div class="footer-grid"><div class="footer-col"><h4>🎓 RankSetu</h4><p style="color:#ccc">Focused banking test series for serious preparation.</p></div><div class="footer-col"><h4>Exams</h4><ul><li><a href="#exams">SBI</a></li><li><a href="#exams">IBPS</a></li><li><a href="#exams">RBI</a></li><li><a href="#exams">Insurance &amp; finance</a></li></ul></div><div class="footer-col"><h4>Quick Links</h4><ul><li><a href="auth.php">Login</a></li><li><a href="instructions.php?sample=1">Free sample</a></li><li><a href="info.php?page=contact">Contact</a></li><li><a href="info.php?page=faq">FAQ</a></li></ul></div><div class="footer-col"><h4>Policies</h4><ul><li><a href="info.php?page=privacy">Privacy</a></li><li><a href="info.php?page=terms">Terms</a></li><li><a href="info.php?page=refund">Refund policy</a></li></ul></div></div><div class="footer-bottom">&copy; <?= date('Y') ?> RankSetu. Built for better banking preparation.</div></div></footer>
<script>
const heroCategory = document.getElementById('hero-category');
const heroExamCategories = ['Banking', 'SSC', 'Railway', 'BPSC', 'State PSC', 'Defence', 'Teaching'];
if (heroCategory && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
	let activeHeroCategory = 0;
	window.setInterval(() => {
		if (document.hidden) return;
		heroCategory.classList.add('is-changing');
		window.setTimeout(() => {
			activeHeroCategory = (activeHeroCategory + 1) % heroExamCategories.length;
			heroCategory.textContent = heroExamCategories[activeHeroCategory];
			heroCategory.classList.remove('is-changing');
		}, 240);
	}, 3200);
}
const primaryNavigation = document.getElementById('primary-navigation');
const navigationToggle = primaryNavigation?.querySelector('.nav-toggle');
const searchControl = primaryNavigation?.querySelector('.search-control');
const searchButton = searchControl?.querySelector('.nav-search');
const searchInput = searchControl?.querySelector('.search-input');
const searchClear = searchControl?.querySelector('.search-clear');
const searchStatus = searchControl?.querySelector('.search-status');
const mainSearchForm = document.getElementById('main-search-form');
const mainSearchInput = document.getElementById('main-search-input');
const examCards = [...document.querySelectorAll('.exam-card')];
const publishedExamNames = examCards.map((card) => card.querySelector('.exam-header')?.textContent.trim()).filter(Boolean);
document.querySelectorAll('.search-suggestion').forEach((suggestion, index) => {
	if (!publishedExamNames[index]) {
		suggestion.remove();
		return;
	}
	suggestion.textContent = publishedExamNames[index];
	suggestion.dataset.search = publishedExamNames[index];
});
if (mainSearchInput) mainSearchInput.placeholder = publishedExamNames.length ? `Search ${publishedExamNames.slice(0, 3).join(', ')}` : 'Search published exam series';
const examGroups = [...document.querySelectorAll('#exams h3')];
let searchHasNavigated = false;
const filterExams = (sourceInput = null) => {
	const rawQuery = (sourceInput?.value ?? mainSearchInput?.value ?? searchInput?.value ?? '').trim();
	const query = rawQuery.toLowerCase();
	let matches = 0;
	if (searchInput && sourceInput !== searchInput) searchInput.value = rawQuery;
	if (mainSearchInput && sourceInput !== mainSearchInput) mainSearchInput.value = rawQuery;
	examCards.forEach((card) => {
		const matchesQuery = !query || card.textContent.toLowerCase().includes(query);
		card.hidden = !matchesQuery;
		if (matchesQuery) matches += 1;
	});
	examGroups.forEach((group) => {
		const groupCards = [...group.nextElementSibling?.querySelectorAll('.exam-card') ?? []];
		group.hidden = query !== '' && !groupCards.some((card) => !card.hidden);
	});
	if (searchStatus) searchStatus.textContent = query ? `${matches} exam${matches === 1 ? '' : 's'} found` : 'Search banking exams';
	if (!query) searchHasNavigated = false;
	if (query.length >= 2 && matches > 0 && !searchHasNavigated) {
		searchHasNavigated = true;
		document.getElementById('exams')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
	}
};
if (primaryNavigation && navigationToggle && navigationToggle.dataset.navigationBound !== 'true') {
	navigationToggle.dataset.navigationBound = 'true';
	navigationToggle.addEventListener('click', () => {
		const isOpen = primaryNavigation.classList.toggle('is-open');
		navigationToggle.setAttribute('aria-expanded', String(isOpen));
		navigationToggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
		navigationToggle.textContent = isOpen ? 'Close' : 'Menu';
	});
	primaryNavigation.querySelectorAll('.nav-links a').forEach((link) => {
		link.addEventListener('click', () => {
			primaryNavigation.classList.remove('is-open');
			navigationToggle.setAttribute('aria-expanded', 'false');
			navigationToggle.textContent = 'Menu';
		});
	});
}
if (searchControl && searchButton && searchInput && searchClear && searchStatus) {
	searchButton.addEventListener('click', () => {
		const isOpen = searchControl.classList.toggle('is-open');
		searchButton.setAttribute('aria-expanded', String(isOpen));
		if (isOpen) searchInput.focus();
	});
	searchInput.addEventListener('input', () => filterExams(searchInput));
	searchClear.addEventListener('click', () => {
		searchInput.value = '';
		filterExams();
		searchInput.focus();
	});
}
if (mainSearchForm && mainSearchInput) {
	mainSearchInput.addEventListener('input', filterExams);
	mainSearchForm.addEventListener('submit', (event) => {
		event.preventDefault();
		filterExams();
	});
	document.querySelectorAll('.search-suggestion').forEach((suggestion) => {
		suggestion.addEventListener('click', () => {
			mainSearchInput.value = suggestion.dataset.search || '';
			filterExams(mainSearchInput);
			mainSearchInput.focus();
		});
	});
}
</script>
</body></html>
