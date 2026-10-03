<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$user = current_user();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Meet Our Director | RankSetu</title>
<style>
:root{--violet:#667eea;--violet-deep:#4f46a5;--navy:#202744;--ink:#242638;--muted:#5d6273;--paper:#f7f8fc;--line:#e4e7f0;--gold:#f0bd5b;--white:#fff;box-sizing:border-box}
*,*::before,*::after{box-sizing:inherit}
html{scroll-behavior:smooth;scroll-padding-top:84px}
body{margin:0;background:var(--white);color:var(--ink);font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.65;-webkit-font-smoothing:antialiased}
img{display:block;max-width:100%}
a{color:inherit;text-decoration:none}
a:focus-visible,button:focus-visible{outline:3px solid var(--gold);outline-offset:3px}
.container{width:min(1160px,calc(100% - 48px));margin-inline:auto}
.site-header{position:sticky;top:0;z-index:10;background:#fff;border-bottom:1px solid var(--line);box-shadow:0 2px 12px rgba(25,35,55,.06)}
.navbar{min-height:72px;display:flex;align-items:center;justify-content:space-between;gap:24px}
.brand{flex:0 0 auto;color:var(--violet-deep);font-size:1.35rem;font-weight:800}
.nav-links{display:flex;align-items:center;justify-content:center;gap:clamp(12px,2vw,25px);margin:0;padding:0;list-style:none}
.nav-links a{display:block;padding:9px 0;color:#34394a;font-size:.92rem;white-space:nowrap}
.nav-links a:hover,.nav-links a[aria-current=page]{color:var(--violet-deep)}
.nav-links a[aria-current=page]{font-weight:700}
.nav-actions{display:flex;align-items:center;gap:10px}
.nav-action{border:1px solid #dce1ea;border-radius:4px;padding:9px 14px;color:#303740;font-size:.88rem;font-weight:700;white-space:nowrap}
.nav-action.primary{border-color:var(--violet);background:var(--violet);color:#fff}
.menu-toggle{display:none;border:1px solid #dce1ea;border-radius:4px;background:#fff;color:var(--ink);padding:8px 10px;font:700 .9rem Arial,sans-serif}
.hero{position:relative;overflow:hidden;background:linear-gradient(118deg,#596fda 0%,#6d62ca 52%,#7952a6 100%);color:#fff}
.hero::after{position:absolute;inset:auto -8% -54% 46%;height:95%;border:1px solid rgba(255,255,255,.15);border-radius:50%;content:"";pointer-events:none}
.hero-inner{position:relative;z-index:1;display:grid;grid-template-columns:minmax(0,1.08fr) minmax(290px,.82fr);align-items:center;gap:clamp(40px,8vw,100px);min-height:590px;padding-block:64px}
.eyebrow{display:flex;align-items:center;gap:10px;margin:0 0 20px;color:#ffe2a0;font-size:.78rem;font-weight:700;letter-spacing:.13em;text-transform:uppercase}
.eyebrow::before{width:28px;height:2px;background:var(--gold);content:""}
h1,h2,h3,p{margin-top:0}
h1,h2,h3{font-family:Georgia,"Times New Roman",serif;font-weight:600;line-height:1.14}
h1{max-width:14ch;margin-bottom:22px;font-size:clamp(2.6rem,5.4vw,4.55rem);color:#fff}
.hero-copy{max-width:54ch;margin-bottom:26px;color:rgba(255,255,255,.88);font-size:1.08rem}
.role-label{display:inline-flex;align-items:center;gap:9px;border:1px solid rgba(255,255,255,.34);border-radius:3px;padding:8px 12px;color:#fff;font-size:.88rem;font-weight:700}
.role-label::before{width:7px;height:7px;border-radius:50%;background:var(--gold);content:""}
.portrait-wrap{position:relative;justify-self:end;width:min(100%,560px);padding:20px 18px 14px;background:rgba(255,255,255,.06);border:2px solid rgba(255,255,255,.72);box-shadow:0 24px 55px rgba(28,20,62,.26)}
.portrait-wrap::before{position:absolute;inset:0;content:"";pointer-events:none;background:linear-gradient(135deg,rgba(255,255,255,.08),transparent 30%,rgba(0,0,0,.04));z-index:1}
.portrait{position:relative;z-index:2;display:block;width:100%;height:auto;max-height:clamp(420px,44vw,630px);margin:0 auto;object-fit:contain;object-position:center;background:#f7f8fc;border-radius:10px}
.portrait-caption{position:absolute;right:18px;bottom:18px;z-index:3;max-width:calc(100% - 36px);padding:12px 16px;background:#fff;color:var(--navy);font-size:.84rem;font-weight:700;box-shadow:0 8px 24px rgba(20,23,45,.17)}
.section{padding:82px 0}
.section.soft{background:var(--paper)}
.section-heading{max-width:720px;margin:0 auto 44px;text-align:center}
.section-heading .eyebrow{justify-content:center;color:var(--violet-deep)}
.section-heading .eyebrow::before{background:var(--violet)}
h2{margin-bottom:14px;color:var(--navy);font-size:clamp(2rem,3.5vw,3rem)}
.section-heading p{margin-bottom:0;color:var(--muted)}
.story-grid{display:grid;grid-template-columns:.72fr 1.28fr;gap:clamp(32px,7vw,90px);align-items:start}
.story-mark{border-top:3px solid var(--violet);padding-top:18px;color:var(--violet-deep);font-size:.78rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
.story-copy h2{max-width:15ch;margin-bottom:22px}
.story-copy p{max-width:68ch;margin-bottom:16px;color:var(--muted)}
.story-copy p:last-child{margin-bottom:0}
.principles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.principle{padding:28px 26px 30px;border-right:1px solid var(--line)}
.principle:first-child{padding-left:0}
.principle:last-child{border-right:0;padding-right:0}
.principle-number{display:block;margin-bottom:24px;color:var(--violet);font-size:.82rem;font-weight:800;letter-spacing:.1em}
.principle h3{margin-bottom:11px;color:var(--navy);font-size:1.42rem}
.principle p{margin:0;color:var(--muted);font-size:.95rem}
.vision-band{display:grid;grid-template-columns:.75fr 1.25fr;gap:35px;align-items:center;padding:clamp(28px,5vw,56px);background:var(--navy);color:#fff}
.vision-band .eyebrow{margin-bottom:0}
.vision-band h2{margin:0;color:#fff;font-size:clamp(1.8rem,3vw,2.65rem)}
.vision-band p{margin:14px 0 0;color:#d8dceb}
.cta{display:flex;align-items:center;justify-content:space-between;gap:28px;padding:36px 0}
.cta h2{margin:0;font-size:clamp(1.7rem,3vw,2.3rem)}
.cta p{margin:8px 0 0;color:var(--muted)}
.button{display:inline-flex;flex:0 0 auto;align-items:center;justify-content:center;min-height:48px;padding:12px 20px;border:1px solid var(--violet);border-radius:4px;background:var(--violet);color:#fff;font-size:.92rem;font-weight:700}
.button:hover{background:var(--violet-deep);border-color:var(--violet-deep)}
.footer{padding:24px 0;border-top:1px solid var(--line);color:var(--muted);font-size:.86rem}
.footer-inner{display:flex;justify-content:space-between;gap:18px;flex-wrap:wrap}
@media(max-width:900px){.navbar{gap:14px}.nav-links{gap:12px}.nav-links a{font-size:.85rem}.nav-action{padding:8px 10px;font-size:.82rem}.hero-inner{grid-template-columns:1fr;min-height:auto;gap:34px;padding-block:52px 46px}.portrait-wrap{justify-self:center;width:min(100%,500px);padding:16px 12px 12px}.portrait{max-height:clamp(300px,62vw,460px)}.principle{padding-inline:18px}}
@media(max-width:720px){.container{width:min(100% - 36px,560px)}.navbar{min-height:66px;flex-wrap:wrap;padding-block:10px}.menu-toggle{display:block}.nav-actions .nav-action{display:none}.nav-links{display:none;order:3;flex:0 0 100%;align-items:stretch;gap:0;padding:4px 0 8px;border-top:1px solid var(--line)}.site-header.is-open .nav-links{display:flex;flex-direction:column}.nav-links a{padding:12px 4px;border-bottom:1px solid #edf0f5;font-size:.95rem}.nav-links li:last-child a{border-bottom:0}.hero-copy{font-size:1rem}.section{padding:60px 0}.story-grid{grid-template-columns:1fr;gap:18px}.story-mark{width:max-content}.story-copy h2{max-width:18ch}.principles{grid-template-columns:1fr}.principle,.principle:first-child,.principle:last-child{padding:22px 0;border-right:0;border-bottom:1px solid var(--line)}.principle:last-child{border-bottom:0}.principle-number{margin-bottom:12px}.vision-band{grid-template-columns:1fr;gap:18px}.cta{align-items:flex-start;flex-direction:column;padding-block:32px}}
@media(max-width:380px){.container{width:calc(100% - 28px)}h1{font-size:2.45rem}.footer-inner{flex-direction:column}}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
</style>
</head>
<body>
<header class="site-header" id="site-header">
  <div class="container navbar">
    <a class="brand" href="index.php">RankSetu</a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-links">Menu</button>
    <ul class="nav-links" id="primary-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="index.php#features">Features</a></li>
      <li><a href="index.php#exams">Exams</a></li>
      <li><a href="index.php#faq">FAQ</a></li>
      <li><a href="info.php?page=about">About Us</a></li>
      <li><a href="director.php" aria-current="page">Director</a></li>
      <li><a href="info.php?page=contact">Contact</a></li>
      <?php if ($user !== null): ?><li><a href="student.php">Dashboard</a></li><?php if (($user['role'] ?? '') === 'admin'): ?><li><a href="admin.php">Admin</a></li><?php endif; ?><li><a href="logout.php">Log out</a></li><?php endif; ?>
    </ul>
    <div class="nav-actions"><?php if ($user === null): ?><a class="nav-action" href="info.php?page=contact">Contact</a><a class="nav-action primary" href="auth.php">Log in</a><?php endif; ?></div>
  </div>
</header>
<main>
  <section class="hero">
    <div class="container hero-inner">
      <div>
        <p class="eyebrow">Leadership &amp; purpose</p>
        <h1>Better preparation begins with a clearer path.</h1>
        <p class="hero-copy">A note on the people-first thinking behind RankSetu: focused practice, honest progress and tools that respect every learner's time.</p>
        <span class="role-label">Founder &amp; Director</span>
      </div>
      <figure class="portrait-wrap">
        <img class="portrait" src="img/founder.png" alt="Founder and director of the FullMockTestSeries learning platform" fetchpriority="high" width="1121" height="1403">
        <figcaption class="portrait-caption">Building with learners in mind</figcaption>
      </figure>
    </div>
  </section>
  <section class="section">
    <div class="container story-grid">
      <div class="story-mark">A founder's perspective</div>
      <div class="story-copy">
        <h2>Make every practice session count.</h2>
        <p>Preparing for a competitive exam takes more than collecting questions. It takes a steady routine, the confidence to learn from mistakes and a clear sense of what to work on next.</p>
        <p>That is the thinking behind RankSetu. The platform brings structured test series, timed attempts and saved results together so learners can spend less energy organizing practice and more energy improving it.</p>
        <p>Our director's focus is to keep the experience useful, straightforward and grounded in the needs of people preparing alongside work, study and everyday life.</p>
      </div>
    </div>
  </section>
  <section class="section soft">
    <div class="container">
      <div class="section-heading">
        <p class="eyebrow">What guides us</p>
        <h2>Purpose, made practical.</h2>
        <p>A useful learning platform should support a real study habit, not add noise to it.</p>
      </div>
      <div class="principles">
        <article class="principle"><span class="principle-number">01 / VISION</span><h3>Progress within reach</h3><p>Make structured exam practice easier to access, easier to follow and more helpful for learners at every stage of their preparation.</p></article>
        <article class="principle"><span class="principle-number">02 / MISSION</span><h3>Practice with purpose</h3><p>Bring relevant test series, realistic timing and clear performance history into one focused place for a more consistent routine.</p></article>
        <article class="principle"><span class="principle-number">03 / PRINCIPLE</span><h3>Earn learner trust</h3><p>Communicate clearly, keep practice results in context and encourage learners to verify important exam information with official sources.</p></article>
      </div>
    </div>
  </section>
  <section class="section">
    <div class="container">
      <div class="vision-band">
        <p class="eyebrow">Our commitment</p>
        <div><h2>Useful tools. Honest expectations. Room to improve.</h2><p>Practice can build familiarity and reveal where to focus next. It cannot promise a result, and it should always work alongside official information and a learner's own study plan.</p></div>
      </div>
      <div class="cta">
        <div><h2>Find your next practice set.</h2><p>Explore the available exam series and choose a place to begin.</p></div>
        <a class="button" href="index.php#exams">Explore test series</a>
      </div>
    </div>
  </section>
</main>
<footer class="footer"><div class="container footer-inner"><span>&copy; <?= date('Y') ?> RankSetu</span><a href="info.php?page=contact">Contact support</a></div></footer>
<script>
const siteHeader = document.getElementById('site-header');
const menuToggle = siteHeader?.querySelector('.menu-toggle');
menuToggle?.addEventListener('click', () => {
  const isOpen = siteHeader.classList.toggle('is-open');
  menuToggle.setAttribute('aria-expanded', String(isOpen));
  menuToggle.textContent = isOpen ? 'Close' : 'Menu';
});
siteHeader?.querySelectorAll('.nav-links a').forEach((link) => {
  link.addEventListener('click', () => {
    siteHeader.classList.remove('is-open');
    menuToggle?.setAttribute('aria-expanded', 'false');
    if (menuToggle) menuToggle.textContent = 'Menu';
  });
});
</script>
</body>
</html>