# Testing

## Automated tests

From the repository root:

```bash
php tests/run.php
```

## PHP syntax check

```bash
find . -name '*.php' -not -path './storage/*' -print0 | xargs -0 -n1 php -l
```

## Manual production checklist

1. Admin login works only on the HTTPS hostname.
2. A wrong admin password is rejected.
3. Repeated wrong logins are rate-limited.
4. Google OAuth connects successfully.
5. Manual sync succeeds and appears in the sync history.
6. Contact count and phone-number count are plausible.
7. A new Yealink device receives a 64-character hexadecimal token.
8. Device export without token is rejected.
9. Wrong device token is rejected.
10. Disabled device is rejected.
11. Contact XML contains the expected number labels such as `Mobil` and `Büro`.
12. The blocklist directory contains only active blocklist entries.
13. Successful device retrieval updates last access and access counter.
14. Token rotation invalidates the previous device URL.
15. Deleting a device invalidates its URL.
16. Admin pages return `Cache-Control: no-store`.
17. Security headers include CSP, frame protection and `Referrer-Policy: no-referrer`.
18. `config/`, `storage/`, `src/`, `tests/` and `tools/` are not reachable through the public hostname.
19. `config/local.php` and runtime JSON files are not present in Git.
