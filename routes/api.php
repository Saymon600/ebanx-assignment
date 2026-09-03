<?php

use App\Controller\EventController;

return function (Slim\App $app) {
    $app->get('/',[EventController::class, 'index']);

};