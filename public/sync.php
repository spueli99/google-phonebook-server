<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

use Phonebook\Security\Csrf;
use Phonebook\View;

$app->admin->requireAuthenticated();
$app->admin->startSession();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? null)) {
    http_response_code(400);
    exit('Invalid request');
}

View::header('Synchronisierung');
try {
    $result = $app->syncService()->run('manual');
    echo '<div class="alert ok">Synchronisierung erfolgreich: ' . (int)$result['contacts'] . ' Kontakte, ' . (int)$result['numbers'] . ' Rufnummern in ' . (int)$result['duration_ms'] . ' ms.</div>';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<div class="alert bad"><strong>Synchronisierung fehlgeschlagen:</strong> ' . View::e($e->getMessage()) . '</div>';
}
echo '<p><a class="button secondary" href="/">Zurück</a></p>';
View::footer();
