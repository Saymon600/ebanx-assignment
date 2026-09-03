<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Service\ResetService;
use App\Service\BalanceService;
use App\Service\EventService;

class EventController {
    public function index(Request $request, Response $response): Response {
        $response->getBody()->write("Hello");
        return $response 
            ->withStatus(200);
            // ->withHeader('Content-Type','application/json');
    }

    public function reset(Request $request, Response $response): Response {
        ResetService::resetAll();

        $response->getBody()->write("OK");
        return $response 
            ->withStatus(200);
    }

    public function balance(Request $request, Response $response): Response {
        $balanceService = new BalanceService();
        [$responseCode,$responseBody] = $balanceService->checkBalance($request);
        $response->getBody()->write($responseBody);
        return $response 
            ->withStatus($responseCode);
    }

    public function event(Request $request, Response $response): Response {
        $eventService = new EventService();
        [$responseCode,$responseBody] = $eventService->processEvent($request);
        $response->getBody()->write($responseBody);

        if(in_array($responseCode,[201,400])){
            return $response 
                ->withStatus($responseCode)
                ->withHeader('Content-Type','application/json');
        }

        return $response 
            ->withStatus(404);
    }
}
