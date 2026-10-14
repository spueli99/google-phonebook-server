<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
$app->admin->logout();
header('Location: /login.php', true, 303);
exit;
