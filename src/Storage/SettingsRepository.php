<?php
declare(strict_types=1);

namespace Phonebook\Storage;

use Phonebook\Config;

final class SettingsRepository
{
    public function __construct(private readonly Config $config)
    {
    }

    /** @return array{country_code:string,phonebook_title:string} */
    public function all(): array
    {
        return array_replace([
            'country_code' => '49',
            'phonebook_title' => 'Google Kontakte',
        ], JsonFile::read($this->config->storagePath('settings')));
    }

    /** @param array<string,mixed> $settings */
    public function save(array $settings): void
    {
        $countryCode = preg_replace('/\D+/', '', (string)($settings['country_code'] ?? '49')) ?? '49';
        if ($countryCode === '') {
            $countryCode = '49';
        }
        $title = trim((string)($settings['phonebook_title'] ?? 'Google Kontakte'));
        if ($title === '') {
            $title = 'Google Kontakte';
        }
        JsonFile::write($this->config->storagePath('settings'), [
            'country_code' => $countryCode,
            'phonebook_title' => mb_substr($title, 0, 100),
        ]);
    }
}
