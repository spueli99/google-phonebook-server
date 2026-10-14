<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

$expected = (string)$app->config->get('security.cron_token');
$actual = (string)($_GET['token'] ?? '');
if ($actual === '' || !hash_equals($expected, $actual)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Forbidden\n");
}

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
try {
    $result = $app->syncService()->run('cron');
    echo 'OK contacts=' . (int)$result['contacts'] . ' numbers=' . (int)$result['numbers'] . ' duration_ms=' . (int)$result['duration_ms'] . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR ' . $e->getMessage() . "\n";
}
