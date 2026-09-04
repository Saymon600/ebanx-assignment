<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

class EventIntegrationTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        apcu_clear_cache();
    }

    /**
    * @return array{0: \Psr\Http\Message\ResponseInterface, 1: string}
    */
    private function postJson(string $path, string $jsonBody): array {
        $app = AppFactory::create();
        $apiRoutes = require __DIR__ . '/../../routes/api.php';
        $apiRoutes($app);
        
        $stream = (new StreamFactory())->createStream($jsonBody);
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', $path)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
            
        $response = $app->handle($request);
        return [$response, (string)$response->getBody()];
    }

    public function testDepositCreatesNewAccountAndReturns201WithJson(): void {
        [$response, $body] = $this->postJson(
            '/event',
            '{"type":"deposit","destination":100,"amount":50.00}',
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $decoded = json_decode($body, true);
        $this->assertArrayHasKey('destination', $decoded);
        $this->assertSame('100', $decoded['destination']['id']);
        $this->assertEquals(50.00, $decoded['destination']['balance']);
    }

    public function testDepositAddsToExistingAccount(): void {
        apcu_store('account:200', ['id' => '200', 'balance' => 100.00]);

        [$response, $body] = $this->postJson(
            '/event',
            '{"type":"deposit","destination":200,"amount":25.50}',
        );

        $this->assertSame(201, $response->getStatusCode());
        $decoded = json_decode($body, true);
        $this->assertEquals(125.50, $decoded['destination']['balance']);
    }

    public function testDepositReturns400ForInvalidDestination(): void {
        [$response, $body] = $this->postJson(
            '/event',
            '{"type":"deposit","destination":"abc","amount":50}',
        );

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Invalid destination', $body);
    }

    public function testWithdrawReturns201AndDeductsBalance(): void {
        apcu_store('account:50', ['id' => '50', 'balance' => 100.00]);

        [$response, $body] = $this->postJson(
            '/event',
            '{"type":"withdraw","origin":50,"amount":30.00}',
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $decoded = json_decode($body, true);
        $this->assertArrayHasKey('origin', $decoded);
        $this->assertEquals(70.00, $decoded['origin']['balance']);
    }

    public function testWithdrawReturns404WhenAccountDoesNotExist(): void {
        [$response, $body] = $this->postJson(
            '/event',
            '{"type":"withdraw","origin":999,"amount":10}',
        );
        
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('0', $body);
    }

    public function testWithdrawReturns422ForInsufficientBalance(): void {
        apcu_store('account:50', ['id' => '50', 'balance' => 10.00]);

        [$response, $body] = $this->postJson(
            '/event',
            '{"type":"withdraw","origin":50,"amount":20}',
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $decoded = json_decode($body, true);
        $this->assertArrayHasKey('message', $decoded);
        $this->assertStringContainsString('Insufficient balance', $decoded['message']);
    }


    public function testTransferMovesMoneyBetweenAccounts(): void {
        apcu_store('account:100', ['id' => '100', 'balance' => 100.00]);
        apcu_store('account:200', ['id' => '200', 'balance' => 50.00]);

        [$response, $body] = $this->postJson(
            '/event',
            '{"type":"transfer","origin":100,"destination":200,"amount":30.00}',
        );

        $this->assertSame(201, $response->getStatusCode());
        $decoded = json_decode($body, true);
        $this->assertEquals(70.00, $decoded['origin']['balance']);
        $this->assertEquals(80.00, $decoded['destination']['balance']);
    }

    public function testTransferCreatesDestinationIfNotExists(): void {
        apcu_store('account:100', ['id' => '100', 'balance' => 50.00]);

        [$response, $body] = $this->postJson(
            '/event',
            '{"type":"transfer","origin":100,"destination":999,"amount":25.00}',
        );

        $this->assertSame(201, $response->getStatusCode());
        $decoded = json_decode($body, true);
        $this->assertEquals(25.00, $decoded['origin']['balance']);
        $this->assertEquals(25.00, $decoded['destination']['balance']);
    }

    public function testTransferReturns404WhenOriginDoesNotExist(): void {
        [$response, $body] = $this->postJson(
            '/event',
            '{"type":"transfer","origin":999,"destination":200,"amount":10}',
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('0', $body);
    }

    public function testReturns400ForEmptyBody(): void {
        [$response, $body] = $this->postJson('/event', '');

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid JSON', $body);
    }

    public function testReturns400ForMissingType(): void {
        [$response, $body] = $this->postJson(
            '/event',
            '{"destination": 100, "amount": 50}',
        );

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid Type', $body);
    }
}