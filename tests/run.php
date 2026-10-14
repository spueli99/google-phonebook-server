<?php
declare(strict_types=1);

if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null): string
    {
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }
}

require dirname(__DIR__) . '/src/autoload.php';

use Phonebook\Config;
use Phonebook\Contacts\ContactNormalizer;
use Phonebook\Export\CsvExporter;
use Phonebook\Export\YealinkExporter;
use Phonebook\Storage\BlocklistRepository;
use Phonebook\Storage\DeviceRepository;
use Phonebook\Storage\SettingsRepository;
use Phonebook\Security\LoginRateLimiter;

$tests = 0;
$assert = static function (mixed $actual, mixed $expected, string $message) use (&$tests): void {
    $tests++;
    if ($actual !== $expected) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
};

$assert(ContactNormalizer::normalizePhone('01520 9853887', '49'), '+4915209853887', 'German mobile');
$assert(ContactNormalizer::normalizePhone('+49 2159 814669', '49'), '+492159814669', 'E.164 input');
$assert(ContactNormalizer::normalizePhone('0049 2159 814669', '49'), '+492159814669', 'International 00 prefix');
$assert(ContactNormalizer::normalizePhone('sip:4915209853887@Versatel.de', '49'), '+4915209853887', 'SIP URI');
$assert(ContactNormalizer::normalizePhone('tel:02159-814669;ext=3', '49'), '+492159814669', 'TEL URI');
$assert(ContactNormalizer::phoneLabel('mobile'), 'Mobil', 'Mobile label mapping');
$assert(ContactNormalizer::phoneLabel('work'), 'Büro', 'Work label mapping');
$assert(ContactNormalizer::phoneLabel('mobile', 'Handy privat'), 'Handy privat', 'formattedType preferred');
$assert(ContactNormalizer::phoneEntries(['numbers' => ['+49123']]), [], 'String-only number entries are rejected');

$contacts = [[
    'name' => 'A & B <Test>',
    'numbers' => [
        ['number' => '+49123', 'label' => 'Mobil', 'type' => 'mobile'],
        ['number' => '+49456', 'label' => 'Büro & Team', 'type' => 'work'],
    ],
]];

$xml = (new YealinkExporter())->render($contacts);
$assert(str_contains($xml, '<YealinkIPPhoneDirectory>'), true, 'Yealink root');
$assert(str_contains($xml, '<Name>A &amp; B &lt;Test&gt;</Name>'), true, 'Yealink name escaping');
$assert(str_contains($xml, '<Telephone label="Mobil">+49123</Telephone>'), true, 'Yealink mobile label');
$assert(str_contains($xml, 'label="Büro &amp; Team"'), true, 'Yealink label escaping');
$assert(substr_count($xml, '<Telephone'), 2, 'Yealink multiple numbers');

$csv = (new CsvExporter())->render($contacts);
$assert(str_contains($csv, 'Name,Typ,Rufnummer'), true, 'CSV header');
$assert(str_contains($csv, 'Mobil,+49123'), true, 'CSV label');

$tmp = sys_get_temp_dir() . '/phonebook-test-' . bin2hex(random_bytes(6));
mkdir($tmp, 0700, true);
$configFile = $tmp . '/local.php';
$config = <<<'PHP2'
<?php
return [
  'app' => ['base_url' => 'https://example.invalid', 'timezone' => 'Europe/Berlin'],
  'admin' => ['password_hash' => '$2y$10$abcdefghijklmnopqrstuv123456789012345678901234567890'],
  'google' => ['client_id' => 'REPLACE_ME', 'client_secret' => 'REPLACE_ME', 'redirect_uri' => 'REPLACE_ME'],
  'security' => ['cron_token' => 'test-cron-token'],
  'paths' => ['storage' => __DIR__ . '/storage'],
];
PHP2;
file_put_contents($configFile, $config);
$cfg = new Config($configFile);
$settings = new SettingsRepository($cfg);
$devices = new DeviceRepository($cfg);
$blocklist = new BlocklistRepository($cfg, $settings);
$limiter = new LoginRateLimiter($cfg);
for ($i = 0; $i < 8; $i++) {
    $limiter->recordFailure('203.0.113.10');
}
$assert($limiter->isBlocked('203.0.113.10'), true, 'Login limiter blocks after repeated failures');
$limiter->clear('203.0.113.10');
$assert($limiter->isBlocked('203.0.113.10'), false, 'Login limiter clears after successful login');

$device = $devices->save([
    'name' => 'T54W Wohnzimmer', 'type' => 'yealink', 'enabled' => '1',
    'contacts_enabled' => '1', 'blocklist_directory_enabled' => '1',
]);
$assert(strlen((string)$device['token']), 64, 'Device token is 256 bit hex');
$assert($devices->authorize((string)$device['id'], (string)$device['token']) !== null, true, 'Correct device token authorizes');
$assert($devices->authorize((string)$device['id'], 'wrong') === null, true, 'Wrong device token rejected');
$assert((bool)$device['blocklist_directory_enabled'], true, 'Yealink blocklist directory enabled');

$oldToken = (string)$device['token'];
$device2 = $devices->save([
    'id' => $device['id'], 'name' => 'T54W Wohnzimmer', 'type' => 'yealink', 'enabled' => '1',
    'contacts_enabled' => '1', 'blocklist_directory_enabled' => '1', 'regenerate_token' => '1',
]);
$assert(hash_equals($oldToken, (string)$device2['token']), false, 'Token regeneration changes token');
$assert($devices->authorize((string)$device['id'], $oldToken) === null, true, 'Old token invalid after regeneration');

try {
    $devices->save([
        'name' => 'Nicht unterstützt', 'type' => 'unsupported', 'enabled' => '1',
        'contacts_enabled' => '1',
    ]);
    $assert(true, false, 'Unsupported device type rejected');
} catch (RuntimeException) {
    $assert(true, true, 'Unsupported device type rejected');
}

$blocked = $blocklist->save(['number' => '02159 123456', 'label' => 'Werbung', 'enabled' => '1']);
$assert($blocked['number'], '+492159123456', 'Blocklist number normalized');
$assert(count($blocklist->active()), 1, 'Active blocklist count');
$blockContacts = $blocklist->asContacts();
$assert($blockContacts[0]['numbers'][0]['label'], 'Gesperrt', 'Blocklist export label');

try {
    $blocklist->save(['number' => '+492159123456', 'label' => 'Duplicate', 'enabled' => '1']);
    $assert(true, false, 'Duplicate blocklist number should fail');
} catch (RuntimeException) {
    $assert(true, true, 'Duplicate blocklist number rejected');
}

$devices->delete((string)$device2['id']);
$assert($devices->find((string)$device2['id']) === null, true, 'Device delete');

$rm = static function (string $path) use (&$rm): void {
    if (is_dir($path)) {
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $item) {
            $rm($path . '/' . $item);
        }
        rmdir($path);
    } elseif (is_file($path)) {
        unlink($path);
    }
};
$rm($tmp);

echo "OK: {$tests} tests passed\n";
