<?php

declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$user = require_login();
$plans = ['all-access' => ['All Government Exam Access Plan', 'all-access', 99900]];
foreach (available_course_catalog() as $seriesSlug => $series) {
    $plans[$seriesSlug] = [$series['title'] . ' Series', $seriesSlug, max(0, (int) ($series['price_paise'] ?? 25000))];
}
$planKey = (string) ($_GET['plan'] ?? $_POST['plan'] ?? '');
if (!isset($plans[$planKey])) {
    header('Location: dashboard.php');
    exit;
}
[$planName, $product, $baseAmount] = $plans[$planKey];
$courseEntitlement = $product !== 'all-access' ? course_entitlement($user, $product) : ['source' => 'paid'];
if ($product !== 'all-access' && $baseAmount === 0) {
    header('Location: product.php?product=' . rawurlencode($product));
    exit;
}
if (in_array($courseEntitlement['source'], ['subscription', 'owned'], true)) {
    if ($courseEntitlement['source'] === 'subscription') {
        enroll_user_in_course($user, $product, 'subscription', (string) ($courseEntitlement['subscription_id'] ?? ''));
    }
    header('Location: product.php?product=' . rawurlencode($product) . '&enrolled=1&subscription=1');
    exit;
}

$coupon = $_SESSION['checkout_coupon'][$planKey] ?? null;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply_coupon') {
    $coupon = valid_coupon((string) ($_POST['coupon'] ?? ''), $user, $product, $baseAmount);
    if ($coupon === null) {
        $error = 'Coupon is invalid, expired, restricted or already used.';
    } else {
        $_SESSION['checkout_coupon'][$planKey] = $coupon;
    }
}
$amount = is_array($coupon) ? (int) $coupon['final_paise'] : $baseAmount;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_gateway_order') {
    if (!razorpay_is_configured()) {
        $error = 'Live Razorpay payments are not configured. Please contact support.';
    } elseif ($amount < 1) {
        $error = 'The discounted total must be greater than zero to pay online.';
    } else {
        try {
            $gatewayOrder = create_razorpay_order($amount, 'course-' . $user['id'] . '-' . bin2hex(random_bytes(6)));
            $_SESSION['checkout_gateway'][$planKey] = [
                'order_id' => $gatewayOrder['id'],
                'amount' => $amount,
                'product' => $product,
                'created_at' => time(),
            ];
        } catch (Throwable $exception) {
            $error = 'We could not start your payment. Please try again later.';
            error_log('Razorpay course order creation failed: ' . $exception->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_payment') {
    $gateway = $_SESSION['checkout_gateway'][$planKey] ?? null;
    $paymentId = trim((string) ($_POST['razorpay_payment_id'] ?? ''));
    $gatewayOrderId = trim((string) ($_POST['razorpay_order_id'] ?? ''));
    $signature = trim((string) ($_POST['razorpay_signature'] ?? ''));
    $verified = is_array($gateway)
        && hash_equals((string) ($gateway['order_id'] ?? ''), $gatewayOrderId)
        && (int) ($gateway['amount'] ?? 0) === $amount
        && ($gateway['product'] ?? '') === $product
        && verify_razorpay_signature($gatewayOrderId, $paymentId, $signature)
        && verify_razorpay_payment($gatewayOrderId, $paymentId, $amount);

    if (!$verified) {
        $error = 'We could not verify this payment. No course was enrolled.';
    } else {
        $order = [
            'id' => $gatewayOrderId,
            'plan' => $planKey,
            'product' => $product,
            'amount' => number_format($amount / 100, 2, '.', ''),
            'original_amount' => number_format($baseAmount / 100, 2, '.', ''),
            'coupon' => is_array($coupon) ? $coupon['code'] : null,
            'discount' => is_array($coupon) ? number_format($coupon['discount_paise'] / 100, 2, '.', '') : '0.00',
            'currency' => 'INR',
            'status' => 'paid',
            'provider' => 'razorpay',
            'provider_order_id' => $gatewayOrderId,
            'provider_payment_id' => $paymentId,
            'created_at' => date('c'),
        ];
        $orders = array_values(array_filter(
            (array) ($user['orders'] ?? []),
            static fn (array $saved): bool => ($saved['id'] ?? '') !== $gatewayOrderId
        ));
        $orders[] = $order;
        update_current_user(['orders' => $orders]);

        if ($product === 'all-access') {
            foreach (published_banking_catalog() as $courseSlug => $series) {
                enroll_user_in_course(current_user() ?? $user, (string) $courseSlug, 'paid');
            }
        } else {
            enroll_user_in_course(current_user() ?? $user, $product, 'paid');
        }
        if (is_array($coupon)) {
            $all = coupons();
            foreach ($all as &$saved) {
                if (($saved['code'] ?? '') === $coupon['code']) {
                    $saved['used'] = (int) ($saved['used'] ?? 0) + 1;
                }
            }
            unset($saved);
            save_coupons($all);
        }
        unset($_SESSION['checkout_coupon'][$planKey], $_SESSION['checkout_gateway'][$planKey]);
        header('Location: orders.php?paid=' . rawurlencode($order['id']));
        exit;
    }
}

$gateway = $_SESSION['checkout_gateway'][$planKey] ?? null;
$planEsc = e($planKey);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Checkout | <?= e(APP_BRAND_NAME) ?></title>
<style>
:root{color-scheme:light;--navy:#16233f;--navy-soft:#243a64;--maroon:#9c3b2e;--paper:#f1eee5;--card:#fffefa;--ink:#1c1a15;--muted:#625e50;--line:#d8d0bc;--gold:#a97a24;--green:#174b32;--red:#70251c;--shadow:0 22px 60px rgba(22,35,63,.09)}
*{box-sizing:border-box}
body.checkout-page{margin:0;min-height:100vh;background:radial-gradient(ellipse at 50% 0,#fffdf7 0,transparent 60%),var(--paper);color:var(--ink);font:16px/1.5 Arial,Helvetica,sans-serif}
a{color:inherit}
.site-head{border-bottom:1px solid rgba(216,208,188,.75)}
.site-head-inner{width:min(1120px,calc(100% - 48px));min-height:72px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:20px}
.brand{color:var(--navy);font:700 1.25rem Georgia,serif;text-decoration:none;letter-spacing:-.02em}
.secure-mark{display:inline-flex;align-items:center;gap:8px;color:var(--muted);font-size:.83rem;font-weight:700}
.secure-icon{display:grid;width:28px;height:28px;place-items:center;border:1px solid #d9e7dc;border-radius:50%;background:#edf5ef;color:var(--green);font-size:.9rem}
.page-wrap{width:min(1020px,calc(100% - 48px));margin:0 auto;padding:44px 0 64px}
.back{display:inline-flex;align-items:center;gap:8px;margin-bottom:24px;color:var(--muted);font-size:.9rem;text-decoration:none}
.back:hover{color:var(--maroon);text-decoration:underline;text-underline-offset:3px}
.checkout-grid{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:24px;align-items:start}
.checkout-card,.summary-card{border:1px solid var(--line);border-radius:16px;background:var(--card);box-shadow:var(--shadow)}
.checkout-card{padding:clamp(24px,4vw,42px)}
.summary-card{position:sticky;top:24px;overflow:hidden}
.summary-top{padding:26px;background:var(--navy);color:#fff}
.eyebrow{margin:0 0 10px;color:var(--maroon);font-size:.75rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
.summary-top .eyebrow{color:#e6bf68}
h1{margin:0;color:var(--navy);font:700 clamp(1.9rem,4vw,2.65rem)/1.12 Georgia,serif;letter-spacing:-.035em}
.summary-top h2{margin:0;color:#fff;font:700 1.2rem/1.3 Georgia,serif}
.summary-top p{margin:8px 0 0;color:#d5dbea;font-size:.9rem}
.account-line{display:flex;align-items:center;gap:11px;margin:22px 0 0;color:var(--muted);font-size:.91rem;overflow-wrap:anywhere}
.account-avatar{flex:0 0 36px;width:36px;height:36px;display:grid;place-items:center;border-radius:50%;background:#eee8d7;color:var(--navy);font-weight:800}
.section-title{margin:30px 0 5px;color:var(--navy);font:700 1.16rem Georgia,serif}
.section-copy{margin:0;color:var(--muted);font-size:.9rem}
.coupon{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;margin-top:18px}
.coupon-field label{display:block;margin:0 0 7px;color:var(--navy);font-size:.82rem;font-weight:700}
.coupon input{display:block;width:100%;min-height:50px;padding:12px 14px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--ink);font:inherit}
.coupon input::placeholder{color:#898372}
.btn{display:inline-flex;min-height:48px;align-items:center;justify-content:center;gap:9px;border:1px solid transparent;border-radius:8px;background:var(--maroon);color:#fff;padding:12px 18px;font:700 .95rem Arial,Helvetica,sans-serif;text-align:center;text-decoration:none;cursor:pointer;transition:background .16s ease,transform .16s ease,box-shadow .16s ease}
.btn:hover{background:#812f25;box-shadow:0 5px 14px rgba(129,47,37,.18)}
.btn:active{transform:translateY(1px)}
.btn:focus-visible,.back:focus-visible,.brand:focus-visible,input:focus-visible{outline:3px solid #d3a74a;outline-offset:3px}
.coupon .btn{align-self:end;min-height:50px;white-space:nowrap}
.notice,.error{margin:18px 0 0;border:1px solid #e4d8b4;border-left:4px solid var(--gold);border-radius:8px;background:#fbf6e7;color:#514727;padding:13px 15px;font-size:.9rem}
.error{border-color:#efc6bf;border-left-color:var(--maroon);background:#fdf0ed;color:var(--red)}
.notice code{overflow-wrap:anywhere}
.payment-area{margin-top:22px}
.payment-area form{margin:0}
.payment-button{width:100%;min-height:54px;font-size:1rem}
.fine-print{margin:12px 0 0;color:var(--muted);font-size:.8rem;line-height:1.55}
.summary-body{padding:24px}
.price-label{margin:0;color:var(--muted);font-size:.83rem;font-weight:700}
.price{margin:5px 0 20px;color:var(--maroon);font:700 clamp(2.5rem,5vw,3.15rem)/1 Georgia,serif;letter-spacing:-.04em;font-variant-numeric:tabular-nums}
.price-row{display:flex;justify-content:space-between;gap:16px;padding:12px 0;border-top:1px solid #e9e3d5;color:var(--muted);font-size:.9rem}
.price-row strong{color:var(--ink);font-weight:700;text-align:right}
.price-row.discount strong{color:var(--green)}
.price-row.total{padding-top:16px;color:var(--navy);font-size:.95rem;font-weight:800}
.price-row.total strong{color:var(--navy);font-size:1.05rem}
.summary-foot{display:flex;align-items:flex-start;gap:10px;margin:18px 0 0;color:var(--muted);font-size:.8rem;line-height:1.5}
.summary-foot .secure-icon{flex:0 0 28px}
@media(max-width:760px){.page-wrap{padding-top:30px}.checkout-grid{grid-template-columns:1fr;gap:16px}.summary-card{position:static;grid-row:1}.summary-top{padding:22px}.summary-body{padding:20px}.price{font-size:2.7rem}.checkout-card{grid-row:2}}
@media(max-width:480px){.site-head-inner,.page-wrap{width:calc(100% - 32px)}.site-head-inner{min-height:62px}.brand{font-size:1.1rem}.secure-mark{font-size:.76rem;gap:6px}.secure-icon{width:26px;height:26px}.page-wrap{padding:22px 0 36px}.back{margin-bottom:16px}.checkout-card,.summary-card{border-radius:12px}.checkout-card{padding:22px 18px}.summary-top{padding:20px}.summary-body{padding:18px}.coupon{grid-template-columns:1fr}.coupon .btn{width:100%}.section-title{margin-top:25px}.account-line{font-size:.85rem}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;transition:none!important}}
</style>
</head>
<body class="checkout-page">
<header class="site-head">
    <div class="site-head-inner">
        <a class="brand" href="index.php"><?= e(APP_BRAND_NAME) ?></a>
        <div class="secure-mark"><span class="secure-icon" aria-hidden="true">✓</span> Secure checkout</div>
    </div>
</header>
<main class="page-wrap">
<a class="back" href="dashboard.php"><span aria-hidden="true">←</span> Back to dashboard</a>
<div class="checkout-grid">
<section class="checkout-card" aria-labelledby="checkout-title">
<p class="eyebrow">Almost there</p>
<h1 id="checkout-title"><?= e($planName) ?></h1>
<div class="account-line"><span class="account-avatar" aria-hidden="true"><?= e(strtoupper(substr((string) $user['email'], 0, 1))) ?></span><span>Signed in as <strong><?= e($user['email']) ?></strong></span></div>
<?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
<h2 class="section-title">Have a coupon?</h2>
<p class="section-copy">Add a code to apply any available savings to your order.</p>
<form method="post" class="coupon">
<input type="hidden" name="plan" value="<?= $planEsc ?>">
<input type="hidden" name="action" value="apply_coupon">
<div class="coupon-field"><label for="coupon-code">Coupon code</label><input id="coupon-code" name="coupon" placeholder="Enter your code" autocomplete="off" autocapitalize="characters" required></div>
<button class="btn" type="submit">Apply code</button>
</form>
<div class="payment-area">
<?php if (is_array($gateway)): ?>
<div class="notice" role="status">Your payment order is ready. Continue securely with Razorpay to complete your enrollment.</div>
<form method="post" id="razorpay-form">
<input type="hidden" name="plan" value="<?= $planEsc ?>">
<input type="hidden" name="action" value="verify_payment">
<input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
<input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
<input type="hidden" name="razorpay_signature" id="razorpay_signature">
<button class="btn payment-button" id="rzp-button" type="button">Pay securely with Razorpay <span aria-hidden="true">→</span></button>
</form>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('rzp-button').addEventListener('click', function () {
    if (typeof window.Razorpay !== 'function') {
        window.alert('The secure payment window could not be loaded. Please refresh the page and try again.');
        return;
    }
    var options = {
        key: <?= json_encode(razorpay_key_id()) ?>,
        amount: <?= (int) $amount ?>,
        currency: 'INR',
        name: <?= json_encode(APP_BRAND_NAME) ?>,
        description: <?= json_encode($planName) ?>,
        order_id: <?= json_encode((string) $gateway['order_id']) ?>,
        prefill: { email: <?= json_encode((string) $user['email']) ?> },
        handler: function (response) {
            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
            document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
            document.getElementById('razorpay_signature').value = response.razorpay_signature;
            document.getElementById('razorpay-form').submit();
        }
    };
    new Razorpay(options).open();
});
</script>
<?php elseif (razorpay_is_configured()): ?>
<form method="post">
<input type="hidden" name="plan" value="<?= $planEsc ?>">
<input type="hidden" name="action" value="create_gateway_order">
<button class="btn payment-button" type="submit">Continue to secure payment <span aria-hidden="true">→</span></button>
</form>
<?php else: ?>
<div class="notice" role="status">Online payment is temporarily unavailable. Please contact support for help completing your enrollment.</div>
<?php endif; ?>
<p class="fine-print">Payments are securely processed by Razorpay. Your course access is activated after payment verification.</p>
</div>
</section>
<aside class="summary-card" aria-labelledby="summary-title">
<div class="summary-top"><p class="eyebrow">Order summary</p><h2 id="summary-title">Your enrollment</h2><p><?= e($planName) ?></p></div>
<div class="summary-body">
<p class="price-label">Total due today</p>
<div class="price">₹<?= number_format($amount / 100, 2) ?></div>
<div class="price-row"><span>Subtotal</span><strong>₹<?= number_format($baseAmount / 100, 2) ?></strong></div>
<?php if (is_array($coupon)): ?>
<div class="price-row discount"><span>Coupon <?= e((string) $coupon['code']) ?></span><strong>−₹<?= number_format(((int) ($coupon['discount_paise'] ?? 0)) / 100, 2) ?></strong></div>
<?php endif; ?>
<div class="price-row total"><span>Total due</span><strong>₹<?= number_format($amount / 100, 2) ?></strong></div>
<div class="summary-foot"><span class="secure-icon" aria-hidden="true">✓</span><span>Your payment details are handled securely by Razorpay. We do not store your card details.</span></div>
</div>
</aside>
</div>
</main>
</body>
</html>
