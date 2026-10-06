<?php
// CLI-only maintenance; clears this application's configured database namespace.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/models/PublicCatalogCache.php';
if (($argv[1] ?? '') !== '--clear') {
    fwrite(STDERR, "Usage: php scripts/clear_reference_cache.php --clear\n");
    exit(1);
}
foreach (['public-catalog', 'registration-academics', 'topic-directory', 'student-interest-directory'] as $name) {
    PublicCatalogCache::directory($name)->clearExisting();
}
for ($year = 2000; $year <= 2100; $year++) {
    PublicCatalogCache::directory('calendar-holidays', 300, $year)->clearExisting();
}
echo "Reference caches cleared for the configured application/database. No database changes.\n";
