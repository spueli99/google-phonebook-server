# Security

## Deployment boundary

`public/` must be the **only** web-accessible directory.

The following directories must stay outside the DocumentRoot:

```text
config/
src/
storage/
tests/
tools/
docs/
```

`config/`, `src/` and `storage/` also contain `.htaccess` deny rules as defense in depth, but they are not a substitute for a correct DocumentRoot.

## Secrets and private data

Never commit or publish:

- `config/local.php`
- Google OAuth client secret
- `storage/google_token.json`
- production admin password hashes
- per-device tokens
- full device export URLs
- cron token or full cron URL
- runtime JSON data under `storage/`
- exported contact data

The supplied `.gitignore` excludes the local configuration and runtime storage files.

## Authentication boundaries

The application deliberately separates credentials:

1. **Admin UI** — password hash + secure PHP session
2. **Device exports** — independent random 256-bit token per device
3. **Cron synchronization** — independent random cron token
4. **Google** — OAuth 2.0 refresh/access token stored under `storage/`

There is no global Remote Phone Book credential.

## Admin session

- session cookie is `Secure`
- session cookie is `HttpOnly`
- `SameSite=Lax`
- session ID is regenerated after successful login
- admin session has an eight-hour idle timeout
- logout invalidates the session cookie and session data

Because cookies are always marked Secure, the application is intended to run only through HTTPS.

## Login throttling

Failed logins are tracked using a SHA-256 hash of the remote IP address. After repeated failures in the configured window, further attempts are temporarily blocked.

The runtime throttle file is private under `storage/`.

## CSRF and OAuth

- state-changing admin forms use CSRF tokens
- the Google OAuth callback validates OAuth `state`
- OAuth state has a limited lifetime

## HTTP security headers

Admin pages send restrictive headers including:

- `Content-Security-Policy`
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: no-referrer`
- `Permissions-Policy`
- HSTS when the request is HTTPS
- `Cache-Control: no-store` for admin pages

## Device tokens in URLs

Yealink Remote Phone Book authentication uses a token in the query string because this is compatible with the tested phone workflow.

Therefore the **complete export URL is a secret**. Query strings can appear in:

- web-server access logs
- browser history
- screenshots
- support tickets
- reverse-proxy logs

Protect these locations. If a device URL is exposed, rotate the device token in the admin UI and replace the URL on the phone.

## Cron token in URL

The cron endpoint also uses a query-string token. Treat the complete cron URL as a secret and do not publish it.

## Storage

Runtime JSON files are written atomically. The application attempts to reduce runtime file permissions to `0600` where supported by the hosting environment.

`storage/` contains personal data such as contact names, phone numbers, blocklist entries and device metadata. Backups of this directory require the same protection as the live installation.

## Google permissions

Phonebook Sync requests only:

```text
https://www.googleapis.com/auth/contacts.readonly
```

Do not broaden the OAuth scope unless an actual feature requires it.

## Reporting security issues

Do not include production credentials, contact data, OAuth tokens, cron URLs or complete device URLs in public GitHub issues. Replace them with sanitized examples.
