<?php
declare(strict_types=1);

namespace Phonebook;

final class View
{
    public const VERSION = '0.2.1';

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function formatTime(mixed $value): string
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return '—';
        }
        try {
            return (new \DateTimeImmutable($raw))->format('d.m.Y H:i:s');
        } catch (\Throwable) {
            return $raw;
        }
    }

    public static function deviceType(string $type): string
    {
        return match ($type) {
            'yealink' => 'Yealink',
            default => ucfirst($type),
        };
    }

    public static function header(string $title, bool $nav = true): void
    {
        Http::securityHeaders();
        header('Cache-Control: no-store, private');
        header('Pragma: no-cache');
        echo '<!doctype html><html lang="de"><head><meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<meta name="color-scheme" content="dark light">';
        echo '<title>' . self::e($title) . ' · Phonebook Sync</title>';
        echo '<link rel="stylesheet" href="/assets/app.css"><script defer src="/assets/theme.js"></script></head><body>';
        echo '<header class="site-header"><div class="site-container nav">';
        echo '<a class="brand" href="/"><span class="brand-mark">P</span><span><strong>Phonebook Sync</strong><small>Google Contacts · Geräte · Sperrliste</small></span></a>';
        if ($nav) {
            echo '<nav class="nav-actions">';
            echo '<a href="/">Status</a><a href="/contacts.php">Kontakte</a><a href="/devices.php">Geräte</a><a href="/blocklist.php">Sperrliste</a><a href="/history.php">Protokoll</a><a href="/exports.php">Exporte</a><a href="/settings.php">Einstellungen</a>';
            echo '<button class="theme-toggle" type="button" data-theme-toggle aria-label="Darstellung wechseln">◐</button><a class="nav-logout" href="/logout.php">Abmelden</a></nav>';
        } else {
            echo '<div class="nav-actions"><button class="theme-toggle" type="button" data-theme-toggle aria-label="Darstellung wechseln">◐</button></div>';
        }
        echo '</div></header><main class="site-main"><div class="site-container">';
    }

    public static function footer(): void
    {
        echo '</div></main><footer class="site-footer"><div class="site-container footer-inner"><span>Phonebook Sync · v' . self::VERSION . '</span><span class="muted">Google Contacts → Yealink Remote Phone Books</span></div></footer></body></html>';
    }
}
