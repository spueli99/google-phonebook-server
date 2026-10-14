<?php
declare(strict_types=1);

namespace Phonebook\Storage;

use Phonebook\Config;

final class ContactRepository
{
    public function __construct(private readonly Config $config)
    {
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        $data = JsonFile::read($this->config->storagePath('contacts'), ['contacts' => []]);
        return is_array($data['contacts'] ?? null) ? array_values($data['contacts']) : [];
    }

    /** @param list<array<string,mixed>> $contacts */
    public function save(array $contacts): void
    {
        JsonFile::write($this->config->storagePath('contacts'), [
            'generated_at' => date(DATE_ATOM),
            'contacts' => array_values($contacts),
        ]);
    }
}
