<?php

/** File cache for public catalog data only; never store accounts or permissions. */
final class PublicCatalogCache
{
    public function __construct(private string $path, private int $ttl = 300) {}

    public static function directory(string $name, int $ttl = 300, ?int $year = null): self
    {
        if (!in_array($name, ['public-catalog', 'registration-academics', 'topic-directory', 'student-interest-directory', 'calendar-holidays'], true)
            || ($name === 'calendar-holidays' ? ($year === null || $year < 2000 || $year > 2100) : $year !== null)) {
            throw new InvalidArgumentException('Invalid reference cache name or year.');
        }
        require_once __DIR__ . '/../../config/database.php';
        $configuration = databaseConfiguration();
        $namespace = hash('sha256', __DIR__ . '|' . $configuration['host'] . '|' . $configuration['port'] . '|' . $configuration['database']);
        return new self(sys_get_temp_dir() . '/olshco-' . $name . '-' . $namespace . ($year === null ? '' : '-' . $year) . '.json', $ttl);
    }

    public function clearExisting(): void
    {
        if (is_file($this->path)) { $this->clear(); }
    }

    public function remember(callable $load): array
    {
        $file = @fopen($this->path, 'c+');
        if ($file === false) { return $load(); }
        try {
            if (!flock($file, LOCK_EX)) { return $load(); }
            rewind($file);
            $entry = json_decode(stream_get_contents($file), true);
            if (is_array($entry) && ($entry['expires'] ?? 0) > time() && is_array($entry['data'] ?? null)) {
                return $entry['data'];
            }
            $data = $load();
            $json = json_encode(['expires' => time() + $this->ttl, 'data' => $data]);
            if ($json !== false) {
                rewind($file);
                if (ftruncate($file, 0)) { fwrite($file, $json); fflush($file); }
            }
            return $data;
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    public function clear(): void
    {
        $file = @fopen($this->path, 'c+');
        if ($file === false) { return; }
        try {
            if (flock($file, LOCK_EX)) { ftruncate($file, 0); fflush($file); }
        } finally { flock($file, LOCK_UN); fclose($file); }
    }
}
