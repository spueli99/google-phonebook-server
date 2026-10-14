<?php
declare(strict_types=1);

namespace Phonebook\Security;

use Phonebook\Config;

final class AdminAuth
{
    private const IDLE_TIMEOUT = 28800; // 8 hours

    private readonly LoginRateLimiter $rateLimiter;

    public function __construct(private readonly Config $config)
    {
        $this->rateLimiter = new LoginRateLimiter($config);
    }

    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name((string)$this->config->get('app.session_name', 'phonebook_sync_admin'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public function login(string $password): bool
    {
        $this->startSession();
        $remoteAddress = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if ($this->rateLimiter->isBlocked($remoteAddress)) {
            usleep(500000);
            return false;
        }

        $hash = (string)$this->config->get('admin.password_hash');
        if (!password_verify($password, $hash)) {
            $this->rateLimiter->recordFailure($remoteAddress);
            usleep(250000);
            return false;
        }

        $this->rateLimiter->clear($remoteAddress);
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['authenticated_at'] = time();
        $_SESSION['last_activity'] = time();
        return true;
    }

    public function logout(): void
    {
        $this->startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?: '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
    }

    public function isAuthenticated(): bool
    {
        $this->startSession();
        if (($_SESSION['authenticated'] ?? false) !== true) {
            return false;
        }
        $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
        if ($lastActivity <= 0 || (time() - $lastActivity) > self::IDLE_TIMEOUT) {
            $this->logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    public function requireAuthenticated(): void
    {
        if (!$this->isAuthenticated()) {
            header('Location: /login.php', true, 303);
            exit;
        }
    }
}
