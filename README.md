# Phonebook Sync

Self-hosted PHP application for synchronizing Google Contacts with Yealink Remote Phone Books.

The application deliberately stays small: **Google People API → normalized local cache → one or more Yealink devices**. Each device gets its own random access token. A central blocklist can optionally be published as a second Yealink Remote Phone Book.

## Features

- read-only Google Contacts sync via Google People API
- OAuth 2.0 web-server flow with refresh-token storage outside the web root
- normalized phone numbers with configurable default country code
- Google number labels such as `Mobil`, `Privat`, `Büro` and custom labels
- multiple Yealink devices
- independent 256-bit token per device
- optional second Yealink directory for the central blocklist
- device access timestamp and access counter
- manual sync and protected URL cron endpoint
- sync history
- admin-only JSON/CSV downloads
- responsive dark/light admin UI
- no database and no framework required

## Supported device path

The current production-tested device format is **Yealink Remote Phone Book XML**. The exporter layer is intentionally small and extensible, but unsupported manufacturers are not shipped as placeholder implementations.

## Requirements

- PHP 8.2 or newer
- PHP extensions: cURL, JSON, mbstring
- HTTPS
- writable `storage/` directory
- Google Cloud project
- Google People API enabled
- OAuth 2.0 client of type **Web application**

## Directory layout

```text
phonebook-sync/
├── public/                  # only public web root
├── src/                     # application code
├── config/                  # local config / secrets
├── storage/                 # OAuth token, cache and runtime data
├── tests/                   # built-in tests
├── tools/                   # installation helper
├── docs/                    # documentation
└── .github/workflows/       # GitHub validation
```

Only `public/` must be reachable by the web server. `config/`, `src/`, `storage/`, `tests/`, `tools/` and `docs/` stay outside the DocumentRoot.

## Quick installation

1. Upload the repository to the server.
2. Point the subdomain DocumentRoot to `public/`.
3. Copy `config/config.example.php` to `config/local.php`.
4. Generate an admin password hash and a cron token.
5. Create and configure the Google OAuth application.
6. Sign in to Phonebook Sync and connect Google.
7. Run the first synchronization.
8. Create a Yealink device and copy its generated Remote Phone Book URL to the phone.
9. Optionally configure a URL cron job.

The complete installation guide is in [`docs/INSTALLATION.md`](docs/INSTALLATION.md).

## Google configuration

The application requests only:

```text
https://www.googleapis.com/auth/contacts.readonly
```

This scope allows reading/downloading contacts but not editing or deleting them.

A detailed, current step-by-step guide is in [`docs/GOOGLE-OAUTH.md`](docs/GOOGLE-OAUTH.md).

## Yealink

Each Yealink device gets an URL similar to:

```text
https://phonebook.example.de/export.php?device=dev_0123456789ab&book=contacts&token=<DEVICE_TOKEN>
```

The complete device setup and blocklist behavior are documented in [`docs/YEALINK.md`](docs/YEALINK.md).

## Security model

There are four separate trust boundaries:

1. admin UI: password hash + secure PHP session
2. device exports: independent random token per device
3. cron: independent random cron token
4. Google: OAuth 2.0 token stored under `storage/`

Never commit `config/local.php`, OAuth tokens, runtime JSON files or full device URLs. See [`SECURITY.md`](SECURITY.md).

## Tests

```bash
php tests/run.php
```

GitHub Actions additionally runs PHP syntax checks on every push and pull request.

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Google Cloud / OAuth](docs/GOOGLE-OAUTH.md)
- [Yealink setup](docs/YEALINK.md)
- [Cron and synchronization](docs/CRON.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Testing](docs/TESTING.md)
- [Adding exporters](docs/ADDING-EXPORTERS.md)
- [Scope](docs/SCOPE.md)
- [Security](SECURITY.md)

## License

MIT
