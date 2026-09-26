<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_admin();
$names = banking_labels();
foreach (series_records() as $record) if (isset($record['slug'], $record['title'])) $names[$record['slug']] = $record['title'];
$slug = (string) ($_GET['series'] ?? $_POST['series'] ?? array_key_first($names));
if (!isset($names[$slug])) $slug = array_key_first($names);
$folder = __DIR__ . '/tests/' . $slug;
$tests = test_records($slug);
$selected = (string) ($_GET['test'] ?? $_POST['test'] ?? array_key_first($tests));
if (!isset($tests[$selected])) $selected = array_key_first($tests);
$selectedTest = $tests[$selected] ?? ['title' => '', 'duration_minutes' => 60, 'questions' => []];
$notice = (string) ($_GET['notice'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'generate_full_length') {
            $duration = max(1, min(300, (int) ($_POST['full_length_duration'] ?? 60)));
            $setCount = max(1, min(15, (int) ($_POST['set_count'] ?? 15)));
            for ($number = 1; $number <= $setCount; $number++) {
                $id = 'test-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT);
                $questions = isset(banking_catalog()[$slug]) ? banking_questions($slug) : [];
                $generated = ['title' => $names[$slug] . ' Full-Length Set ' . str_pad((string) $number, 2, '0', STR_PAD_LEFT), 'duration_minutes' => $duration, 'questions' => $questions];
                if (db_enabled()) save_test_record($slug, $id, $generated);
                else { if (!is_dir($folder)) mkdir($folder, 0700, true); file_put_contents($folder . '/' . $id . '.json', json_encode($generated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX); }
            }
            header('Location: admin-tests.php?series=' . rawurlencode($slug) . '&test=test-01&notice=' . rawurlencode($setCount . ' full-length sets generated')); exit;
        }
        if ($action === 'create_test') {
            $number = count($tests) + 1;
            $id = 'test-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            $newTest = ['title' => $names[$slug] . ' Practice Set ' . str_pad((string) $number, 2, '0', STR_PAD_LEFT), 'duration_minutes' => max(1, (int) ($_POST['duration'] ?? 60)), 'questions' => isset(banking_catalog()[$slug]) ? banking_questions($slug) : []];
            if (db_enabled()) save_test_record($slug, $id, $newTest); else { if (!is_dir($folder)) mkdir($folder, 0700, true); file_put_contents($folder . '/' . $id . '.json', json_encode($newTest, JSON_PRETTY_PRINT), LOCK_EX); }
            header('Location: admin-tests.php?series=' . rawurlencode($slug) . '&test=' . $id . '&notice=New+test+created'); exit;
        }
        if ($action === 'update_test') {
            $selectedTest['title'] = trim((string) ($_POST['title'] ?? $selectedTest['title']));
            $selectedTest['duration_minutes'] = max(1, min(300, (int) ($_POST['duration'] ?? 60)));
            if (db_enabled()) save_test_record($slug, $selected, $selectedTest); else { if (!is_dir($folder)) mkdir($folder, 0700, true); file_put_contents($folder . '/' . $selected . '.json', json_encode($selectedTest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX); }
            header('Location: admin-tests.php?series=' . rawurlencode($slug) . '&test=' . $selected . '&notice=Test+updated'); exit;
        }
        if ($action === 'delete_test') {
            if (count($tests) <= 1) throw new RuntimeException('A series must keep at least one test.');
            if (db_enabled()) delete_test_record($slug, $selected); else @unlink($folder . '/' . $selected . '.json');
            header('Location: admin-tests.php?series=' . rawurlencode($slug) . '&notice=Test+deleted'); exit;
        }
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
?><!doctype html>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form.toolbar');
    if (!form) return;
    const countLabel = document.createElement('label');
    countLabel.textContent = 'Number of sets (1-15)';
    const countInput = document.createElement('input');
    countInput.name = 'set_count';
    countInput.type = 'number';
    countInput.min = '1';
    countInput.max = '15';
    countInput.value = '15';
    countInput.required = true;
    countLabel.append(countInput);
    const submit = form.querySelector('button[type="submit"]');
    form.insertBefore(countLabel, submit);
    if (submit) {
        submit.textContent = 'Generate selected sets';
        form.addEventListener('submit', (event) => {
            const count = Math.max(1, Math.min(15, Number(countInput.value) || 15));
            countInput.value = String(count);
            if (!window.confirm(`Generate or refresh ${count} full-length set${count === 1 ? '' : 's'}?`)) event.preventDefault();
        });
    }
    const description = form.closest('.panel')?.querySelector('p.muted');
    if (description) description.textContent = 'Choose how many numbered sets to create or refresh. Each set can be edited individually.';
});
</script>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($names[$slug]) ?> sets | RankSetu Admin</title><style>:root{--navy:#16233f;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24;--red:#70251c}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font:15px Arial}header{background:var(--navy);border-bottom:3px solid var(--gold);padding:17px 5%;display:flex;justify-content:space-between;gap:18px}.brand{font:700 1.45rem Georgia;color:#fff;text-decoration:none}.nav{display:flex;gap:18px;flex-wrap:wrap}.nav a{color:#f5ead2;text-decoration:none;font-size:.88rem}.wrap{width:min(1120px,calc(100% - 32px));margin:auto;padding:36px 0 70px}h1,h2,h3{font-family:Georgia;color:var(--navy)}h1{font-size:clamp(2rem,4vw,3.2rem);margin:0 0 8px}.muted{color:var(--muted)}.hero{display:flex;justify-content:space-between;gap:20px;align-items:end;margin-bottom:24px}.panel{background:var(--card);border:1px solid var(--line);padding:22px;margin-top:16px}.toolbar{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.toolbar label{flex:1;min-width:160px}label{display:block;font-size:.76rem;font-weight:700;margin:10px 0 5px}input,select{width:100%;padding:10px;border:1px solid var(--line);background:#fffdf7;font:inherit}.btn{display:inline-block;background:var(--maroon);color:#fff;border:0;padding:10px 13px;font-weight:700;text-decoration:none;cursor:pointer;font-size:.8rem}.btn.outline{background:transparent;color:var(--maroon);border:1px solid var(--maroon)}.btn.danger{background:var(--red)}.notice,.error{padding:12px;margin:14px 0}.notice{background:#e0f0e6;color:#174b32}.error{background:#f7ded8;color:var(--red)}.set-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.set-card{background:#fffdf7;border:1px solid var(--line);padding:16px}.set-card.active{border-top:4px solid var(--maroon)}.set-card h3{margin:0 0 8px;font-size:1.1rem}.meta{color:var(--muted);font-size:.8rem;line-height:1.5}.actions{display:flex;gap:7px;flex-wrap:wrap;margin-top:14px}.edit{display:grid;grid-template-columns:1fr 180px auto;gap:12px;align-items:end}.edit label{margin:0}.empty{padding:20px 0;color:var(--muted)}@media(max-width:800px){.set-grid{grid-template-columns:repeat(2,1fr)}.edit{grid-template-columns:1fr}}@media(max-width:520px){.set-grid{grid-template-columns:1fr}.hero{display:block}}
</style></head><body><header><a class="brand" href="admin.php">RankSetu Admin</a><nav class="nav"><a href="admin.php?series=<?= e($slug) ?>">Series</a><a href="admin-questions.php?series=<?= e($slug) ?>&amp;test=<?= e($selected) ?>">Questions</a><a href="logout.php">Log out</a></nav></header><main class="wrap"><section class="hero"><div><div class="muted">Parent series</div><h1><?= e($names[$slug]) ?></h1><p class="muted">Manage child full-length tests, timing and question content.</p></div><a class="btn outline" href="admin.php?series=<?= e($slug) ?>">Back to series</a></section><?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?><?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?><section class="panel"><h2>Generate full-length series</h2><p class="muted">Creates 15 numbered sets. Each banking set starts with 10 questions and can be edited individually.</p><form method="post" class="toolbar"><input type="hidden" name="action" value="generate_full_length"><input type="hidden" name="series" value="<?= e($slug) ?>"><label>Duration per set (minutes)<input name="full_length_duration" type="number" min="1" max="300" value="60" required></label><button class="btn" type="submit" onclick="return confirm('Generate or refresh all 15 full-length sets?')">Generate 15 sets</button></form></section><section class="panel"><h2><?= count($tests) ?> available sets</h2><div class="set-grid"><?php foreach ($tests as $id => $test): ?><article class="set-card <?= $id === $selected ? 'active' : '' ?>"><h3><?= e($test['title'] ?? strtoupper($id)) ?></h3><div class="meta"><?= count($test['questions'] ?? []) ?> questions &bull; <?= (int) ($test['duration_minutes'] ?? 60) ?> minutes<br>Child test: <?= e($id) ?></div><div class="actions"><a class="btn outline" href="admin-tests.php?series=<?= e($slug) ?>&amp;test=<?= e($id) ?>">Edit set</a><a class="btn" href="admin-questions.php?series=<?= e($slug) ?>&amp;test=<?= e($id) ?>">Edit questions</a><form method="post" onsubmit="return confirm('Delete this test set?')"><input type="hidden" name="action" value="delete_test"><input type="hidden" name="series" value="<?= e($slug) ?>"><input type="hidden" name="test" value="<?= e($id) ?>"><button class="btn danger" type="submit">Delete</button></form></div></article><?php endforeach; ?></div><?php if ($tests === []): ?><div class="empty">No sets yet. Generate the 15-set series above.</div><?php endif; ?></section><section class="panel"><h2>Edit selected set</h2><form method="post" class="edit"><input type="hidden" name="action" value="update_test"><input type="hidden" name="series" value="<?= e($slug) ?>"><input type="hidden" name="test" value="<?= e($selected) ?>"><label>Set title<input name="title" value="<?= e((string) ($selectedTest['title'] ?? '')) ?>" required></label><label>Duration (minutes)<input name="duration" type="number" min="1" max="300" value="<?= (int) ($selectedTest['duration_minutes'] ?? 60) ?>" required></label><button class="btn" type="submit">Save set</button></form><div class="actions"><a class="btn outline" href="admin-questions.php?series=<?= e($slug) ?>&amp;test=<?= e($selected) ?>">Open question editor</a></div></section></main></body></html>
