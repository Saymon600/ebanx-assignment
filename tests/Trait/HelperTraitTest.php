<?php

namespace Tests\Trait;
use PHPUnit\Framework\TestCase;
use App\Trait\HelperTrait;

class HelperTraitWrapper {
    use HelperTrait;
}

class HelperTraitTest extends TestCase {
    private HelperTraitWrapper $helper;
    protected function setUp(): void {
        parent::setUp();
        apcu_clear_cache();
        $this->helper = new HelperTraitWrapper();
    }

    public function testGetAccountDataReturnsNullWhenAccountDoesNotExist(): void{
        $result = $this->helper->getAccountData(9999);
        $this->assertNull($result);
    }

    public function testGetAccountDataReturnsDataWhenAccountExists(): void {
        apcu_store('account:100', ['id' => '100', 'balance' => 100.00]);
        $result = $this->helper->getAccountData(100);

        $this->assertNotNull($result);
        $this->assertSame('100', $result['id']);
        $this->assertSame(100.00, $result['balance']);
    }

    public function testGetAccountDataReturnsNullAfterCacheIsCleared(): void {
        apcu_store('account:700', ['id' => '700', 'balance' => 9.99]);
        apcu_clear_cache();

        $result = $this->helper->getAccountData(700);
        $this->assertNull($result);
    }

    public function testValidateIdAcceptsPositiveIntegers(): void {
        $this->assertTrue($this->helper->validateId(1));
        $this->assertTrue($this->helper->validateId(333));
        $this->assertTrue($this->helper->validateId(9999));
        $this->assertTrue($this->helper->validateId('42'));
    }

    public function testValidateIdRejectsZeroAndNegativeNumbers(): void {
        $this->assertFalse($this->helper->validateId("0"));
        $this->assertFalse($this->helper->validateId(0));
        $this->assertFalse($this->helper->validateId(-1));
        $this->assertFalse($this->helper->validateId(-100));
        $this->assertFalse($this->helper->validateId(-999));
    }

    public function testValidateIdRejectsNonNumericValues(): void {
        $this->assertFalse($this->helper->validateId('abc'));
        $this->assertFalse($this->helper->validateId(null));
        $this->assertFalse($this->helper->validateId([]));
        $this->assertFalse($this->helper->validateId(1.5));
        $this->assertFalse($this->helper->validateId("10.50abc"));  
        $this->assertFalse($this->helper->validateId(''));
    }

    public function testValidateAmountAcceptsPositiveFloats(): void {
        $this->assertTrue($this->helper->validateAmount(10.0));
        $this->assertTrue($this->helper->validateAmount(0.01));
        $this->assertTrue($this->helper->validateAmount(999.99));
        $this->assertTrue($this->helper->validateAmount("40.55"));
        $this->assertTrue($this->helper->validateAmount(1000));
    }

    public function testValidateAmountRejectsZeroAndNegativeAmounts(): void {
        $this->assertFalse($this->helper->validateAmount(0));
        $this->assertFalse($this->helper->validateAmount(-50.0));
        $this->assertFalse($this->helper->validateAmount(-0.45));
    }

    public function testValidateAmountRejectsNonNumericValues(): void {
        $this->assertFalse($this->helper->validateAmount('abc'));
        $this->assertFalse($this->helper->validateAmount(null));
        $this->assertFalse($this->helper->validateAmount([]));
        $this->assertFalse($this->helper->validateAmount("10.50abc"));  
        $this->assertFalse($this->helper->validateAmount(''));
    }

    public function testErrorResponseReturnsCorrectStructure(): void {
        [$errorCode,$responseBody] = $this->helper->errorResponse(400,'Unit Test Error.');
        $this->assertSame(400,$errorCode);

        $decoded = json_decode($responseBody,true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('message',$decoded);
        $this->assertSame('Unit Test Error.',$decoded['message']);
    }
    
    public function testErrorResponseHandlesDifferentStatusCodes(): void {
        [$errorCode, $responseBody] = $this->helper->errorResponse(404, 'Not found.');
        $this->assertSame(404, $errorCode);
        $this->assertStringContainsString('Not found.', $responseBody);

        [$errorCode500, $responseBody500] = $this->helper->errorResponse(500, 'Server error.');
        $this->assertSame(500, $errorCode500);
        $this->assertStringContainsString('Server error.', $responseBody500);
    }

    public function testErrorResponseReturnsValidJson(): void {
        [$errorCode, $responseBody] = $this->helper->errorResponse(400, 'Test');
        $this->assertNotNull(json_decode($responseBody), 'Response must be valid JSON');
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
    }

    public function testCreateAccountStoresAccountWithZeroBalance(): void {
        $result = $this->helper->createAccount(42);
        $this->assertNotNull($result);
        $this->assertSame('42', $result['id']);
        $this->assertSame(0, $result['balance']);

        $stored = apcu_fetch('account:42');
        $this->assertNotFalse($stored);
        $this->assertSame('42', $stored['id']);
        $this->assertSame(0, $stored['balance']);
    }

    public function testCreateAccountReturnsAccountData(): void {
        $result = $this->helper->createAccount(100);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id',$result);
        $this->assertArrayHasKey('balance',$result);
    }

    public function testCreateAccountCanStoreMultipleAccounts(): void {
        $this->helper->createAccount(1);
        $this->helper->createAccount(2);
        $this->helper->createAccount(3);

        $this->assertNotFalse(apcu_fetch('account:1'));
        $this->assertNotFalse(apcu_fetch('account:2'));
        $this->assertNotFalse(apcu_fetch('account:3'));
    }
}