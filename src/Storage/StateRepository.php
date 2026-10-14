<?php
declare(strict_types=1);

namespace Phonebook\Storage;

use Phonebook\Config;

final class StateRepository
{
    public function __construct(private readonly Config $config)
    {
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        return array_replace([
            'last_sync' => null,
            'contacts' => 0,
            'numbers' => 0,
            'last_error' => null,
        ], JsonFile::read($this->config->storagePath('state')));
    }

    public function success(int $contacts, int $numbers): void
    {
        JsonFile::write($this->config->storagePath('state'), [
            'last_sync' => date(DATE_ATOM),
            'contacts' => $contacts,
            'numbers' => $numbers,
            'last_error' => null,
        ]);
    }

    public function failure(string $message): void
    {
        $state = $this->all();
        $state['last_error'] = mb_substr($message, 0, 1000);
        JsonFile::write($this->config->storagePath('state'), $state);
    }
}
