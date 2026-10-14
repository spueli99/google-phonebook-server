<?php
declare(strict_types=1);

namespace Phonebook\Google;

use Phonebook\Config;
use Phonebook\Storage\JsonFile;
use RuntimeException;

final class OAuthClient
{
    public function __construct(private readonly Config $config)
    {
    }

    public function authorizationUrl(string $state): string
    {
        $this->config->requireGoogleConfigured();
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $this->config->get('google.client_id'),
            'redirect_uri' => $this->config->get('google.redirect_uri'),
            'response_type' => 'code',
            'scope' => $this->config->get('google.scope'),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    public function exchangeCode(string $code): void
    {
        $this->config->requireGoogleConfigured();
        $token = $this->tokenRequest([
            'client_id' => $this->config->get('google.client_id'),
            'client_secret' => $this->config->get('google.client_secret'),
            'code' => $code,
            'redirect_uri' => $this->config->get('google.redirect_uri'),
            'grant_type' => 'authorization_code',
        ]);
        $this->saveToken($token);
    }

    public function isConnected(): bool
    {
        return is_file($this->config->storagePath('token'));
    }

    public function accessToken(): string
    {
        $this->config->requireGoogleConfigured();
        $file = $this->config->storagePath('token');
        $token = JsonFile::read($file);
        if (!$token) {
            throw new RuntimeException('Google OAuth is not connected.');
        }

        $expiresIn = (int)($token['expires_in'] ?? 3600);
        $savedAt = (int)($token['saved_at'] ?? 0);
        if (!empty($token['access_token']) && ($savedAt + $expiresIn - 120) > time()) {
            return (string)$token['access_token'];
        }

        $refreshToken = (string)($token['refresh_token'] ?? '');
        if ($refreshToken === '') {
            throw new RuntimeException('Google refresh token is missing. Reconnect Google.');
        }

        $new = $this->tokenRequest([
            'client_id' => $this->config->get('google.client_id'),
            'client_secret' => $this->config->get('google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
        $new['refresh_token'] = $refreshToken;
        $this->saveToken($new);
        return (string)$new['access_token'];
    }

    private function saveToken(array $token): void
    {
        $token['saved_at'] = time();
        JsonFile::write($this->config->storagePath('token'), $token);
    }

    private function tokenRequest(array $params): array
    {
        [$status, $body] = $this->request(
            'POST',
            'https://oauth2.googleapis.com/token',
            ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query($params)
        );
        $data = json_decode($body, true);
        if ($status < 200 || $status >= 300 || !is_array($data)) {
            throw new RuntimeException("Google token request failed (HTTP {$status}).");
        }
        return $data;
    }

    public function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is required.');
        }
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize cURL.');
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'PhonebookSync/0.2.2',
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('HTTP request failed: ' . $error);
        }
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return [$status, (string)$response];
    }
}
