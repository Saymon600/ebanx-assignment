<?php

use App\Controller\EventController;

return function (Slim\App $app) {
    $app->get('/',[EventController::class, 'index']);
    $app->get('/reset',[EventController::class, 'reset']);
    $app->get('/balance',[EventController::class, 'balance']);
};