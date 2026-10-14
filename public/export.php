<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

use Phonebook\Export\YealinkExporter;

$id = trim((string)($_GET['device'] ?? ''));
$token = trim((string)($_GET['token'] ?? ''));
$book = strtolower(trim((string)($_GET['book'] ?? 'contacts')));

if ($id === '' || $token === '') {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Device authentication required\n");
}

$device = $app->devices->authorize($id, $token);
if ($device === null) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Forbidden\n");
}

$type = (string)($device['type'] ?? '');
if ($book === 'contacts') {
    if (empty($device['contacts_enabled'])) {
        http_response_code(404);
        exit('Contact directory disabled');
    }
    if (empty($app->state->all()['last_sync'])) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        exit("Contact cache is not synchronized yet.\n");
    }
    $contacts = $app->contacts->all();
    $exporter = match ($type) {
        'yealink' => new YealinkExporter(),
        default => null,
    };
} elseif ($book === 'blocklist') {
    if ($type !== 'yealink' || empty($device['blocklist_directory_enabled'])) {
        http_response_code(404);
        exit('Blocklist directory disabled');
    }
    $contacts = $app->blocklist->asContacts();
    $exporter = new YealinkExporter();
} else {
    http_response_code(404);
    exit('Unknown directory');
}

if ($exporter === null) {
    http_response_code(404);
    exit('Unsupported device type');
}

$payload = $exporter->render($contacts);
$app->devices->recordAccess($id, $book);
header('Content-Type: ' . $exporter->contentType());
header('Cache-Control: private, max-age=300');
header('X-Robots-Tag: noindex, nofollow, noarchive');
echo $payload;
