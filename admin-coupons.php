<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_admin();
$allCoupons = coupons();
$selectedCode = strtoupper(trim((string) ($_GET['code'] ?? $_POST['original_code'] ?? '')));
$selected = null;
foreach ($allCoupons as $coupon) if (strtoupper((string) ($coupon['code'] ?? '')) === $selectedCode) { $selected = $coupon; break; }
$error = '';
$notice = (string) ($_GET['notice'] ?? '');
$products = ['all' => 'All government exam series'];
foreach (published_banking_catalog() as $slug => $series) $products[$slug] = $series['title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'delete_coupon') {
            $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
            save_coupons(array_values(array_filter($allCoupons, static fn(array $coupon): bool => strtoupper((string) ($coupon['code'] ?? '')) !== $code)));
            header('Location: admin-coupons.php?notice=Coupon+deleted');
            exit;
        }
        if ($action === 'save_coupon') {
            $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
            $originalCode = strtoupper(trim((string) ($_POST['original_code'] ?? '')));
            $type = ($_POST['type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
            $value = max(0, (int) ($_POST['value'] ?? 0));
            $product = trim((string) ($_POST['product'] ?? 'all'));
            if ($code === '' || !preg_match('/^[A-Z0-9_-]{3,64}$/', $code)) throw new RuntimeException('Use 3-64 letters, numbers, underscores or hyphens for the coupon code.');
            if ($value < 1 || ($type === 'percent' && $value > 100)) throw new RuntimeException('Enter a valid discount value.');
            if (!isset($products[$product])) throw new RuntimeException('Choose an active course for this coupon.');
            $coupon = ['code' => $code, 'type' => $type, 'value' => $value, 'product' => $product, 'active' => isset($_POST['active']), 'expires_at' => trim((string) ($_POST['expires_at'] ?? '')), 'max_uses' => ($_POST['max_uses'] ?? '') === '' ? null : max(1, (int) $_POST['max_uses']), 'used' => 0, 'emails' => array_values(array_filter(array_map(static fn(string $email): string => strtolower(trim($email)), preg_split('/[,\r\n]+/', (string) ($_POST['emails'] ?? '')) ?: [])))];
            $updated = false;
            foreach ($allCoupons as &$saved) {
                if (strtoupper((string) ($saved['code'] ?? '')) === ($originalCode !== '' ? $originalCode : $code)) { $coupon['used'] = (int) ($saved['used'] ?? 0); $saved = $coupon; $updated = true; break; }
            }
            unset($saved);
            if (!$updated) $allCoupons[] = $coupon;
            save_coupons($allCoupons);
            header('Location: admin-coupons.php?code=' . rawurlencode($code) . '&notice=Coupon+saved');
            exit;
        }
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}

$selected = $selected ?? ['code' => '', 'type' => 'percent', 'value' => 10, 'product' => 'all', 'active' => true, 'expires_at' => '', 'max_uses' => '', 'emails' => []];
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Coupons | RankSetu Admin</title><style>:root{--navy:#16233f;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24;--red:#70251c}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font:15px Arial}header{background:var(--navy);border-bottom:3px solid var(--gold);padding:17px 5%;display:flex;justify-content:space-between;gap:18px}.brand{font:700 1.45rem Georgia;color:#fff;text-decoration:none}.nav{display:flex;gap:18px;flex-wrap:wrap}.nav a{color:#f5ead2;text-decoration:none;font-size:.88rem}.wrap{width:min(1120px,calc(100% - 32px));margin:auto;padding:36px 0}.layout{display:grid;grid-template-columns:1fr 360px;gap:18px}.panel{background:var(--card);border:1px solid var(--line);padding:22px}.panel h1,.panel h2{font-family:Georgia;color:var(--navy);margin-top:0}.coupon-row{display:grid;grid-template-columns:1fr auto auto auto;gap:12px;align-items:center;border-bottom:1px solid var(--line);padding:13px 0}.coupon-row:last-child{border-bottom:0}.code{font-weight:700;color:var(--navy)}.muted{color:var(--muted);font-size:.82rem}.pill{font-size:.7rem;padding:4px 7px;background:#e0f0e6;color:#286447;font-weight:700}.pill.off{background:#f0e6df;color:var(--red)}label{display:block;font-size:.76rem;font-weight:700;margin:13px 0 5px}input,select,textarea{width:100%;padding:10px;border:1px solid var(--line);background:#fffdf7;font:inherit}textarea{min-height:70px}.check{display:flex;gap:8px;align-items:center}.check input{width:auto}.btn{display:inline-block;background:var(--maroon);color:#fff;border:0;padding:10px 13px;font-weight:700;cursor:pointer;text-decoration:none;font-size:.8rem}.danger{background:var(--red)}.outline{background:transparent;color:var(--maroon);border:1px solid var(--maroon)}.notice,.error{padding:12px;margin:0 0 16px}.notice{background:#e0f0e6;color:#174b32}.error{background:#f7ded8;color:var(--red)}.actions{display:flex;gap:7px;flex-wrap:wrap}@media(max-width:800px){.layout{grid-template-columns:1fr}.coupon-row{grid-template-columns:1fr 1fr}.coupon-row .actions{grid-column:1/-1}}
</style></head><body><header><a class="brand" href="admin.php">RankSetu Admin</a><nav class="nav"><a href="admin.php">Control centre</a><a href="banking-test-series.php">View site</a><a href="logout.php">Log out</a></nav></header><main class="wrap"><div class="layout"><section class="panel"><h1>Coupons</h1><p class="muted">Coupons are redeemed only during checkout. Values are stored in the active database backend.</p><?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?><?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?><?php if ($allCoupons === []): ?><p class="muted">No coupons created yet.</p><?php else: ?><?php foreach ($allCoupons as $coupon): ?><div class="coupon-row"><div><div class="code"><?= e((string) $coupon['code']) ?></div><div class="muted"><?= e((string) ($coupon['type'] ?? 'percent')) ?> <?= (int) ($coupon['value'] ?? 0) ?> &bull; used <?= (int) ($coupon['used'] ?? 0) ?><?= !empty($coupon['max_uses']) ? ' / ' . (int) $coupon['max_uses'] : '' ?></div></div><span class="pill <?= empty($coupon['active']) ? 'off' : '' ?>"><?= empty($coupon['active']) ? 'Inactive' : 'Active' ?></span><span class="muted"><?= e((string) ($products[$coupon['product'] ?? 'all'] ?? 'All banking series')) ?></span><div class="actions"><a class="btn outline" href="admin-coupons.php?code=<?= e((string) $coupon['code']) ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this coupon?')"><input type="hidden" name="action" value="delete_coupon"><input type="hidden" name="code" value="<?= e((string) $coupon['code']) ?>"><button class="btn danger" type="submit">Delete</button></form></div></div><?php endforeach; ?><?php endif; ?></section><aside class="panel"><h2><?= $selected['code'] !== '' ? 'Edit coupon' : 'Create coupon' ?></h2><form method="post"><input type="hidden" name="action" value="save_coupon"><input type="hidden" name="original_code" value="<?= e((string) $selected['code']) ?>"><label for="code">Code</label><input id="code" name="code" value="<?= e((string) $selected['code']) ?>" placeholder="BANK10" required><label for="type">Discount type</label><select id="type" name="type"><option value="percent" <?= ($selected['type'] ?? '') === 'percent' ? 'selected' : '' ?>>Percentage</option><option value="fixed" <?= ($selected['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed rupees</option></select><label for="value">Discount value</label><input id="value" name="value" type="number" min="1" value="<?= (int) ($selected['value'] ?? 10) ?>" required><label for="product">Applies to</label><select id="product" name="product"><?php foreach ($products as $productSlug => $productTitle): ?><option value="<?= e($productSlug) ?>" <?= ($selected['product'] ?? 'all') === $productSlug ? 'selected' : '' ?>><?= e($productTitle) ?></option><?php endforeach; ?></select><label for="expires_at">Expires at</label><input id="expires_at" name="expires_at" type="datetime-local" value="<?= e(str_replace(' ', 'T', substr((string) ($selected['expires_at'] ?? ''), 0, 16))) ?>"><label for="max_uses">Maximum uses</label><input id="max_uses" name="max_uses" type="number" min="1" value="<?= e((string) ($selected['max_uses'] ?? '')) ?>" placeholder="Unlimited"><label for="emails">Allowed emails (optional)</label><textarea id="emails" name="emails" placeholder="one@email.com, another@email.com"><?= e(implode(', ', (array) ($selected['emails'] ?? []))) ?></textarea><label class="check"><input type="checkbox" name="active" <?= !empty($selected['active']) ? 'checked' : '' ?>> Active coupon</label><button class="btn" type="submit">Save coupon</button></form></aside></div></main></body></html>
