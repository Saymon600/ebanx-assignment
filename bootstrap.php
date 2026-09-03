<?php

use Slim\Factory\AppFactory;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Psr7\Response as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setErrorHandler(
    HttpException::class,
    function (Request $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails) {
        $response = new Response();
        $response->getBody()->write('0');

        if($exception instanceof HttpMethodNotAllowedException){
            return $response->withStatus(405);  
        }
        
        return $response->withStatus(404);
    },
    true
);

$apiRoutes = require_once __DIR__ . '/routes/api.php';
$apiRoutes($app);

$app->run();
