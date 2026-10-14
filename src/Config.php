<?php
declare(strict_types=1);

namespace Phonebook;

use RuntimeException;

final class Config
{
    /** @var array<string,mixed> */
    private array $data;

    public function __construct(string $file)
    {
        if (!is_file($file)) {
            throw new RuntimeException('Missing config/local.php. Copy config/config.example.php first.');
        }
        $data = require $file;
        if (!is_array($data)) {
            throw new RuntimeException('Configuration file must return an array.');
        }
        $this->data = $data;
        $this->validate();
    }

    public function get(string $path, mixed $default = null): mixed
    {
        $value = $this->data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function storagePath(string $name): string
    {
        $storage = rtrim((string)$this->get('paths.storage'), '/');
        $defaults = [
            'token' => $storage . '/google_token.json',
            'settings' => $storage . '/settings.json',
            'state' => $storage . '/state.json',
            'contacts' => $storage . '/cache/contacts.json',
            'devices' => $storage . '/devices.json',
            'blocklist' => $storage . '/blocklist.json',
            'sync_history' => $storage . '/sync_history.json',
            'login_throttle' => $storage . '/login_throttle.json',
        ];
        if (!array_key_exists($name, $defaults)) {
            throw new RuntimeException("Unknown storage path '{$name}'.");
        }
        return (string)$this->get('paths.' . $name, $defaults[$name]);
    }

    public function isGoogleConfigured(): bool
    {
        foreach (['google.client_id', 'google.client_secret', 'google.redirect_uri'] as $key) {
            $value = (string)$this->get($key, '');
            if ($value === '' || str_starts_with($value, 'REPLACE_')) {
                return false;
            }
        }
        return true;
    }

    public function requireGoogleConfigured(): void
    {
        if (!$this->isGoogleConfigured()) {
            throw new RuntimeException(
                'Google OAuth ist noch nicht konfiguriert. Bitte Client-ID und Client-Secret in config/local.php eintragen.'
            );
        }
    }

    private function validate(): void
    {
        foreach (['app.base_url', 'admin.password_hash', 'security.cron_token', 'paths.storage'] as $key) {
            $value = (string)$this->get($key, '');
            if ($value === '' || str_starts_with($value, 'REPLACE_')) {
                throw new RuntimeException("Configuration value '{$key}' is missing or still a placeholder.");
            }
        }

        $baseUrl = (string)$this->get('app.base_url');
        if (!str_starts_with($baseUrl, 'https://')) {
            throw new RuntimeException('app.base_url must use HTTPS.');
        }
    }
}
