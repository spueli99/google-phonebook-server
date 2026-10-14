<?php
declare(strict_types=1);

namespace Phonebook\Security;

use RuntimeException;

final class Csrf
{
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Session must be started before CSRF token use.');
        }
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf_token'];
    }

    public static function validate(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        $expected = (string)($_SESSION['csrf_token'] ?? '');
        return $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }
}
