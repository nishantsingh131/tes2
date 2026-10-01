<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

$pages = [
    'about' => [
        'title' => 'About RankSetu',
        'eyebrow' => 'Focused practice, clearer progress',
        'intro' => 'RankSetu is an independent practice platform built to help exam aspirants prepare through structured tests, useful feedback and a steady routine.',
        'sections' => [
            ['title' => 'Why RankSetu exists', 'body' => 'Exam preparation often means juggling scattered question sets, timing practice and personal notes. RankSetu brings practice series, timed attempts and saved results into one place so learners can spend more time preparing and less time organizing.'],
            ['title' => 'A practice platform, not an exam authority', 'body' => 'RankSetu is independently operated and is not affiliated with, endorsed by, or an official representative of any government, regulator, recruitment board, university or examination authority. Exam names and marks belong to their respective owners and are used only to identify the relevant preparation category.'],
            ['title' => 'How learning works here', 'body' => 'Choose a series, review its instructions, attempt a test within its configured time and use your saved results to identify areas for further study. Features, available tests, languages and scoring may vary by series. Check the details shown on each series page before enrolling.'],
            ['title' => 'Our commitment', 'body' => 'We aim to present content clearly, protect account information and improve the platform over time. Practice supports preparation; it cannot replace official notifications, primary study materials or a learner\'s own judgment.'],
        ],
    ],
    'contact' => [
        'title' => 'Contact Us',
        'eyebrow' => 'Support and enquiries',
        'intro' => 'Send a message about your account, enrollment, a test, accessibility or a privacy request. Do not include passwords, one-time codes, full payment-card details or other secrets.',
        'sections' => [
            ['title' => 'What happens next', 'body' => 'Your message is saved for the RankSetu support team. Include the series or order reference when it helps us investigate, but never send your password or payment credentials. The support address and response process must be configured by the operator before public launch.'],
            ['title' => 'Privacy requests', 'body' => 'Use this form to ask about, correct or request deletion of personal information associated with your account. We may need to verify your identity before acting and may retain information where the law requires it.'],
        ],
    ],
    'disclaimer' => [
        'title' => 'Important Disclaimer',
        'eyebrow' => 'Please read before relying on content',
        'intro' => 'RankSetu provides independent educational practice tools. Use official sources for authoritative exam, eligibility, recruitment, payment and legal information.',
        'sections' => [
            ['title' => 'No official affiliation or endorsement', 'body' => 'Unless a page expressly says otherwise, RankSetu is not affiliated with or endorsed by any government department, public authority, examination board, regulator, employer, school, university or payment provider. Names, logos and marks remain the property of their respective owners.'],
            ['title' => 'Educational practice only', 'body' => 'Questions, explanations, scores, rankings and other materials are supplied for general learning and practice. They may be simplified, incomplete, outdated or contain errors. A practice score is not an official result, prediction, credential or guarantee of selection, employment, admission, rank or any particular outcome.'],
            ['title' => 'Verify official information', 'body' => 'Dates, eligibility, fees, syllabus, vacancies, exam pattern, marking rules, identity requirements and other decisions can change. Confirm them directly with the relevant official authority before applying, paying a fee or making a consequential decision.'],
            ['title' => 'Availability and external services', 'body' => 'The platform and its content may be changed, interrupted or unavailable. Links to third-party sites are provided for convenience; RankSetu does not control or guarantee their content, security, availability or policies.'],
            ['title' => 'No professional advice', 'body' => 'Nothing on RankSetu is legal, financial, medical, recruitment or other regulated professional advice. Obtain advice from a suitably qualified professional where your circumstances require it.'],
            ['title' => 'Liability and mandatory rights', 'body' => 'To the maximum extent permitted by applicable law, RankSetu disclaims implied warranties and liability for indirect or consequential loss arising from use of the service. Nothing in this notice excludes a right or liability that applicable law does not allow to be excluded.'],
        ],
    ],
    'faq' => [
        'title' => 'Frequently Asked Questions',
        'eyebrow' => 'Help centre',
        'intro' => 'Quick answers about using RankSetu.',
        'sections' => [
            ['title' => 'How do I start a series?', 'body' => 'Open a series from the catalog, sign in or create an account, and follow the enrollment options shown on that series. Enrolled series appear in your student dashboard.'],
            ['title' => 'How do I contact support?', 'body' => 'Use the Contact page form. Your message is saved for the support team; never include a password, one-time code or full payment-card number.'],
            ['title' => 'Are practice results official?', 'body' => 'No. Results are for practice and progress tracking only. Verify official results and exam information with the relevant authority.'],
            ['title' => 'Can I practice on mobile?', 'body' => 'The pages are designed to work on modern phones, tablets and desktop browsers.'],
        ],
    ],
    'privacy' => [
        'title' => 'Privacy Policy',
        'eyebrow' => 'How information is handled',
        'intro' => 'This policy describes the information RankSetu may process to operate accounts, practice features and support requests. The operator must complete the business identity, contact and jurisdiction details below and obtain local privacy review before launch.',
        'sections' => [
            ['title' => 'Who is responsible', 'body' => 'Service name: RankSetu. Legal operator: [INSERT REGISTERED LEGAL NAME]. Registered or principal address: [INSERT FULL POSTAL ADDRESS]. Privacy contact: [INSERT MONITORED PRIVACY EMAIL]. These operator details must be completed before this policy is published as final.'],
            ['title' => 'Information we collect', 'body' => 'Depending on your use of the service, we may process account details such as name and email; authentication data such as a password hash; enrollment, test responses, scores and activity history; order, payment-status and invoice references; and messages you submit through support forms. The application should not request or store payment-card security codes or account passwords in support messages.'],
            ['title' => 'Technical information and cookies', 'body' => 'The service uses session data needed for sign-in, security and form protection. Google Analytics (measurement ID G-9VBBPWLJEJ) measures site visits and interactions and may process technical or device information and use cookies or similar identifiers, depending on its configuration. Google processes this information under its own terms and privacy policy. Hosting systems may also record technical logs such as IP address, browser, request time and error information for security, troubleshooting and abuse prevention. The operator should configure Analytics retention and data-sharing settings and obtain any consent required by applicable law.'],
            ['title' => 'Purposes and legal basis', 'body' => 'Information may be used to create and secure accounts, provide tests and dashboards, record enrollments and orders, answer support requests, prevent fraud and abuse, maintain service reliability, meet legal obligations and resolve disputes. The operator must identify and document the applicable legal basis and any consent requirements for each purpose in every jurisdiction served.'],
            ['title' => 'How information is shared', 'body' => 'Information may be accessible to authorized service personnel and to hosting, database, email, payment, security and support providers that are needed to operate the service. Providers should be contractually restricted to appropriate purposes and safeguards. Information may also be disclosed when legally required, to protect users or the service, or as part of a business transfer subject to applicable law. RankSetu does not sell personal information for money.'],
            ['title' => 'Retention', 'body' => 'Information is retained only for as long as needed for the purposes above, account operation, support, security, legal recordkeeping and dispute resolution, then deleted or de-identified when reasonably practicable. Before launch, publish actual retention periods for account records, attempts, orders, support messages, security logs and backups, taking statutory accounting and limitation periods into account.'],
            ['title' => 'Security and storage', 'body' => 'We use access controls and reasonable technical and organizational safeguards appropriate to the service, but no internet transmission or storage system can be guaranteed completely secure. The operator must restrict database access, maintain backups, patch the server, protect configuration secrets and prevent direct public access to private data files.'],
            ['title' => 'Your choices and rights', 'body' => 'Subject to applicable law, you may request access to, correction of or deletion of personal information, withdraw consent where processing relies on consent, object to certain processing, or raise a complaint with the responsible authority. Contact the privacy address above. We may verify identity, explain any lawful retention, and respond within the period required by applicable law.'],
            ['title' => 'Children and international users', 'body' => 'The operator must set an age policy and appropriate guardian-consent process based on the ages of intended learners and laws where the service is offered. If information about a child was provided without required authorization, contact the privacy address. Data may be processed in the country where the operator or its providers maintain systems; document safeguards required for cross-border transfers.'],
            ['title' => 'Changes and complaints', 'body' => 'We may update this policy when the service or law changes. The final published version should show its effective date and a clear summary of material changes. Privacy grievances should be directed to [INSERT GRIEVANCE OFFICER OR PRIVACY CONTACT AND POSTAL DETAILS REQUIRED BY APPLICABLE LAW].'],
        ],
    ],
    'terms' => [
        'title' => 'Terms of Use',
        'eyebrow' => 'Terms and conditions',
        'intro' => 'These proposed terms govern access to RankSetu. The operator must complete the legal identity, address, effective date and governing-law details and obtain qualified review before relying on them.',
        'sections' => [
            ['title' => 'Operator and acceptance', 'body' => 'RankSetu is operated by [INSERT REGISTERED LEGAL NAME], of [INSERT FULL POSTAL ADDRESS] (the "Operator"). These terms apply when you access or use the site, create an account, enroll in a series or submit content. If you do not agree, do not use the service. Mandatory consumer rights remain unaffected.'],
            ['title' => 'Eligibility and accounts', 'body' => 'You must be legally able to enter these terms where you live. Provide accurate account information, keep credentials confidential, use a unique password and promptly report suspected unauthorized access. You are responsible for activity through your account except to the extent caused by the Operator or applicable law provides otherwise. Do not create accounts for another person without authority.'],
            ['title' => 'Service and educational use', 'body' => 'RankSetu provides practice questions, timed tests, learning records and related features. Content is for personal, non-commercial study unless a written license says otherwise. A score, rank or completion indicator is an internal practice result and is not an official examination result or guarantee of success.'],
            ['title' => 'Acceptable use', 'body' => 'Do not interfere with service security or availability; probe, scrape or automate access without permission; upload malware or unlawful material; impersonate another person; misuse other users\' information; bypass access controls; or reproduce, sell, publish or redistribute content without permission. Do not submit passwords, payment secrets or sensitive personal data in support forms.'],
            ['title' => 'Content and intellectual property', 'body' => 'The service, its software, branding, original question material and presentation are owned by or licensed to the Operator and protected by applicable law. You retain rights in content you lawfully submit, and grant the Operator a limited license to store and use it to operate, secure and support the service. Respect third-party rights and official exam authority marks.'],
            ['title' => 'Enrollment, fees and payments', 'body' => 'Series access, included tests, validity, price, taxes and payment conditions are those displayed before enrollment or checkout. Do not rely on a payment confirmation shown only in your browser; access is granted after server-side verification. Payment providers may apply their own terms. No paid feature should be enabled until the checkout, tax disclosures, invoice process and refund terms have been reviewed and configured.'],
            ['title' => 'Suspension and termination', 'body' => 'We may restrict or suspend access where reasonably necessary to protect the service, investigate suspected abuse, comply with law or address a serious breach. Where practical, we will provide notice and a way to appeal. You may stop using the service and request account deletion through support, subject to lawful retention and outstanding obligations.'],
            ['title' => 'Availability, warranties and liability', 'body' => 'We work to keep the service available and accurate but do not promise uninterrupted access or error-free content. To the extent permitted by law, the service is provided without implied warranties and the Operator is not liable for indirect or consequential loss. These terms do not limit liability or remedies that applicable law makes non-excludable. Have local counsel define any lawful liability cap, consumer disclosures and exceptions before launch.'],
            ['title' => 'Complaints, governing law and changes', 'body' => 'Contact [INSERT SUPPORT EMAIL] or [INSERT POSTAL ADDRESS] about a concern. Governing law, venue, dispute resolution, statutory grievance contact and notice requirements must be completed by the Operator for its actual country and customer locations; no placeholder here selects a jurisdiction. We may revise these terms and will communicate material changes as required by law.'],
        ],
    ],
    'refund' => [
        'title' => 'Refund and Cancellation Policy',
        'eyebrow' => 'Orders, cancellations and refunds',
        'intro' => 'This proposed policy must match the actual checkout, products, payment provider and consumer laws in the markets served. Publish the final version before accepting real payments.',
        'sections' => [
            ['title' => 'Current payment status', 'body' => 'The Operator must state here whether real payments are currently accepted. Demo or test-mode transactions are not real purchases and must never be represented as completed paid orders. Do not activate live payment credentials until this policy and checkout disclosures are finalized.'],
            ['title' => 'Cancellation before access', 'body' => 'Before purchase, review the series, included tests, validity period, price, taxes and payment details. If an order is duplicated, unauthorized or charged but access is not provided, contact support promptly with the order reference. We will investigate and correct confirmed billing or access errors.'],
            ['title' => 'Digital access and refund requests', 'body' => 'Because test-series access is digital, eligibility for cancellation or refund may depend on whether access has started, the nature of the issue and applicable consumer law. The final policy must specify any request window, exceptions, partial-refund rules and the method and timing of repayment. No term here removes a mandatory statutory cancellation, cooling-off or refund right.'],
            ['title' => 'How to request help', 'body' => 'Contact [INSERT MONITORED SUPPORT EMAIL] and include the account email, order ID, purchase date, series and a short description. Never send a password, one-time code, full card number or card security code. We may request reasonable evidence needed to verify the transaction.'],
            ['title' => 'Review and resolution', 'body' => 'The Operator should acknowledge requests, investigate payment-provider records, explain the decision and provide a route to escalate unresolved complaints. Publish the actual response target and refund-processing estimate here after confirming them with the payment provider and legal adviser.'],
            ['title' => 'Consumer rights and contact', 'body' => 'This policy is subject to mandatory rights under applicable law. Legal operator: [INSERT REGISTERED LEGAL NAME]. Postal address and grievance contact: [INSERT REQUIRED DETAILS]. Keep a copy of the order confirmation and all support correspondence.'],
        ],
    ],
];

$key = (string) ($_GET['page'] ?? 'about');
if (!isset($pages[$key])) {
    http_response_code(404);
    $key = 'not_found';
    $page = [
        'title' => 'Page not found',
        'eyebrow' => '404',
        'intro' => 'This information page does not exist. Browse the site or return to the exam catalog.',
        'sections' => [],
    ];
} else {
    $page = $pages[$key];
}
$user = current_user();
$contactValues = [
    'name' => (string) ($user['name'] ?? ''),
    'email' => (string) ($user['email'] ?? ''),
    'subject' => '',
    'message' => '',
];
$contactError = '';
$contactNotice = '';
$contactCsrf = $_SESSION['contact_csrf'] ??= bin2hex(random_bytes(32));

if ($key === 'contact' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_message') {
    foreach (array_keys($contactValues) as $field) {
        $postedValue = $_POST[$field] ?? '';
        $contactValues[$field] = is_string($postedValue) ? $postedValue : '';
    }
    $contactValues['name'] = trim(preg_replace('/[\r\n\t]+/u', ' ', $contactValues['name']) ?? '');
    $contactValues['email'] = strtolower(trim($contactValues['email']));
    $contactValues['subject'] = trim(preg_replace('/[\r\n\t]+/u', ' ', $contactValues['subject']) ?? '');
    $contactValues['message'] = trim(str_replace(["\r\n", "\r"], "\n", $contactValues['message']));
    $contactLength = static function (string $value): int {
        if (function_exists('mb_strlen')) return mb_strlen($value, 'UTF-8');
        $count = preg_match_all('/./us', $value, $matches);
        return $count === false ? PHP_INT_MAX : $count;
    };
    $contactTimestamps = array_values(array_filter((array) ($_SESSION['contact_submissions'] ?? []), static fn($time): bool => is_int($time) && $time > time() - 3600));

    if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals($contactCsrf, (string) $_POST['csrf_token'])) {
        http_response_code(400);
        $contactError = 'This form expired or could not be verified. Refresh the page and try again.';
    } elseif (!empty($_POST['website'])) {
        $_SESSION['contact_flash'] = 'Thank you. Your message has been received.';
        header('Location: info.php?page=contact&sent=1', true, 303);
        exit;
    } elseif (count($contactTimestamps) >= 5) {
        http_response_code(429);
        $contactError = 'For safety, please wait before sending another message.';
    } elseif (preg_match('//u', implode('', $contactValues)) !== 1 || $contactLength($contactValues['name']) < 2 || $contactLength($contactValues['name']) > 120 || !filter_var($contactValues['email'], FILTER_VALIDATE_EMAIL) || strlen($contactValues['email']) > 254 || $contactLength($contactValues['subject']) < 3 || $contactLength($contactValues['subject']) > 160 || $contactLength($contactValues['message']) < 20 || $contactLength($contactValues['message']) > 5000 || ($_POST['privacy_ack'] ?? null) !== '1') {
        $contactError = 'Check the form fields. Use a valid email, a subject, a message of 20 to 5,000 characters, and confirm the privacy notice.';
    } elseif (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $contactValues['message']) === 1) {
        $contactError = 'The message contains unsupported control characters. Remove them and try again.';
    } else {
        try {
            store_contact_message([
                'id' => bin2hex(random_bytes(16)),
                'name' => $contactValues['name'],
                'email' => $contactValues['email'],
                'subject' => $contactValues['subject'],
                'message' => $contactValues['message'],
            ]);
            $contactTimestamps[] = time();
            $_SESSION['contact_submissions'] = $contactTimestamps;
            $_SESSION['contact_flash'] = 'Your message has been saved for the RankSetu support team.';
            unset($_SESSION['contact_csrf']);
            header('Location: info.php?page=contact&sent=1', true, 303);
            exit;
        } catch (Throwable $exception) {
            error_log('Contact message could not be stored: ' . $exception->getMessage());
            $contactError = 'We could not save your message right now. Please try again later.';
        }
    }
}

if ($key === 'contact' && ($_GET['sent'] ?? '') === '1') {
    $contactNotice = (string) ($_SESSION['contact_flash'] ?? 'Your message has been received.');
    unset($_SESSION['contact_flash']);
}
$policyDraft = in_array($key, ['privacy', 'terms', 'refund', 'disclaimer'], true);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page['title']) ?> | RankSetu</title>
<style>
:root{--navy:#16233f;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Arial,sans-serif}.wrap{width:min(900px,calc(100% - 40px));margin:auto;padding:44px 0 60px}.crumb{color:var(--maroon);font-weight:700;text-decoration:none;font-size:.88rem}.hero{background:var(--navy);color:#fff;padding:34px;margin-top:18px;border-top:4px solid var(--gold)}.eyebrow{font-size:.78rem;color:#e0b34f;font-weight:700;text-transform:uppercase;letter-spacing:.08em}.hero h1{font:600 2.5rem Georgia,serif;margin:12px 0;color:#fff}.hero p{color:#e0dccd;font-size:1.05rem;line-height:1.7;margin:0;max-width:68ch}.content{display:grid;gap:12px;margin-top:20px}.section{background:var(--card);border:1px solid var(--line);padding:24px}.section h2{font:600 1.25rem Georgia,serif;color:var(--navy);margin:0 0 9px}.section p{color:var(--muted);line-height:1.75;margin:0}.review-note,.notice,.error{margin-top:18px;padding:14px 16px;border-left:4px solid var(--gold);background:#fff9e8;color:var(--ink);line-height:1.6}.notice{border-color:#286447;background:#e7f2ea}.error{border-color:#9c3b2e;background:#f8e8e4}.contact-form{display:grid;gap:14px;margin-top:12px}.contact-form label{display:grid;gap:6px;font-size:.88rem;font-weight:700;color:var(--ink)}.contact-form input,.contact-form textarea{width:100%;border:1px solid var(--line);border-radius:2px;background:#fffdf7;padding:11px 12px;color:var(--ink);font:inherit}.contact-form input:focus,.contact-form textarea:focus{outline:2px solid #a97a24;outline-offset:1px}.contact-form textarea{min-height:170px;resize:vertical;line-height:1.5}.contact-form .consent{display:flex;align-items:flex-start;gap:9px;font-weight:400;line-height:1.5}.contact-form .consent input{width:18px;height:18px;flex:0 0 auto;margin:1px 0}.contact-form a,.quick a{color:var(--maroon);font-weight:700}.btn{justify-self:start;border:0;background:var(--maroon);color:#fff;padding:12px 18px;font-weight:700;font-size:.92rem;cursor:pointer}.btn:hover{background:#832f24}.honey{position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden}.quick{display:flex;flex-wrap:wrap;gap:9px 16px;margin-top:24px;padding-top:18px;border-top:1px solid var(--line)}.quick a{font-size:.86rem;text-decoration:none}.quick a[aria-current="page"]{text-decoration:underline;text-underline-offset:3px}@media(max-width:650px){.wrap{width:calc(100% - 28px);padding:28px 0 42px}.hero{padding:24px}.hero h1{font-size:2rem}.section{padding:20px}.btn{width:100%}}
</style>
</head>
<body>
<header><a class="brand" href="index.php">RankSetu</a><nav class="nav" aria-label="Account navigation"><?php if ($user !== null): ?><a href="student.php">Dashboard</a><a href="logout.php">Log out</a><?php else: ?><a href="auth.php">Log in</a><?php endif; ?></nav></header>
<main class="wrap">
    <a class="crumb" href="index.php">&larr; Back to RankSetu</a>
    <section class="hero"><div class="eyebrow"><?= e($page['eyebrow']) ?></div><h1><?= e($page['title']) ?></h1><p><?= e($page['intro']) ?></p></section>
    <?php if ($policyDraft): ?><aside class="review-note"><strong>Pre-launch legal review required.</strong> Replace every bracketed business detail, confirm the actual service practices, payment terms and jurisdictions, and have qualified local counsel review these draft terms before publication.</aside><?php endif; ?>
    <?php if ($key === 'contact' && $contactNotice !== ''): ?><div class="notice" role="status"><?= e($contactNotice) ?></div><?php endif; ?>
    <?php if ($key === 'contact' && $contactError !== ''): ?><div class="error" role="alert"><?= e($contactError) ?></div><?php endif; ?>
    <section class="content">
        <?php foreach ($page['sections'] as $section): ?>
            <article class="section"><h2><?= e($section['title']) ?></h2><p><?= nl2br(e($section['body'])) ?></p></article>
        <?php endforeach; ?>
        <?php if ($key === 'contact'): ?>
            <article class="section"><h2>Send a message</h2><p>Your message is stored for the support team. We do not send an automatic email confirmation.</p>
                <form class="contact-form" method="post" action="info.php?page=contact" accept-charset="UTF-8">
                    <input type="hidden" name="action" value="send_message">
                    <input type="hidden" name="csrf_token" value="<?= e($contactCsrf) ?>">
                    <div class="honey" aria-hidden="true"><label>Leave this field blank<input name="website" tabindex="-1" autocomplete="off"></label></div>
                    <label for="contact-name">Your name<input id="contact-name" name="name" maxlength="120" autocomplete="name" required value="<?= e($contactValues['name']) ?>"></label>
                    <label for="contact-email">Email address<input id="contact-email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= e($contactValues['email']) ?>"></label>
                    <label for="contact-subject">Subject<input id="contact-subject" name="subject" maxlength="160" minlength="3" required value="<?= e($contactValues['subject']) ?>"></label>
                    <label for="contact-message">Message<textarea id="contact-message" name="message" minlength="20" maxlength="5000" required><?= e($contactValues['message']) ?></textarea></label>
                    <label class="consent"><input type="checkbox" name="privacy_ack" value="1" required><span>I have read the <a href="info.php?page=privacy">Privacy Policy</a> and understand this message will be stored so RankSetu can respond.</span></label>
                    <button class="btn" type="submit">Send message</button>
                </form>
            </article>
        <?php endif; ?>
    </section>
    <nav class="quick" aria-label="Information pages">
        <?php foreach (['about' => 'About', 'contact' => 'Contact', 'disclaimer' => 'Disclaimer', 'faq' => 'FAQ', 'privacy' => 'Privacy', 'terms' => 'Terms', 'refund' => 'Refunds'] as $pageKey => $label): ?>
            <a href="info.php?page=<?= e($pageKey) ?>" <?= $key === $pageKey ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
</main>
</body></html>

