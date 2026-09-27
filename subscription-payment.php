<?php

declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$user = require_login();
$slug = (string) ($_GET['plan'] ?? $_POST['plan'] ?? '');
$plan = null;
foreach (subscription_plans(true) as $candidate) {
    if (($candidate['slug'] ?? '') === $slug) {
        $plan = $candidate;
        break;
    }
}
if ($plan === null) {
    http_response_code(404);
    exit('Subscription plan not found.');
}
foreach (user_subscriptions((string) $user['id']) as $existing) {
    if (($existing['status'] ?? '') === 'ACTIVE' && (empty($existing['expires_at']) || strtotime((string) $existing['expires_at']) > time())) {
        header('Location: dashboard.php');
        exit;
    }
}

$error = '';
$coupon = $_SESSION['subscription_coupon'][$slug] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply_coupon') {
    $coupon = valid_coupon((string) ($_POST['coupon'] ?? ''), $user, 'all', (int) $plan['price_paise']);
    if ($coupon === null) {
        $error = 'Invalid, expired, restricted or already used coupon.';
    } else {
        $_SESSION['subscription_coupon'][$slug] = $coupon;
    }
}
$discount = is_array($coupon) ? (int) $coupon['discount_paise'] : 0;
$final = max(0, (int) $plan['price_paise'] - $discount);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_gateway_order') {
    if (!razorpay_is_configured()) {
        $error = 'Live Razorpay payment is unavailable. Please contact support.';
    } elseif ($final < 1) {
        $error = 'The discounted total must be greater than zero to pay online.';
    } else {
        try {
            $gatewayOrder = create_razorpay_order($final, 'sub-' . $user['id'] . '-' . bin2hex(random_bytes(6)));
            $_SESSION['subscription_gateway'][$slug] = [
                'order_id' => $gatewayOrder['id'],
                'amount' => $final,
                'created_at' => time(),
            ];
        } catch (Throwable $exception) {
            $error = 'We could not start your payment. Please try again later.';
            error_log('Razorpay subscription order creation failed: ' . $exception->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_payment') {
    $gateway = $_SESSION['subscription_gateway'][$slug] ?? null;
    $paymentId = trim((string) ($_POST['razorpay_payment_id'] ?? ''));
    $gatewayOrderId = trim((string) ($_POST['razorpay_order_id'] ?? ''));
    $signature = trim((string) ($_POST['razorpay_signature'] ?? ''));
    $verified = is_array($gateway)
        && hash_equals((string) ($gateway['order_id'] ?? ''), $gatewayOrderId)
        && (int) ($gateway['amount'] ?? 0) === $final
        && verify_razorpay_signature($gatewayOrderId, $paymentId, $signature)
        && verify_razorpay_payment($gatewayOrderId, $paymentId, $final);

    if (!$verified) {
        $error = 'We could not verify this payment. No subscription was activated.';
    } else {
        $start = new DateTimeImmutable('now');
        $unit = (string) ($plan['duration_unit'] ?? 'day');
        $interval = $unit === 'year'
            ? 'P' . (int) $plan['duration'] . 'Y'
            : ($unit === 'month' ? 'P' . (int) $plan['duration'] . 'M' : 'P' . (int) $plan['duration'] . 'D');
        $expires = $start->add(new DateInterval($interval));
        $subscriptionId = 'subscription-' . bin2hex(random_bytes(10));
        $orderId = (string) $gateway['order_id'];
        $createdAt = date('Y-m-d H:i:s');
        $subscription = [
            'id' => $subscriptionId,
            'user_id' => $user['id'],
            'plan_id' => $plan['id'],
            'plan_slug' => $plan['slug'],
            'plan_name' => $plan['name'],
            'status' => 'ACTIVE',
            'start_at' => $start->format('Y-m-d H:i:s'),
            'expires_at' => $expires->format('Y-m-d H:i:s'),
            'amount_paise' => $plan['price_paise'],
            'discount_paise' => $discount,
            'tax_paise' => 0,
            'final_amount_paise' => $final,
            'coupon_code' => is_array($coupon) ? $coupon['code'] : null,
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'created_at' => $createdAt,
            'covered_courses' => (array) ($plan['covered_courses'] ?? []),
            'covered_groups' => (array) ($plan['covered_groups'] ?? []),
            'all_access' => !empty($plan['all_access']),
        ];
        $order = [
            'id' => $orderId,
            'plan' => $plan['name'],
            'product' => $plan['name'],
            'amount' => number_format($final / 100, 2, '.', ''),
            'original_amount' => number_format(((int) $plan['price_paise']) / 100, 2, '.', ''),
            'coupon' => is_array($coupon) ? $coupon['code'] : null,
            'discount' => number_format($discount / 100, 2, '.', ''),
            'currency' => $plan['currency'] ?? 'INR',
            'status' => 'paid',
            'provider' => 'razorpay',
            'provider_order_id' => $orderId,
            'provider_payment_id' => $paymentId,
            'created_at' => $createdAt,
            'subscription_id' => $subscriptionId,
        ];
        $orders = array_values(array_filter(
            (array) ($user['orders'] ?? []),
            static fn (array $saved): bool => ($saved['id'] ?? '') !== $orderId
        ));
        $orders[] = $order;
        create_subscription_record($subscription);
        update_current_user(['orders' => $orders]);
        if (is_array($coupon)) {
            $all = coupons();
            foreach ($all as &$saved) {
                if (strtoupper((string) ($saved['code'] ?? '')) === strtoupper((string) $coupon['code'])) {
                    $saved['used'] = (int) ($saved['used'] ?? 0) + 1;
                }
            }
            unset($saved);
            save_coupons($all);
        }
        unset($_SESSION['subscription_coupon'][$slug], $_SESSION['subscription_gateway'][$slug]);
        header('Location: orders.php?paid=' . rawurlencode($orderId));
        exit;
    }
}

$gateway = $_SESSION['subscription_gateway'][$slug] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Subscription checkout | <?= e(APP_BRAND_NAME) ?></title>
<style>
body.checkout-page{margin:0;background:#efece2;color:#1c1a15;font:16px Arial}
.box{box-sizing:border-box;width:min(640px,calc(100% - 32px));margin:36px auto;background:#f8f6ee;border:1px solid #cfc7ac;padding:30px}
h1{font:600 2.3rem Georgia;color:#16233f}
.muted{color:#625e50}
.line{display:flex;justify-content:space-between;border-bottom:1px solid #cfc7ac;padding:11px 0}
.price{font:700 2.5rem Georgia;color:#9c3b2e;margin:20px 0}
.error{background:#f7ded8;color:#70251c;padding:12px;margin:16px 0}
.coupon{display:flex;gap:8px;margin:20px 0}
.coupon input{flex:1;padding:11px;border:1px solid #cfc7ac}
.btn{border:0;background:#9c3b2e;color:white;padding:13px 18px;font-weight:700;cursor:pointer}
.notice{background:#e0f0e6;color:#174b32;padding:13px;margin:16px 0}
.back{display:block;color:#9c3b2e;margin-top:20px}
</style>
</head>
<body class="checkout-page">
<main class="box">
<h1><?= e((string) $plan['name']) ?></h1>
<p class="muted">Every eligible banking exam and practice course is included.</p>
<div class="line"><span>Subscription price</span><strong>₹<?= number_format(((int) $plan['price_paise']) / 100, 2) ?></strong></div>
<?php if (is_array($coupon)): ?>
<div class="line"><span>Coupon <?= e((string) $coupon['code']) ?></span><strong>-₹<?= number_format($discount / 100, 2) ?></strong></div>
<?php endif; ?>
<div class="price">₹<?= number_format($final / 100, 2) ?></div>
<?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="coupon">
<input type="hidden" name="plan" value="<?= e($slug) ?>">
<input type="hidden" name="action" value="apply_coupon">
<input name="coupon" placeholder="Coupon code" required>
<button class="btn" type="submit">Apply coupon</button>
</form>
<?php if (is_array($gateway)): ?>
<div class="notice">Complete payment through Razorpay to activate your subscription.</div>
<form method="post" id="razorpay-form">
<input type="hidden" name="plan" value="<?= e($slug) ?>">
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
        amount: <?= (int) $final ?>,
        currency: 'INR',
        name: <?= json_encode(APP_BRAND_NAME) ?>,
        description: <?= json_encode((string) $plan['name']) ?>,
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
<input type="hidden" name="plan" value="<?= e($slug) ?>">
<input type="hidden" name="action" value="create_gateway_order">
<button class="btn" type="submit">Continue to Razorpay</button>
</form>
<?php else: ?>
<div class="notice">Live checkout needs a Razorpay Key ID starting with <code>rzp_live_</code> and its matching Key Secret configured on the server.</div>
<?php endif; ?>
<a class="back" href="subscription.php">Back to plans</a>
</main>
</body>
</html>
