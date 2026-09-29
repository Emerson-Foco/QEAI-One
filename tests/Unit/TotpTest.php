<?php

namespace Tests\Unit;

use App\Support\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    public function test_generates_and_verifies_codes(): void
    {
        $secret = Totp::generateSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);

        $code = Totp::code($secret);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue(Totp::verify($secret, $code));
        $this->assertFalse(Totp::verify($secret, '000000') && $code !== '000000');
    }

    public function test_provisioning_uri(): void
    {
        $uri = Totp::provisioningUri('ABCDEF234567', 'user@example.com', 'QEAI One');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('ABCDEF234567', $uri);
        $this->assertStringContainsString('issuer=QEAI%20One', $uri);
    }
}
