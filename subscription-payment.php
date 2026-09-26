<?php

declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$user = require_login();
$slug = (string) ($_GET['plan'] ?? $_POST['plan'] ?? '');
$plan = null;
foreach (subscription_plans(true) as $candidate) if (($candidate['slug'] ?? '') === $slug) { $plan = $candidate; break; }
if ($plan === null) { http_response_code(404); exit('Subscription plan not found.'); }
foreach (user_subscriptions((string) $user['id']) as $existing) if (($existing['status'] ?? '') === 'ACTIVE' && (empty($existing['expires_at']) || strtotime((string) $existing['expires_at']) > time())) { header('Location: dashboard.php'); exit; }

$error = '';
$coupon = $_SESSION['subscription_coupon'][$slug] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply_coupon') {
    $coupon = valid_coupon((string) ($_POST['coupon'] ?? ''), $user, 'all', (int) $plan['price_paise']);
    if ($coupon === null) $error = 'Invalid, expired, restricted or already used coupon.';
    else $_SESSION['subscription_coupon'][$slug] = $coupon;
}
$discount = is_array($coupon) ? (int) $coupon['discount_paise'] : 0;
$final = max(0, (int) $plan['price_paise'] - $discount);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_gateway_order') {
    if (payment_mode() === 'demo') {
        $orderId = 'SUB-' . strtoupper(bin2hex(random_bytes(6)));
        $_SESSION['subscription_gateway'][$slug] = ['order_id' => $orderId, 'amount' => $final, 'created_at' => time()];
    } elseif (razorpay_key_id() !== '' && razorpay_key_secret() !== '') {
        try {
            $gatewayOrder = create_razorpay_order($final, 'sub-' . $user['id'] . '-' . time());
            $_SESSION['subscription_gateway'][$slug] = ['order_id' => $gatewayOrder['id'], 'amount' => $final, 'created_at' => time()];
        } catch (Throwable $exception) { $error = $exception->getMessage(); }
    } else { $error = 'Payment gateway is not configured. Set Razorpay credentials before accepting payments.'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_payment') {
    $gateway = $_SESSION['subscription_gateway'][$slug] ?? null;
    $paymentId = trim((string) ($_POST['razorpay_payment_id'] ?? ''));
    $gatewayOrderId = trim((string) ($_POST['razorpay_order_id'] ?? ''));
    $signature = trim((string) ($_POST['razorpay_signature'] ?? ''));
    $verified = payment_mode() === 'demo'
        ? is_array($gateway) && hash_equals((string) ($gateway['order_id'] ?? ''), $gatewayOrderId)
        : is_array($gateway) && hash_equals((string) ($gateway['order_id'] ?? ''), $gatewayOrderId) && verify_razorpay_signature($gatewayOrderId, $paymentId, $signature);
    if (!$verified) $error = 'We could not verify this payment. No subscription was activated.';
    else {
        $start = new DateTimeImmutable('now');
        $interval = ($plan['duration_unit'] ?? 'day') === 'year' ? 'P' . (int) $plan['duration'] . 'Y' : (($plan['duration_unit'] ?? 'day') === 'month' ? 'P' . (int) $plan['duration'] . 'M' : 'P' . (int) $plan['duration'] . 'D');
        $expires = $start->add(new DateInterval($interval));
        $subscriptionId = 'subscription-' . bin2hex(random_bytes(10));
        $orderId = (string) $gateway['order_id'];
        $subscription = ['id'=>$subscriptionId,'user_id'=>$user['id'],'plan_id'=>$plan['id'],'plan_slug'=>$plan['slug'],'plan_name'=>$plan['name'],'status'=>'ACTIVE','start_at'=>$start->format('Y-m-d H:i:s'),'expires_at'=>$expires->format('Y-m-d H:i:s'),'amount_paise'=>$plan['price_paise'],'discount_paise'=>$discount,'tax_paise'=>0,'final_amount_paise'=>$final,'coupon_code'=>is_array($coupon)?$coupon['code']:null,'order_id'=>$orderId,'payment_id'=>$paymentId !== '' ? $paymentId : null,'created_at'=>date('Y-m-d H:i:s'),'covered_courses'=>[],'covered_groups'=>[],'all_access'=>true];
        $order = ['id'=>$orderId,'plan'=>$plan['name'],'product'=>$plan['name'],'amount'=>number_format($final/100,2,'.',''),'original_amount'=>number_format(((int)$plan['price_paise'])/100,2,'.',''),'coupon'=>is_array($coupon)?$coupon['code']:null,'discount'=>number_format($discount/100,2,'.',''),'currency'=>$plan['currency']??'INR','status'=>'paid','provider'=>payment_mode()==='demo'?'demo':'razorpay','provider_order_id'=>$orderId,'provider_payment_id'=>$paymentId,'created_at'=>$subscription['created_at'],'subscription_id'=>$subscriptionId];
        $orders = array_values(array_filter((array)($user['orders'] ?? []), static fn(array $saved): bool => ($saved['id'] ?? '') !== $orderId)); $orders[] = $order;
        create_subscription_record($subscription); update_current_user(['orders'=>$orders]);
        if (is_array($coupon)) { $all = coupons(); foreach ($all as &$saved) if (strtoupper((string)($saved['code']??'')) === strtoupper((string)$coupon['code'])) $saved['used'] = (int)($saved['used']??0) + 1; unset($saved); save_coupons($all); }
        unset($_SESSION['subscription_coupon'][$slug], $_SESSION['subscription_gateway'][$slug]); header('Location: orders.php?paid=' . rawurlencode($orderId)); exit;
    }
}
$gateway = $_SESSION['subscription_gateway'][$slug] ?? null;
if (payment_mode() === 'demo' && is_array($gateway)) {
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Demo payment | <?= e(APP_BRAND_NAME) ?></title><style>body{font:16px Arial;background:#efece2;color:#1c1a15;padding:40px}.box{max-width:560px;margin:auto;background:#f8f6ee;border:1px solid #cfc7ac;padding:30px}h1{font:600 2.2rem Georgia;color:#16233f}.btn{background:#9c3b2e;color:#fff;border:0;padding:13px 18px;font-weight:700;cursor:pointer}.back{display:block;color:#9c3b2e;margin-top:20px}</style></head><body><main class="box"><h1>Bank Premium</h1><p>Demo payment order created for ₹<?= number_format($final / 100, 2) ?>.</p><form method="post"><input type="hidden" name="plan" value="<?= e($slug) ?>"><input type="hidden" name="action" value="verify_payment"><input type="hidden" name="razorpay_order_id" value="<?= e((string)$gateway['order_id']) ?>"><button class="btn" type="submit">Complete demo payment</button></form><a class="back" href="subscription.php">Back to plans</a></main></body></html><?php
    exit;
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Bank Premium payment | <?= e(APP_BRAND_NAME) ?></title><style>body{margin:0;background:#efece2;color:#1c1a15;font:16px Arial}.box{width:min(600px,calc(100% - 32px));margin:50px auto;background:#f8f6ee;border:1px solid #cfc7ac;padding:30px}h1{font:600 2.3rem Georgia;color:#16233f}.muted{color:#625e50}.line{display:flex;justify-content:space-between;border-bottom:1px solid #cfc7ac;padding:11px 0}.price{font:700 2.5rem Georgia;color:#9c3b2e;margin:20px 0}.error{background:#f7ded8;color:#70251c;padding:12px;margin:16px 0}.coupon{display:flex;gap:8px;margin:20px 0}.coupon input{flex:1;padding:11px;border:1px solid #cfc7ac}.btn{border:0;background:#9c3b2e;color:white;padding:13px 18px;font-weight:700;cursor:pointer}.notice{background:#e0f0e6;color:#174b32;padding:13px;margin:16px 0}.back{display:block;color:#9c3b2e;margin-top:20px}</style></head><body><main class="box"><h1><?= e((string)$plan['name']) ?></h1><p class="muted">Every eligible banking exam and practice course is included.</p><div class="line"><span>Subscription price</span><strong>₹<?= number_format(((int)$plan['price_paise'])/100,2) ?></strong></div><?php if(is_array($coupon)): ?><div class="line"><span>Coupon <?= e((string)$coupon['code']) ?></span><strong>-₹<?= number_format($discount/100,2) ?></strong></div><?php endif; ?><div class="price">₹<?= number_format($final/100,2) ?></div><?php if($error!==''): ?><div class="error"><?= e($error) ?></div><?php endif; ?><form method="post" class="coupon"><input type="hidden" name="plan" value="<?= e($slug) ?>"><input type="hidden" name="action" value="apply_coupon"><input name="coupon" placeholder="Coupon code" required><button class="btn" type="submit">Apply coupon</button></form><?php if(is_array($gateway)): ?><div class="notice">Payment order created. Complete payment to activate your premium membership.</div><form method="post" id="verify-form"><input type="hidden" name="plan" value="<?= e($slug) ?>"><input type="hidden" name="action" value="verify_payment"><input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id"><input type="hidden" name="razorpay_order_id" id="razorpay_order_id"><input type="hidden" name="razorpay_signature" id="razorpay_signature"><button class="btn" type="button" id="pay-button">Pay ₹<?= number_format($final/100,2) ?></button></form><script src="https://checkout.razorpay.com/v1/checkout.js"></script><script>document.getElementById('pay-button').addEventListener('click',function(){const checkout=new Razorpay({key:<?= json_encode(razorpay_key_id()) ?>,amount:<?= (int)$final ?>,currency:'INR',name:<?= json_encode(APP_BRAND_NAME) ?>,description:'<?= e((string)$plan['name']) ?>',order_id:<?= json_encode((string)$gateway['order_id']) ?>,handler:function(response){document.getElementById('razorpay_payment_id').value=response.razorpay_payment_id;document.getElementById('razorpay_order_id').value=response.razorpay_order_id;document.getElementById('razorpay_signature').value=response.razorpay_signature;document.getElementById('verify-form').submit();}});checkout.open();});</script><?php else: ?><form method="post"><input type="hidden" name="plan" value="<?= e($slug) ?>"><input type="hidden" name="action" value="create_gateway_order"><button class="btn" type="submit"><?= payment_mode()==='demo' ? 'Start demo payment' : 'Create secure payment' ?></button></form><?php endif; ?><a class="back" href="subscription.php">Back to plans</a></main></body></html>
