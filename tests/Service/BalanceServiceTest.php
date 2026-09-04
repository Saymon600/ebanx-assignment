<?php

namespace Tests\Service;

use App\Service\BalanceService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;

class BalanceServiceTest extends TestCase {
    private BalanceService $service;

    protected function setUp(): void {
        parent::setUp();
        apcu_clear_cache();
        $this->service = new BalanceService();
    }

    public function testReturns404WhenAccountIdIsMissing(): void {
        $request = $this->createStub(Request::class);
        $request->method('getQueryParams')->willReturn([]);

        [$statusCode, $body] = $this->service->checkBalance($request);
        $this->assertSame(404, $statusCode);
        $this->assertSame('0', $body);
    }

    public function testReturns404WhenAccountIdIsInvalid(): void {
        $request = $this->createStub(Request::class);
        $request->method('getQueryParams')->willReturn(['account_id' => 'aaa']);

        [$statusCode, $body] = $this->service->checkBalance($request);
        $this->assertSame(404, $statusCode);
        $this->assertSame('0', $body);
    }

    public function testReturns404WhenAccountIdIsZero(): void {
        $request = $this->createStub(Request::class);
        $request->method('getQueryParams')->willReturn(['account_id' => '0']);

        [$statusCode, $body] = $this->service->checkBalance($request);
        $this->assertSame(404, $statusCode);
        $this->assertSame('0', $body);
    }

    public function testReturns404WhenAccountIdIsNegative(): void {
        $request = $this->createStub(Request::class);
        $request->method('getQueryParams')->willReturn(['account_id' => '-5']);

        [$statusCode, $body] = $this->service->checkBalance($request);
        $this->assertSame(404, $statusCode);
        $this->assertSame('0', $body);
    }

    public function testReturns404WhenAccountDoesNotExist(): void {
        $request = $this->createStub(Request::class);
        $request->method('getQueryParams')->willReturn(['account_id' => '99999']);

        [$statusCode, $body] = $this->service->checkBalance($request);
        $this->assertSame(404, $statusCode);
        $this->assertSame('0', $body);
    }

    public function testReturns200AndFormattedBalanceForExistingAccount(): void {
        apcu_store('account:100', ['id' => '100', 'balance' => 150.50]);
        $request = $this->createStub(Request::class);
        $request->method('getQueryParams')->willReturn(['account_id' => '100']);

        [$statusCode, $body] = $this->service->checkBalance($request);
        $this->assertSame(200, $statusCode);
        $this->assertSame('150.50', $body);
    }

    public function testReturns200WithZeroBalanceWhenBalanceIsZero(): void {
        apcu_store('account:200', ['id' => '200', 'balance' => 0.00]);
        $request = $this->createStub(Request::class);
        $request->method('getQueryParams')->willReturn(['account_id' => '200']);

        [$statusCode, $body] = $this->service->checkBalance($request);
        $this->assertSame(200, $statusCode);
        $this->assertSame('0.00', $body);
    }
}