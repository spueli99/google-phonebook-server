<?php
declare(strict_types=1);

use Phonebook\App;
use Phonebook\Http;

$root = dirname(__DIR__);
require_once $root . '/src/autoload.php';
Http::securityHeaders();
$app = new App($root);
