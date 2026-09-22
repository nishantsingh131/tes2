<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
header('Content-Type: application/json; charset=utf-8');
$published = [];
foreach (series_records() as $record) {
    if (!empty($record['active']) && isset($record['slug'], $record['title'])) {
        $published[] = ['slug' => $record['slug'], 'title' => $record['title'], 'stage' => $record['stage'] ?? 'Practice', 'description' => $record['description'] ?? 'Admin-published practice series.'];
    }
}
echo json_encode($published, JSON_UNESCAPED_SLASHES);
