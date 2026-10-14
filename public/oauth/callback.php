<?php
declare(strict_types=1);
require dirname(__DIR__) . '/_bootstrap.php';

use Phonebook\View;

$app->admin->requireAuthenticated();
$app->admin->startSession();
$expected = (string)($_SESSION['oauth_state'] ?? '');
$created = (int)($_SESSION['oauth_state_created'] ?? 0);
$actual = (string)($_GET['state'] ?? '');
unset($_SESSION['oauth_state'], $_SESSION['oauth_state_created']);

View::header('Google OAuth');
try {
    if ($expected === '' || $actual === '' || !hash_equals($expected, $actual) || (time() - $created) > 600) {
        throw new RuntimeException('OAuth state validation failed or expired.');
    }
    if (!empty($_GET['error'])) {
        throw new RuntimeException('Google OAuth error: ' . (string)$_GET['error']);
    }
    $code = (string)($_GET['code'] ?? '');
    if ($code === '') {
        throw new RuntimeException('Google OAuth code is missing.');
    }
    $app->oauth->exchangeCode($code);
    $result = $app->syncService()->run('oauth');
    echo '<div class="alert ok">Google wurde verbunden. ' . (int)$result['contacts'] . ' Kontakte wurden synchronisiert.</div>';
} catch (Throwable $e) {
    http_response_code(400);
    echo '<div class="alert bad">' . View::e($e->getMessage()) . '</div>';
}
echo '<p><a class="button secondary" href="/">Zurück</a></p>';
View::footer();
