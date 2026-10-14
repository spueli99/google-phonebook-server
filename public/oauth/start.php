<?php
declare(strict_types=1);
require dirname(__DIR__) . '/_bootstrap.php';

$app->admin->requireAuthenticated();
$app->admin->startSession();
$state = bin2hex(random_bytes(32));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_state_created'] = time();
header('Location: ' . $app->oauth->authorizationUrl($state), true, 302);
exit;
