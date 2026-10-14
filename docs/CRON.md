# Automatic synchronization / cron

Phonebook Sync provides a protected HTTP endpoint for scheduled Google synchronization.

## Cron URL

```text
https://phonebook.example.de/cron.php?token=<CRON_TOKEN>
```

The token is configured only in `config/local.php`:

```php
'security' => [
    'cron_token' => 'LONG_RANDOM_SECRET',
],
```

Generate a token with:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

## Interval

Choose the interval based on how quickly changes need to reach the local cache.

For a private phonebook, 5–30 minutes is normally more than sufficient.

Example:

```text
300 seconds = 5 minutes
```

The Yealink phone has its own independent Remote Phone Book refresh interval. There is no need for both intervals to be equal.

## ALL-INKL

If the hosting package supports URL cron jobs, configure the complete HTTPS cron URL including its secret token.

Do not publish or paste the full URL into public tickets, GitHub issues or screenshots.

## Responses

Successful run:

```text
OK contacts=264 numbers=378 duration_ms=1234
```

Failed run:

```text
HTTP 500
ERROR ...
```

Wrong/missing token:

```text
HTTP 403
Forbidden
```

Every synchronization run is stored in the sync history.
