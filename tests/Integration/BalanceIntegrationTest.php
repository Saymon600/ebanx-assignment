<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;

class BalanceIntegrationTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        apcu_clear_cache();
    }

    private function createAppAndRequest(string $method, string $uri): array{
        $app = AppFactory::create();

        $apiRoutes = require __DIR__ . '/../../routes/api.php';
        $apiRoutes($app);

        $request = (new \Slim\Psr7\Factory\ServerRequestFactory())
            ->createServerRequest($method, $uri);

        $response = $app->handle($request);
        return [$response, (string)$response->getBody()];
    }

    public function testBalanceReturns200ForExistingAccount(): void {
        apcu_store('account:100', ['id' => '100', 'balance' => 555.55]);

        [$response, $body] = $this->createAppAndRequest('GET', '/balance?account_id=100');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('555.55', $body);
    }

    public function testBalanceReturns404ForMissingAccount(): void {
        [$response, $body] = $this->createAppAndRequest('GET', '/balance?account_id=99999');
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('0', $body);
    }

    public function testBalanceReturns404WhenAccountIdMissing(): void {
        [$response, $body] = $this->createAppAndRequest('GET', '/balance');
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('0', $body);
    }

    public function testBalanceReturns200WithZeroBalance(): void {
        apcu_store('account:200', ['id' => '200', 'balance' => 0.00]);
        [$response, $body] = $this->createAppAndRequest('GET', '/balance?account_id=200');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('0.00', $body);
    }
}