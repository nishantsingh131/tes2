<?php

declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_admin();

$names = ['ssc-cgl'=>'SSC CGL', 'ssc-chsl'=>'SSC CHSL', 'rrb-ntpc'=>'RRB NTPC', 'rrb-group-d'=>'RRB Group D', 'ibps-po'=>'IBPS PO', 'sbi-clerk'=>'SBI Clerk', 'bpsc-prelims'=>'BPSC Prelims', 'state-psc-mains'=>'State PSC Mains', 'nda-cds'=>'NDA & CDS', 'ctet'=>'CTET'];
foreach (series_records() as $record) if (isset($record['slug'], $record['title'])) $names[$record['slug']] = $record['title'];
$slug = (string) ($_GET['series'] ?? $_POST['series'] ?? array_key_first($names));
if (!isset($names[$slug])) $slug = array_key_first($names);
$folder = __DIR__ . '/tests/' . $slug;
$files = glob($folder . '/test-*.json') ?: [];
$tests = [];
foreach ($files as $file) { $id = pathinfo($file, PATHINFO_FILENAME); $tests[$id] = json_decode((string) file_get_contents($file), true); }
uksort($tests, fn ($a, $b) => (int) substr($a, 5) <=> (int) substr($b, 5));
$selected = (string) ($_GET['test'] ?? $_POST['test'] ?? array_key_first($tests));
if (!isset($tests[$selected])) $selected = array_key_first($tests);
$selectedTest = $tests[$selected] ?? ['title'=>'', 'duration_minutes'=>10, 'questions'=>[]];
$notice = (string) ($_GET['notice'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'create_test') {
            $number = count($tests) + 1;
            $id = 'test-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            file_put_contents($folder . '/' . $id . '.json', json_encode(['title'=>$names[$slug] . ' Mock ' . str_pad((string) $number, 2, '0', STR_PAD_LEFT), 'duration_minutes'=>120, 'questions'=>[]], JSON_PRETTY_PRINT), LOCK_EX);
            header('Location: admin-tests.php?series=' . rawurlencode($slug) . '&test=' . $id . '&notice=New+test+created'); exit;
        }
        if ($action === 'update_test') {
            $selectedTest['title'] = trim((string) $_POST['title']);
            $selectedTest['duration_minutes'] = max(1, (int) $_POST['duration']);
            file_put_contents($folder . '/' . $selected . '.json', json_encode($selectedTest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
            header('Location: admin-tests.php?series=' . rawurlencode($slug) . '&test=' . $selected . '&notice=Test+updated'); exit;
        }
        if ($action === 'delete_test') {
            if (count($tests) <= 1) throw new RuntimeException('A series must keep at least one test.');
            @unlink($folder . '/' . $selected . '.json');
            header('Location: admin-tests.php?series=' . rawurlencode($slug) . '&notice=Test+deleted'); exit;
        }
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Test sequence | RankSetu Admin</title><style>body{margin:0;background:#efece2;font-family:Arial;color:#1c1a15}header{background:#16233f;border-bottom:3px solid #a97a24;padding:18px 5%;display:flex;justify-content:space-between}.brand{font:700 1.5rem Georgia;color:#fff;text-decoration:none}.nav a{color:#f5ead2;text-decoration:none;margin-left:18px}.wrap{width:min(1050px,calc(100% - 40px));margin:auto;padding:42px 0}h1,h2{font-family:Georgia;color:#16233f}h1{font-size:2.5rem}.muted{color:#625e50}.sequence{display:flex;gap:8px;flex-wrap:wrap;margin:24px 0}.test-link{padding:11px 14px;background:#f8f6ee;border:1px solid #cfc7ac;color:#16233f;text-decoration:none;font-weight:700}.test-link.active{background:#9c3b2e;color:#fff}.panel{background:#f8f6ee;border:1px solid #cfc7ac;padding:26px;margin-top:18px}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.stat{padding:15px;border:1px solid #cfc7ac;background:#fffdf7}.stat strong{display:block;color:#9c3b2e;font-size:1.5rem}label{display:block;font-size:.8rem;font-weight:700;margin:12px 0 5px}input{width:100%;padding:11px;border:1px solid #cfc7ac;background:#fffdf7;font:inherit}.btn{display:inline-block;background:#9c3b2e;color:#fff;border:0;padding:11px 16px;font-weight:700;text-decoration:none;cursor:pointer;margin-top:16px}.danger{background:#70251c}.outline{background:transparent;color:#9c3b2e;border:1px solid #9c3b2e}.notice,.error{padding:13px;margin:15px 0}.notice{background:#e0f0e6;color:#174b32}.error{background:#f7ded8;color:#70251c}@media(max-width:650px){.stats{grid-template-columns:1fr}}</style></head><body><header><a class="brand" href="admin.php">RankSetu Admin</a><nav class="nav"><a href="admin.php?series=<?= e($slug) ?>">Series</a><a href="admin-questions.php?series=<?= e($slug) ?>&amp;test=<?= e($selected) ?>">Questions</a><a href="logout.php">Log out</a></nav></header><main class="wrap"><div class="muted">Parent series / child tests / questions</div><h1><?= e($names[$slug]) ?></h1><p class="muted">Manage each full-length paper separately, then manage its child questions.</p><?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?><?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?><div class="sequence"><?php foreach ($tests as $id => $item): ?><a class="test-link <?= $id === $selected ? 'active' : '' ?>" href="admin-tests.php?series=<?= e($slug) ?>&amp;test=<?= e($id) ?>"><?= e(strtoupper(str_replace('test-', 'Test ', $id))) ?></a><?php endforeach; ?></div><form method="post"><input type="hidden" name="action" value="create_test"><input type="hidden" name="series" value="<?= e($slug) ?>"><button class="btn" type="submit">+ Add next test</button></form><section class="panel"><h2>Edit <?= e(strtoupper(str_replace('test-', 'Test ', $selected))) ?></h2><div class="stats"><div class="stat"><strong><?= count($selectedTest['questions'] ?? []) ?></strong><span>questions</span></div><div class="stat"><strong><?= e((string) ($selectedTest['duration_minutes'] ?? 10)) ?></strong><span>minutes</span></div><div class="stat"><strong><?= count($tests) ?></strong><span>tests</span></div></div><form method="post"><input type="hidden" name="action" value="update_test"><input type="hidden" name="series" value="<?= e($slug) ?>"><input type="hidden" name="test" value="<?= e($selected) ?>"><label>Test title</label><input name="title" value="<?= e($selectedTest['title'] ?? '') ?>" required><label>Duration in minutes</label><input type="number" name="duration" min="1" max="300" value="<?= e((string) ($selectedTest['duration_minutes'] ?? 120)) ?>" required><button class="btn" type="submit">Update test</button><a class="btn outline" href="admin-questions.php?series=<?= e($slug) ?>&amp;test=<?= e($selected) ?>">Manage questions</a></form><form method="post" onsubmit="return confirm('Delete this test?')"><input type="hidden" name="action" value="delete_test"><input type="hidden" name="series" value="<?= e($slug) ?>"><input type="hidden" name="test" value="<?= e($selected) ?>"><button class="btn danger" type="submit">Delete test</button></form></section></main></body></html>
