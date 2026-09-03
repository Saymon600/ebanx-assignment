<?php

use Slim\Factory\AppFactory;
require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$apiRoutes = require_once __DIR__ . '/routes/api.php';
$apiRoutes($app);

$app->run();
