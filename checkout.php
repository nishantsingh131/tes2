<?php

declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$user = require_login();
$plans = ['all-access' => ['All Banking Access Plan', 'all-access', 99900]];
foreach (published_banking_catalog() as $seriesSlug => $series) {
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
body.checkout-page{margin:0;background:#efece2;font:16px Arial;color:#1c1a15}
.box{box-sizing:border-box;width:min(640px,calc(100% - 32px));margin:36px auto;background:#f8f6ee;border:1px solid #cfc7ac;padding:32px}
h1{font:600 2rem Georgia;color:#16233f}
.eyebrow{color:#9c3b2e;font-weight:700;font-size:.75rem;text-transform:uppercase}
.muted{color:#625e50}
.price{font:700 2.4rem Georgia;color:#9c3b2e;margin:20px 0}
.notice,.error{padding:14px;margin:18px 0;border-left:4px solid #a97a24;background:#a97a241c;color:#625e50}
.error{border-color:#9c3b2e;background:#f7ded8;color:#70251c}
.coupon{display:flex;gap:8px;flex-wrap:wrap}
.coupon input{flex:1;min-width:180px;padding:12px;border:1px solid #cfc7ac}
.btn{border:0;background:#9c3b2e;color:#fff;padding:13px 18px;font-weight:700;cursor:pointer}
.back{display:block;margin-top:22px;color:#9c3b2e;text-decoration:none}
</style>
</head>
<body class="checkout-page">
<main class="box">
<div class="eyebrow">Secure checkout</div>
<h1><?= e($planName) ?></h1>
<p class="muted">Signed in as <?= e($user['email']) ?></p>
<?php if (is_array($coupon)): ?>
<p class="muted">Original: <s>₹<?= number_format($baseAmount / 100, 2) ?></s> &bull; Coupon <strong><?= e($coupon['code']) ?></strong> saved ₹<?= number_format($coupon['discount_paise'] / 100, 2) ?></p>
<?php endif; ?>
<div class="price">₹<?= number_format($amount / 100, 2) ?></div>
<?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="coupon">
<input type="hidden" name="plan" value="<?= $planEsc ?>">
<input type="hidden" name="action" value="apply_coupon">
<input name="coupon" placeholder="Coupon code" required>
<button class="btn" type="submit">Apply coupon</button>
</form>
<?php if (is_array($gateway)): ?>
<div class="notice">Complete payment through Razorpay to enroll in this course.</div>
<form method="post" id="razorpay-form">
<input type="hidden" name="plan" value="<?= $planEsc ?>">
<input type="hidden" name="action" value="verify_payment">
<input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
<input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
<input type="hidden" name="razorpay_signature" id="razorpay_signature">
<button class="btn" id="rzp-button" type="button">Pay securely with Razorpay</button>
</form>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('rzp-button').onclick = function () {
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
};
</script>
<?php elseif (razorpay_is_configured()): ?>
<form method="post">
<input type="hidden" name="plan" value="<?= $planEsc ?>">
<input type="hidden" name="action" value="create_gateway_order">
<button class="btn" type="submit">Continue to Razorpay</button>
</form>
<?php else: ?>
<div class="notice">Live checkout needs a Razorpay Key ID starting with <code>rzp_live_</code> and its matching Key Secret configured on the server.</div>
<?php endif; ?>
<a class="back" href="dashboard.php">Back to dashboard</a>
</main>
</body>
</html>
