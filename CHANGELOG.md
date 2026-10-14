# Changelog

## 0.2.2

Documentation and security-hardening release.

- add defense-in-depth `.htaccess` deny rules for `config/` and `src/`
- avoid exposing internal exception messages through the public cron endpoint
- make export error responses consistently plain text
- document stricter permissions for `config/local.php` where supported
- clarify Google OAuth Testing / refresh-token behavior for unattended operation
- add a Yealink export test step before configuring the phone
- remove the redundant ALL-INKL stub document; shared-hosting guidance stays in `INSTALLATION.md`

## 0.2.1

Current clean baseline for the `main` branch.

- Google People API read-only synchronization
- structured contact model with number type/label support
- Yealink Remote Phone Book XML
- multiple Yealink devices with independent 256-bit tokens
- central blocklist with optional second Yealink directory
- device access tracking
- manual and cron synchronization
- synchronization history
- hardened admin authentication, CSRF and OAuth state validation
- restrictive security headers
- atomic JSON storage
- responsive dark/light admin interface
