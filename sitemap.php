<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$maxUrlsPerSitemap = 10000;
$baseUrl = rtrim(APP_CANONICAL_URL, '/');
$fixedLocations = [$baseUrl . '/', $baseUrl . '/director.php'];
foreach (['about', 'contact', 'faq', 'disclaimer'] as $page) {
    $fixedLocations[] = $baseUrl . '/info.php?page=' . rawurlencode($page);
}
$pdo = db_pdo();
$eligibleSeriesSql = "SELECT s.slug FROM series s WHERE s.active=1 AND LOWER(TRIM(s.title)) NOT IN ('test','demo','sample','untitled') AND EXISTS (SELECT 1 FROM tests t JOIN questions q ON q.test_id=t.id WHERE t.series_id=s.id AND t.active=1)";
$memoryLocations = [];
$productCount = 0;

if ($pdo !== null) {
    $databaseSeriesCount = (int) $pdo->query('SELECT COUNT(*) FROM series')->fetchColumn();
    if ($databaseSeriesCount === 0) $pdo = null;
}
if ($pdo !== null) {
    $productCount = (int) $pdo->query('SELECT COUNT(*) FROM (' . $eligibleSeriesSql . ') AS eligible_series')->fetchColumn();
} else {
    foreach (published_banking_catalog() as $slug => $series) {
        $title = trim((string) ($series['title'] ?? ''));
        if ($title === '' || preg_match('/^(test|demo|sample|untitled)$/i', $title) === 1 || site_published_test_count((string) $slug) === 0) continue;
        $memoryLocations[] = $baseUrl . '/product.php?product=' . rawurlencode((string) $slug);
    }
    $productCount = count($memoryLocations);
}

$fixedLocationCount = count($fixedLocations);
$urlCount = $fixedLocationCount + $productCount;
$sitemapCount = max(1, (int) ceil($urlCount / $maxUrlsPerSitemap));
if ($sitemapCount > 50000) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('The sitemap index exceeds the protocol limit; configure nested sitemap indexes.');
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
if (isset($_GET['part'])) {
    $partValue = $_GET['part'];
    $part = is_string($partValue) ? filter_var($partValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $sitemapCount]]) : false;
    if ($part === false) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Sitemap part not found.');
    }
    if ($pdo === null) {
        $allLocations = array_merge($fixedLocations, $memoryLocations);
        $chunk = array_slice($allLocations, ($part - 1) * $maxUrlsPerSitemap, $maxUrlsPerSitemap);
    } else {
        $chunk = $part === 1 ? $fixedLocations : [];
        $productOffset = $part === 1 ? 0 : (($part - 1) * $maxUrlsPerSitemap) - $fixedLocationCount;
        $productLimit = $part === 1 ? $maxUrlsPerSitemap - $fixedLocationCount : $maxUrlsPerSitemap;
        if ($productLimit > 0 && $productOffset < $productCount) {
            $statement = $pdo->query($eligibleSeriesSql . ' ORDER BY s.slug LIMIT ' . (int) $productLimit . ' OFFSET ' . (int) $productOffset);
            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $series) {
                $chunk[] = $baseUrl . '/product.php?product=' . rawurlencode((string) $series['slug']);
            }
        }
    }
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($chunk as $location) {
        echo '<url><loc>' . htmlspecialchars($location, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc></url>';
    }
    echo '</urlset>';
} else {
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    for ($part = 1; $part <= $sitemapCount; $part++) {
        $sitemapUrl = $baseUrl . '/sitemap.php?part=' . $part;
        echo '<sitemap><loc>' . htmlspecialchars($sitemapUrl, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc></sitemap>';
    }
    echo '</sitemapindex>';
}