<?php

declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_admin();
$from = trim((string) ($_GET['from'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));
$fromTime = $from !== '' && strtotime($from) !== false ? strtotime($from . ' 00:00:00') : null;
$toTime = $to !== '' && strtotime($to) !== false ? strtotime($to . ' 23:59:59') : null;
$users = users();
$paidStatuses = ['paid','captured','success','completed'];
$refundedStatuses = ['refunded','refund'];
$metrics = ['paid_orders'=>0,'gross_revenue'=>0,'discounts'=>0,'refunds'=>0,'subscription_sales'=>0,'subscription_revenue'=>0,'course_sales'=>0,'course_revenue'=>0,'new_users'=>0,'active_users'=>0,'premium_users'=>0,'active_enrollments'=>0,'attempts'=>0];
$courseSales = [];
$planSales = [];
foreach ($users as $user) {
    $created = strtotime((string) ($user['created_at'] ?? ''));
    if (($fromTime === null || ($created !== false && $created >= $fromTime)) && ($toTime === null || ($created !== false && $created <= $toTime))) $metrics['new_users']++;
    if (!empty($user['active'])) $metrics['active_users']++;
    foreach ((array) ($user['enrolled'] ?? []) as $slug) if (user_has_course_access($user, (string) $slug)) $metrics['active_enrollments']++;
    $metrics['attempts'] += count((array) ($user['attempts'] ?? []));
    foreach (user_subscriptions((string) ($user['id'] ?? '')) as $subscription) {
        $expires = strtotime((string) ($subscription['expires_at'] ?? ''));
        if (($subscription['status'] ?? '') === 'ACTIVE' && ($expires === false || $expires > time())) $metrics['premium_users']++;
    }
    foreach ((array) ($user['orders'] ?? []) as $order) {
        $date = strtotime((string) ($order['created_at'] ?? ''));
        if ($fromTime !== null && ($date === false || $date < $fromTime)) continue;
        if ($toTime !== null && ($date === false || $date > $toTime)) continue;
        $status = strtolower((string) ($order['status'] ?? ''));
        $amount = (int) round(((float) ($order['amount'] ?? 0)) * 100);
        $original = (int) round(((float) ($order['original_amount'] ?? $order['amount'] ?? 0)) * 100);
        $discount = (int) round(((float) ($order['discount'] ?? 0)) * 100);
        if (in_array($status, $refundedStatuses, true)) { $metrics['refunds'] += $amount; continue; }
        if (!in_array($status, $paidStatuses, true)) continue;
        $metrics['paid_orders']++;
        $metrics['gross_revenue'] += $amount;
        $metrics['discounts'] += max(0, $original - $amount, $discount);
        $product = (string) ($order['product'] ?? $order['plan'] ?? '');
        $isSubscription = !empty($order['subscription_id']) || stripos($product, 'premium') !== false || stripos($product, 'subscription') !== false;
        if ($isSubscription) { $metrics['subscription_sales']++; $metrics['subscription_revenue'] += $amount; $planSales[$product] = ($planSales[$product] ?? 0) + 1; }
        else { $metrics['course_sales']++; $metrics['course_revenue'] += $amount; $courseSales[$product] = ($courseSales[$product] ?? 0) + 1; }
    }
}
arsort($courseSales); arsort($planSales);
$netCollected = max(0, $metrics['gross_revenue'] - $metrics['refunds']);
$money = static fn(int $paise): string => '₹' . number_format($paise / 100, 2);
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Business analytics | Admin</title><style>:root{--navy:#16233f;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24;--green:#286447;--red:#70251c}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font:15px Arial,sans-serif}header{background:var(--navy);border-bottom:3px solid var(--gold);padding:16px 5%;display:flex;justify-content:space-between;gap:18px}.brand{font:700 1.45rem Georgia;color:#fff;text-decoration:none}.nav{display:flex;gap:16px;flex-wrap:wrap}.nav a{color:#f5ead2;text-decoration:none}.wrap{width:min(1280px,calc(100% - 32px));margin:auto;padding:34px 0 70px}h1,h2,h3{font-family:Georgia;color:var(--navy)}h1{font-size:clamp(2rem,4vw,3.2rem);margin:0 0 8px}.muted{color:var(--muted)}.intro{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:24px}.filters,.metrics,.grid{display:grid;gap:14px}.filters{grid-template-columns:1fr 1fr auto;background:var(--card);border:1px solid var(--line);padding:18px;margin-bottom:20px}.filters label{font-size:.78rem;font-weight:700;color:var(--muted)}input{display:block;width:100%;margin-top:5px;padding:10px;border:1px solid var(--line);background:#fffdf7;font:inherit}.btn{border:0;background:var(--maroon);color:#fff;padding:11px 15px;font-weight:700;cursor:pointer}.filters .btn{align-self:end}.metrics{grid-template-columns:repeat(5,1fr);margin-bottom:22px}.metric,.panel{background:var(--card);border:1px solid var(--line);padding:19px}.metric strong{display:block;color:var(--maroon);font:700 1.55rem Georgia,serif;margin-bottom:4px}.metric span{color:var(--muted);font-size:.76rem;line-height:1.35}.grid{grid-template-columns:repeat(3,1fr)}.panel h2{margin-top:0}.panel table{width:100%;border-collapse:collapse}.panel th,.panel td{text-align:left;padding:10px 6px;border-top:1px solid var(--line)}.panel th{font-size:.72rem;color:var(--muted);text-transform:uppercase}.notice{background:#fff3ed;border-left:4px solid var(--gold);padding:13px;margin-bottom:20px;color:var(--muted)}.empty{color:var(--muted);padding:20px 0}@media(max-width:1000px){.metrics{grid-template-columns:repeat(3,1fr)}.grid{grid-template-columns:1fr 1fr}}@media(max-width:650px){.intro{display:block}.filters{grid-template-columns:1fr}.metrics,.grid{grid-template-columns:1fr 1fr}.filters .btn{width:100%}.panel{overflow-x:auto}.panel table{min-width:420px}}
</style></head><body><header><a class="brand" href="admin.php">RankSetu Admin</a><nav class="nav"><a href="admin.php">Control centre</a><a href="admin-course-control.php">Users &amp; course access</a><a href="admin-subscriptions.php">Subscriptions</a><a href="admin-coupons.php">Coupons</a><a href="logout.php">Log out</a></nav></header><main class="wrap"><div class="intro"><div><h1>Business analytics</h1><p class="muted">Real-time request-based business, sales and learner metrics from verified stored records.</p></div><div class="muted">Updated <?= e(date('d M Y, H:i:s')) ?></div></div><form class="filters" method="get"><label>From date<input type="date" name="from" value="<?= e($from) ?>"></label><label>To date<input type="date" name="to" value="<?= e($to) ?>"></label><button class="btn" type="submit">Apply date filter</button></form><div class="notice"><strong>Financial note:</strong> “Profit before expenses” is shown as collected revenue less refunds. True profit requires operating costs, taxes and gateway fees, which are not currently stored in this application.</div><section class="metrics"><div class="metric"><strong><?= $metrics['paid_orders'] ?></strong><span>Paid orders</span></div><div class="metric"><strong><?= $money($metrics['gross_revenue']) ?></strong><span>Gross collected revenue</span></div><div class="metric"><strong><?= $money($metrics['refunds']) ?></strong><span>Refunds recorded</span></div><div class="metric"><strong><?= $money($netCollected) ?></strong><span>Collected after refunds</span></div><div class="metric"><strong><?= $money($metrics['discounts']) ?></strong><span>Discount impact</span></div><div class="metric"><strong><?= $metrics['subscription_sales'] ?></strong><span>Premium sales</span></div><div class="metric"><strong><?= $money($metrics['subscription_revenue']) ?></strong><span>Premium revenue</span></div><div class="metric"><strong><?= $metrics['course_sales'] ?></strong><span>Course sales</span></div><div class="metric"><strong><?= $metrics['active_users'] ?></strong><span>Active users</span></div><div class="metric"><strong><?= $metrics['premium_users'] ?></strong><span>Active premium users</span></div></section><section class="grid"><article class="panel"><h2>Sales mix</h2><table><tr><th>Category</th><th>Sales</th><th>Revenue</th></tr><tr><td>Bank Premium</td><td><?= $metrics['subscription_sales'] ?></td><td><?= $money($metrics['subscription_revenue']) ?></td></tr><tr><td>Individual courses</td><td><?= $metrics['course_sales'] ?></td><td><?= $money($metrics['course_revenue']) ?></td></tr><tr><td>Total</td><td><?= $metrics['paid_orders'] ?></td><td><?= $money($metrics['gross_revenue']) ?></td></tr></table></article><article class="panel"><h2>Operations</h2><table><tr><th>Metric</th><th>Value</th></tr><tr><td>New users</td><td><?= $metrics['new_users'] ?></td></tr><tr><td>Active enrollments</td><td><?= $metrics['active_enrollments'] ?></td></tr><tr><td>Submitted attempts</td><td><?= $metrics['attempts'] ?></td></tr><tr><td>Profit before expenses</td><td><?= $money($netCollected) ?></td></tr></table></article><article class="panel"><h2>Top premium plans</h2><?php if($planSales===[]): ?><div class="empty">No paid premium sales in this period.</div><?php else: ?><table><?php foreach(array_slice($planSales,0,5,true) as $plan=>$count): ?><tr><td><?= e($plan) ?></td><td><?= (int)$count ?></td></tr><?php endforeach; ?></table><?php endif; ?></article></section><section class="panel" style="margin-top:14px"><h2>Top individual courses sold</h2><?php if($courseSales===[]): ?><div class="empty">No individual paid course sales in this period.</div><?php else: ?><table><tr><th>Course</th><th>Sales</th></tr><?php foreach(array_slice($courseSales,0,10,true) as $course=>$count): ?><tr><td><?= e($course) ?></td><td><?= (int)$count ?></td></tr><?php endforeach; ?></table><?php endif; ?></section></main></body></html>
