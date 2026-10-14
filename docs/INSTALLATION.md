# Installation

This guide describes a clean installation on ordinary PHP shared hosting such as ALL-INKL.

## 1. Upload the repository

Example project path:

```text
/www/htdocs/<account>/phonebook.example.de/
```

The repository itself contains `public/`, `src/`, `config/`, `storage/`, `tests/`, `tools/` and `docs/`.

## 2. Set the subdomain DocumentRoot

The web server DocumentRoot must point **only** to:

```text
/phonebook.example.de/public/
```

Do not point it at the repository root.

This is the most important deployment boundary: OAuth tokens, configuration and runtime data are outside the public directory.

## 3. Check PHP

Recommended:

```bash
php -v
```

PHP 8.2 or newer is required. The application also requires cURL, JSON and mbstring.

## 4. Create the local configuration

From the repository root:

```bash
cp config/config.example.php config/local.php
```

`config/local.php` is ignored by Git and must never be committed.

## 5. Generate the admin password hash

```bash
php tools/hash-password.php 'A-STRONG-ADMIN-PASSWORD'
```

Copy only the resulting hash into:

```php
'admin' => [
    'password_hash' => 'PASTE_PASSWORD_HASH_HERE',
],
```

Do not store the clear-text password in the configuration.

## 6. Generate the cron token

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Put the result into:

```php
'security' => [
    'cron_token' => 'PASTE_RANDOM_TOKEN_HERE',
],
```

## 7. Configure application URL

Example:

```php
'app' => [
    'name' => 'Phonebook Sync',
    'base_url' => 'https://phonebook.example.de',
    'timezone' => 'Europe/Berlin',
    'session_name' => 'phonebook_sync_admin',
],
```

`base_url` must use HTTPS and must not end in `/`.

## 8. Configure Google

Create the Google Cloud project, enable People API and create a Web Application OAuth client as described in [GOOGLE-OAUTH.md](GOOGLE-OAUTH.md).

The redirect URI must exactly match:

```text
https://phonebook.example.de/oauth/callback.php
```

Then set:

```php
'google' => [
    'client_id' => 'YOUR_CLIENT_ID.apps.googleusercontent.com',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    'redirect_uri' => 'https://phonebook.example.de/oauth/callback.php',
    'scope' => 'https://www.googleapis.com/auth/contacts.readonly',
],
```

## 9. Storage paths

Normally these defaults should be left unchanged:

```php
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
```

There is one contact cache: `storage/cache/contacts.json`. Yealink XML is rendered on request rather than maintained as a second persistent contact database.

## 10. File permissions

Typical shared-hosting permissions are sufficient:

```text
directories 0755
files       0644
```

Runtime files created by the application are reduced to `0600` where the hosting environment permits it.

The PHP process must be able to write to `storage/` and `storage/cache/`.

## 11. Validate the installation files

From the repository root:

```bash
find . -name '*.php' -not -path './storage/*' -print0 | xargs -0 -n1 php -l
php tests/run.php
```

Both commands should complete without errors.

## 12. Open the application

Open:

```text
https://phonebook.example.de/
```

Sign in using the admin password whose hash is in `config/local.php`.

## 13. Connect Google

On the status page choose **Google verbinden** and complete the Google consent flow.

After a successful callback the OAuth token is stored in:

```text
storage/google_token.json
```

Do not download, publish or commit this file.

## 14. First synchronization

Choose **Jetzt synchronisieren**.

After success the dashboard should show the number of contacts and phone numbers. The contact cache is stored in:

```text
storage/cache/contacts.json
```

## 15. Create a Yealink device

Open **Geräte → Gerät hinzufügen** and enter a descriptive name such as:

```text
T54W Wohnzimmer
```

The server creates:

- a random device ID
- an independent 256-bit token
- a contact Remote Phone Book URL
- optionally a second blocklist directory URL

Copy the contact URL to the Yealink configuration. See [YEALINK.md](YEALINK.md).

## 16. Configure automatic synchronization

The application can be synchronized through the protected cron URL:

```text
https://phonebook.example.de/cron.php?token=<CRON_TOKEN>
```

See [CRON.md](CRON.md).

## 17. Recommended final checks

- login only works over HTTPS
- Google status is connected
- manual sync succeeds
- contacts are visible in the admin UI
- Yealink contact URL returns XML only with the correct token
- a wrong device token returns `403`
- device access time increases after the phone fetches the directory
- `config/`, `storage/`, `src/` and `tools/` cannot be accessed via the public hostname
