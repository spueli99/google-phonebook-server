# ALL-INKL installation

The complete clean-install guide is maintained in:

[INSTALLATION.md](INSTALLATION.md)

For ALL-INKL the key deployment rule is that the subdomain DocumentRoot points to the repository's `public/` directory, for example:

```text
/www/htdocs/<account>/phonebook.example.de/public/
```

The repository root itself must not be the public web root.
