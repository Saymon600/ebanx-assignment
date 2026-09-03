<?php

use App\Controller\EventController;

return function (Slim\App $app) {
    $app->get('/',[EventController::class, 'index']);
    $app->get('/balance',[EventController::class, 'balance']);
    $app->post('/reset',[EventController::class, 'reset']);
    $app->post('/event',[EventController::class, 'event']);
};