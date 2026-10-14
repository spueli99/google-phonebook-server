<?php
declare(strict_types=1);

namespace Phonebook\Security;

use Phonebook\Config;
use Phonebook\Storage\JsonFile;

final class LoginRateLimiter
{
    private const WINDOW_SECONDS = 900;
    private const MAX_FAILURES = 8;

    public function __construct(private readonly Config $config)
    {
    }

    public function isBlocked(string $remoteAddress): bool
    {
        $entry = $this->entry($remoteAddress);
        if ($entry === null) {
            return false;
        }
        return (int)($entry['first_failure_at'] ?? 0) >= time() - self::WINDOW_SECONDS
            && (int)($entry['failures'] ?? 0) >= self::MAX_FAILURES;
    }

    public function recordFailure(string $remoteAddress): void
    {
        $key = $this->key($remoteAddress);
        $data = $this->read();
        $now = time();
        $entry = is_array($data[$key] ?? null) ? $data[$key] : [];
        $first = (int)($entry['first_failure_at'] ?? 0);
        if ($first < $now - self::WINDOW_SECONDS) {
            $entry = ['first_failure_at' => $now, 'failures' => 0];
        }
        $entry['failures'] = (int)($entry['failures'] ?? 0) + 1;
        $entry['last_failure_at'] = $now;
        $data[$key] = $entry;
        $this->write($this->prune($data, $now));
    }

    public function clear(string $remoteAddress): void
    {
        $key = $this->key($remoteAddress);
        $data = $this->read();
        if (!array_key_exists($key, $data)) {
            return;
        }
        unset($data[$key]);
        $this->write($data);
    }

    /** @return array<string,mixed>|null */
    private function entry(string $remoteAddress): ?array
    {
        $entry = $this->read()[$this->key($remoteAddress)] ?? null;
        return is_array($entry) ? $entry : null;
    }

    private function key(string $remoteAddress): string
    {
        return hash('sha256', trim($remoteAddress));
    }

    /** @return array<string,mixed> */
    private function read(): array
    {
        return JsonFile::read($this->config->storagePath('login_throttle'), []);
    }

    /** @param array<string,mixed> $data */
    private function write(array $data): void
    {
        JsonFile::write($this->config->storagePath('login_throttle'), $data);
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function prune(array $data, int $now): array
    {
        foreach ($data as $key => $entry) {
            if (!is_array($entry) || (int)($entry['last_failure_at'] ?? 0) < $now - 86400) {
                unset($data[$key]);
            }
        }
        return $data;
    }
}
