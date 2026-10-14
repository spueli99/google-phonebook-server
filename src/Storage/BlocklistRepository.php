<?php
declare(strict_types=1);

namespace Phonebook\Storage;

use Phonebook\Config;
use Phonebook\Contacts\ContactNormalizer;
use RuntimeException;

final class BlocklistRepository
{
    public function __construct(private readonly Config $config, private readonly SettingsRepository $settings)
    {
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        $data = JsonFile::read($this->config->storagePath('blocklist'), ['entries' => []]);
        $entries = is_array($data['entries'] ?? null) ? $data['entries'] : [];
        usort($entries, static fn(array $a, array $b): int => strcasecmp((string)($a['label'] ?? ''), (string)($b['label'] ?? '')));
        return array_values($entries);
    }

    /** @return list<array<string,mixed>> */
    public function active(): array
    {
        return array_values(array_filter($this->all(), static fn(array $entry): bool => !empty($entry['enabled'])));
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function save(array $input): array
    {
        $id = trim((string)($input['id'] ?? ''));
        $entries = $this->all();
        $existing = null;
        if ($id !== '') {
            foreach ($entries as $entry) {
                if ((string)($entry['id'] ?? '') === $id) {
                    $existing = $entry;
                    break;
                }
            }
            if ($existing === null) {
                throw new RuntimeException('Sperrlisteneintrag wurde nicht gefunden.');
            }
        } else {
            $id = 'blk_' . bin2hex(random_bytes(6));
        }

        $countryCode = (string)($this->settings->all()['country_code'] ?? '49');
        $number = ContactNormalizer::normalizePhone(trim((string)($input['number'] ?? '')), $countryCode);
        if ($number === '') {
            throw new RuntimeException('Bitte eine gültige Rufnummer eingeben.');
        }
        foreach ($entries as $entry) {
            if ((string)($entry['number'] ?? '') === $number && (string)($entry['id'] ?? '') !== $id) {
                throw new RuntimeException('Diese Rufnummer steht bereits auf der Sperrliste.');
            }
        }

        $label = trim((string)($input['label'] ?? '')) ?: 'Gesperrt';
        $now = date(DATE_ATOM);
        $record = [
            'id' => $id,
            'number' => $number,
            'label' => mb_substr($label, 0, 120),
            'comment' => mb_substr(trim((string)($input['comment'] ?? '')), 0, 500),
            'enabled' => !empty($input['enabled']),
            'created_at' => (string)($existing['created_at'] ?? $now),
            'updated_at' => $now,
        ];

        $found = false;
        foreach ($entries as $index => $entry) {
            if ((string)($entry['id'] ?? '') === $id) {
                $entries[$index] = $record;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $entries[] = $record;
        }
        JsonFile::write($this->config->storagePath('blocklist'), ['entries' => array_values($entries)]);
        return $record;
    }

    public function delete(string $id): void
    {
        $entries = array_values(array_filter(
            $this->all(),
            static fn(array $entry): bool => (string)($entry['id'] ?? '') !== $id
        ));
        JsonFile::write($this->config->storagePath('blocklist'), ['entries' => $entries]);
    }

    /** @return list<array{name:string,numbers:list<array{number:string,label:string,type:string}>}> */
    public function asContacts(): array
    {
        $contacts = [];
        foreach ($this->active() as $entry) {
            $contacts[] = [
                'name' => (string)($entry['label'] ?? 'Gesperrt'),
                'numbers' => [[
                    'number' => (string)$entry['number'],
                    'label' => 'Gesperrt',
                    'type' => 'blocklist',
                ]],
            ];
        }
        return $contacts;
    }
}
