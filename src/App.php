<?php
declare(strict_types=1);

namespace Phonebook;

use Phonebook\Google\GoogleContactsClient;
use Phonebook\Google\OAuthClient;
use Phonebook\Security\AdminAuth;
use Phonebook\Storage\BlocklistRepository;
use Phonebook\Storage\ContactRepository;
use Phonebook\Storage\DeviceRepository;
use Phonebook\Storage\SettingsRepository;
use Phonebook\Storage\StateRepository;
use Phonebook\Storage\SyncHistoryRepository;
use Phonebook\Sync\SyncService;

final class App
{
    public readonly Config $config;
    public readonly AdminAuth $admin;
    public readonly SettingsRepository $settings;
    public readonly StateRepository $state;
    public readonly ContactRepository $contacts;
    public readonly DeviceRepository $devices;
    public readonly BlocklistRepository $blocklist;
    public readonly SyncHistoryRepository $history;
    public readonly OAuthClient $oauth;

    public function __construct(string $root)
    {
        require_once $root . '/src/autoload.php';
        $this->config = new Config($root . '/config/local.php');
        date_default_timezone_set((string)$this->config->get('app.timezone', 'Europe/Berlin'));
        $this->admin = new AdminAuth($this->config);
        $this->settings = new SettingsRepository($this->config);
        $this->state = new StateRepository($this->config);
        $this->contacts = new ContactRepository($this->config);
        $this->devices = new DeviceRepository($this->config);
        $this->blocklist = new BlocklistRepository($this->config, $this->settings);
        $this->history = new SyncHistoryRepository($this->config);
        $this->oauth = new OAuthClient($this->config);
    }

    public function syncService(): SyncService
    {
        $settings = $this->settings->all();
        $google = new GoogleContactsClient($this->oauth, (string)$settings['country_code']);
        return new SyncService($google, $this->contacts, $this->state, $this->history);
    }
}
