<?php

declare(strict_types=1);

function site_header(string $title, string $section = 'site', bool $showUser = true): void
{
    echo site_header_markup($section, $showUser);
}

function site_header_markup(string $section = 'site', bool $showUser = true): string
{
    $user = function_exists('current_user') ? current_user() : null;
    $isAdmin = is_array($user) && (($user['role'] ?? 'student') === 'admin');
    $hasPremium = false;
    if (is_array($user) && function_exists('user_subscriptions')) foreach (user_subscriptions((string) $user['id']) as $subscription) {
        if (function_exists('subscription_is_current') ? subscription_is_current($subscription) : (($subscription['status'] ?? '') === 'ACTIVE' && (empty($subscription['expires_at']) || strtotime((string) $subscription['expires_at']) > time()))) { $hasPremium = true; break; }
    }
    $moreLinks = '<a href="index.php#features">Features</a><a href="index.php#faq">FAQ</a><a href="info.php?page=contact">Contact</a>';
    if ($user !== null) {
        if ($hasPremium) $moreLinks .= '<a href="subscription.php">Premium user</a>';
        $moreLinks .= '<a href="student.php">Dashboard</a><a href="blog-submit.php">Blog &amp; tutorials</a>';
        if ($isAdmin) $moreLinks .= '<a href="blog-admin.php">Manage blog</a><a href="blog-admin.php?review=pending">Review blog posts</a><a href="admin.php">Admin</a>';
        $moreLinks .= '<a href="logout.php">Log out</a>';
    } else {
        $moreLinks .= '<a href="auth.php">Log in</a>';
    }
    $links = '<a href="index.php">Home</a><a href="index.php#exams">Exams</a><a href="blog.php">Blog</a><a href="subscription.php">Subscriptions</a><a href="info.php?page=about">About Us</a><a href="director.php">Director</a><details class="nav-more"><summary class="nav-more-toggle">More</summary><div class="nav-more-menu">' . $moreLinks . '</div></details>';
    if ($showUser && $user !== null) $links .= '<span class="site-user">' . e((string) ($user['name'] ?? '')) . '</span>';

    return '<header class="site-header"><div class="container"><nav id="primary-navigation" aria-label="Primary navigation"><a class="logo" href="index.php" aria-label="' . e(APP_BRAND_NAME . ' - ' . APP_BRAND_TAGLINE) . '"><svg class="header-brand-mark" viewBox="0 0 56 56" aria-hidden="true" focusable="false"><path d="M6 17.5 28 7l22 10.5L28 28 6 17.5Z" fill="#168be1"/><path d="M15 22v11.2c7.8 6.7 18.2 6.7 26 0V22L28 29l-13-7Z" fill="#f8b63e"/><path d="M28 29 15 22v11.2c7.8 6.7 18.2 6.7 26 0V22l-13 7Z" fill="#fff" fill-opacity=".96"/><path d="M21 34.2 25.5 39l10-11.2" fill="none" stroke="#2f7657" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5"/><path d="M48 19v11" stroke="#f8b63e" stroke-linecap="round" stroke-width="2.5"/><circle cx="48" cy="32.5" r="2.5" fill="#f8b63e"/></svg><span class="header-wordmark"><span>FullMockTest</span><span class="header-wordmark-bottom"><strong>Series</strong><span class="header-domain">.com</span></span></span></a><div class="nav-links" id="primary-links">' . $links . '</div><div class="nav-tools"><button class="nav-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="primary-links">Menu</button></div></nav></div></header>';
}

function site_footer(string $section = 'site'): void
{
    echo site_footer_markup($section);
}

function site_homepage_learning_section(): string
{
    return '<section id="features" class="section"><div class="container"><h2>A useful routine for mock-test practice</h2><p class="study-note">Series, question counts and time limits vary. Use the details shown on each published series page, and check the latest official notice for your exam.</p><div class="features"><article class="feature"><div class="icon">01</div><h3>Choose the right series</h3><p>Match the published series to your exam and stage. Review its question count and time limit before you begin.</p></article><article class="feature"><div class="icon">02</div><h3>Take a timed attempt</h3><p>Read the instructions, set aside uninterrupted time and answer without relying on notes during the attempt.</p></article><article class="feature"><div class="icon">03</div><h3>Review before repeating</h3><p>Use the result and saved attempt history to find missed questions, revisit the related topic and plan your next practice session.</p></article></div><p class="study-note">Practice scores are for learning, not official results or a prediction of selection. Confirm eligibility, exam dates and rules with the relevant authority.</p></div></section>';
}

function site_homepage_guides_and_faq_markup(): string
{
    $guides = [
        ['why-choose-ranksetu', 'Why choose RankSetu for mock-test practice?', 'Find published exam series, attempt timed question sets, and review your saved results from one account. Choose a listing only after checking its details.', 'index.php#exams', 'Explore available series'],
        ['trust-through-transparency', 'Trust starts with clear expectations', 'Series availability, question counts, timings, and prices can differ. We show the details available for each listing so you can review before enrolling.', 'index.php#exams', 'Review the catalog'],
        ['how-to-choose-a-series', 'How to choose the right test series', 'Match the exam and stage first. Then check the series description, the number of available tests, question count, and time limit on its product page.', 'index.php#exams', 'Compare test series'],
        ['practice-on-your-schedule', 'Build a practice routine that fits your schedule', 'Set a repeatable time for practice, attempt one available test without interruptions, and reserve time afterwards to review questions you found difficult.', 'instructions.php?sample=1', 'Try a sample test'],
        ['understand-mock-tests', 'What a mock test can—and cannot—do', 'A mock test gives you a structured way to practise and review. It cannot guarantee an exam score, appointment, rank, or selection outcome.', 'info.php?page=disclaimer', 'Read the disclaimer'],
        ['check-official-notices', 'Use official notices for exam decisions', 'Use this site for practice. For eligibility, application dates, exam schedules, syllabus changes, and official marking rules, rely on the recruiting authority’s latest notice.', 'info.php?page=disclaimer', 'Review exam guidance'],
        ['timed-practice', 'Make timed practice more useful', 'Before starting, note the displayed time limit. After submitting, compare your accuracy and pace with your own earlier attempts rather than guessing from a single score.', 'instructions.php?sample=1', 'See how practice works'],
        ['review-results', 'Turn each result into a revision plan', 'Look beyond the total percentage: identify missed questions, revisit the relevant topic, and use a later attempt to check whether your approach improved.', 'student.php', 'Open your dashboard'],
        ['track-progress', 'Track progress without losing your history', 'When signed in, submitted attempts are available from your account history. Use the dates and scores to notice patterns and set a realistic next goal.', 'history.php', 'View test history'],
        ['practice-again', 'Use reattempts as deliberate practice', 'Repeating a test can help you check recall, but first review the questions you missed. Your saved submissions let you compare attempts over time.', 'history.php', 'Review past attempts'],
        ['phone-friendly-practice', 'Practise on the device that works for you', 'The site pages adapt to phone, tablet, and desktop widths. Keep a stable connection and use a screen size that lets you read each question comfortably.', 'instructions.php?sample=1', 'Try the sample'],
        ['question-quality', 'Read question sets carefully', 'Question wording and structure vary by series. Read any passage or direction supplied with a set before answering its related questions.', 'index.php#exams', 'Choose a series'],
        ['catalog-availability', 'Check what is available before enrolling', 'The catalog includes published series with available practice content. Open a product page to see its current tests and details; availability may change as content is maintained.', 'index.php#exams', 'Browse available tests'],
        ['account-and-enrollment', 'Keep learning connected to your account', 'Sign in before starting paid enrollment so the purchase and course access can be associated with your account. Use the dashboard to return to your learning.', 'auth.php', 'Sign in or create an account'],
        ['payments-and-receipts', 'Know where to find purchase records', 'After a successful purchase, check Orders for the order record and available invoice. If access or payment status looks wrong, contact support with the order reference.', 'orders.php', 'Open orders'],
        ['coupons-and-pricing', 'Check price and coupon terms before checkout', 'The product page and checkout show the current payable price. If a coupon field is offered, confirm the code is accepted and the final total before paying.', 'subscription.php', 'Review available plans'],
        ['responsible-preparation', 'Prepare consistently, not endlessly', 'Short, focused sessions with review are easier to sustain than unplanned marathon practice. Balance mock tests with syllabus study, revision, and rest.', 'blog.php', 'Read preparation articles'],
        ['accessibility-and-comfort', 'Make your practice setup comfortable', 'Use readable zoom, adequate lighting, and a stable surface. If a page or question is difficult to use, tell support which page and device you used.', 'info.php?page=contact', 'Contact support'],
        ['privacy-and-account-safety', 'Protect your account while you practise', 'Use a unique password and never share passwords or one-time codes in support messages. Review the privacy information to understand how account data is handled.', 'info.php?page=privacy', 'Read the privacy policy'],
        ['feedback-and-support', 'Help us improve the learning experience', 'If a question, result, enrollment, or page appears incorrect, send a concise description and the relevant test or order reference. Never include payment credentials.', 'info.php?page=contact', 'Get in touch'],
    ];
    $guideMarkup = '';
    foreach ($guides as $index => [$id, $title, $body, $href, $label]) {
        $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
        $officialSources = $id === 'check-official-notices'
            ? '<div class="home-official-sources"><span>Official recruitment updates:</span><a href="https://sbi.bank.in/web/careers/current-openings" target="_blank" rel="noopener noreferrer">SBI Careers <span aria-hidden="true">↗</span></a><a href="https://www.ibps.in/" target="_blank" rel="noopener noreferrer">IBPS <span aria-hidden="true">↗</span></a></div>'
            : '';
        $guideMarkup .= '<section class="home-guide-section" id="' . e($id) . '" aria-labelledby="' . e($id) . '-title">'
            . '<div class="home-guide-inner"><span class="home-guide-number" aria-hidden="true">' . $number . '</span>'
            . '<div class="home-guide-copy"><p class="home-guide-kicker">Practice guide ' . $number . '</p>'
            . '<h2 id="' . e($id) . '-title">' . e($title) . '</h2><p>' . e($body) . '</p>'
            . '<a href="' . e($href) . '">' . e($label) . '<span aria-hidden="true"> →</span></a>' . $officialSources . '</div>'
            . '<span class="home-guide-orbit" aria-hidden="true"></span></div></section>';
    }

    $faqGroups = [
        'Getting started' => [
            ['What is RankSetu?', 'RankSetu is a practice platform where learners can browse published exam series, attempt available tests, and review saved results.'],
            ['How do I find a test series for my exam?', 'Open the exam catalog, choose a published series, and check its stage and description. Use the product page to review the tests currently available.'],
            ['Do I need an account to browse the site?', 'You can browse public pages without signing in. An account is required for features that need saved progress or enrollment.'],
            ['How do I create an account?', 'Choose Log in or Dashboard from the site navigation and follow the account form. Use an email address you can access.'],
            ['Can I try a practice test before enrolling?', 'Use the Free sample link where available to preview the practice flow. The sample may have different content from a paid series.'],
            ['Where do I see series details?', 'Open a series from the catalog. Its product page lists the current available tests and the details recorded for that series.'],
            ['Are all exam categories always available?', 'No. The catalog shows published series with available practice content. Check it again later if the series you need is not listed.'],
            ['Can I use RankSetu on a phone?', 'Yes. Pages are designed to adapt to common phone, tablet, and desktop screen sizes. A current browser and stable connection are recommended.'],
            ['Do I need to install an app?', 'No app installation is required to use the website in a supported browser.'],
            ['How should I choose between two series?', 'Compare the exam stage, description, current test availability, question count, timing, and price shown on each product page.'],
        ],
        'Tests and results' => [
            ['How do I start a test?', 'Sign in if the test requires an account, open an available series, and follow the instructions shown before starting the test.'],
            ['How long is each test?', 'Time limits vary by test. Check the product or test instructions for the time displayed for the specific set you are about to attempt.'],
            ['How many questions are in a test?', 'Question counts can differ between test sets. Check the test or series details before beginning.'],
            ['Can I pause a timed test?', 'Treat a timed attempt as a continuous session unless the on-screen instructions explicitly provide a pause option.'],
            ['What happens when I submit a test?', 'The site records the submitted attempt and shows a result page with the score information available for that test.'],
            ['Can I retake a test?', 'You can start another attempt when the test is available to you. Submitted attempts are retained separately so you can review your history.'],
            ['Where can I see my past scores?', 'Open Test history from the navigation or dashboard to review saved attempts and their result pages.'],
            ['How is my percentage score displayed?', 'The result page calculates a percentage from the score and total questions recorded for that submitted attempt.'],
            ['Why is my attempt missing from history?', 'Confirm that you submitted the test while signed in to the account you are viewing. If it is still missing, contact support with the test name and approximate date.'],
            ['Can I review a submitted result later?', 'Use the result link in Recent test activity or Test history when it is available for that attempt.'],
        ],
        'Learning and progress' => [
            ['What does the performance trend show?', 'The dashboard trend visualizes saved test percentages over your latest recorded attempts. It updates as new attempts are submitted.'],
            ['What does score distribution mean?', 'It groups your saved attempt percentages into score ranges to give you a quick view of how those attempts are distributed.'],
            ['How is my average score calculated?', 'The dashboard average uses the percentage scores recorded for your saved attempts. It is a practice indicator, not an official exam score.'],
            ['Why is my dashboard chart empty?', 'Charts need saved test attempts to display. Submit a practice test while signed in, then revisit your dashboard.'],
            ['Does RankSetu provide subject-wise performance?', 'The dashboard displays only the performance data currently recorded for your tests. Subject-level charts appear only if a test provides that data.'],
            ['How should I use a low score?', 'Review the questions you missed, identify the topics to revisit, and plan a focused practice session. One result alone does not define your ability.'],
            ['Should I repeat the same test immediately?', 'Review your answers first. A later attempt after revision gives you a more useful comparison than repeating from memory right away.'],
            ['Can mock-test scores predict selection?', 'No. Practice scores cannot guarantee or reliably predict a recruitment outcome. Selection depends on official exam rules and many factors beyond a mock test.'],
            ['How often should I practise?', 'Choose a sustainable schedule that leaves time for learning and review. The right frequency depends on your preparation stage and personal routine.'],
            ['Where can I find preparation advice?', 'Visit the site blog for published preparation articles, and use the official recruiting authority for exam rules and notices.'],
        ],
        'Plans, payments, and access' => [
            ['Where can I see the price?', 'The current price is shown on the relevant product or plan page and again at checkout before payment.'],
            ['Can I use a coupon code?', 'If a coupon field is offered at checkout, enter the code and apply it. Check that the discount and revised total are displayed before continuing.'],
            ['How do I know a coupon was applied?', 'The checkout summary should show the accepted discount and updated total. Do not proceed if the displayed amount is not what you expect.'],
            ['How is an online payment processed?', 'The checkout identifies the payment provider and shows the total before you continue. Follow the provider’s payment flow and do not share one-time codes with anyone.'],
            ['Where can I find my order?', 'Open Orders from your account navigation or dashboard to view recent purchase records.'],
            ['Can I download an invoice?', 'If an invoice is available for an order, use the invoice action in Orders or the dashboard order history.'],
            ['I paid but cannot access the series. What should I do?', 'Check that you are signed in to the purchasing account and review Orders. If access is still missing, contact support with the order reference; do not send card details or passwords.'],
            ['What if my payment appears pending or failed?', 'Check the order status before trying again to avoid duplicate payments. If the status remains unclear, contact support with the order reference and approximate time.'],
            ['How long does access last?', 'Access depends on the product or plan terms shown before purchase. Review those terms at checkout and in the relevant plan details.'],
            ['How do I request a refund?', 'Read the published refund policy and contact support with your order reference and reason. Requests are handled according to the stated policy and applicable requirements.'],
        ],
        'Account, privacy, and support' => [
            ['I forgot my password. How can I sign in?', 'Use the password reset option on the sign-in page and follow the instructions sent to your registered email address.'],
            ['How do I update my account details?', 'Sign in and use the account or profile controls available on the site. Contact support if you cannot change a required detail.'],
            ['Can I share my account with someone else?', 'Keep your login credentials private. Account activity and saved progress are associated with the account used to sign in.'],
            ['What information should I include in a support request?', 'Describe the issue, the page or test involved, your device/browser, and a relevant order or attempt reference. Never include your password, one-time code, or full payment details.'],
            ['How do I report a question that looks incorrect?', 'Contact support with the series, test name, question number, and a short description of the issue so it can be reviewed.'],
            ['How is my account information handled?', 'Review the site Privacy policy for information about account data and related practices.'],
            ['Does RankSetu guarantee exam success?', 'No. The platform provides practice tools and does not guarantee selection, a rank, a score, or employment.'],
            ['Where can I read the terms and policies?', 'Use the Policies links in the site footer to open the current Terms, Privacy, Refund, and Disclaimer pages.'],
            ['What should I do if a page does not load?', 'Refresh once, check your internet connection, and try a current browser. If the issue persists, contact support with the page address and time of the problem.'],
            ['How can I contact RankSetu?', 'Use the Contact link in the site navigation or footer and include enough detail for support to understand your question.'],
        ],
    ];

    $faqMarkup = '';
    foreach ($faqGroups as $category => $questions) {
        $items = '';
        foreach ($questions as [$question, $answer]) {
            $items .= '<details class="home-faq-item" data-faq-text="' . e(strtolower($question . ' ' . $answer)) . '"><summary>' . e($question) . '</summary><p>' . e($answer) . '</p></details>';
        }
        $faqMarkup .= '<details class="home-faq-category"' . ($faqMarkup === '' ? ' open' : '') . '><summary>' . e($category) . '<span class="home-faq-count">' . count($questions) . '</span></summary><div class="home-faq-list">' . $items . '</div></details>';
    }

    $guideMarkup = '<section id="practice-guides" class="home-guides" aria-labelledby="home-guides-heading"><div class="home-guides-wrap">'
        . '<div class="home-guides-intro"><div><p class="home-guides-kicker">A better way to practise</p><h2 id="home-guides-heading">Make every practice session count</h2><p>Practical guidance for choosing tests, reviewing results, and preparing with clear expectations.</p></div><a href="index.php#exams">Explore test series <span aria-hidden="true">→</span></a></div>'
        . '<div class="home-guide-grid">' . $guideMarkup . '</div></div></section>';

    return $guideMarkup
        . '<section id="faq" class="home-faq-section" aria-labelledby="home-faq-heading"><div class="home-faq-wrap">'
        . '<div class="home-faq-intro"><p class="home-faq-kicker">Straightforward answers</p><h2 id="home-faq-heading">Frequently asked questions</h2>'
        . '<p>Find practical information about choosing a series, taking tests, tracking results, and managing your account.</p></div>'
        . '<label class="home-faq-search-label" for="home-faq-search">Search all 50 questions</label><div class="home-faq-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m16 16 5 5"></path></svg><input id="home-faq-search" type="search" placeholder="Try “payment”, “test history” or “phone”" autocomplete="off"></div>'
        . '<p class="home-faq-result-count" aria-live="polite">50 answers across 5 topics</p><div class="home-faq-groups">' . $faqMarkup . '</div>'
        . '<p class="home-faq-contact">Still need help? <a href="info.php?page=contact">Contact support</a>.</p></div></section>'
        . '<style>
        .home-guide-section{padding:22px 20px;background:#f5f3ed}
        .home-guide-section:nth-of-type(even){background:#fff}
        .home-guide-inner{position:relative;display:grid;grid-template-columns:64px minmax(0,1fr);align-items:center;gap:18px;width:min(980px,100%);min-height:142px;margin:auto;padding:25px 32px;overflow:hidden;border:1px solid #e5e0d4;border-radius:18px;background:#fff;box-shadow:0 12px 32px rgba(22,35,63,.045)}
        .home-guide-section:nth-of-type(even) .home-guide-inner{background:linear-gradient(120deg,#fff,#fbf8f0)}
        .home-guide-number{display:grid;width:54px;height:54px;place-items:center;border:1px solid #ead7ad;border-radius:16px;background:#fbf4e4;color:#9c3b2e;font:700 1rem/1 ui-monospace,monospace}
        .home-guide-copy{position:relative;z-index:1;max-width:72ch}
        .home-guide-kicker{margin:0 0 4px;color:#9c3b2e;font-size:.7rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
        .home-guide-copy h2{margin:0 0 6px;color:#16233f;font-size:clamp(1.15rem,2vw,1.45rem);letter-spacing:-.025em}
        .home-guide-copy>p:not(.home-guide-kicker){margin:0;color:#5d6370;font-size:.9rem;line-height:1.65}
        .home-guide-copy>a{display:inline-flex;align-items:center;gap:4px;margin-top:10px;color:#9c3b2e;font-size:.8rem;font-weight:750;text-decoration:none}
        .home-guide-copy>a:hover{text-decoration:underline;text-underline-offset:3px}
        .home-official-sources{display:flex;align-items:center;flex-wrap:wrap;gap:8px 14px;margin-top:12px;color:#69707a;font-size:.72rem}
        .home-official-sources>span{font-weight:650}
        .home-official-sources a{color:#243a64;font-weight:750;text-decoration:underline;text-decoration-color:#c08a2e;text-underline-offset:3px}
        .home-official-sources a:hover{color:#9c3b2e}
        .home-guide-orbit{position:absolute;right:-33px;bottom:-75px;width:150px;height:150px;border:1px solid rgba(192,138,46,.19);border-radius:50%;box-shadow:0 0 0 18px rgba(192,138,46,.035),0 0 0 38px rgba(192,138,46,.025)}
        .home-faq-section{padding:74px 20px;background:linear-gradient(180deg,#f7f5ef,#fff)}
        .home-faq-wrap{width:min(920px,100%);margin:auto}
        .home-faq-intro{max-width:700px;margin:0 auto 28px;text-align:center}
        .home-faq-kicker{margin:0 0 8px;color:#9c3b2e;font-size:.74rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
        .home-faq-intro h2{margin:0 0 10px;color:#16233f;font-size:clamp(1.8rem,4vw,2.65rem);letter-spacing:-.04em}
        .home-faq-intro>p:last-child{margin:0;color:#646a76;line-height:1.65}
        .home-faq-search-label{display:block;margin:0 0 8px;color:#16233f;font-size:.8rem;font-weight:750}
        .home-faq-search{display:flex;align-items:center;gap:11px;padding:0 15px;border:1px solid #d9d5ca;border-radius:12px;background:#fff;box-shadow:0 5px 18px rgba(22,35,63,.04)}
        .home-faq-search:focus-within{border-color:#9c3b2e;box-shadow:0 0 0 3px rgba(156,59,46,.12)}
        .home-faq-search svg{width:19px;height:19px;flex:none;fill:none;stroke:#747987;stroke-width:1.8;stroke-linecap:round}
        .home-faq-search input{width:100%;min-width:0;min-height:52px;border:0;outline:0;background:transparent;color:#1c1a15;font:inherit;font-size:.92rem}
        .home-faq-search input::placeholder{color:#8b8d94}
        .home-faq-result-count{margin:11px 0 16px;color:#727681;font-size:.75rem}
        .home-faq-groups{display:grid;gap:11px}
        .home-faq-category{overflow:hidden;border:1px solid #e4e0d6;border-radius:13px;background:#fff;box-shadow:0 5px 18px rgba(22,35,63,.035)}
        .home-faq-category>summary{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;color:#16233f;font-size:.94rem;font-weight:780;cursor:pointer;list-style:none}
        .home-faq-category>summary::-webkit-details-marker,.home-faq-item>summary::-webkit-details-marker{display:none}
        .home-faq-category>summary:after{content:"+";color:#9c3b2e;font-size:1.25rem;font-weight:500}
        .home-faq-category[open]>summary:after{content:"−"}
        .home-faq-count{margin-left:auto;padding:3px 8px;border-radius:999px;background:#f5f1e7;color:#78633e;font-size:.68rem;font-weight:750}
        .home-faq-list{padding:0 18px 7px}
        .home-faq-item{border-top:1px solid #eeece6}
        .home-faq-item>summary{position:relative;padding:14px 30px 14px 0;color:#292f3a;font-size:.86rem;font-weight:680;line-height:1.5;cursor:pointer;list-style:none}
        .home-faq-item>summary:after{position:absolute;top:12px;right:2px;content:"+";color:#9c3b2e;font-size:1.18rem}
        .home-faq-item[open]>summary{color:#9c3b2e}
        .home-faq-item[open]>summary:after{content:"−"}
        .home-faq-item>p{max-width:75ch;margin:0;padding:0 24px 15px 0;color:#626874;font-size:.83rem;line-height:1.7}
        .home-faq-item[hidden],.home-faq-category[hidden]{display:none!important}
        .home-faq-contact{margin:20px 0 0;color:#626874;text-align:center;font-size:.86rem}
        .home-faq-contact a{color:#9c3b2e;font-weight:750;text-underline-offset:3px}
        @media(max-width:640px){.home-guide-section{padding:12px 14px}.home-guide-inner{grid-template-columns:44px minmax(0,1fr);gap:12px;min-height:0;padding:19px 16px;border-radius:14px}.home-guide-number{width:40px;height:40px;border-radius:12px;font-size:.83rem}.home-guide-kicker{font-size:.64rem}.home-guide-copy h2{font-size:1.08rem}.home-guide-copy>p:not(.home-guide-kicker){font-size:.82rem}.home-guide-orbit{right:-70px;bottom:-95px}.home-faq-section{padding:48px 16px}.home-faq-category>summary{padding:14px;font-size:.87rem}.home-faq-list{padding:0 14px 5px}.home-faq-item>summary{font-size:.82rem}}
        @media(prefers-reduced-motion:reduce){.home-guide-section *,.home-faq-section *{scroll-behavior:auto!important;animation:none!important;transition:none!important}}

        .home-guides{padding:76px 20px;background:#f4f5f6}
        .home-guides-wrap{width:min(1160px,100%);margin:auto}
        .home-guides-intro{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin:0 auto 25px}
        .home-guides-kicker{margin:0 0 7px;color:#8f3a30;font-size:.72rem;font-weight:800;letter-spacing:.13em;text-transform:uppercase}
        .home-guides-intro h2{margin:0 0 8px;color:#192a42;font-size:clamp(1.8rem,3.6vw,2.55rem);letter-spacing:-.04em;text-align:left}
        .home-guides-intro>div>p:last-child{max-width:64ch;margin:0;color:#647080;font-size:.91rem;line-height:1.65}
        .home-guides-intro>a{display:inline-flex;flex:none;align-items:center;gap:8px;padding:10px 14px;border:1px solid #d6dce4;border-radius:9px;background:#fff;color:#253954;font-size:.8rem;font-weight:750;transition:border-color .18s ease,background .18s ease,transform .18s ease}
        .home-guides-intro>a:hover{transform:translateY(-1px);border-color:#9c3b2e;background:#fbf8f4}
        .home-guide-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));align-items:stretch;gap:13px}
        .home-guide-section{min-width:0;padding:0;background:transparent}
        .home-guide-inner,.home-guide-section:nth-of-type(even) .home-guide-inner{height:100%;min-height:0;grid-template-columns:46px minmax(0,1fr);gap:15px;padding:20px 21px;border:1px solid #e1e5e9;border-radius:13px;background:#fff;box-shadow:0 4px 14px rgba(22,35,63,.035);transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}
        .home-guide-inner:hover{transform:translateY(-2px);border-color:#cbd3dc;box-shadow:0 12px 26px rgba(22,35,63,.075)}
        .home-guide-number{width:40px;height:40px;border:1px solid #e2e6eb;border-radius:10px;background:#f4f6f8;color:#51627a;font-size:.78rem}
        .home-guide-kicker{margin-bottom:4px;color:#8e5144;font-size:.62rem;letter-spacing:.1em}
        .home-guide-copy h2{margin:0 0 6px;color:#1d2e46;font-size:1.04rem;letter-spacing:-.015em;line-height:1.35}
        .home-guide-copy>p:not(.home-guide-kicker){color:#616c79;font-size:.8rem;line-height:1.6}
        .home-guide-copy>a{margin-top:9px;color:#813c32;font-size:.75rem}
        .home-guide-orbit{display:none}
        .home-official-sources{gap:7px 12px;margin-top:10px;font-size:.68rem}
        .home-faq-section{padding:76px 20px;background:#fff}
        .home-faq-intro{margin-bottom:25px}
        .home-faq-kicker{color:#8f3a30}
        .home-faq-intro h2{color:#192a42}
        .home-faq-search{border-color:#dfe3e8;border-radius:10px;box-shadow:none}
        .home-faq-search:focus-within{border-color:#9c3b2e;box-shadow:0 0 0 3px rgba(156,59,46,.1)}
        .home-faq-category{border-color:#e1e5e9;border-radius:11px;box-shadow:none}
        .home-faq-category>summary{color:#22344d;font-size:.88rem}
        .home-faq-item>summary{font-size:.83rem}
        #features.section h2,#exams.section h2{color:#192a42;letter-spacing:-.035em}
        #exams .container>h3{color:#56657a!important;font-size:.78rem!important;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
        #features .feature .icon{border:1px solid #e3e6e9;border-radius:10px;background:#f1f3f5;filter:grayscale(1);opacity:.72}
        #exams .exam-visual img{filter:saturate(.58)}
        .home-motion-ready .home-reveal{opacity:0;transform:translateY(12px);transition:opacity .42s ease,transform .42s cubic-bezier(.2,.7,.2,1);transition-delay:var(--reveal-delay,0ms)}
        .home-motion-ready .home-reveal.is-visible{opacity:1;transform:translateY(0)}
        .hero{position:relative;isolation:isolate;overflow:hidden;padding:clamp(76px,9vw,126px) 0 44px!important;background:radial-gradient(ellipse at 82% 6%,rgba(190,143,63,.19),transparent 34%),linear-gradient(128deg,#14233d 0%,#1d3153 58%,#223c60 100%)!important;text-align:left!important}
        .hero:before,.hero:after{position:absolute;z-index:-1;pointer-events:none;content:""}
        .hero:before{inset:0;background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);background-size:48px 48px;mask-image:linear-gradient(90deg,transparent,#000 40%,#000)}
        .hero:after{top:12%;right:-150px;width:min(42vw,560px);aspect-ratio:1;border:1px solid rgba(235,196,124,.12);border-radius:50%;box-shadow:0 0 0 36px rgba(235,196,124,.025),0 0 0 82px rgba(235,196,124,.02)}
        .hero .container{position:relative;z-index:1;width:min(1160px,calc(100% - 48px));padding:0;margin:0 auto}
        .hero .hero-title{display:flex;flex-wrap:wrap;align-items:baseline;justify-content:flex-start;gap:0 .22em;width:100%;min-height:0;margin:0;color:#fff;font-size:clamp(2.7rem,6.2vw,5rem);font-weight:750;letter-spacing:-.055em;line-height:1.03;white-space:normal;animation:home-title-enter .65s cubic-bezier(.2,.7,.2,1) both}
        .hero-prefix,.hero-suffix{display:inline-block;color:#f7f8fb}
        .hero-category{display:inline-block;width:auto;min-height:0;color:#e8bd70;text-align:left;opacity:1;transform:none;transition:none}
        .hero>.container>p{max-width:59ch;margin:20px 0 0;color:#d1d9e5;font-size:clamp(1rem,1.45vw,1.16rem);line-height:1.72;text-align:left}
        .main-search{width:min(720px,100%);margin:28px 0 0}
        .main-search-form{min-height:68px;gap:12px;padding:7px 8px 7px 18px;border:1px solid rgba(255,255,255,.58);border-radius:13px;background:#fff;box-shadow:0 16px 42px rgba(6,14,28,.24);transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease}
        .main-search-form:focus-within{transform:translateY(-1px);border-color:#edc876;box-shadow:0 0 0 4px rgba(237,200,118,.2),0 18px 42px rgba(6,14,28,.24)}
        .main-search-form svg{width:21px;height:21px;margin:0;color:#6d7787}
        .main-search-input{min-height:48px;padding:10px 4px;color:#1d2b40;font-size:1rem}
        .main-search-input::placeholder{color:#7a8492}
        .main-search-submit{min-height:50px;padding:0 21px;border-radius:9px;background:#9c3b2e;color:#fff;font-size:.88rem;transition:background .18s ease,transform .18s ease}
        .main-search-submit:hover{transform:translateY(-1px);background:#7f3027}
        .search-suggestions{justify-content:flex-start;align-items:center;gap:8px;margin-top:14px;color:#cbd3df;font-size:.76rem}
        .search-suggestion{min-height:32px;padding:5px 11px;border-color:rgba(226,234,245,.28);border-radius:999px;background:rgba(255,255,255,.055);color:#f5f7fa;font-size:.74rem;transition:background .18s ease,border-color .18s ease,transform .18s ease}
        .search-suggestion:hover,.search-suggestion:focus-visible{transform:translateY(-1px);border-color:rgba(237,200,118,.72);background:rgba(255,255,255,.12)}
        .hero .main-search-status{min-height:1.25em;margin:10px 2px 0;color:#d6deea;font-size:.76rem;line-height:1.5}
        .hero .main-search+.btn-white{display:inline-flex;min-height:46px;align-items:center;margin-top:22px;padding:0 17px;border:1px solid rgba(245,247,250,.35);border-radius:9px;background:rgba(255,255,255,.07);color:#fff;font-size:.84rem;transition:background .18s ease,border-color .18s ease,transform .18s ease}
        .hero .main-search+.btn-white:hover{transform:translateY(-1px);border-color:#e8bd70;background:rgba(255,255,255,.13)}
        .hero .stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0;width:min(830px,100%);max-width:none;margin:40px 0 0;padding-top:23px;border-top:1px solid rgba(228,235,243,.18)}
        .hero .stat{min-width:0;padding:0 20px;border-right:1px solid rgba(228,235,243,.16);border-radius:0;background:transparent;text-align:left}
        .hero .stat:first-child{padding-left:0}
        .hero .stat:last-child{border-right:0}
        .hero .stat .number{color:#f0c87a;font-size:1.72rem;line-height:1.2}
        .hero .stat>div:last-child{margin-top:5px;color:#c0cad8;font-size:.75rem}
        #features.section,#exams.section{padding:76px 0;background:#fff}
        #features.section>.container,#exams.section>.container{width:min(1160px,calc(100% - 48px));padding:0}
        #features.section h2{margin:0 0 28px;color:#18283f;font-size:clamp(1rem,4.2vw,2.55rem);font-weight:700;letter-spacing:-.04em;line-height:1.14;text-align:left}
        #exams .catalog-heading{display:flex;align-items:center;justify-content:space-between;gap:24px;margin:0 0 34px;padding:0 0 24px;border-bottom:1px solid #e1e5ea}
        #exams .catalog-heading-copy{min-width:0}
        #exams .catalog-kicker{display:flex;align-items:center;gap:8px;margin:0 0 8px;color:#8b5c28;font-size:.7rem;font-weight:800;letter-spacing:.16em;line-height:1.2;text-transform:uppercase}
        #exams .catalog-kicker:before{width:19px;height:2px;border-radius:2px;background:#b8863b;content:""}
        #exams .catalog-heading h2{margin:0;color:#18283f;font-size:clamp(1.2rem,3.1vw,2.55rem);font-weight:750;letter-spacing:-.045em;line-height:1.12;text-align:left;white-space:nowrap}
        #exams .catalog-subtitle{margin:10px 0 0;color:#647184;font-size:.92rem;line-height:1.55}
        #exams .catalog-count{display:flex;flex:0 0 auto;align-items:center;gap:10px;padding:10px 14px;border:1px solid #e0e5eb;border-radius:12px;background:#fff;color:#5c6878}
        #exams .catalog-count strong{color:#1d3151;font-size:1.35rem;font-weight:800;line-height:1}
        #exams .catalog-count span{font-size:.7rem;font-weight:700;line-height:1.35}
        #features .study-note{margin:14px 0 20px;color:#606b79;font-size:.86rem;text-align:left}
        #features .features{grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
        #features .feature{min-width:0;padding:23px;border:1px solid #e4e8ed;border-top:2px solid #9c3b2e;border-radius:13px;background:#fff;box-shadow:0 5px 17px rgba(21,39,63,.035);text-align:left;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}
        #features .feature:hover{transform:translateY(-3px);border-color:#d0d7e0;box-shadow:0 13px 28px rgba(21,39,63,.09)}
        #features .feature .icon{display:flex;width:34px;height:34px;align-items:center;justify-content:center;margin:0 0 17px;border-radius:9px;background:#f7f0e7;color:#8d3b30;font-size:.72rem;font-weight:800}
        #features .feature h3{margin:0 0 7px;color:#1b2b42;font-size:1rem;font-weight:720}
        #features .feature p{color:#606b79;font-size:.84rem;line-height:1.65}
        #features .study-note:last-child{margin-top:22px;padding:13px 15px;border-left:3px solid #bd8b43;border-radius:0 8px 8px 0;background:#faf7f0}
        #exams.section{background:#f5f6f7}
        #exams .container>h3{margin:28px 0 13px!important;color:#344763!important;font-size:1.04rem!important;font-weight:750;letter-spacing:.01em}
        #exams .exams{display:flex;flex-flow:row nowrap;align-items:stretch;gap:16px;overflow-x:auto;overscroll-behavior-inline:contain;padding:3px 3px 14px;scroll-snap-type:x mandatory;scrollbar-width:thin}
        #exams .exam-card{display:flex;flex:0 0 clamp(260px,31%,360px);min-width:0;flex-direction:column;overflow:hidden;scroll-snap-align:start;border:1px solid #e1e5ea;border-radius:14px;background:#fff;box-shadow:0 5px 18px rgba(21,39,63,.045);transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}
        #exams .exam-card:hover{transform:translateY(-3px);border-color:#cdd5df;box-shadow:0 15px 32px rgba(21,39,63,.11)}
        #exams .exam-visual{height:154px;background:#e9edf1}
        #exams .exam-visual img{transition:transform .35s ease}
        #exams .exam-card:hover .exam-visual img{transform:scale(1.025)}
        #exams .exam-header{padding:16px 18px 0;background:#fff;color:#1b2b42;font-size:1.15rem;font-weight:750;text-align:left}
        #exams .exam-body{display:flex;flex:1;flex-direction:column;padding:8px 18px 18px}
        #exams .exam-body h3{margin:0 0 7px;color:#6d7784;font-size:.73rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase}
        #exams .exam-body p{min-height:68px;margin:0;color:#5d6876;font-size:.83rem;line-height:1.6}
        #exams .exam-meta{margin-top:14px;padding-top:13px;border-color:#e9edf1;color:#364355;font-size:.8rem}
        #exams .exam-price{color:#243a64;font-size:1rem;font-weight:800}
        #exams .exam-price.exam-price-free{display:inline-flex;align-items:center;justify-content:center;padding:7px 13px;border:1px solid #a8dfbd;border-radius:999px;background:#eaf8ef;color:#176b3a;font-size:.82rem;font-weight:900;letter-spacing:.1em;line-height:1;box-shadow:0 2px 7px rgba(23,107,58,.12)}
        #exams .exam-link{display:flex;align-items:center;justify-content:center;min-height:44px;margin-top:14px;border-radius:9px;background:#1d3151;color:#fff;font-size:.82rem;transition:background .18s ease,transform .18s ease}
        #exams .exam-link:hover{transform:translateY(-1px);background:#9c3b2e}
        #exams .exam-card[hidden]{display:none!important}
        #exams .exam-card:focus-within{outline:3px solid rgba(156,59,46,.35);outline-offset:3px}
        .benefits{max-width:1000px;gap:14px}
        .benefit-item{padding:22px;border:1px solid #e3e6eb;border-left:3px solid #566b85;border-radius:12px;background:#fff;box-shadow:none}
        .benefit-item h4{margin:0 0 7px;color:#263a55;font-size:.95rem}
        .benefit-item p{color:#606b79;font-size:.84rem;line-height:1.65}
        .benefit-panel{gap:14px}
        .benefit-box{padding:24px;border:1px solid #e3e6eb;border-top:2px solid #61748d;border-radius:13px;box-shadow:0 5px 18px rgba(21,39,63,.035)}
        .benefit-box h3,.benefit-box.positive h3,.benefit-box.caution h3{color:#243750;font-size:1.06rem}
        .benefit-box.positive,.benefit-box.caution{border-top-color:#61748d}
        .benefit-box.positive li::before{content:"✓";color:#426b58}
        .benefit-box.caution li::before{content:"•";color:#8f6b32}
        .benefit-box li{color:#566170;font-size:.87rem;line-height:1.6}
        @media(max-width:900px){#features .features{grid-template-columns:repeat(2,minmax(0,1fr))}.hero .container{width:min(100% - 40px,760px)}.hero .hero-title{font-size:clamp(2.8rem,7vw,4.2rem)}.home-guide-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:600px){.hero{padding:66px 0 34px!important}.hero .container{width:calc(100% - 36px)}.hero .hero-title{gap:0 .17em;font-size:clamp(2.3rem,10vw,3.45rem);line-height:1.02}.hero>.container>p{max-width:38ch;margin-top:16px;font-size:.96rem;line-height:1.65}.main-search{margin-top:22px}.main-search-form{min-height:58px;gap:7px;padding:5px 5px 5px 12px;border-radius:11px}.main-search-input{min-height:44px;padding:8px 2px;font-size:.9rem}.main-search-submit{min-height:44px;padding:0 13px;font-size:.78rem}.search-suggestions{gap:7px;margin-top:11px}.search-suggestion{min-height:30px;padding:4px 9px;font-size:.7rem}.hero .stats{gap:0;margin-top:29px;padding-top:18px}.hero .stat{padding:0 9px}.hero .stat .number{font-size:1.35rem}.hero .stat>div:last-child{font-size:.67rem;line-height:1.35}#features.section,#exams.section{padding:52px 0}#features.section>.container,#exams.section>.container{width:calc(100% - 32px)}#features.section h2{margin-bottom:21px;font-size:clamp(1rem,4.3vw,1.35rem)}#exams .catalog-heading{align-items:flex-start;gap:12px;margin-bottom:25px;padding-bottom:18px}#exams .catalog-heading h2{font-size:clamp(.78rem,3.6vw,1.15rem);letter-spacing:-.035em}#exams .catalog-kicker{margin-bottom:7px;font-size:.58rem}#exams .catalog-kicker:before{width:14px}#exams .catalog-subtitle{max-width:34ch;margin-top:7px;font-size:.74rem;line-height:1.45}#exams .catalog-count{gap:6px;padding:7px 9px;border-radius:9px}#exams .catalog-count strong{font-size:1.05rem}#exams .catalog-count span{font-size:.59rem}#features .features,.benefits,.benefit-panel{grid-template-columns:minmax(0,1fr);gap:12px}#exams .exams{display:grid;grid-template-columns:minmax(0,1fr);overflow:visible;padding:0;scroll-snap-type:none}#exams .exam-card{width:100%;min-width:0;flex:none}#features .feature{padding:19px}#features .feature .icon{margin-bottom:11px}#exams .container>h3{margin-top:22px!important}#exams .exam-visual{height:165px}#exams .exam-body{padding:8px 16px 16px}#exams .exam-body p{min-height:0}.benefit-item,.benefit-box{padding:19px}.home-guides{padding:54px 16px}.home-guides-wrap{width:100%}.home-guides-intro{align-items:flex-start;flex-direction:column;gap:13px;margin-bottom:18px}.home-guide-grid{grid-template-columns:minmax(0,1fr);gap:10px}.home-guide-section{padding:0}.home-guide-inner,.home-guide-section:nth-of-type(even) .home-guide-inner{grid-template-columns:36px minmax(0,1fr);gap:11px;min-height:0;padding:16px 14px;border-radius:12px}.home-guide-number{width:34px;height:34px;border-radius:9px;font-size:.74rem}.home-guide-orbit{display:none}.home-faq-section{padding:48px 16px}.home-faq-intro{margin-bottom:22px}.home-faq-category>summary{padding:14px;font-size:.86rem}.home-faq-list{padding:0 13px 5px}.home-faq-item>summary{font-size:.82rem}}
        @media(max-width:360px){#exams .catalog-heading{flex-direction:column;gap:10px}#exams .catalog-heading-copy{width:100%}#exams .catalog-heading h2{font-size:1rem}#exams .catalog-count{align-self:flex-start}}
        @media(prefers-reduced-motion:reduce){.hero .hero-title{animation:none!important}.home-reveal,.home-reveal.is-visible{transition:none!important;transform:none!important;opacity:1!important}}
        @keyframes home-title-enter{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
        </style>';
}

function site_homepage_subscription_section(): string
{
    $catalog = available_course_catalog();
    $publishedTestCount = 0;
    foreach (array_keys($catalog) as $slug) $publishedTestCount += site_published_test_count((string) $slug);
    $courseCount = count($catalog);

    return '
<section class="section site-premium-showcase-section" aria-labelledby="subscription-heading">
  <div class="container">
    <div class="site-premium-showcase">
      <div class="site-premium-copy">
        <span class="premium-kicker">Premium access</span>
        <h2 id="subscription-heading">One plan for published exam practice</h2>
        <p>Review the current Premium price, access period, and included courses before subscribing. Coverage is based on published series and remains subject to the plan terms and subscription dates.</p>
        <div class="premium-cta-group">
          <a class="btn btn-primary" href="subscription.php">View plans</a>
          <a class="btn btn-white" href="index.php#exams">Browse exams</a>
        </div>
        <ul class="premium-feature-list" aria-label="Premium benefits">
          <li>See included courses before purchase</li>
          <li>Practice published exam series</li>
          <li>Track attempts from your account</li>
        </ul>
      </div>

      <div class="site-premium-visual" aria-hidden="true">
        <div class="premium-visual-card">
          <div class="premium-glow premium-glow-one"></div>
          <div class="premium-glow premium-glow-two"></div>
          <div class="premium-image-frame">
            <img src="img/fullmocktestseries.png" alt="FullMockTestSeries brand preview">
          </div>
          <div class="premium-mini-card premium-mini-card-top">
            <span class="mini-label">Published series</span>
            <strong>' . number_format($courseCount) . '</strong>
          </div>
          <div class="premium-mini-card premium-mini-card-bottom">
            <span class="mini-label">Available practice tests</span>
            <strong>' . number_format($publishedTestCount) . '</strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>';
}

function site_homepage_blog_section_markup(): string
{
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'blog-core.php';
    $posts = blog_public_posts(['limit' => 4]);
    if ($posts === []) return '';

    $cards = '';
    foreach ($posts as $post) {
        $category = blog_category_by_id((string) ($post['category_id'] ?? ''));
        $categoryName = (string) ($category['name'] ?? 'Latest article');
        $slug = rawurlencode((string) ($post['slug'] ?? ''));
        $title = trim((string) ($post['title'] ?? '')) ?: 'Untitled story';
        $excerpt = trim((string) ($post['excerpt'] ?? ''));
        if ($excerpt === '') $excerpt = blog_excerpt_from_content((string) ($post['content'] ?? ''), 150);
        $publishedAt = blog_post_timestamp(blog_post_published_at($post));
        $date = $publishedAt === false ? '' : date('M j, Y', $publishedAt);
        $image = trim((string) ($post['featured_image'] ?? ''));
        $imageMarkup = $image === '' ? '' : '<img class="home-blog-card-image" src="' . e($image) . '" alt="" loading="lazy">';
        $cards .= '<article class="home-blog-card">'
            . ($imageMarkup !== '' ? '<a class="home-blog-card-image-link" href="blog.php?slug=' . e($slug) . '" tabindex="-1" aria-hidden="true">' . $imageMarkup . '</a>' : '')
            . '<div class="home-blog-card-content">'
            . '<span class="home-blog-category">' . e($categoryName) . '</span>'
            . '<h3><a href="blog.php?slug=' . e($slug) . '">' . e($title) . '</a></h3>'
            . ($excerpt !== '' ? '<p class="home-blog-excerpt">' . e($excerpt) . '</p>' : '')
            . '<div class="home-blog-card-footer">'
            . '<span class="home-blog-meta">' . ($date !== '' ? e($date) . ' <span aria-hidden="true">·</span> ' : '') . e(blog_post_reading_time((string) ($post['content'] ?? ''))) . '</span>'
            . '<a class="home-blog-read-link" href="blog.php?slug=' . e($slug) . '">Read story <span aria-hidden="true">→</span></a>'
            . '</div></div></article>';
    }

    return '<section class="section home-blog-section" aria-labelledby="home-blog-heading"><div class="container">'
        . '<div class="home-blog-heading"><div><span class="home-blog-kicker">Ideas for your next step</span><h2 id="home-blog-heading">Latest from our blog</h2><p>Fresh strategies and thoughtful guidance for your exam preparation.</p></div>'
        . '<a class="home-blog-all-link" href="blog.php">Explore all stories <span aria-hidden="true">→</span></a></div>'
        . '<div class="home-blog-grid">' . $cards . '</div></div></section>';
}

function site_social_profiles(): array
{
    return [
        'Facebook' => ['env' => 'FACEBOOK_URL', 'constant' => 'HOSTINGER_FACEBOOK_URL', 'hosts' => ['facebook.com', 'www.facebook.com']],
        'LinkedIn' => ['env' => 'LINKEDIN_URL', 'constant' => 'HOSTINGER_LINKEDIN_URL', 'hosts' => ['linkedin.com', 'www.linkedin.com']],
        'Twitter' => ['env' => 'TWITTER_URL', 'constant' => 'HOSTINGER_TWITTER_URL', 'hosts' => ['twitter.com', 'www.twitter.com', 'x.com', 'www.x.com']],
        'Instagram' => ['env' => 'INSTAGRAM_URL', 'constant' => 'HOSTINGER_INSTAGRAM_URL', 'hosts' => ['instagram.com', 'www.instagram.com']],
        'YouTube' => ['env' => 'YOUTUBE_URL', 'constant' => 'HOSTINGER_YOUTUBE_URL', 'hosts' => ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be']],
    ];
}

function site_social_icon(string $network): string
{
    $paths = [
        'Facebook' => '<path d="M13.4 21v-8h2.7l.4-3h-3.1V8.1c0-.9.3-1.5 1.5-1.5h1.7V3.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3V10H7.3v3h2.8v8h3.3z"/>',
        'LinkedIn' => '<path d="M5.1 3.7a2 2 0 1 1 0 4 2 2 0 0 1 0-4ZM3.4 9h3.4v11.6H3.4V9Zm5.5 0h3.2v1.6h.1c.5-.9 1.6-1.9 3.3-1.9 3.5 0 4.2 2.3 4.2 5.3v6.6h-3.4v-5.8c0-1.4 0-3.1-1.9-3.1s-2.2 1.5-2.2 3v5.9H8.9V9Z"/>',
        'Twitter' => '<path d="M18.9 3h2.9l-6.4 7.3 7.5 10h-5.9l-4.6-6-5.3 6H4.2l6.9-7.9L3.9 3h6l4.2 5.6L18.9 3Zm-1 15.5h1.6L9 4.7H7.3l10.6 13.8Z"/>',
        'Instagram' => '<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.6" cy="6.6" r="1.2"/>',
        'YouTube' => '<path d="M23 7.1a3 3 0 0 0-2.1-2.1C19.1 4.5 12 4.5 12 4.5s-7.1 0-8.9.5A3 3 0 0 0 1 7.1 31 31 0 0 0 .5 12c0 1.6.2 3.3.5 4.9a3 3 0 0 0 2.1 2.1c1.8.5 8.9.5 8.9.5s7.1 0 8.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .5-4.9 31 31 0 0 0-.5-4.9ZM9.8 15.5v-7l6 3.5-6 3.5Z"/>',
    ];

    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . ($paths[$network] ?? '') . '</svg>';
}

function site_social_links_markup(): string
{
    $markup = '';
    foreach (site_social_profiles() as $network => $profile) {
        $url = getenv($profile['env']);
        if ($url === false || trim($url) === '') $url = defined($profile['constant']) ? constant($profile['constant']) : '';
        $url = is_string($url) ? trim($url) : '';
        $parts = $url !== '' ? parse_url($url) : false;
        $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';
        $valid = filter_var($url, FILTER_VALIDATE_URL) !== false
            && is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && !isset($parts['user'])
            && !isset($parts['pass'])
            && in_array($host, $profile['hosts'], true);
        $icon = site_social_icon($network);
        if ($valid) {
            $markup .= '<a class="social-link" href="' . e($url) . '" target="_blank" rel="noopener noreferrer" aria-label="Visit our ' . e($network) . ' profile" title="' . e($network) . '">' . $icon . '</a>';
        } else {
            $markup .= '<span class="social-link social-link-pending" role="img" aria-label="' . e($network) . ' profile link not configured" title="' . e($network) . ' link will be added">' . $icon . '</span>';
        }
    }
    return $markup;
}

function site_published_test_count(string $slug): int
{
    $count = 0;
    foreach (test_records($slug) as $test) {
        $questions = $test['questions'] ?? [];
        if (is_array($questions) && $questions !== []) $count++;
    }
    return $count;
}

function site_exam_search_aliases(array $series): array
{
    $title = trim((string) ($series['title'] ?? ''));
    if (preg_match('/\b(?:SBI|IBPS)\b.*\bPO\b/i', $title) === 1) {
        return ['Bank PO', 'Probationary Officer'];
    }
    return [];
}

function site_seo_head_markup(string $currentHtml): string
{
    $baseUrl = rtrim(APP_CANONICAL_URL, '/');
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $page = (string) ($_GET['page'] ?? 'about');
    $title = '';
    $description = '';
    $ogTitle = '';
    $ogDescription = '';
    $canonical = '';
    $robots = 'noindex,follow';
    $ogType = 'website';
    $imageUrl = '';
    $imageAlt = '';
    $schema = null;

    if (in_array($script, ['index.php', 'banking-test-series.php'], true)) {
        $publishedCatalog = available_course_catalog();
        $publishedTitles = array_values(array_filter(array_map(
            static fn(array $series): string => trim((string) ($series['title'] ?? '')),
            $publishedCatalog
        )));
        $featuredTitles = array_slice($publishedTitles, 0, 2);
        $featuredExamNames = implode(' & ', $featuredTitles);
        $title = $featuredExamNames !== ''
            ? $featuredExamNames . ' Mock Tests | ' . APP_BRAND_NAME
            : 'Government Exam Mock Tests | ' . APP_BRAND_NAME;
        $description = $publishedTitles !== []
            ? 'Practice ' . implode(' and ', $featuredTitles) . ' mock tests online. Check published questions and timings, then review your results.'
            : 'Browse published government-exam practice series, compare available questions and timings, and check official notices for current exam rules.';
        $canonical = $baseUrl . '/';
        $robots = 'index,follow,max-image-preview:large';
        $organizationId = $baseUrl . '/#organization';
        $publishedSeriesItems = [];
        foreach ($publishedCatalog as $slug => $series) {
            $publishedSeriesItems[] = [
                '@type' => 'ListItem',
                'position' => count($publishedSeriesItems) + 1,
                'name' => (string) ($series['title'] ?? $slug),
                'item' => $baseUrl . '/product.php?product=' . rawurlencode((string) $slug),
            ];
        }
        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $organizationId,
                    'name' => APP_BRAND_NAME,
                    'url' => $baseUrl . '/',
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $baseUrl . '/img/fullmocktestseries.png',
                        'width' => 1536,
                        'height' => 1024,
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $baseUrl . '/#website',
                    'name' => APP_BRAND_NAME,
                    'url' => $baseUrl . '/',
                    'inLanguage' => 'en-IN',
                    'publisher' => ['@id' => $organizationId],
                ],
                [
                    '@type' => 'ItemList',
                    '@id' => $baseUrl . '/#published-series',
                    'name' => 'Published government exam mock test series',
                    'numberOfItems' => count($publishedCatalog),
                    'itemListElement' => $publishedSeriesItems,
                ],
            ],
        ];
    } elseif ($script === 'product.php') {
        $slugValue = $_GET['product'] ?? $_POST['product'] ?? '';
        $slug = is_string($slugValue) ? $slugValue : '';
        $catalog = available_course_catalog();
        if (isset($catalog[$slug])) {
            $series = $catalog[$slug];
            $seriesTitle = (string) ($series['title'] ?? $slug);
            $searchAliases = site_exam_search_aliases($series);
            $title = $seriesTitle
                . ($searchAliases !== [] ? ' & Bank PO Mock Tests' : ' Mock Test Series')
                . ' | ' . APP_BRAND_NAME;
            $availableTests = array_filter(
                test_records($slug),
                static fn(array $test): bool => is_array($test['questions'] ?? null) && $test['questions'] !== []
            );
            $availableTestCount = count($availableTests);
            $questionCounts = array_values(array_map(
                static fn(array $test): int => count((array) ($test['questions'] ?? [])),
                $availableTests
            ));
            $durations = array_values(array_map(
                static fn(array $test): int => max(1, (int) ($test['duration_minutes'] ?? 60)),
                $availableTests
            ));
            $seoSeriesName = $seriesTitle . ($searchAliases !== [] ? ' (' . implode(', ', $searchAliases) . ')' : '');
            $description = $availableTestCount > 0
                ? 'Practice ' . $seoSeriesName . ' online with ' . $availableTestCount . ' timed mock test' . ($availableTestCount === 1 ? '' : 's') . '. Review your results and check official notices.'
                : 'Review the ' . $seriesTitle . ' series description and stage. Practice tests will appear here when questions are published.';
            if ($availableTestCount === 1 && isset($questionCounts[0])) {
                $duration = $durations[0] ?? 60;
                $description = 'Try the ' . number_format($questionCounts[0]) . '-question ' . $seoSeriesName . ' mock test in ' . $duration . ' minutes. Review test details and use your result to guide revision.';
            } elseif ($availableTestCount > 1 && $questionCounts !== [] && $durations !== []) {
                $minimumQuestions = min($questionCounts);
                $maximumQuestions = max($questionCounts);
                $minimumDuration = min($durations);
                $maximumDuration = max($durations);
                $questionSummary = $minimumQuestions === $maximumQuestions
                    ? number_format($minimumQuestions) . ' questions each'
                    : number_format($minimumQuestions) . '-' . number_format($maximumQuestions) . ' questions';
                $durationSummary = $minimumDuration === $maximumDuration
                    ? $minimumDuration . ' minutes each'
                    : $minimumDuration . '-' . $maximumDuration . ' minutes';
                $description = 'Practice ' . $seoSeriesName . ' online: ' . $availableTestCount . ' timed tests, ' . $questionSummary . ', ' . $durationSummary . '. Review results and check official notices.';
            }
            $imagePath = (string) ($series['image_path'] ?? '');
            if (preg_match('~^uploads/series/[A-Za-z0-9._/-]+$~', $imagePath) === 1 && !str_contains($imagePath, '..')) $imageUrl = $baseUrl . '/' . $imagePath;
            if (preg_match('/^(test|demo|sample|untitled)$/i', trim($seriesTitle)) !== 1 && $availableTestCount > 0) {
                $canonical = $baseUrl . '/product.php?product=' . rawurlencode($slug);
                $robots = 'index,follow,max-image-preview:large';
                $testList = [];
                foreach ($availableTests as $position => $test) {
                    $testList[] = [
                        '@type' => 'ListItem',
                        'position' => count($testList) + 1,
                        'name' => (string) ($test['title'] ?? 'Practice test ' . ($position + 1)),
                    ];
                }
                $schema = [
                    '@context' => 'https://schema.org',
                    '@graph' => [
                        [
                            '@type' => 'WebPage',
                            '@id' => $canonical . '#webpage',
                            'url' => $canonical,
                            'name' => $title,
                            'description' => $description,
                            'inLanguage' => 'en-IN',
                            'breadcrumb' => ['@id' => $canonical . '#breadcrumb'],
                            'mainEntity' => ['@id' => $canonical . '#practice-tests'],
                        ],
                        [
                            '@type' => 'ItemList',
                            '@id' => $canonical . '#practice-tests',
                            'name' => $seriesTitle . ' available practice tests',
                            'numberOfItems' => $availableTestCount,
                            'itemListElement' => $testList,
                        ],
                        [
                            '@type' => 'BreadcrumbList',
                            '@id' => $canonical . '#breadcrumb',
                            'itemListElement' => [
                                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $baseUrl . '/'],
                                ['@type' => 'ListItem', 'position' => 2, 'name' => $seriesTitle, 'item' => $canonical],
                            ],
                        ],
                    ],
                ];
            } else {
                $robots = 'noindex,follow';
            }
        }
    } elseif ($script === 'blog.php') {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'blog-core.php';
        $slugValue = $_GET['slug'] ?? '';
        $slug = is_string($slugValue) ? trim($slugValue) : '';
        if ($slug !== '') {
            $post = blog_get_post_by_slug($slug);
            if ($post !== null && ($post['status'] ?? '') === BLOG_STATUS_PUBLISHED) {
                $title = trim((string) ($post['seo_title'] ?? '')) ?: trim((string) ($post['title'] ?? '')) . ' | ' . APP_BRAND_NAME;
                $description = trim((string) ($post['seo_description'] ?? '')) ?: trim((string) ($post['excerpt'] ?? ''));
                $ogTitle = trim((string) ($post['og_title'] ?? ''));
                $ogDescription = trim((string) ($post['og_description'] ?? ''));
                $canonical = $baseUrl . '/blog.php?slug=' . rawurlencode((string) ($post['slug'] ?? $slug));
                $robots = 'index,follow,max-image-preview:large';
                $ogType = 'article';
                $featuredImage = (string) ($post['featured_image'] ?? '');
                if (preg_match('~^uploads/blog/[A-Za-z0-9._-]+$~', $featuredImage) === 1) {
                    $imageUrl = $baseUrl . '/' . $featuredImage;
                    $imageAlt = trim((string) ($post['title'] ?? '')) ?: $title;
                }
                $headline = trim((string) ($post['title'] ?? '')) ?: $title;
                $authorName = blog_post_author_name($post);
                $authorType = preg_match('/\b(?:editorial|team)\b/i', $authorName) === 1 ? 'Organization' : 'Person';
                $publishedValue = blog_post_published_at($post);
                $modifiedValue = blog_post_public_modified_at($post);
                $publishedTimestamp = blog_post_timestamp($publishedValue);
                $modifiedTimestamp = blog_post_timestamp($modifiedValue);
                $articlePublishedTime = $publishedTimestamp !== false ? date(DATE_ATOM, $publishedTimestamp) : '';
                $articleModifiedTime = $modifiedTimestamp !== false ? date(DATE_ATOM, $modifiedTimestamp) : '';
                $articleSection = '';
                $paginationLinks = [];
                $articleSchema = [
                    '@type' => 'BlogPosting',
                    '@id' => $canonical . '#article',
                    'mainEntityOfPage' => ['@id' => $canonical . '#webpage'],
                    'headline' => $headline,
                    'description' => $description,
                    'author' => ['@type' => $authorType, 'name' => $authorName],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => APP_BRAND_NAME,
                        'url' => $baseUrl . '/',
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => $baseUrl . '/img/fullmocktestseries.png',
                        ],
                    ],
                ];
                $category = blog_category_by_id((string) ($post['category_id'] ?? ''));
                if ($category !== null && trim((string) ($category['name'] ?? '')) !== '') {
                    $articleSection = (string) $category['name'];
                    $articleSchema['articleSection'] = $articleSection;
                }
                $articleTags = array_values(array_filter(array_map(
                    static fn($tag): string => trim((string) $tag),
                    (array) ($post['tags'] ?? [])
                )));
                if ($articleTags !== []) $articleSchema['keywords'] = $articleTags;
                if ($imageUrl !== '') $articleSchema['image'] = [$imageUrl];
                if ($articlePublishedTime !== '') $articleSchema['datePublished'] = $articlePublishedTime;
                if ($articleModifiedTime !== '') $articleSchema['dateModified'] = $articleModifiedTime;
                $schema = [
                    '@context' => 'https://schema.org',
                    '@graph' => [
                        [
                            '@type' => 'WebPage',
                            '@id' => $canonical . '#webpage',
                            'url' => $canonical,
                            'name' => $title,
                            'description' => $description,
                            'inLanguage' => 'en-IN',
                            'breadcrumb' => ['@id' => $canonical . '#breadcrumb'],
                            'mainEntity' => ['@id' => $canonical . '#article'],
                        ],
                        $articleSchema,
                        [
                            '@type' => 'BreadcrumbList',
                            '@id' => $canonical . '#breadcrumb',
                            'itemListElement' => [
                                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $baseUrl . '/'],
                                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $baseUrl . '/blog.php'],
                                ['@type' => 'ListItem', 'position' => 3, 'name' => $headline, 'item' => $canonical],
                            ],
                        ],
                    ],
                ];
            } else {
                $title = 'Story not found | ' . APP_BRAND_NAME;
            }
        } else {
            $allPublishedPosts = blog_public_posts();
            $paginationState = blog_pagination($allPublishedPosts, $_GET['page'] ?? null);
            $publishedPosts = $paginationState['posts'];
            $blogPage = $paginationState['page'];
            $blogPageCount = $paginationState['page_count'];
            $title = 'Exam Preparation Blog & Study Guides | ' . APP_BRAND_NAME;
            $description = 'Read practical exam-preparation articles on mock-test strategy, revision, time management and current affairs. Browse published guides and check official notices for exam rules.';
            if ($blogPage > 1) $title = 'Exam Preparation Blog & Study Guides — Page ' . $blogPage . ' | ' . APP_BRAND_NAME;
            if ($allPublishedPosts !== []) {
                $canonical = $baseUrl . '/blog.php' . ($blogPage > 1 ? '?page=' . $blogPage : '');
                $robots = 'index,follow,max-image-preview:large';
                $blogItems = [];
                foreach ($publishedPosts as $position => $publishedPost) {
                    $postSlug = trim((string) ($publishedPost['slug'] ?? ''));
                    if ($postSlug === '') continue;
                    $blogItems[] = [
                        '@type' => 'ListItem',
                        'position' => count($blogItems) + 1,
                        'name' => trim((string) ($publishedPost['title'] ?? '')) ?: 'Exam preparation article',
                        'item' => $baseUrl . '/blog.php?slug=' . rawurlencode($postSlug),
                    ];
                }
                $featuredImage = (string) ($publishedPosts[0]['featured_image'] ?? '');
                if (preg_match('~^uploads/blog/[A-Za-z0-9._-]+$~', $featuredImage) === 1) {
                    $imageUrl = $baseUrl . '/' . $featuredImage;
                    $imageAlt = (string) ($publishedPosts[0]['title'] ?? 'Exam preparation article');
                }
                if ($blogPage > 1) {
                    $description = 'Page ' . $blogPage . ' of practical exam-preparation articles on mock-test strategy, revision, time management and current affairs.';
                }
                $schema = [
                    '@context' => 'https://schema.org',
                    '@graph' => [
                        [
                            '@type' => 'CollectionPage',
                            '@id' => $canonical . '#webpage',
                            'url' => $canonical,
                            'name' => $title,
                            'description' => $description,
                            'inLanguage' => 'en-IN',
                            'breadcrumb' => ['@id' => $canonical . '#breadcrumb'],
                            'mainEntity' => ['@id' => $canonical . '#articles'],
                        ],
                        [
                            '@type' => 'ItemList',
                            '@id' => $canonical . '#articles',
                            'name' => 'Published exam preparation articles',
                            'numberOfItems' => count($blogItems),
                            'itemListElement' => $blogItems,
                        ],
                        [
                            '@type' => 'BreadcrumbList',
                            '@id' => $canonical . '#breadcrumb',
                            'itemListElement' => [
                                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $baseUrl . '/'],
                                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $canonical],
                            ],
                        ],
                    ],
                ];
                if ($blogPage > 1) $paginationLinks[] = '<link rel="prev" href="' . e($baseUrl . '/blog.php' . ($blogPage === 2 ? '' : '?page=' . ($blogPage - 1))) . '">';
                if ($blogPage < $blogPageCount) $paginationLinks[] = '<link rel="next" href="' . e($baseUrl . '/blog.php?page=' . ($blogPage + 1)) . '">';
            }
        }
    } elseif ($script === 'director.php') {
        $title = 'Meet Our Director | RankSetu';
        $description = 'Meet the founder and director behind RankSetu and learn the vision, mission and learner-first principles guiding the platform.';
        $canonical = $baseUrl . '/director.php';
        $robots = 'index,follow,max-image-preview:large';
        $imageUrl = $baseUrl . '/img/founder.png';
        $ogType = 'profile';
    } elseif ($script === 'info.php') {
        $infoPages = [
            'about' => ['About ' . APP_BRAND_NAME, 'Learn how FullMockTestSeries.com organizes timed mock tests and test series for competitive exam practice.'],
            'contact' => ['Contact ' . APP_BRAND_NAME, 'Contact FullMockTestSeries.com about accounts, enrollments, practice tests, accessibility or privacy requests.'],
            'faq' => ['Mock Test Practice FAQs | ' . APP_BRAND_NAME, 'Answers about choosing a test series, starting practice, saved results, accounts and support.'],
            'disclaimer' => ['Practice Test Disclaimer | ' . APP_BRAND_NAME, 'Read important information about independent practice materials, exam updates and official sources.'],
            'privacy' => ['Privacy Policy | ' . APP_BRAND_NAME, 'Learn what account, test, order and support information this service may process and how to contact the operator.'],
            'terms' => ['Terms of Use | ' . APP_BRAND_NAME, 'Review the proposed terms for using FullMockTestSeries.com mock tests, accounts and enrollment features.'],
            'refund' => ['Refund and Cancellation Policy | ' . APP_BRAND_NAME, 'Review the proposed digital-series cancellation and refund information before using paid services.'],
        ];
        if (isset($infoPages[$page])) {
            [$title, $description] = $infoPages[$page];
            $canonical = $baseUrl . '/info.php?page=' . rawurlencode($page);
            $robots = in_array($page, ['privacy', 'terms', 'refund'], true) ? 'noindex,follow' : 'index,follow,max-image-preview:large';
        }
    }

    if ($title === '' && preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $currentHtml, $titleMatch) === 1) {
        $title = html_entity_decode(strip_tags($titleMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    $tags = [
        '<title>' . e($title !== '' ? $title : APP_BRAND_NAME) . '</title>',
        '<meta name="robots" content="' . e($robots) . '">',
        '<meta property="og:site_name" content="' . e(APP_BRAND_NAME) . '">',
    ];
    if ($description !== '') {
        $shareTitle = $ogTitle !== '' ? $ogTitle : $title;
        $shareDescription = $ogDescription !== '' ? $ogDescription : $description;
        $tags[] = '<meta name="description" content="' . e($description) . '">';
        $tags[] = '<meta property="og:title" content="' . e($shareTitle) . '">';
        $tags[] = '<meta property="og:description" content="' . e($shareDescription) . '">';
        $tags[] = '<meta property="og:type" content="' . e($ogType) . '">';
        if ($canonical !== '') $tags[] = '<meta property="og:url" content="' . e($canonical) . '">';
        if ($imageUrl !== '') {
            $tags[] = '<meta property="og:image" content="' . e($imageUrl) . '">';
            $tags[] = '<meta property="og:image:alt" content="' . e($imageAlt !== '' ? $imageAlt : $title . ' image') . '">';
            $tags[] = '<meta name="twitter:card" content="summary_large_image">';
            $tags[] = '<meta name="twitter:image" content="' . e($imageUrl) . '">';
        } else {
            $tags[] = '<meta name="twitter:card" content="summary">';
        }
        $tags[] = '<meta name="twitter:title" content="' . e($shareTitle) . '">';
        $tags[] = '<meta name="twitter:description" content="' . e($shareDescription) . '">';
    }
    if ($canonical !== '') $tags[] = '<link rel="canonical" href="' . e($canonical) . '">';
    if ($schema !== null) {
        $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        if ($json !== false) $tags[] = '<script type="application/ld+json">' . $json . '</script>';
    }
    if (isset($paginationLinks)) $tags = array_merge($tags, $paginationLinks);
    if (!empty($articlePublishedTime)) $tags[] = '<meta property="article:published_time" content="' . e($articlePublishedTime) . '">';
    if (!empty($articleModifiedTime)) $tags[] = '<meta property="article:modified_time" content="' . e($articleModifiedTime) . '">';
    if (!empty($articleSection)) $tags[] = '<meta property="article:section" content="' . e($articleSection) . '">';
    return implode("\n", $tags);
}

function site_footer_markup(string $section = 'site'): string
{
    $examLinks = '';
    foreach (available_course_catalog() as $slug => $series) {
        $examLinks .= '<a href="product.php?product=' . e(rawurlencode((string) $slug)) . '">' . e((string) ($series['title'] ?? $slug)) . '</a>';
    }
    if ($examLinks === '') $examLinks = '<a href="index.php#exams">Browse the exam catalog</a>';

    return '<footer class="site-footer"><div class="site-footer-grid"><section class="footer-brand-column"><a class="footer-brand" href="index.php" aria-label="' . e(APP_BRAND_NAME . ' home') . '"><svg class="footer-brand-mark" viewBox="0 0 56 56" aria-hidden="true" focusable="false"><path d="M6 17.5 28 7l22 10.5L28 28 6 17.5Z" fill="#168be1"/><path d="M15 22v11.2c7.8 6.7 18.2 6.7 26 0V22L28 29l-13-7Z" fill="#f8b63e"/><path d="M28 29 15 22v11.2c7.8 6.7 18.2 6.7 26 0V22l-13 7Z" fill="#fff" fill-opacity=".96"/><path d="M21 34.2 25.5 39l10-11.2" fill="none" stroke="#2f7657" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5"/><path d="M48 19v11" stroke="#f8b63e" stroke-linecap="round" stroke-width="2.5"/><circle cx="48" cy="32.5" r="2.5" fill="#f8b63e"/></svg><span class="footer-wordmark"><span>FullMockTest</span><span class="footer-wordmark-bottom"><strong>Series</strong><span class="footer-domain">.com</span></span></span></a><p>' . e(APP_BRAND_TAGLINE) . '</p></section><section class="footer-links"><h2 class="footer-heading">Published series</h2>' . $examLinks . '</section><section class="footer-links"><h2 class="footer-heading">Quick Links</h2><a href="auth.php">Login</a><a href="instructions.php?sample=1">Free sample</a><a href="info.php?page=about">About Us</a><a href="director.php">Director</a><a href="info.php?page=contact">Contact</a><a href="info.php?page=faq">FAQ</a></section><section class="footer-links"><h2 class="footer-heading">Policies</h2><a href="info.php?page=privacy">Privacy</a><a href="info.php?page=terms">Terms</a><a href="info.php?page=refund">Refund policy</a><a href="info.php?page=disclaimer">Disclaimer</a></section></div><div class="site-social-row"><div class="site-social-website"><span class="site-social-label">Website</span><a href="https://fullmocktestseries.com">fullmocktestseries.com</a></div><div class="site-social-block"><span class="site-social-label">Follow us</span><div class="social-links" aria-label="Social media profiles">' . site_social_links_markup() . '</div></div></div><div class="footer-bottom">&copy; ' . date('Y') . ' ' . e(APP_BRAND_NAME) . '. ' . e(APP_BRAND_TAGLINE) . '</div></footer>';
}

function site_layout_styles(): void
{
    echo site_layout_css();
}

function site_layout_css(): string
{
    return <<<'CSS'
<style>
html,body{max-width:100%;overflow-x:hidden}
:root{box-sizing:border-box}
header.site-header{position:sticky;top:0;z-index:1000;display:block!important;width:100%;max-width:100%;min-width:0;box-sizing:border-box;background:rgba(255,255,255,.97)!important;border-bottom:1px solid #e8e1d3!important;padding:0!important;box-shadow:0 12px 30px rgba(22,35,63,.08);color:#172033}
header.site-header>.container{width:100%;max-width:1680px;margin:0 auto;padding:0 24px;box-sizing:border-box}
header.site-header nav#primary-navigation{display:flex;min-height:78px;align-items:center;justify-content:space-between;gap:18px;width:100%}
header.site-header .logo{display:flex;align-items:center;flex:0 0 auto;padding-right:18px;border-right:0;white-space:nowrap}
header.site-header .logo{gap:9px;min-width:0;text-decoration:none}
header.site-header .header-brand-mark{display:block;width:44px;height:44px;flex:none}
header.site-header .header-wordmark{display:grid;gap:0;color:#172033;font:800 1.04rem/.98 Arial,sans-serif;letter-spacing:-.045em}
header.site-header .header-wordmark-bottom{display:flex;align-items:center;gap:4px;margin-top:3px;color:#e99a27;font-size:1.15rem;line-height:1}
header.site-header .header-wordmark-bottom strong{font-weight:850}
header.site-header .header-domain{display:inline-flex;align-items:center;padding:2px 5px 3px;border-radius:999px;background:#1678d2;color:#fff;font-size:.64rem;font-weight:800;letter-spacing:0}
header.site-header .nav-links{display:flex;flex:1 1 auto;align-items:center;justify-content:flex-end;flex-wrap:nowrap;gap:6px 10px;min-width:0;margin:0;padding:0;list-style:none;white-space:nowrap;overflow:visible}
header.site-header .nav-links a{display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:7px 9px;border-radius:999px;color:#1d2433;text-decoration:none;font:600 .84rem/1.2 Arial,sans-serif;white-space:nowrap;transition:color .18s ease,background-color .18s ease,transform .18s ease}
header.site-header .nav-links a:hover,header.site-header .nav-links a:focus-visible{color:#9c3b2e;background:#f9f0ea;text-decoration:none;transform:translateY(-1px)}
header.site-header .nav-more{position:relative;flex:0 0 auto}
header.site-header .nav-more-toggle{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:36px;padding:7px 10px;border:0;border-radius:999px;background:transparent;color:#1d2433;font:600 .84rem/1.2 Arial,sans-serif;white-space:nowrap;cursor:pointer;list-style:none}
header.site-header .nav-more-toggle::-webkit-details-marker{display:none}
header.site-header .nav-more-toggle::after{content:"";width:6px;height:6px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(45deg) translateY(-2px)}
header.site-header .nav-more-toggle:hover,header.site-header .nav-more-toggle:focus-visible{color:#9c3b2e;background:#f9f0ea;outline:2px solid #d7bca5;outline-offset:1px}
header.site-header .nav-more-menu{position:absolute;top:calc(100% + 8px);right:0;z-index:1001;display:none;width:min(260px,calc(100vw - 32px));min-width:min(210px,calc(100vw - 32px));max-width:calc(100vw - 32px);box-sizing:border-box;padding:7px;border:1px solid #eadac9;border-radius:8px;background:#fff;box-shadow:0 12px 28px rgba(22,35,63,.14)}
header.site-header .nav-more[open]>.nav-more-menu{display:grid}
header.site-header .nav-links .nav-more-menu a{display:flex;justify-content:flex-start;min-height:40px;padding:10px 12px;border-radius:5px}
header.site-header .site-user{display:inline-flex;align-items:center;flex:0 0 auto;padding:7px 9px;border:1px solid #eadac9;background:#fffaf5;border-radius:999px;color:#9c3b2e;font-size:.82rem;font-weight:700;white-space:nowrap}
header.site-header .nav-tools{display:none}
header.site-header .nav-toggle{border:1px solid #d9d0c4;background:#f7efe7;color:#1d2433;padding:9px 12px;border-radius:8px;font:700 .85rem Arial,sans-serif;cursor:pointer;transition:all .18s ease}
header.site-header .nav-toggle:hover,header.site-header .nav-toggle:focus-visible{background:#f1e0d1;border-color:#d3b79d}
@media(min-width:381px) and (max-width:1000px){header.site-header>.container{padding:0 16px}header.site-header nav#primary-navigation{gap:8px}header.site-header .header-brand-mark{width:42px;height:42px}header.site-header .header-wordmark{font-size:.98rem}header.site-header .header-wordmark-bottom{font-size:1.08rem}header.site-header .logo{padding-right:4px}header.site-header .nav-links{gap:2px 4px}header.site-header .nav-links>a{padding:6px 5px;font-size:.78rem}header.site-header .nav-more-toggle{padding:6px 6px;font-size:.78rem}header.site-header .site-user{padding:5px 6px;font-size:.72rem}}
@media(min-width:381px) and (max-width:720px){header.site-header nav#primary-navigation{flex-wrap:wrap;justify-content:center}header.site-header .logo{justify-content:center;width:100%;padding:0}header.site-header .nav-tools{display:none}header.site-header .nav-links{display:flex;flex:0 0 100%;width:100%;justify-content:center;flex-wrap:wrap;padding:0 0 8px;white-space:normal}header.site-header .nav-more{width:auto}header.site-header .nav-more-toggle{width:auto;border-bottom:0}header.site-header .nav-more-menu{position:absolute;right:0;min-width:190px;margin:0;background:#fff;box-shadow:0 12px 28px rgba(22,35,63,.14)}}
.site-footer{display:block;width:100%;max-width:100%;min-width:0;box-sizing:border-box;margin:0;padding:52px 24px 20px;background:linear-gradient(180deg,#181f2d 0%,#101827 100%);color:#ebedf2}
.site-footer-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:34px;width:100%;max-width:1240px;margin:0 auto}
.footer-brand{display:inline-flex;align-items:center;gap:11px;max-width:100%;color:#fff;text-decoration:none;font:700 1.3rem Arial,sans-serif}
.footer-brand:focus-visible{outline:2px solid #f8d68d;outline-offset:5px;border-radius:6px}
.footer-brand-mark{display:block;width:48px;height:48px;flex:none}
.footer-wordmark{display:grid;gap:0;color:#fff;font-size:1.12rem;font-weight:800;line-height:1.02;letter-spacing:-.045em}
.footer-wordmark-bottom{display:flex;align-items:center;gap:5px;margin-top:3px;color:#ffad36;font-size:1.25rem}
.footer-wordmark-bottom strong{font-weight:850}
.footer-domain{display:inline-flex;align-items:center;padding:2px 6px 3px;border-radius:999px;background:#1678d2;color:#fff;font-size:.72rem;font-weight:800;letter-spacing:0}
.site-footer p{max-width:32ch;color:#d8deea;font-size:1rem;line-height:1.7;margin:16px 0 0}
.footer-links{display:grid;align-content:start;justify-items:start;gap:12px;min-width:0}
.footer-heading{margin:0 0 12px;color:#fff;font:700 1rem Arial,sans-serif;text-align:left;letter-spacing:.04em;text-transform:uppercase}
.footer-links a{color:#dfe6f1;text-decoration:none;font-size:.98rem;line-height:1.55;overflow-wrap:anywhere;padding:2px 0;transition:color .18s ease}
.footer-links a:hover,.footer-links a:focus-visible{color:#f8d68d;text-decoration:underline;text-underline-offset:3px}
.study-note{width:min(72ch,100%);margin:18px auto 24px;color:#4e5663;font-size:1rem;line-height:1.7;text-align:center}
.site-premium-showcase-section{padding-top:18px;padding-bottom:10px}
.site-premium-showcase{position:relative;display:grid;grid-template-columns:1.08fr .92fr;align-items:center;gap:38px;padding:34px 34px;border:1px solid rgba(188,163,125,.45);border-radius:28px;background:linear-gradient(135deg,#17213d 0%,#0d1529 42%,#1a2346 100%);box-shadow:0 22px 60px rgba(14,20,34,.22);overflow:hidden}
.site-premium-showcase::before{content:"";position:absolute;inset:0;background:radial-gradient(circle at top left,rgba(255,202,102,.25),transparent 28%),radial-gradient(circle at bottom right,rgba(80,148,255,.22),transparent 34%);pointer-events:none}
.site-premium-copy,.site-premium-visual{position:relative;z-index:1}
.site-premium-copy{padding:10px 8px 10px 4px;color:#edf3ff}
.premium-kicker{display:inline-flex;align-items:center;gap:8px;padding:7px 12px;border-radius:999px;background:rgba(255,201,117,.14);border:1px solid rgba(255,201,117,.25);color:#ffd78d;font-size:.75rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
.site-premium-copy h2{margin:18px 0 12px;color:#fff;font-size:clamp(2rem,4vw,3.4rem);line-height:1.08;letter-spacing:-.04em}
.site-premium-copy p{max-width:540px;margin:0;color:#d9e1f0;font-size:1.04rem;line-height:1.7}
.premium-cta-group{display:flex;flex-wrap:wrap;gap:14px;margin-top:24px}
.premium-feature-list{display:flex;flex-wrap:wrap;gap:12px 18px;list-style:none;padding:0;margin:22px 0 0;color:#ebf1ff;font-size:.96rem;font-weight:600}
.premium-feature-list li{position:relative;padding-left:22px;color:#e8edf8}
.premium-feature-list li::before{content:"";position:absolute;left:0;top:9px;width:10px;height:10px;border-radius:50%;background:linear-gradient(135deg,#ffd88b,#e7a64a);box-shadow:0 0 12px rgba(255,205,124,.7)}
.site-premium-visual{display:flex;justify-content:center;align-items:center;padding:8px 0}
.premium-visual-card{position:relative;width:min(100%,560px);aspect-ratio:1.1;display:flex;align-items:center;justify-content:center;border-radius:30px;background:linear-gradient(180deg,rgba(255,255,255,.12),rgba(166,177,214,.08));border:1px solid rgba(255,255,255,.2);box-shadow:inset 0 1px 0 rgba(255,255,255,.15),0 26px 40px rgba(7,11,20,.28);overflow:hidden;animation:floatCard 5.5s ease-in-out infinite}
.premium-glow{position:absolute;border-radius:50%;filter:blur(8px);opacity:.8}
.premium-glow-one{width:180px;height:180px;background:rgba(255,188,92,.3);top:18%;left:12%;animation:pulseGlow 4s ease-in-out infinite}
.premium-glow-two{width:220px;height:220px;background:rgba(62,128,255,.25);bottom:10%;right:10%;animation:pulseGlow 5.2s ease-in-out infinite reverse}
.premium-image-frame{position:relative;z-index:2;width:min(82%,420px);padding:18px 16px;border-radius:26px;background:linear-gradient(145deg,rgba(15,24,44,.8),rgba(26,38,68,.92));border:1px solid rgba(255,255,255,.12);box-shadow:0 16px 35px rgba(12,15,24,.32);backdrop-filter:blur(8px)}
.premium-image-frame img{display:block;width:100%;height:auto;border-radius:14px;background:#fff}
.premium-mini-card{position:absolute;z-index:3;display:grid;gap:2px;padding:10px 12px;border-radius:14px;border:1px solid rgba(255,255,255,.18);background:rgba(13,20,33,.78);backdrop-filter:blur(10px);box-shadow:0 10px 25px rgba(9,13,20,.25);color:#fff;animation:floatCard 6s ease-in-out infinite}
.premium-mini-card-top{top:14%;right:8%}
.premium-mini-card-bottom{bottom:10%;left:8%;animation-delay:.8s}
.mini-label{font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;color:#c9d6ef}
.premium-mini-card strong{font-size:1.05rem;color:#fff}
@keyframes floatCard{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
@keyframes pulseGlow{0%,100%{transform:scale(.96);opacity:.55}50%{transform:scale(1.08);opacity:1}}
.dashboard-shell{display:grid!important;grid-template-columns:250px minmax(0,1fr);gap:28px;align-items:start}
.dashboard-sidebar{background:#fff;border:1px solid #e4e7ef;padding:20px;position:sticky;top:100px;border-radius:14px;box-shadow:0 14px 30px rgba(22,35,63,.04)}
.dashboard-sidebar h2{font:700 1.1rem Georgia,serif;color:#16233f;margin:0 0 14px}
.dashboard-sidebar .premium-status{background:#fff3ed;border-left:4px solid #9c3b2e;padding:12px;margin-bottom:18px;color:#70251c;font-size:.86rem;line-height:1.45;border-radius:8px}
.dashboard-sidebar nav{display:grid;gap:5px}
.dashboard-sidebar nav a{color:#303740;text-decoration:none;padding:10px 8px;border-bottom:1px solid #eef0f4;font-size:.9rem;border-radius:6px}
.dashboard-sidebar nav a:hover{color:#9c3b2e;background:#fff8f4}
.dashboard-content{min-width:0}
.site-social-row{display:flex;align-items:center;justify-content:space-between;gap:24px;width:100%;max-width:1240px;margin:36px auto 0;padding-top:24px;border-top:1px solid rgba(255,255,255,.12)}
.site-social-block,.site-social-website{display:grid;gap:12px;justify-items:start}
.site-social-label{color:#d9dfec;font-size:.76rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em}
.site-social-website>a{color:#fff;font-size:.98rem;font-weight:700;text-decoration:none}
.site-social-website>a:hover,.site-social-website>a:focus-visible{color:#f8d68d;text-decoration:underline;text-underline-offset:3px}
.social-links{display:flex;align-items:center;gap:10px}
.social-link{display:grid;width:42px;height:42px;place-items:center;border:1px solid rgba(255,255,255,.18);border-radius:50%;background:rgba(255,255,255,.04);color:#f1f1f1;transition:all .18s ease}
a.social-link:hover,a.social-link:focus-visible{transform:translateY(-2px);border-color:#f8d68d;background:rgba(248,214,141,.12);color:#f8d68d}
.social-link-pending{opacity:.4;cursor:not-allowed}
.social-link svg{width:19px;height:19px;fill:currentColor}
.social-link:focus-visible,.site-social-website>a:focus-visible{outline:2px solid #f8d68d;outline-offset:3px}
.footer-bottom{width:100%;max-width:1240px;margin:42px auto 0;border-top:1px solid rgba(255,255,255,.12);padding-top:18px;color:#d2d9e6;font-size:.88rem;text-align:center}
@media(max-width:380px){header.site-header nav#primary-navigation{gap:12px}header.site-header .nav-links{gap:8px 12px}}
@media(max-width:380px){header.site-header>.container{padding:0 18px}header.site-header nav#primary-navigation{min-height:68px;flex-wrap:wrap;gap:0}header.site-header .logo{max-width:calc(100% - 64px);padding-right:12px}header.site-header .header-brand-mark{width:40px;height:40px}header.site-header .header-wordmark{font-size:.92rem}header.site-header .header-wordmark-bottom{font-size:1.02rem}header.site-header .nav-tools{display:flex;align-items:center;margin-left:auto}header.site-header .nav-toggle{display:block;border-radius:8px}header.site-header .nav-links{display:none;flex:none;width:100%;min-width:0;box-sizing:border-box;align-items:stretch;flex-direction:column;justify-content:flex-start;gap:0;padding:8px 0 12px;overflow:visible;white-space:normal}header.site-header nav#primary-navigation.is-open .nav-links{display:flex}header.site-header .nav-links>a{display:block;width:100%;box-sizing:border-box;padding:12px 10px;border-radius:8px;border-bottom:1px solid #f0e6d9;font-size:.95rem;white-space:normal;overflow-wrap:anywhere}header.site-header .nav-more{width:100%}header.site-header .nav-more-toggle{display:flex;justify-content:space-between;width:100%;padding:12px 10px;border-radius:8px;border-bottom:1px solid #f0e6d9;font-size:.95rem}header.site-header .nav-more-menu{position:static;min-width:0;margin:4px 0 6px 10px;box-shadow:none;border-radius:6px;background:#fbf8f3}header.site-header .nav-links .nav-more-menu a{padding:11px 12px}header.site-header .nav-links .site-user{display:flex;max-width:100%;padding:10px 12px;margin-top:4px;white-space:normal;overflow-wrap:anywhere;border-radius:8px}}
@media(max-width:700px){.dashboard-shell{display:block!important}.dashboard-sidebar{position:static;margin-bottom:24px}.dashboard-sidebar nav{grid-template-columns:repeat(2,minmax(0,1fr));gap:0 8px}.dashboard-sidebar nav a{padding:9px 4px}}
@media(max-width:850px){.site-footer-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:36px 28px}.site-footer p{margin-top:18px}}
@media(max-width:520px){.site-footer{padding:38px 22px 18px}.site-footer-grid{grid-template-columns:minmax(0,1fr);gap:28px}.footer-links{gap:10px}.footer-heading{margin-bottom:6px}.site-social-row{align-items:flex-start;flex-direction:column;margin-top:28px;padding-top:20px}.social-links{gap:8px}.footer-bottom{margin-top:32px;padding-top:18px}}
*,*::before,*::after{box-sizing:inherit}
@media(max-width:1000px){
    header.site-header>.container{padding:0 16px}
    header.site-header nav#primary-navigation{display:grid!important;grid-template-columns:minmax(0,1fr) 44px;grid-template-rows:68px auto;min-height:68px;align-items:center;justify-content:normal;gap:0 8px}
    header.site-header .logo{grid-column:1;grid-row:1;width:100%;max-width:100%;min-width:0;justify-self:start;justify-content:flex-start!important;padding:0}
    header.site-header .nav-tools{grid-column:2;grid-row:1;display:flex;align-items:center;justify-self:end;margin:0}
    header.site-header .nav-toggle{display:grid;width:44px;height:44px;place-items:center;padding:0;border:0;border-radius:6px;background:transparent;color:#1d2433;font-size:0}
    header.site-header .nav-toggle::before{content:"";width:22px;height:14px;background:linear-gradient(currentColor 0 0) 0 0/100% 2px no-repeat,linear-gradient(currentColor 0 0) 0 6px/100% 2px no-repeat,linear-gradient(currentColor 0 0) 0 12px/100% 2px no-repeat}
    header.site-header nav#primary-navigation.is-open .nav-toggle::before{width:20px;height:20px;background:linear-gradient(45deg,transparent 46%,currentColor 47% 53%,transparent 54%),linear-gradient(-45deg,transparent 46%,currentColor 47% 53%,transparent 54%)}
    header.site-header .nav-links{grid-column:1/-1;grid-row:2;display:none!important;flex:none;width:100%;min-width:0;align-items:stretch;flex-direction:column;justify-content:flex-start;gap:0;padding:8px 0 12px;white-space:normal}
    header.site-header nav#primary-navigation.is-open .nav-links{display:flex!important}
    header.site-header .nav-links>a{display:flex;justify-content:flex-start;width:100%;min-height:44px;padding:12px 10px;border-bottom:1px solid #f0e6d9;border-radius:6px;white-space:normal;overflow-wrap:anywhere}
    header.site-header .nav-more{position:relative;align-self:stretch;width:100%;min-width:0;flex:0 0 auto;box-sizing:border-box}
    header.site-header .nav-more-toggle{display:flex;justify-content:space-between;width:100%;max-width:100%;min-width:0;min-height:44px;box-sizing:border-box;padding:12px 10px;border-radius:6px;border-bottom:1px solid #f0e6d9}
    header.site-header .nav-more-menu{position:static;top:auto;right:auto;z-index:auto;display:none;width:100%;min-width:0;max-width:100%;max-height:min(55vh,360px);overflow-y:auto;box-sizing:border-box;margin:6px 0 0;padding:8px;border:1px solid #eadac9;border-radius:8px;background:#fbf8f3;box-shadow:none}
    header.site-header .nav-links .site-user{display:flex;max-width:100%;padding:10px 12px;margin-top:4px;white-space:normal;overflow-wrap:anywhere;border-radius:8px}
}
@media(max-width:980px){.site-premium-showcase{grid-template-columns:1fr;padding:28px 22px}.site-premium-copy{padding:8px 0}.site-premium-copy h2{text-align:left}.site-premium-copy p{max-width:100%}.premium-feature-list{gap:10px 16px}.premium-cta-group{justify-content:flex-start}}
@media(max-width:560px){.site-premium-showcase{padding:22px 18px;border-radius:22px}.site-premium-copy h2{font-size:2.2rem}.premium-cta-group{flex-direction:column;align-items:stretch}.premium-cta-group .btn{width:100%;justify-content:center}.premium-feature-list{display:grid;grid-template-columns:1fr;gap:10px}.premium-mini-card{padding:8px 10px}.premium-mini-card strong{font-size:.96rem}.premium-image-frame{width:min(90%,380px)}}
.home-blog-section{background:linear-gradient(180deg,var(--paper-2),var(--paper))}
.home-blog-section>.container{width:min(100% - 48px,1160px);margin:0 auto}
.home-blog-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:28px}
.home-blog-heading>div{max-width:650px}
.home-blog-kicker{display:block;margin-bottom:9px;color:var(--maroon);font-size:.76rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase}
.home-blog-heading h2{margin:0 0 9px;color:var(--navy);font:700 clamp(1.9rem,3.2vw,2.65rem)/1.12 'Source Serif 4',Georgia,serif;text-align:left}
.home-blog-heading p{max-width:58ch;color:var(--ink-soft);font-size:.98rem}
.home-blog-all-link{display:inline-flex;align-items:center;gap:9px;flex:0 0 auto;padding:11px 15px;border:1px solid var(--line);border-radius:999px;background:var(--card);color:var(--navy);font-size:.88rem;font-weight:700;transition:transform .18s ease,border-color .18s ease,background .18s ease}
.home-blog-all-link:hover{transform:translateY(-2px);border-color:var(--maroon);background:var(--tint-2)}
.home-blog-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));align-items:stretch;gap:18px}
.home-blog-card{display:flex;min-width:0;flex-direction:column;overflow:hidden;border:1px solid var(--line);border-radius:14px;background:var(--card);box-shadow:0 10px 28px rgba(22,35,63,.07);transition:transform .2s ease,box-shadow .2s ease}
.home-blog-card:hover{transform:translateY(-4px);box-shadow:0 18px 38px rgba(22,35,63,.12)}
.home-blog-card-image-link{display:block;overflow:hidden;aspect-ratio:16/9;background:var(--paper-2)}
.home-blog-card-image{display:block;width:100%;height:100%;object-fit:cover;transition:transform .35s ease}
.home-blog-card:hover .home-blog-card-image{transform:scale(1.04)}
.home-blog-card-content{display:flex;min-width:0;flex:1;flex-direction:column;padding:20px}
.home-blog-category{align-self:flex-start;margin-bottom:11px;padding:5px 9px;border-radius:999px;background:var(--tint-2);color:var(--maroon);font-size:.69rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase}
.home-blog-card h3{margin:0 0 10px;color:var(--navy);font:700 1.2rem/1.3 'Source Serif 4',Georgia,serif}
.home-blog-card h3 a{color:inherit;text-decoration:none}
.home-blog-card h3 a:hover{color:var(--maroon)}
.home-blog-excerpt{display:-webkit-box;overflow:hidden;margin:0 0 20px;color:var(--ink-soft);font-size:.9rem;line-height:1.6;-webkit-box-orient:vertical;-webkit-line-clamp:3}
.home-blog-card-footer{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-top:auto;padding-top:14px;border-top:1px solid var(--line)}
.home-blog-meta{color:var(--ink-soft);font-size:.72rem}
.home-blog-read-link{display:inline-flex;align-items:center;gap:6px;color:var(--maroon);font-size:.8rem;font-weight:700;white-space:nowrap}
.home-blog-read-link:hover{text-decoration:underline;text-underline-offset:3px}
.home-blog-section a:focus-visible{outline:2px solid var(--focus);outline-offset:3px}
@media(max-width:980px){.home-blog-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.home-blog-card-content{padding:18px}}
@media(max-width:600px){.home-blog-section>.container{width:calc(100% - 32px)}.home-blog-heading{align-items:flex-start;flex-direction:column;gap:16px;margin-bottom:22px}.home-blog-heading h2{font-size:2rem}.home-blog-heading p{font-size:.92rem}.home-blog-all-link{padding:10px 14px}.home-blog-grid{grid-template-columns:minmax(0,1fr);gap:14px}.home-blog-card{border-radius:12px}.home-blog-card-content{padding:18px}.home-blog-card h3{font-size:1.25rem}}
</style>
CSS;
}

function site_layout_output_filter(string $html): string
{
    if (stripos($html, '<html') === false) return $html;

    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (in_array($script, ['index.php', 'banking-test-series.php'], true)) {
        $publishedCourses = available_course_catalog();
        $publishedTitles = array_values(array_filter(array_map(
            static fn(array $series): string => trim((string) ($series['title'] ?? '')),
            $publishedCourses
        )));
        $featuredTitles = array_slice($publishedTitles, 0, 2);
        $homeTitleMarkup = $featuredTitles !== []
            ? '<span class="hero-prefix">Online mock tests for</span><span class="hero-category">' . e(implode(' & ', $featuredTitles)) . '</span>'
            : '<span class="hero-prefix">Government exam</span><span class="hero-category">mock tests</span>';
        $homeDescription = $publishedTitles !== []
            ? 'Take published ' . implode(' and ', $featuredTitles) . ' mock tests online. Check question counts and time limits, then review your results.'
            : 'Browse published government-exam practice series and review each test’s questions and time limit before you begin.';
        $html = preg_replace(
            '/<h1 class="hero-title">.*?<\/h1>/s',
            '<h1 class="hero-title">' . $homeTitleMarkup . '</h1>',
            $html,
            1
        ) ?? $html;
        $html = str_replace(
            'Find practice for Bihar Police, BPSC, State PCS, UPSC Civil Services and Banking exams. Only series with published questions appear in the catalog.',
            e($homeDescription),
            $html
        );
        $searchTerms = [];
        foreach ($publishedCourses as $series) {
            $seriesTitle = trim((string) ($series['title'] ?? ''));
            if ($seriesTitle !== '') $searchTerms[] = $seriesTitle;
            $searchTerms = array_merge($searchTerms, site_exam_search_aliases($series));
        }
        $searchTerms = array_slice(array_values(array_unique($searchTerms)), 0, 5);
        $searchButtons = '';
        foreach ($searchTerms as $searchTerm) {
            $searchButtons .= '<button class="search-suggestion" type="button" data-search="' . e($searchTerm) . '">' . e($searchTerm) . '</button>';
        }
        if ($searchButtons !== '') {
            $suggestions = '<div class="search-suggestions" aria-label="Search published exam series">Search available series: ' . $searchButtons . '</div>';
            $html = preg_replace(
                '/<div class="search-suggestions"[^>]*>.*?<\/div>/s',
                $suggestions,
                $html,
                1
            ) ?? $html;
            $html = str_replace(
                'placeholder="Search BPSC, Bihar Police, UPSC, State PCS..."',
                'placeholder="Search published exam series..."',
                $html
            );
        }
        $html = preg_replace_callback('/<section id="features" class="section">.*?<\/section>/is', static fn(array $matches): string => site_homepage_learning_section(), $html, 1) ?? $html;
        $html = str_replace('<section id="exams"', site_homepage_subscription_section() . '<section id="exams"', $html);
        $blogSection = site_homepage_blog_section_markup();
        if ($blogSection !== '') $html = preg_replace('/<footer\b/i', $blogSection . '<footer', $html, 1) ?? $html;
        $html = str_replace('❓ Frequently Asked Questions', 'Frequently asked questions', $html);
    }
    if ($script === 'index.php') {
        $html = preg_replace_callback(
            '/<section\b(?=[^>]*\bid="faq")[^>]*>.*?<\/section>/is',
            static fn(array $matches): string => site_homepage_guides_and_faq_markup(),
            $html,
            1
        ) ?? $html;
    }
    if ($script === 'admin.php') {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'blog-core.php';
        $html = str_replace('<a href="blog-admin.php">Blog manager</a>', '<a href="blog-admin.php">Blog &amp; tutorials</a><a href="blog-admin.php?review=pending">Blog review queue</a>', $html);
    }
    if ($script === 'dashboard.php') {
        $dashboardUser = function_exists('current_user') ? current_user() : null;
        $activeSubscription = null;
        if (is_array($dashboardUser) && function_exists('user_subscriptions')) foreach (user_subscriptions((string) $dashboardUser['id']) as $subscription) {
            if (function_exists('subscription_is_current') ? subscription_is_current($subscription) : (($subscription['status'] ?? '') === 'ACTIVE' && (empty($subscription['expires_at']) || strtotime((string) $subscription['expires_at']) > time()))) { $activeSubscription = $subscription; break; }
        }
        $sidebarStatus = $activeSubscription === null ? '<div class="premium-status">No active premium subscription.</div>' : '<div class="premium-status"><strong>Premium user</strong><br>' . e((string) ($activeSubscription['plan_name'] ?? 'Premium subscription')) . '<br>Valid until ' . e(date('d M Y', strtotime((string) $activeSubscription['expires_at']))) . '</div>';
        $adminSidebar = $dashboardUser !== null && (($dashboardUser['role'] ?? 'student') === 'admin') ? '<h2 style="margin-top:26px">Administration</h2><nav aria-label="Administration navigation"><a href="admin.php">Control centre</a><a href="blog-admin.php">Blog &amp; tutorials</a><a href="blog-admin.php?review=pending">Blog review queue</a><a href="admin-business.php">Business analytics</a><a href="admin-course-control.php">Users &amp; course access</a><a href="admin-subscriptions.php">Manage subscriptions</a><a href="admin-coupons.php">Manage coupons</a><a href="admin-tests.php">Manage tests</a></nav>' : '';
        $sidebar = '<aside class="dashboard-sidebar"><h2>My account</h2>' . $sidebarStatus . '<nav aria-label="Account navigation"><a href="dashboard.php">Dashboard</a><a href="blog-submit.php">Blog &amp; tutorials</a><a href="subscription.php">My subscription</a><a href="index.php#exams">Browse exam series</a><a href="orders.php">Orders and invoices</a><a href="history.php">Test history</a></nav>' . $adminSidebar . '</aside>';
        $html = str_replace('<main class="wrap">', '<main class="dashboard-shell wrap">' . $sidebar . '<div class="dashboard-content">', $html, $mainReplaced);
        if ($mainReplaced > 0) $html = preg_replace('/<\/main>/i', '</div></main>', $html, 1) ?? $html;
    }
    $html = str_replace('Full-Length Tests', 'Available Practice Tests', $html);
    $html = str_replace('Published Exams', 'Available Series', $html);
    $html = str_replace('Questions Per Set', 'Questions Per Test', $html);
    $html = str_replace('Build a focused preparation plan for India\'s leading public-sector exams. Explore the active series below as new categories are published.', 'Build a focused preparation plan for India\'s leading public-sector exams. Explore currently published series below; new categories appear when their practice sets are ready.', $html);
    $html = str_replace('Timed practice sets designed around the latest pattern and marking approach.', 'Use the questions and time limit shown for this series. Confirm current exam patterns and marking rules with the official exam authority.', $html);
    $html = str_replace('subscription-checkout.php?plan=', 'subscription-payment.php?plan=', $html);
    $html = str_replace('â‚¹', '₹', $html);

    if ($script === 'blog.php') {
        $html = preg_replace('/<meta\b(?=[^>]*(?:name|property)=["\'](?:description|robots|og:[^"\']+|twitter:[^"\']+)["\'])[^>]*>\s*/i', '', $html) ?? $html;
        $html = preg_replace('/<link\b(?=[^>]*\brel=["\']canonical["\'])[^>]*>\s*/i', '', $html) ?? $html;
    }
    if ($script === 'instructions.php') {
        $instructionMobileCss = '<style>body{min-width:0;overflow-x:hidden}.wrap{min-width:0}h1{overflow-wrap:anywhere;word-break:normal}@media(max-width:600px){.wrap{width:calc(100% - 32px);padding:32px 0}.panel{min-width:0;padding:20px 16px}h1{font-size:2rem;line-height:1.18}.rule{overflow-wrap:anywhere}.btn{max-width:100%;white-space:normal}.back{display:block;margin:16px 0 0}}</style>';
        $html = preg_replace('/<\/head>/i', $instructionMobileCss . '</head>', $html, 1) ?? $html;
    }
    $header = site_header_markup();
    $footerMarkup = site_footer_markup();

    $seoHead = site_seo_head_markup($html);
    $html = preg_replace('/<title\b[^>]*>.*?<\/title>/is', '', $html, 1) ?? $html;
    $favicon = '<link rel="icon" type="image/png" href="/img/fullmocktestseries.png">';
    $googleAnalytics = '<script async src="https://www.googletagmanager.com/gtag/js?id=G-9VBBPWLJEJ"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag(\'js\',new Date());gtag(\'config\',\'G-9VBBPWLJEJ\');</script>';
    $blogMobileNavigationCss = in_array($script, ['blog.php', 'blog-submit.php'], true)
        ? '<style>html,body{overflow-x:clip!important}@media(max-width:720px){header.site-header>.container{padding:0 12px}header.site-header nav#primary-navigation{grid-template-rows:62px auto;align-items:center}header.site-header .logo{grid-column:1/-1;width:100%;max-width:100%;justify-content:center}header.site-header .nav-tools{display:none!important}header.site-header .nav-links{display:flex!important;grid-column:1/-1;grid-row:2;flex:0 0 100%;flex-direction:row!important;align-items:center;align-content:center;width:100%;justify-content:center;flex-wrap:wrap;gap:2px 4px;padding:0 0 8px;white-space:normal;overflow:visible}header.site-header .nav-links>a{display:inline-flex!important;flex:0 1 auto;width:auto!important;max-width:100%;min-height:34px;padding:7px 8px;font-size:.76rem}header.site-header .nav-links .site-user{max-width:100%;white-space:normal;overflow-wrap:anywhere}header.site-header .nav-more{display:flex;flex:0 0 100%;flex-direction:column;align-items:center;width:100%;align-self:auto;position:static}header.site-header .nav-more-toggle{width:auto;min-height:34px;padding:7px 8px}header.site-header .nav-more[open]>.nav-more-menu{position:static!important;inset:auto!important;display:grid!important;grid-template-columns:1fr;align-self:center!important;width:calc(100vw - 40px)!important;min-width:0;max-width:calc(100vw - 40px);max-height:min(45vh,320px);overflow-y:auto;margin:6px 0 0!important;padding:6px;border:1px solid #eadac9;border-radius:8px;background:#fff!important;box-shadow:0 8px 20px rgba(22,35,63,.1)!important}header.site-header .nav-links .nav-more-menu a{display:flex!important;justify-content:flex-start;min-height:42px;width:100%;padding:11px 12px;white-space:normal;overflow-wrap:anywhere;border-radius:6px}}</style>'
        : '';
    $html = preg_replace('/<\/head>/i', site_layout_css() . $blogMobileNavigationCss . $favicon . $seoHead . $googleAnalytics . '</head>', $html, 1) ?? $html;
    if ($script === 'index.php') {
        $faqSearchScript = '<script>(()=>{const input=document.getElementById("home-faq-search");const count=document.querySelector(".home-faq-result-count");if(!input||!count)return;const categories=[...document.querySelectorAll(".home-faq-category")];const questions=categories.flatMap(category=>[...category.querySelectorAll(".home-faq-item")]);const update=()=>{const query=input.value.trim().toLocaleLowerCase();let visible=0;for(const category of categories){let categoryVisible=0;for(const item of category.querySelectorAll(".home-faq-item")){const match=query===""||item.dataset.faqText.includes(query);item.hidden=!match;if(match){visible++;categoryVisible++;}}category.hidden=categoryVisible===0;if(query&&categoryVisible)category.open=true;}count.textContent=query?`${visible} ${visible===1?"answer":"answers"} found`:`${questions.length} answers across ${categories.length} topics`;};input.addEventListener("input",update);})();</script>';
        $html = preg_replace('/<\/body>/i', $faqSearchScript . '</body>', $html, 1) ?? $html;
    }
    if ($script === 'result.php') $html = preg_replace('/<\/head>/i', '<style>@media(max-width:600px){.panel .btn{display:block;width:100%;margin:0 0 10px;text-align:center}.panel .btn:last-child{margin-bottom:0}}</style></head>', $html, 1) ?? $html;
    $headerCount = 0;
    if (in_array($script, ['blog.php', 'blog-submit.php'], true)) {
        $html = preg_replace('/<header\b[^>]*>.*?<\/header>/is', '', $html, 1, $headerCount) ?? $html;
        $html = preg_replace_callback('/<body\b[^>]*>/i', static fn(array $matches): string => $matches[0] . $header, $html, 1) ?? $html;
    } elseif ($script !== 'attempt.php') {
        $html = preg_replace_callback('/<header\b[^>]*>.*?<\/header>/is', static fn(array $matches): string => $header, $html, 1, $headerCount) ?? $html;
        if ($headerCount === 0) {
            $html = preg_replace_callback('/<body\b[^>]*>/i', static fn(array $matches): string => $matches[0] . $header, $html, 1) ?? $html;
        }
    }
    $html = preg_replace_callback('/<footer\b[^>]*>.*?<\/footer>/is', static fn(array $matches): string => $footerMarkup, $html) ?? $html;
    if (stripos($html, 'id="test-form"') !== false) {
        $timerPlacement = '<script>(()=>{const timer=document.getElementById("timer");const title=document.querySelector("main h1");if(timer&&title){title.insertAdjacentElement("afterend",timer);timer.style.position="static";timer.style.width="fit-content";timer.style.margin="12px 0 18px";timer.style.boxShadow="0 3px 10px rgba(0,0,0,.12)";}})();</script>';
        $confirmation = '<script>(()=>{const testForm=document.getElementById("test-form");if(!testForm)return;let submitted=false;const startedAt=testForm.querySelector("[name=started_at]")?.value||"0";const storageKey="active-quiz:"+location.pathname+location.search+":"+startedAt;const saved=JSON.parse(localStorage.getItem(storageKey)||"{}");Object.entries(saved).forEach(([name,value])=>{const input=testForm.querySelector("[name=\""+name+"\"][value=\""+value+"\"]");if(input)input.checked=true;});testForm.addEventListener("change",()=>{const answers={};testForm.querySelectorAll("input[type=radio]:checked").forEach(input=>answers[input.name]=input.value);localStorage.setItem(storageKey,JSON.stringify(answers));});testForm.addEventListener("submit",event=>{if(submitted)return;if(!window.confirm("Submit this test now? You will not be able to change your answers after submission.")){event.preventDefault();return;}submitted=true;localStorage.removeItem(storageKey);});const nativeSubmit=testForm.submit.bind(testForm);testForm.submit=()=>{submitted=true;testForm.dataset.autoSubmitting="true";localStorage.removeItem(storageKey);nativeSubmit();};window.addEventListener("beforeunload",event=>{if(!submitted&&!testForm.dataset.autoSubmitting){event.preventDefault();event.returnValue="Your test is still in progress.";}});})();</script>';
    } else {
        $timerPlacement = '';
        $confirmation = '';
    }
    $navigationScript = '<script>(()=>{const navigation=document.getElementById("primary-navigation");if(!navigation)return;const toggle=navigation.querySelector(".nav-toggle");const moreMenu=navigation.querySelector(".nav-more");const moreToggle=moreMenu?.querySelector(".nav-more-toggle");const closeMore=()=>{if(moreMenu){moreMenu.open=false;moreToggle?.setAttribute("aria-expanded","false");}};if(moreMenu&&moreToggle&&moreToggle.dataset.moreToggleBound!=="true"){moreToggle.dataset.moreToggleBound="true";moreToggle.setAttribute("aria-expanded",String(moreMenu.open));moreToggle.addEventListener("click",event=>{event.preventDefault();moreMenu.open=!moreMenu.open;moreToggle.setAttribute("aria-expanded",String(moreMenu.open));});moreMenu.querySelectorAll("a").forEach(link=>link.addEventListener("click",closeMore));document.addEventListener("click",event=>{if(!moreMenu.contains(event.target))closeMore();});document.addEventListener("keydown",event=>{if(event.key==="Escape")closeMore();});}if(toggle&&toggle.dataset.moreCloseBound!=="true"){toggle.dataset.moreCloseBound="true";toggle.addEventListener("click",closeMore);}if(!toggle||toggle.dataset.navigationBound==="true")return;toggle.dataset.navigationBound="true";const close=()=>{navigation.classList.remove("is-open");toggle.setAttribute("aria-expanded","false");toggle.setAttribute("aria-label","Open navigation menu");};toggle.addEventListener("click",()=>{closeMore();const isOpen=navigation.classList.toggle("is-open");toggle.setAttribute("aria-expanded",String(isOpen));toggle.setAttribute("aria-label",isOpen?"Close navigation menu":"Open navigation menu");});navigation.querySelectorAll(".nav-links>a").forEach(link=>link.addEventListener("click",close));document.addEventListener("keydown",event=>{if(event.key==="Escape"){closeMore();close();}});})();</script>';
    $adminControlScript = $script === 'admin-course-control.php' ? '<script>document.querySelectorAll(".user-card").forEach(card=>{const userId=card.querySelector("input[name=user_id]")?.value;if(!userId)return;const link=document.createElement("a");link.href="admin-user-detail.php?user="+encodeURIComponent(userId);link.textContent="View full user details";link.className="btn outline";link.style.marginTop="12px";card.querySelector(".user-head")?.appendChild(link);if(card.textContent.includes("Premium:")){const premium=document.createElement("a");premium.href="admin-subscription-control.php?user="+encodeURIComponent(userId);premium.textContent="Manage premium";premium.className="btn danger";premium.style.margin="12px 0 0 8px";card.querySelector(".user-head")?.appendChild(premium);}if(!card.querySelector(".course-table")&&card.textContent.includes("Premium:")){const note=document.createElement("p");note.className="muted";note.textContent="Premium course coverage follows the plan’s included exam groups unless an individual course has been revoked. Use full user details to manage course access.";card.querySelector(".user-head")?.after(note);}});</script>' : '';
    $adminDetailScript = $script === 'admin-user-detail.php' ? '<script>const premiumBox=document.querySelector(".premium");const userId=new URLSearchParams(location.search).get("user");if(premiumBox&&userId){const link=document.createElement("a");link.href="admin-subscription-control.php?user="+encodeURIComponent(userId);link.textContent="Manage premium status";link.className="btn";link.style.marginTop="12px";premiumBox.appendChild(link);}</script>' : '';
    $adminAccountScript = $script === 'admin-user-detail.php' ? '<script>const detailUserId=new URLSearchParams(location.search).get("user");const detailPanel=document.querySelector("main .panel");if(detailUserId&&detailPanel){const link=document.createElement("a");link.href="admin-account-control.php?user="+encodeURIComponent(detailUserId);link.textContent="Deactivate user and remove credentials";link.className="btn";link.style.background="#70251c";link.style.marginTop="18px";detailPanel.parentElement?.appendChild(link);}</script>' : '';
    $html = str_replace('RankSetu', APP_BRAND_NAME, $html);
    $html = str_replace('Government exam mock tests and practice series.', APP_BRAND_TAGLINE, $html);
    if (stripos($html, '<footer') === false) $footerMarkup = site_footer_markup();
    else $footerMarkup = '';
    return preg_replace('/<\/body>/i', $navigationScript . $adminControlScript . $adminDetailScript . $adminAccountScript . $timerPlacement . $confirmation . $footerMarkup . '</body>', $html, 1) ?? $html;
}
