<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EventController
{
    public function index(Request $request, Response $response): Response
    {
        $response->getBody()->write("Hello");
        return $response;
    }
}
