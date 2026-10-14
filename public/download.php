<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

use Phonebook\Export\CsvExporter;
use Phonebook\Export\JsonExporter;
use Phonebook\Export\YealinkExporter;

$app->admin->requireAuthenticated();
$format = strtolower(trim((string)($_GET['format'] ?? 'json')));
$contacts = $app->contacts->all();
$exporter = match ($format) {
    'json' => new JsonExporter(),
    'csv' => new CsvExporter(),
    'yealink' => new YealinkExporter(),
    default => null,
};
if ($exporter === null) {
    http_response_code(404);
    exit('Unknown export format');
}

$ext = $format === 'csv' ? 'csv' : ($format === 'json' ? 'json' : 'xml');
header('Content-Type: ' . $exporter->contentType());
header('Content-Disposition: attachment; filename="phonebook-' . $format . '.' . $ext . '"');
header('Cache-Control: no-store');
echo $exporter->render($contacts);
