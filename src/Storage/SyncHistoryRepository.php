<?php
declare(strict_types=1);

namespace Phonebook\Storage;

use Phonebook\Config;

final class SyncHistoryRepository
{
    public function __construct(private readonly Config $config)
    {
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        $data = JsonFile::read($this->config->storagePath('sync_history'), ['runs' => []]);
        return is_array($data['runs'] ?? null) ? array_values($data['runs']) : [];
    }

    /** @return list<array<string,mixed>> */
    public function latest(int $limit = 20): array
    {
        return array_slice(array_reverse($this->all()), 0, max(1, min($limit, 100)));
    }

    public function add(string $source, bool $success, int $contacts, int $numbers, float $duration, ?string $error = null): void
    {
        $runs = $this->all();
        $runs[] = [
            'at' => date(DATE_ATOM),
            'source' => in_array($source, ['manual', 'cron', 'oauth'], true) ? $source : 'other',
            'success' => $success,
            'contacts' => $contacts,
            'numbers' => $numbers,
            'duration_ms' => (int)round($duration * 1000),
            'error' => $error === null ? null : mb_substr($error, 0, 500),
        ];
        JsonFile::write($this->config->storagePath('sync_history'), ['runs' => array_slice($runs, -100)]);
    }
}
