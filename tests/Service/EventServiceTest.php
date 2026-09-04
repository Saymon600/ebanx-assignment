<?php

namespace Tests\Service;
use App\Service\EventService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamInterface;

class EventServiceTest extends TestCase {
    private EventService $service;
    protected function setUp(): void {
        parent::setUp();
        apcu_clear_cache();
        $this->service = new EventService();
    }

    private function createRequestWithBody(string $jsonBody): Request {
        $stream = $this->createStub(StreamInterface::class);
        $stream->method('__toString')->willReturn($jsonBody);

        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn($stream);

        return $request;
    }

    public function testReturns400ForEmptyBody(): void {
        $request = $this->createRequestWithBody('');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(400, $statusCode);
        $this->assertStringContainsString('Invalid JSON', $body);
    }

    public function testReturns400ForInvalidJson(): void {
        $request = $this->createRequestWithBody('not-json');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(400, $statusCode);
        $this->assertStringContainsString('Invalid JSON', $body);
    }

    public function testReturns400ForMissingType(): void {
        $request = $this->createRequestWithBody('{"destination": 100, "amount": 50}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(400, $statusCode);
        $this->assertStringContainsString('Invalid Type', $body);
    }

    public function testReturns400ForInvalidType(): void {
        $request = $this->createRequestWithBody('{"type":"saque","destination":100,"amount":50}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(400, $statusCode);
        $this->assertStringContainsString('Invalid Type', $body);
    }

    public function testDepositReturns400ForInvalidDestination(): void {
        $request = $this->createRequestWithBody('{"type":"deposit","destination":"abc","amount":50}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(400, $statusCode);
        $this->assertStringContainsString('Invalid destination', $body);
    }

    public function testDepositReturns400ForInvalidAmount(): void {
        $request = $this->createRequestWithBody('{"type":"deposit","destination":100,"amount":-5}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(400, $statusCode);
        $this->assertStringContainsString('Invalid amount', $body);
    }

    public function testDepositCreatesNewAccountAndReturns201(): void {
        $request = $this->createRequestWithBody('{"type":"deposit","destination":100,"amount":50.00}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(201, $statusCode);

        $decoded = json_decode($body, true);
        $this->assertArrayHasKey('destination', $decoded);
        $this->assertSame('100', $decoded['destination']['id']);
        $this->assertEquals(50.00, $decoded['destination']['balance']);

        $stored = apcu_fetch('account:100');
        $this->assertNotFalse($stored);
        $this->assertEquals(50.00, $stored['balance']);
    }

    public function testDepositAddsToExistingAccount(): void {
        apcu_store('account:200', ['id' => '200', 'balance' => 100.00]);
        $request = $this->createRequestWithBody('{"type":"deposit","destination":200,"amount":25.50}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(201, $statusCode);

        $decoded = json_decode($body, true);
        $this->assertSame(125.50, $decoded['destination']['balance']);
    }

    public function testDepositAmountWithManyDecimalsIsFormattedToTwo(): void {
        $request = $this->createRequestWithBody('{"type":"deposit","destination":300,"amount":99.999}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(201, $statusCode);

        $decoded = json_decode($body, true);
        $this->assertEquals(100.00, $decoded['destination']['balance']);
    }

    public function testWithdrawReturns400ForInvalidOrigin(): void {
        $request = $this->createRequestWithBody('{"type":"withdraw","origin":"abc","amount":50}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(400, $statusCode);
        $this->assertStringContainsString('Invalid origin', $body);
    }

    public function testWithdrawReturns400ForInvalidAmount(): void {
        $request = $this->createRequestWithBody('{"type":"withdraw","origin":100,"amount":-5}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(400, $statusCode);
        $this->assertStringContainsString('Invalid amount', $body);
    }

    public function testWithdrawReturns404WhenAccountDoesNotExist(): void {
        $request = $this->createRequestWithBody('{"type":"withdraw","origin":999,"amount":10}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(404, $statusCode);
        $this->assertSame('0', $body);
    }

    public function testWithdrawReturns400ForInsufficientBalance(): void {
        apcu_store('account:50', ['id' => '50', 'balance' => 10.00]);
        $request = $this->createRequestWithBody('{"type":"withdraw","origin":50,"amount":20}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(400, $statusCode);
        $this->assertStringContainsString('Insufficient balance', $body);
    }

    public function testWithdrawReturns201AndDeductsBalance(): void {
        apcu_store('account:50', ['id' => '50', 'balance' => 100.00]);
        $request = $this->createRequestWithBody('{"type":"withdraw","origin":50,"amount":30.00}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(201, $statusCode);

        $decoded = json_decode($body, true);
        $this->assertArrayHasKey('origin', $decoded);
        $this->assertSame('50', $decoded['origin']['id']);
        $this->assertEquals(70.00, $decoded['origin']['balance']);
    }

    public function testWithdrawCanDrainAccountToZero(): void {
        apcu_store('account:60', ['id' => '60', 'balance' => 50.00]);
        $request = $this->createRequestWithBody('{"type":"withdraw","origin":60,"amount":50.00}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(201, $statusCode);

        $decoded = json_decode($body, true);
        $this->assertEquals(0.00, $decoded['origin']['balance']);
    }

    public function testTransferReturns400ForInvalidDestination(): void {
        $request = $this->createRequestWithBody('{"type":"transfer","origin":100,"destination":"abc","amount":10}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(400, $statusCode);
        $this->assertStringContainsString('Invalid destination', $body);
    }

    public function testTransferReturns400ForInvalidOrigin(): void {
        $request = $this->createRequestWithBody('{"type":"transfer","origin":"abc","destination":200,"amount":10}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(400, $statusCode);
        $this->assertStringContainsString('Invalid origin', $body);
    }

    public function testTransferReturns400ForInvalidAmount(): void {
        $request = $this->createRequestWithBody('{"type":"transfer","origin":100,"destination":200,"amount":-5}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(400, $statusCode);
        $this->assertStringContainsString('Invalid amount', $body);
    }

    public function testTransferReturns404WhenOriginDoesNotExist(): void {
        $request = $this->createRequestWithBody('{"type":"transfer","origin":999,"destination":200,"amount":10}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(404, $statusCode);
        $this->assertSame('0', $body);
    }

    public function testTransferReturns400ForInsufficientOriginBalance(): void {
        apcu_store('account:100', ['id' => '100', 'balance' => 5.00]);
        $request = $this->createRequestWithBody('{"type":"transfer","origin":100,"destination":200,"amount":10}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(400, $statusCode);
        $this->assertStringContainsString('Insufficient balance', $body);
    }

    public function testTransferCreatesDestinationIfNotExistsAndReturns201(): void {
        apcu_store('account:100', ['id' => '100', 'balance' => 100.00]);
        $request = $this->createRequestWithBody('{"type":"transfer","origin":100,"destination":999,"amount":25.00}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(201, $statusCode);

        $decoded = json_decode($body, true);
        $this->assertArrayHasKey('origin', $decoded);
        $this->assertArrayHasKey('destination', $decoded);
        $this->assertEquals(75.00, $decoded['origin']['balance']);
        $this->assertEquals(25.00, $decoded['destination']['balance']);
    }

    public function testTransferMovesMoneyBetweenExistingAccounts(): void {
        apcu_store('account:100', ['id' => '100', 'balance' => 100.00]);
        apcu_store('account:200', ['id' => '200', 'balance' => 50.00]);
        $request = $this->createRequestWithBody('{"type":"transfer","origin":100,"destination":200,"amount":30.00}');

        [$statusCode, $body] = $this->service->processEvent($request);
        $this->assertSame(201, $statusCode);

        $decoded = json_decode($body, true);
        $this->assertEquals(70.00, $decoded['origin']['balance']);
        $this->assertEquals(80.00, $decoded['destination']['balance']);

        $originStored = apcu_fetch('account:100');
        $destStored = apcu_fetch('account:200');
        $this->assertEquals(70.00, $originStored['balance']);
        $this->assertEquals(80.00, $destStored['balance']);
    }
}