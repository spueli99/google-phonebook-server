# Contributing

Issues and pull requests are welcome.

Before opening a pull request:

```bash
find . -name '*.php' -not -path './storage/*' -print0 | xargs -0 -n1 php -l
php tests/run.php
```

Never include real OAuth tokens, Client Secrets, passwords, telephone numbers or private contact names in commits, issues or test fixtures.
