<?php

namespace Tests\Service;

use App\Service\ResetService;
use PHPUnit\Framework\TestCase;

class ResetServiceTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        apcu_clear_cache();
    }

    public function testResetClearsAllData(): void {
        apcu_store("account:100", ["id" => "100", "balance" => 10.00]);
        apcu_store("account:300", ["id" => "300", "balance" => 20.50]);
        apcu_store('dummy_key', 'test');

        $this->assertNotFalse(apcu_fetch('account:100'), 'Condition: data should exist before reset.');
        $this->assertNotFalse(apcu_fetch('account:300'), 'Condition: data should exist before reset.');
        $this->assertNotFalse(apcu_fetch('dummy_key'), 'Condition: data should exist before reset.');

        $result = ResetService::resetAll();

        $this->assertTrue($result, 'resetAll() must return true on success.');
        $this->assertFalse(apcu_fetch('account:100'), 'account:100 must be empty.');
        $this->assertFalse(apcu_fetch('account:300'), 'account:300 must be empty.');
        $this->assertFalse(apcu_fetch('dummy_key'), 'some_key must be empty.');
    }

    public function testApcuExtensionIsAvailable(): void {
        $this->assertTrue(extension_loaded('apcu'), 'APCu extension must be loaded.');
    }
}