<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'Phonebook Sync',
        'base_url' => 'https://phonebook.example.de',
        'timezone' => 'Europe/Berlin',
        'session_name' => 'phonebook_sync_admin',
    ],

    'admin' => [
        // Create with: php tools/hash-password.php 'YOUR-ADMIN-PASSWORD'
        'password_hash' => 'REPLACE_WITH_PASSWORD_HASH',
    ],

    'google' => [
        'client_id' => 'REPLACE_ME.apps.googleusercontent.com',
        'client_secret' => 'REPLACE_ME',
        'redirect_uri' => 'https://phonebook.example.de/oauth/callback.php',
        'scope' => 'https://www.googleapis.com/auth/contacts.readonly',
    ],

    'security' => [
        // Long random secret used only by the URL cron endpoint.
        'cron_token' => 'REPLACE_WITH_LONG_RANDOM_CRON_TOKEN',
    ],

    'paths' => [
        'storage' => dirname(__DIR__) . '/storage',
        'token' => dirname(__DIR__) . '/storage/google_token.json',
        'settings' => dirname(__DIR__) . '/storage/settings.json',
        'state' => dirname(__DIR__) . '/storage/state.json',
        'contacts' => dirname(__DIR__) . '/storage/cache/contacts.json',
        'devices' => dirname(__DIR__) . '/storage/devices.json',
        'blocklist' => dirname(__DIR__) . '/storage/blocklist.json',
        'sync_history' => dirname(__DIR__) . '/storage/sync_history.json',
        'login_throttle' => dirname(__DIR__) . '/storage/login_throttle.json',
    ],
];
