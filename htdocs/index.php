<?php

require __DIR__ . '/../c3.php';

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';

$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

require __DIR__ . '/../src/routes.php';

$app->run();

