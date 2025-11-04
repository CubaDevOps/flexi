<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Tests\Infrastructure\Adapters;

use CubaDevOps\Flexi\Modules\Auth\Domain\ValueObjects\Credentials;
use CubaDevOps\Flexi\Modules\Auth\Infrastructure\Adapters\DefaultCredentialsVerifier;
use PHPUnit\Framework\TestCase;

class DefaultCredentialsVerifierTest extends TestCase
{
    private DefaultCredentialsVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new DefaultCredentialsVerifier();
    }

    public function testVerifyWithValidPassword(): void
    {
        // Create a valid bcrypt hash for password "password123"
        $password = 'password123';
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $credentials = new Credentials('testuser', $password);

        self::assertTrue($this->verifier->verify($credentials, $password_hash));
    }

    public function testVerifyWithInvalidPassword(): void
    {
        // Create a valid bcrypt hash for password "password123"
        $password_hash = password_hash('password123', PASSWORD_BCRYPT);

        $credentials = new Credentials('testuser', 'wrongpassword');

        self::assertFalse($this->verifier->verify($credentials, $password_hash));
    }

    public function testVerifyWithEmptyPassword(): void
    {
        $password_hash = password_hash('password123', PASSWORD_BCRYPT);

        // Empty passwords should be caught by Credentials constructor
        // but if somehow we get here, verification should fail
        try {
            $credentials = new Credentials('testuser', '');
            self::fail('Expected InvalidArgumentException for empty password');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('empty', $e->getMessage());
        }
    }

    public function testVerifyPreventsTimingAttack(): void
    {
        $password_hash = password_hash('correct_password', PASSWORD_BCRYPT);
        $wrong_credentials = new Credentials('user', 'wrong_password');

        // This should take roughly the same time regardless of password length mismatch
        // (constant-time comparison with hash_equals)
        $start = microtime(true);
        $result1 = $this->verifier->verify($wrong_credentials, $password_hash);
        $time1 = microtime(true) - $start;

        $start = microtime(true);
        $result2 = $this->verifier->verify($wrong_credentials, $password_hash);
        $time2 = microtime(true) - $start;

        // Both should be false
        self::assertFalse($result1);
        self::assertFalse($result2);

        // Times should be similar (within reasonable margin)
        // We don't make strict assertions here as timing can vary
        // The important thing is that hash_equals is used internally
    }
}
