<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$admin = require_admin();
$records = series_records();
$storedBySlug = [];
foreach ($records as $record) if (!empty($record['slug'])) $storedBySlug[$record['slug']] = $record;
$series = [];
foreach (banking_catalog() as $slug => $definition) {
    $series[$slug] = $storedBySlug[$slug] ?? [
        'id' => 'builtin-' . substr(hash('sha256', $slug), 0, 16),
        'slug' => $slug,
        'title' => $definition['title'],
        'stage' => $definition['stage'],
        'description' => $definition['description'],
        'group' => $definition['group'],
        'price_paise' => 25000,
        'active' => true,
        'created_at' => date('c'),
    ];
}
foreach ($records as $record) if (!isset($series[$record['slug'] ?? '']) && !empty($record['slug'])) $series[$record['slug']] = $record;
$requestedSlug = (string) ($_GET['series'] ?? $_POST['series'] ?? '');
$showArchived = (string) ($_GET['view'] ?? '') === 'archived'
    || ($requestedSlug !== '' && isset($series[$requestedSlug]) && empty($series[$requestedSlug]['active']));
$visibleSeries = array_filter($series, static fn(array $item): bool => !empty($item['active']) !== $showArchived);

$slug = (string) ($_GET['series'] ?? $_POST['series'] ?? array_key_first($visibleSeries));
if (!isset($visibleSeries[$slug])) $slug = (string) array_key_first($visibleSeries);
$notice = (string) ($_GET['notice'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'create_series') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $newSlug = slugify((string) ($_POST['slug'] ?? $title));
            $stage = trim((string) ($_POST['stage'] ?? 'Practice'));
            $description = trim((string) ($_POST['description'] ?? ''));
            $parentGroup = trim((string) ($_POST['parent_group'] ?? 'Other exams'));
            $pricePaise = price_to_paise((string) ($_POST['price'] ?? '250'));
            if ($title === '' || strlen($title) > 255 || $newSlug === '' || $stage === '' || strlen($stage) > 100 || $parentGroup === '' || strlen($parentGroup) > 255 || $description === '' || isset($storedBySlug[$newSlug])) throw new RuntimeException('Use unique series details within the allowed lengths.');
            $imagePath = save_series_image_upload((array) ($_FILES['image'] ?? []), $newSlug);
            $records[] = ['id' => 'series-' . bin2hex(random_bytes(8)), 'slug' => $newSlug, 'title' => $title, 'stage' => $stage, 'description' => $description, 'group' => $parentGroup, 'price_paise' => $pricePaise, 'image_path' => $imagePath, 'active' => isset($_POST['publish']), 'created_at' => date('c')];
            try {
                save_series_records($records);
            } catch (Throwable $exception) {
                delete_series_image($imagePath);
                throw $exception;
            }
            if (db_enabled()) save_test_record($newSlug, 'test-01', ['title' => $title . ' Starter Test', 'duration_minutes' => 10, 'questions' => []]);
            else { $folder = __DIR__ . '/tests/' . $newSlug; if (!is_dir($folder)) mkdir($folder, 0700, true); file_put_contents($folder . '/test-01.json', json_encode(['title' => $title . ' Starter Test', 'duration_minutes' => 10, 'questions' => []], JSON_PRETTY_PRINT), LOCK_EX); }
            header('Location: admin.php?series=' . rawurlencode($newSlug) . '&notice=' . rawurlencode(isset($_POST['publish']) ? 'Series created and published' : 'Series created as draft'));
            exit;
        }
        if ($action === 'update_series') {
            if (!isset($series[$slug])) throw new RuntimeException('Select an existing series before saving changes.');
            $updated = $series[$slug];
            $updated['title'] = trim((string) ($_POST['title'] ?? $updated['title']));
            $updated['stage'] = trim((string) ($_POST['stage'] ?? $updated['stage']));
            $updated['description'] = trim((string) ($_POST['description'] ?? $updated['description']));
            $updated['group'] = trim((string) ($_POST['parent_group'] ?? $updated['group'] ?? 'Other exams'));
            $updated['price_paise'] = price_to_paise((string) ($_POST['price'] ?? number_format(((int) ($updated['price_paise'] ?? 25000)) / 100, 2, '.', '')));
            $previousImagePath = isset($updated['image_path']) && is_string($updated['image_path']) ? $updated['image_path'] : null;
            $updated['image_path'] = save_series_image_upload((array) ($_FILES['image'] ?? []), $slug, $previousImagePath);
            $updated['active'] = isset($_POST['active']);
            $found = false;
            foreach ($records as &$record) {
                if (($record['slug'] ?? '') === $slug) { $record = array_merge($record, $updated); $found = true; break; }
            }
            unset($record);
            if (!$found) $records[] = $updated;
            try {
                save_series_records($records);
            } catch (Throwable $exception) {
                if ($updated['image_path'] !== $previousImagePath) delete_series_image($updated['image_path']);
                throw $exception;
            }
            if ($updated['image_path'] !== $previousImagePath) delete_series_image($previousImagePath);
            header('Location: admin.php?series=' . rawurlencode($slug) . '&notice=Series+updated');
            exit;
        }
        if ($action === 'delete_series') {
            if (!isset($series[$slug])) throw new RuntimeException('Select an existing series before deleting it.');
            $records = array_values(array_filter($records, static fn(array $record): bool => ($record['slug'] ?? '') !== $slug));
            if (isset($series[$slug])) {
                $series[$slug]['active'] = false;
                $records[] = $series[$slug];
            }
            save_series_records($records);
            delete_series_content($slug);
            header('Location: admin.php?notice=Series+deleted+and+hidden');
            exit;
        }
        if ($action === 'toggle_user') {
            $userId = (string) ($_POST['user_id'] ?? '');
            if ($userId === (string) ($admin['id'] ?? '')) throw new RuntimeException('You cannot deactivate your own administrator account.');
            set_user_active($userId, (string) ($_POST['active'] ?? '0') !== '1');
            header('Location: admin.php#progress');
            exit;
        }
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}

$selected = $series[$slug] ?? ['title' => '', 'stage' => 'Online Exam', 'description' => '', 'active' => false];
$users = users();
$totalAttempts = 0;
$totalOrders = 0;
$totalScore = 0;
$activeUsers = 0;
$userProgress = [];
foreach ($users as $record) {
    if (!empty($record['active'])) $activeUsers++;
    $attempts = (array) ($record['attempts'] ?? []);
    $score = 0;
    foreach ($attempts as $attempt) $score += (int) ($attempt['percentage'] ?? 0);
    $count = count($attempts);
    $totalAttempts += $count;
    $totalScore += $score;
    $totalOrders += count((array) ($record['orders'] ?? []));
    $userProgress[] = ['id' => $record['id'] ?? '', 'name' => $record['name'] ?? 'Student', 'email' => $record['email'] ?? '', 'active' => !empty($record['active']), 'enrolled' => count((array) ($record['enrolled'] ?? [])), 'attempts' => $count, 'average' => $count ? (int) round($score / $count) : 0, 'last' => $attempts === [] ? null : end($attempts)];
}
usort($userProgress, static fn(array $a, array $b): int => $b['attempts'] <=> $a['attempts']);
$average = $totalAttempts ? (int) round($totalScore / $totalAttempts) : 0;
?><!doctype html>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach((form) => {
        const price = form.querySelector('input[name="price"]');
        const action = form.querySelector('input[name="action"]');
        if (!price || !action || !['create_series', 'update_series'].includes(action.value)) return;
        const priceLabel = price.closest('label');
        if (!priceLabel) return;
        const accessLabel = document.createElement('label');
        accessLabel.append(document.createTextNode('Enrollment type'));
        const accessType = document.createElement('select');
        accessType.name = 'access_type';
        accessType.add(new Option('Paid', 'paid'));
        accessType.add(new Option('Free', 'free'));
        accessLabel.append(accessType);
        priceLabel.before(accessLabel);
        let paidPrice = Number(price.value) > 0 ? price.value : '250';
        accessType.value = Number(price.value) === 0 ? 'free' : 'paid';
        const syncAccessType = () => {
            if (accessType.value === 'free') {
                if (Number(price.value) > 0) paidPrice = price.value;
                price.value = '0';
                priceLabel.hidden = true;
            } else {
                priceLabel.hidden = false;
                if (Number(price.value) === 0) price.value = paidPrice;
            }
        };
        accessType.addEventListener('change', syncAccessType);
        syncAccessType();
    });
    const tabs = [...document.querySelectorAll('.tabs .tab')];
    const catalog = document.getElementById('catalog');
    const progress = document.getElementById('progress');
    if (!tabs.length || !catalog || !progress) return;
    const activate = (view) => {
        const showProgress = view === 'progress';
        catalog.hidden = showProgress;
        progress.hidden = !showProgress;
        tabs.forEach((tab) => tab.classList.toggle('active', tab.getAttribute('href') === `#${view}`));
        history.replaceState(null, '', `#${view}`);
        window.scrollTo({top: 0, behavior: 'smooth'});
    };
    tabs.forEach((tab) => tab.addEventListener('click', (event) => {
        event.preventDefault();
        activate((tab.getAttribute('href') || '#catalog').slice(1));
    }));
    activate(location.hash === '#progress' ? 'progress' : 'catalog');
});
</script>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin workspace | RankSetu</title>
<style>
:root{--navy:#16233f;--navy2:#243a64;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24;--green:#286447;--red:#70251c}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font:15px Arial,sans-serif}header{background:var(--navy);border-bottom:3px solid var(--gold);padding:16px 5%;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{font:700 1.45rem Georgia,serif;color:#fff;text-decoration:none}.nav{display:flex;gap:18px;align-items:center;flex-wrap:wrap}.nav a{color:#f5ead2;text-decoration:none;font-size:.88rem}.nav a:hover{text-decoration:underline}.wrap{width:min(1240px,calc(100% - 40px));margin:auto;padding:36px 0 70px}h1,h2,h3{font-family:Georgia,serif;color:var(--navy)}h1{font-size:clamp(2rem,4vw,3.1rem);margin:0 0 7px}h2{margin:0 0 14px}.muted{color:var(--muted)}.intro{display:flex;justify-content:space-between;align-items:end;gap:24px;margin-bottom:26px}.tabs{display:flex;gap:8px;border-bottom:1px solid var(--line);margin-bottom:24px}.tab{padding:12px 16px;color:var(--muted);font-weight:700;text-decoration:none;border-bottom:3px solid transparent}.tab:hover,.tab.active{color:var(--maroon);border-color:var(--maroon)}.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:28px}.stat,.panel,.series-card{background:var(--card);border:1px solid var(--line)}.stat{padding:17px}.stat strong{display:block;color:var(--maroon);font:700 1.7rem Georgia,serif}.stat span{font-size:.78rem;color:var(--muted)}.layout{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(280px,.65fr);gap:18px}.panel{padding:22px}.series-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.series-card{padding:16px;display:flex;flex-direction:column;min-height:205px}.series-card h3{font-size:1.08rem;margin:7px 0}.series-card p{font-size:.82rem;color:var(--muted);line-height:1.45;flex:1}.badge{font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:var(--maroon)}.badge.off{color:var(--muted)}.actions{display:flex;gap:7px;flex-wrap:wrap;margin-top:10px}.btn{display:inline-block;background:var(--maroon);color:#fff;border:0;padding:9px 11px;font-size:.78rem;font-weight:700;text-decoration:none;cursor:pointer}.btn:hover{background:#832f24}.btn.outline{background:transparent;color:var(--maroon);border:1px solid var(--maroon)}.btn.danger{background:var(--red)}label{display:block;font-size:.76rem;font-weight:700;margin:13px 0 5px}input,textarea{width:100%;padding:10px;border:1px solid var(--line);background:#fffdf7;font:inherit}textarea{min-height:90px;resize:vertical}.check{display:flex;gap:8px;align-items:center;font-weight:700}.check input{width:auto}.notice,.error{padding:12px;margin:14px 0}.notice{background:#e0f0e6;color:#174b32}.error{background:#f7ded8;color:var(--red)}.progress-list{display:grid;gap:10px}.user-row{display:grid;grid-template-columns:1.2fr .8fr .5fr .5fr;gap:12px;align-items:center;border-bottom:1px solid var(--line);padding:12px 0;font-size:.84rem}.user-row:last-child{border-bottom:0}.user-name{font-weight:700;color:var(--navy)}.user-email{display:block;color:var(--muted);font-size:.73rem;margin-top:3px}.pill{display:inline-block;background:#e0f0e6;color:var(--green);padding:4px 7px;font-size:.7rem;font-weight:700}.pill.off{background:#f0e6df;color:var(--red)}.empty{padding:25px 0;color:var(--muted)}@media(max-width:950px){.stats{grid-template-columns:repeat(3,1fr)}.layout{grid-template-columns:1fr}.series-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:600px){.wrap{width:min(100% - 26px,1240px);padding-top:25px}.intro{display:block}.stats{grid-template-columns:repeat(2,1fr)}.series-grid{grid-template-columns:1fr}.user-row{grid-template-columns:1fr 1fr}.user-row .metric{justify-self:end}}
</style>
<style>
.series-card .actions{display:grid;grid-template-columns:1fr;gap:8px}.series-card .actions .btn,.series-card>form .btn{width:100%;text-align:center}.series-card>form{margin-top:10px!important}.layout>#editor form .btn{width:100%;max-width:240px;text-align:center}.layout>#editor form+form{margin-top:10px}.panel{min-width:0}.btn{max-width:100%;white-space:normal;text-align:center}
@media(max-width:900px){.layout{grid-template-columns:1fr}.series-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.stats{grid-template-columns:repeat(3,1fr)}.intro{align-items:flex-start;flex-direction:column;gap:8px}}
@media(max-width:600px){.wrap{width:min(100% - 28px,1240px);padding-top:24px}.series-grid,.stats{grid-template-columns:1fr}.panel{padding:16px}.tabs{overflow-x:auto}.tab{white-space:nowrap}.nav{gap:10px}.series-card{min-height:0}.series-card .btn{padding:10px 12px}}
#progress[hidden]{display:none}.user-row{display:grid;grid-template-columns:minmax(180px,2fr) .8fr .8fr 1fr 1.2fr auto;align-items:center;gap:14px;padding:14px 0;border-top:1px solid var(--line)}.user-row form{margin:0}.user-email{display:block;color:var(--muted);font-size:.78rem;margin-top:4px}.pill{display:inline-block;padding:4px 8px;background:#e0f0e6;color:var(--green);font-size:.75rem;font-weight:700}.pill.off{background:#eee1df;color:var(--red)}
@media(max-width:700px){.user-row{grid-template-columns:1fr 1fr;gap:10px}.user-row>form{grid-column:1/-1}.user-row>form .btn{width:100%}}
</style>
</head>
<body><header><a class="brand" href="admin.php">RankSetu Admin</a><nav class="nav"><a href="banking-test-series.php">View site</a><a href="blog-admin.php">Blog manager</a><a href="admin-business.php">Business analytics</a><a href="admin-course-control.php">Users &amp; course access</a><a href="subscription.php">Subscriptions</a><a href="admin-subscriptions.php">Manage plans</a><a href="admin-coupons.php">Coupons</a><a href="logout.php">Log out</a></nav></header>
<main class="wrap"><div class="intro"><div><h1>Admin workspace</h1><p class="muted">Manage banking series, questions and learner progress from one place.</p></div><div class="muted">Signed in as <?= e((string) $admin['name']) ?></div></div>
<nav class="tabs"><a class="tab active" href="#catalog">Series &amp; questions</a><a class="tab" href="#progress">User progress</a></nav>
<p><a class="btn outline" href="admin.php<?= $showArchived ? '' : '?view=archived' ?>#catalog"><?= $showArchived ? 'View published series' : 'View archived series' ?></a></p>
<?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?><?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
<section class="panel" id="create-series"><h2>Create a test series</h2><p class="muted">Create multiple series under the same parent category. Each starts with one starter set.</p><form method="post" enctype="multipart/form-data" class="toolbar"><input type="hidden" name="action" value="create_series"><label>Parent category<input name="parent_group" value="Other exams" placeholder="Example: Banking exams" required></label><label>Series title<input name="title" placeholder="Example: NABARD Grade A" required></label><label>URL slug<input name="slug" placeholder="nabard-grade-a" pattern="[a-z0-9-]+" title="Use lowercase letters, numbers and hyphens only"></label><label>Exam stage<input name="stage" value="Online Exam" required></label><label>Price (INR)<input name="price" type="number" min="0" max="1000000" step="0.01" value="250" required></label><label>Description<input name="description" placeholder="Short description for students" required></label><label>Series image (JPG, PNG or WebP, max 3 MB)<input name="image" type="file" accept="image/jpeg,image/png,image/webp"></label><label class="check"><input type="checkbox" name="publish" checked> Publish on main page</label><button class="btn" type="submit">Create series</button></form></section>
<section class="stats"><div class="stat"><strong><?= count($series) ?></strong><span>all series, incl. archived</span></div><div class="stat"><strong><?= count(array_filter($series, static fn(array $item): bool => !empty($item['active']))) ?></strong><span>published series</span></div><div class="stat"><strong><?= count($users) ?></strong><span>registered users</span></div><div class="stat"><strong><?= $activeUsers ?></strong><span>active users</span></div><div class="stat"><strong><?= $average ?>%</strong><span>average score</span></div></section>
<section id="catalog" class="layout"><div class="panel"><h2>Banking series</h2><p class="muted">Select a series to edit its details or manage its tests and questions.</p><div class="series-grid"><?php foreach ($visibleSeries as $itemSlug => $item): ?><article class="series-card"><div class="badge <?= empty($item['active']) ? 'off' : '' ?>"><?= empty($item['active']) ? 'Archived' : 'Published' ?></div><h3><?= e($item['title']) ?></h3><p><?= e($item['description'] ?? '') ?></p><div class="actions"><a class="btn outline" href="admin.php?series=<?= e($itemSlug) ?>#editor">Edit series</a><a class="btn" href="admin-tests.php?series=<?= e($itemSlug) ?>">Tests &amp; questions</a></div><form method="post" style="margin-top:10px" onsubmit="return confirm('Delete this complete series and hide all of its tests?')"><input type="hidden" name="action" value="delete_series"><input type="hidden" name="series" value="<?= e($itemSlug) ?>"><button class="btn danger" type="submit">Delete series</button></form></article><?php endforeach; ?></div></div>
<aside id="editor" class="panel"><h2>Edit series</h2><p class="muted">Changes apply to the selected series in the active storage backend.</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="update_series"><input type="hidden" name="series" value="<?= e($slug) ?>"><label for="parent_group">Parent category</label><input id="parent_group" name="parent_group" value="<?= e((string) ($selected['group'] ?? 'Other exams')) ?>" required><label for="title">Series title</label><input id="title" name="title" value="<?= e((string) $selected['title']) ?>" required><label for="stage">Exam stage</label><input id="stage" name="stage" value="<?= e((string) $selected['stage']) ?>" required><label for="price">Price (INR)</label><input id="price" name="price" type="number" min="0" max="1000000" step="0.01" value="<?= e(number_format(((int) ($selected['price_paise'] ?? 25000)) / 100, 2, '.', '')) ?>" required><label for="description">Description</label><textarea id="description" name="description" required><?= e((string) ($selected['description'] ?? '')) ?></textarea><label>Replace series image (JPG, PNG or WebP, max 3 MB)<input name="image" type="file" accept="image/jpeg,image/png,image/webp"></label><label class="check"><input type="checkbox" name="active" <?= !empty($selected['active']) ? 'checked' : '' ?>> Published in catalog</label><button class="btn" type="submit">Save series</button></form><form method="post" onsubmit="return confirm('Delete this complete series and hide all of its tests?')"><input type="hidden" name="action" value="delete_series"><input type="hidden" name="series" value="<?= e($slug) ?>"><button class="btn danger" type="submit">Delete complete series</button></form></aside></section>
<section id="progress" class="panel" style="margin-top:18px"><h2>User progress</h2><p class="muted">Learner activity, enrollment, attempts, averages and latest activity from stored records.</p><div class="progress-list"><?php if ($userProgress === []): ?><div class="empty">No registered users yet.</div><?php else: ?><div class="user-row" style="font-weight:700;color:var(--muted)"><span>User</span><span>Status</span><span>Enrolled</span><span>Attempts / score</span><span>Last activity</span><span>Access</span></div><?php foreach ($userProgress as $progress): ?><div class="user-row"><div><span class="user-name"><?= e((string) $progress['name']) ?></span><span class="user-email"><?= e((string) $progress['email']) ?></span></div><div><span class="pill <?= $progress['active'] ? '' : 'off' ?>"><?= $progress['active'] ? 'Active' : 'Inactive' ?></span></div><div><?= (int) $progress['enrolled'] ?> series</div><div><?= (int) $progress['attempts'] ?> / <?= (int) $progress['average'] ?>%</div><div><?= $progress['last'] === null ? 'No attempts yet' : e((string) ($progress['last']['submitted_at'] ?? $progress['last']['created_at'] ?? 'Recent attempt')) ?></div><form method="post" onsubmit="return confirm('Change this user access?')"><input type="hidden" name="action" value="toggle_user"><input type="hidden" name="user_id" value="<?= e((string) ($progress['id'] ?? '')) ?>"><input type="hidden" name="active" value="<?= $progress['active'] ? '1' : '0' ?>"><button class="btn <?= $progress['active'] ? 'danger' : '' ?>" type="submit"><?= $progress['active'] ? 'Deactivate' : 'Activate' ?></button></form></div><?php endforeach; ?><?php endif; ?></div></section>
</main></body></html>
