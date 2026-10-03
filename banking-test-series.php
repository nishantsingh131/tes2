<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$user = current_user();
$catalog = available_course_catalog();
$testsBySeries = [];
foreach (array_keys($catalog) as $slug) {
	$testsBySeries[$slug] = array_filter(test_records($slug), static fn(array $test): bool => is_array($test['questions'] ?? null) && $test['questions'] !== []);
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
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Government Exam Mock Tests and Test Series | RankSetu</title>
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
@media(prefers-reduced-motion:reduce){.hero .hero-title{animation:none}}
</style>
</head>
<body>
<header><div class="container"><nav id="primary-navigation"><a class="logo" href="index.php">RankSetu</a><div class="nav-links" id="primary-links"><a href="index.php">Home</a><a href="#features">Features</a><a href="#exams">Exams</a><a href="subscription.php">Subscriptions</a><a href="#faq">FAQ</a><a href="director.php">About Us</a><a href="info.php?page=contact">Contact</a><?php if ($user !== null): ?><a href="student.php">Dashboard</a><?php if (($user['role'] ?? '') === 'admin'): ?><a href="admin.php">Admin</a><?php endif; ?><a href="logout.php">Log out</a><span style="color:#667eea;font-weight:bold;margin-left:8px"><?= e((string) $user['name']) ?></span><?php endif; ?></div><div class="nav-tools"><?php if ($user === null): ?><a class="nav-offline" href="info.php?page=contact">Contact support</a><a class="nav-login" href="auth.php">Log in</a><?php endif; ?><button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-links">Menu</button></div></nav></div></header>
<section class="hero"><div class="container"><h1 class="hero-title"><span class="hero-prefix">Prepare for</span><span class="hero-category">Government</span><span class="hero-suffix">Exams</span></h1><p>Find practice for Bihar Police, BPSC, State PCS, UPSC Civil Services and Banking exams. Only series with published questions appear in the catalog.</p><div class="main-search"><form class="main-search-form" id="main-search-form"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="6.5"></circle><path d="m16 16 4 4"></path></svg><input class="main-search-input" id="main-search-input" type="search" placeholder="Search BPSC, Bihar Police, UPSC, State PCS..." aria-label="Search government exam test series"><button class="main-search-submit" type="submit">Search</button></form><p class="main-search-status" id="main-search-status" aria-live="polite"><?= (int) $examCount ?> published exam series. Search by exam, category or stage.</p><div class="search-suggestions" aria-label="Popular government exam searches">Browse quickly: <button class="search-suggestion" type="button" data-search="BPSC">BPSC</button><button class="search-suggestion" type="button" data-search="Bihar Police">Bihar Police</button><button class="search-suggestion" type="button" data-search="State PCS">State PCS</button><button class="search-suggestion" type="button" data-search="UPSC Civil Services">UPSC Civil Services</button><button class="search-suggestion" type="button" data-search="Banking">Banking</button></div></div><a class="btn btn-white" href="instructions.php?sample=1">Try a free sample</a><div class="stats"><div class="stat"><div class="number"><?= number_format($examCount) ?></div><div>Published Exams</div></div><div class="stat"><div class="number"><?= number_format($testCount) ?></div><div>Full-Length Tests</div></div><div class="stat"><div class="number"><?= e($questionsPerSet) ?></div><div>Questions Per Set</div></div></div></div></section>
<section id="features" class="section"><div class="container"><h2>Why students choose RankSetu</h2><div class="features"><div class="feature"><div class="icon">📊</div><h3>Progress Analytics</h3><p>Track every attempt with scores, timings and performance history.</p></div><div class="feature"><div class="icon">🎯</div><h3>Exam-Focused Sets</h3><p>Practice structured child test sets for the exam you actually target.</p></div><div class="feature"><div class="icon">⚡</div><h3>Timed Practice</h3><p>Build speed and accuracy with a dedicated timer for every test.</p></div><div class="feature"><div class="icon">🏆</div><h3>Performance History</h3><p>Compare attempts and identify the areas that need more work.</p></div><div class="feature"><div class="icon">📱</div><h3>Mobile Ready</h3><p>Study comfortably on desktop, tablet or phone.</p></div><div class="feature"><div class="icon">🔄</div><h3>Unlimited Attempts</h3><p>Reattempt enrolled tests and measure your improvement.</p></div><div class="feature"><div class="icon">🧠</div><h3>Smart Revision</h3><p>Return to difficult topics and turn every mistake into a focused revision goal.</p></div><div class="feature"><div class="icon">🔒</div><h3>Secure Accounts</h3><p>Keep your enrollments, attempts and progress organized in one protected account.</p></div><div class="feature"><div class="icon">📝</div><h3>Exam-Like Practice</h3><p>Build confidence with structured question sets designed for realistic practice.</p></div></div></div></section>
<section id="exams" class="section"><div class="container"><div class="catalog-heading"><div class="catalog-heading-copy"><p class="catalog-kicker">Published practice</p><h2>Explore government exam test series</h2><p class="catalog-subtitle">Choose a published series and find the right practice set for your exam.</p></div><div class="catalog-count"><strong><?= number_format($examCount) ?></strong><span>exam series<br>available</span></div></div><?php foreach ($groups as $group => $exams): ?><h3 style="font-size:1.45em;margin:32px 0 18px;color:#667eea"><?= e($group) ?></h3><div class="exams"><?php foreach ($exams as $slug => $exam): ?><?php $pricePaise = max(0, (int) ($exam['price_paise'] ?? 25000)); ?><article class="exam-card" data-search="<?= e(implode(' ', [$group, $exam['title'], $exam['stage'], $exam['description']])) ?>"><div class="exam-visual"><img src="<?= e($exam['image_path'] ?? 'assets/exam-card.svg') ?>" alt="<?= e($exam['title']) ?> practice overview" loading="lazy"></div><div class="exam-header"><?= e($exam['title']) ?></div><div class="exam-body"><h3><?= e($exam['stage']) ?></h3><p><?= e($exam['description']) ?></p><div class="exam-meta"><span><?= (int) ($setCounts[$slug] ?? 0) ?> Test Sets<br><small>Questions and timing per set</small></span><span class="exam-price<?= $pricePaise === 0 ? ' exam-price-free' : '' ?>"><?= $pricePaise === 0 ? 'FREE' : '₹' . number_format($pricePaise / 100, 2) ?></span></div><a class="btn btn-primary exam-link" href="product.php?product=<?= e($slug) ?>">View test series</a></div></article><?php endforeach; ?></div><?php endforeach; ?></div></section>
<section class="section"><div class="container"><h2>Built for consistent preparation</h2><div class="benefits"><div class="benefit-item"><h4>✓ Structured Practice</h4><p>Move from one set to the next while keeping every result in your history.</p></div><div class="benefit-item"><h4>✓ Exam-Focused Sets</h4><p>Practice published question sets for the government exam you are preparing for.</p></div><div class="benefit-item"><h4>✓ Secure Accounts</h4><p>Your enrollments, attempts and purchases are associated with your account.</p></div><div class="benefit-item"><h4>✓ Growing Exam Coverage</h4><p>Browse BPSC and banking practice now, or search for Bihar Police, State PCS and UPSC Civil Services as new sets are published.</p></div></div></div></section>
<section class="section"><div class="container"><h2>⚖️ Know What You’re Getting</h2><p style="text-align:center;color:#555;max-width:680px;margin:-22px auto 34px">A focused practice platform is powerful when you use it consistently. Here is the honest picture before you begin.</p><div class="benefit-panel"><article class="benefit-box positive"><h3>What works in your favor</h3><ul><li>Practice on your phone, tablet or desktop whenever you have time.</li><li>Track scores, timing and progress after every saved attempt.</li><li>Start with free sample tests before committing to a full routine.</li><li>Find published government-exam practice sets in one catalog.</li></ul></article><article class="benefit-box caution"><h3>What to keep in mind</h3><ul><li>Practice tests support preparation but cannot guarantee selection.</li><li>Content quality depends on regular updates and honest review.</li><li>Internet access is needed to load tests and save your progress.</li><li>Real improvement still requires a consistent study plan outside the platform.</li></ul></article></div></div></section>
<section id="faq" class="section"><div class="container"><h2>❓ Frequently Asked Questions</h2><div class="faq-container"><div class="faq-item"><div class="faq-q">Which exam test series can I browse?<span>+</span></div><div class="faq-a">Browse every series with published questions in the catalog. Search by exam name or category, including BPSC, Bihar Police, State PCS, UPSC Civil Services and Banking; new series appear when their questions are published.</div></div><div class="faq-item"><div class="faq-q">How many tests and questions does each series include?<span>+</span></div><div class="faq-a">The number of available tests, questions and time limits varies by exam series. Check each series card and its details before enrolling.</div></div><div class="faq-item"><div class="faq-q">Can I try a test before creating an account?<span>+</span></div><div class="faq-a">Yes. Use the free sample test from this page. Guest results are temporary; logged-in attempts are saved to your dashboard.</div></div><div class="faq-item"><div class="faq-q">Where can I see my performance?<span>+</span></div><div class="faq-a">Your dashboard and history pages store scores, time used and every submitted test attempt.</div></div><div class="faq-item"><div class="faq-q">Can I practice on a phone?</div><div class="faq-a">Yes. The catalog, test instructions and practice flow are designed to work across modern phones, tablets and desktop browsers.</div></div><div class="faq-item"><div class="faq-q">What happens after I enroll in a series?</div><div class="faq-a">The series becomes available from your student dashboard, where you can open tests, submit attempts and review your history.</div></div><div class="faq-item"><div class="faq-q">Are the tests timed?</div><div class="faq-a">Each test can use its configured duration. Follow the instructions before starting so you know how much time is available.</div></div><div class="faq-item"><div class="faq-q">Can I retake a test?</div><div class="faq-a">Yes. Retaking a set helps you compare attempts and measure whether your speed and accuracy are improving.</div></div><div class="faq-item"><div class="faq-q">How can I get help with an account issue?</div><div class="faq-a">Use the Contact link and include your registered email, series name and a short description. Never send your password or payment credentials.</div></div></div></div></section>
<section class="container"><div class="cta"><h2>Ready to Start?</h2><p>Choose a published government-exam series and build a stronger preparation routine.</p><a class="btn btn-white" href="#exams">Explore Test Series</a></div></section>
<footer><div class="container"><div class="footer-grid"><div class="footer-col"><h4>🎓 RankSetu</h4><p style="color:#ccc">Government exam mock tests and practice series.</p></div><div class="footer-col"><h4>Exam categories</h4><ul><li><a href="#exams">BPSC &amp; State PCS</a></li><li><a href="#exams">Bihar Police</a></li><li><a href="#exams">UPSC Civil Services</a></li><li><a href="#exams">Banking</a></li></ul></div><div class="footer-col"><h4>Quick Links</h4><ul><li><a href="auth.php">Login</a></li><li><a href="instructions.php?sample=1">Free sample</a></li><li><a href="info.php?page=contact">Contact</a></li><li><a href="info.php?page=faq">FAQ</a></li></ul></div><div class="footer-col"><h4>Policies</h4><ul><li><a href="info.php?page=privacy">Privacy</a></li><li><a href="info.php?page=terms">Terms</a></li><li><a href="info.php?page=refund">Refund policy</a></li></ul></div></div><div class="footer-bottom">&copy; <?= date('Y') ?> RankSetu. Government exam practice for every aspirant.</div></div></footer>
<script>
const primaryNavigation = document.getElementById('primary-navigation');
const navigationToggle = primaryNavigation?.querySelector('.nav-toggle');
const searchControl = primaryNavigation?.querySelector('.search-control');
const searchButton = searchControl?.querySelector('.nav-search');
const searchInput = searchControl?.querySelector('.search-input');
const searchClear = searchControl?.querySelector('.search-clear');
const searchStatus = searchControl?.querySelector('.search-status');
const mainSearchForm = document.getElementById('main-search-form');
const mainSearchInput = document.getElementById('main-search-input');
const mainSearchStatus = document.getElementById('main-search-status');
const examCards = [...document.querySelectorAll('.exam-card')];
const publishedExamNames = examCards.map((card) => card.querySelector('.exam-header')?.textContent.trim()).filter(Boolean);
if (mainSearchInput && publishedExamNames.length) mainSearchInput.placeholder = 'Search published exam series...';
const examGroups = [...document.querySelectorAll('#exams h3')];
const filterExams = (sourceInput = null) => {
	const rawQuery = (sourceInput?.value ?? mainSearchInput?.value ?? searchInput?.value ?? '').trim();
	const query = rawQuery.toLowerCase();
	let matches = 0;
	if (searchInput && sourceInput !== searchInput) searchInput.value = rawQuery;
	if (mainSearchInput && sourceInput !== mainSearchInput) mainSearchInput.value = rawQuery;
	examCards.forEach((card) => {
		const searchableText = `${card.textContent} ${card.dataset.search || ''}`.toLowerCase();
		const requestedBank = query.match(/\b(sbi|ibps)\b/)?.[1];
		const matchesBankPoAlias = /\b(?:bank\s+pos?|probationary officers?)\b/.test(query)
			&& /\b(?:sbi|ibps)\s+po\b/.test(searchableText)
			&& (!requestedBank || searchableText.includes(requestedBank));
		const matchesQuery = !query || searchableText.includes(query) || matchesBankPoAlias;
		card.hidden = !matchesQuery;
		if (matchesQuery) matches += 1;
	});
	examGroups.forEach((group) => {
		const groupCards = [...group.nextElementSibling?.querySelectorAll('.exam-card') ?? []];
		group.hidden = query !== '' && !groupCards.some((card) => !card.hidden);
	});
	if (searchStatus) searchStatus.textContent = query ? `${matches} exam series found` : 'Search government exams';
	if (mainSearchStatus) mainSearchStatus.textContent = query ? `${matches} published series found${matches ? '. Results update as you type.' : '. Series appear here when questions are published.'}` : `Search ${publishedExamNames.length} published government-exam series by exam, category or stage.`;
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
		if (mainSearchInput.value.trim() && examCards.some((card) => !card.hidden)) {
			document.getElementById('exams')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	});
	document.querySelectorAll('.search-suggestion').forEach((suggestion) => {
		suggestion.addEventListener('click', () => {
			mainSearchInput.value = suggestion.dataset.search || '';
			filterExams(mainSearchInput);
			mainSearchInput.focus();
			if (examCards.some((card) => !card.hidden)) {
				document.getElementById('exams')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		});
	});
}
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
	const revealItems = document.querySelectorAll('#features .feature, #exams .exam-card, .home-guide-inner, .home-faq-category');
	if (revealItems.length) {
		document.documentElement.classList.add('home-motion-ready');
		const revealObserver = new IntersectionObserver((entries, observer) => {
			entries.forEach((entry) => {
				if (!entry.isIntersecting) return;
				entry.target.classList.add('is-visible');
				observer.unobserve(entry.target);
			});
		}, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });
		revealItems.forEach((item, index) => {
			item.classList.add('home-reveal');
			item.style.setProperty('--reveal-delay', `${Math.min(index % 6, 5) * 45}ms`);
			revealObserver.observe(item);
		});
	}
}
</script>
</body></html>
