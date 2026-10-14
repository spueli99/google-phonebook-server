<?php
declare(strict_types=1);

namespace Phonebook\Storage;

use Phonebook\Config;
use RuntimeException;

final class DeviceRepository
{
    private const TYPES = ['yealink'];


    public function __construct(private readonly Config $config)
    {
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        $data = JsonFile::read($this->config->storagePath('devices'), ['devices' => []]);
        $devices = is_array($data['devices'] ?? null) ? $data['devices'] : [];
        usort($devices, static fn(array $a, array $b): int => strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? '')));
        return array_values($devices);
    }

    /** @return array<string,mixed>|null */
    public function find(string $id): ?array
    {
        foreach ($this->all() as $device) {
            if (hash_equals((string)($device['id'] ?? ''), $id)) {
                return $device;
            }
        }
        return null;
    }

    /** @return array<string,mixed>|null */
    public function authorize(string $id, string $token): ?array
    {
        $device = $this->find($id);
        if ($device === null || empty($device['enabled'])) {
            return null;
        }
        $expected = (string)($device['token'] ?? '');
        if ($expected === '' || $token === '' || !hash_equals($expected, $token)) {
            return null;
        }
        return $device;
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function save(array $input): array
    {
        $devices = $this->all();
        $id = trim((string)($input['id'] ?? ''));
        $existing = $id !== '' ? $this->find($id) : null;
        if ($id !== '' && $existing === null) {
            throw new RuntimeException('Gerät wurde nicht gefunden.');
        }
        if ($id === '') {
            $id = 'dev_' . bin2hex(random_bytes(6));
        }

        $type = strtolower(trim((string)($input['type'] ?? 'yealink')));
        if (!in_array($type, self::TYPES, true)) {
            throw new RuntimeException('Unbekannter Gerätetyp.');
        }
        $name = trim((string)($input['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Gerätename darf nicht leer sein.');
        }

        $token = (string)($existing['token'] ?? '');
        if ($token === '' || !empty($input['regenerate_token'])) {
            $token = bin2hex(random_bytes(32));
        }

        $now = date(DATE_ATOM);
        $record = [
            'id' => $id,
            'name' => mb_substr($name, 0, 120),
            'type' => $type,
            'enabled' => !empty($input['enabled']),
            'token' => $token,
            'contacts_enabled' => !empty($input['contacts_enabled']),
            'blocklist_directory_enabled' => $type === 'yealink' && !empty($input['blocklist_directory_enabled']),
            'notes' => mb_substr(trim((string)($input['notes'] ?? '')), 0, 500),
            'created_at' => (string)($existing['created_at'] ?? $now),
            'updated_at' => $now,
            'last_access_at' => $existing['last_access_at'] ?? null,
            'last_access_book' => $existing['last_access_book'] ?? null,
            'access_count' => (int)($existing['access_count'] ?? 0),
        ];

        $found = false;
        foreach ($devices as $index => $device) {
            if ((string)($device['id'] ?? '') === $id) {
                $devices[$index] = $record;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $devices[] = $record;
        }
        $this->write($devices);
        return $record;
    }

    public function delete(string $id): void
    {
        $devices = array_values(array_filter(
            $this->all(),
            static fn(array $device): bool => (string)($device['id'] ?? '') !== $id
        ));
        $this->write($devices);
    }

    public function recordAccess(string $id, string $book): void
    {
        $devices = $this->all();
        foreach ($devices as &$device) {
            if ((string)($device['id'] ?? '') !== $id) {
                continue;
            }
            $device['last_access_at'] = date(DATE_ATOM);
            $device['last_access_book'] = $book;
            $device['access_count'] = (int)($device['access_count'] ?? 0) + 1;
            break;
        }
        unset($device);
        $this->write($devices);
    }

    /** @param list<array<string,mixed>> $devices */
    private function write(array $devices): void
    {
        JsonFile::write($this->config->storagePath('devices'), ['devices' => array_values($devices)]);
    }
}
