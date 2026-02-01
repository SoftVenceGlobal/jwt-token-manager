<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager\Tests\Unit;

use DevToolbelt\JwtTokenManager\Exceptions\InvalidSignatureException;
use DevToolbelt\JwtTokenManager\Exceptions\JwtException;
use DevToolbelt\JwtTokenManager\Tests\TestCase;

final class InvalidSignatureExceptionTest extends TestCase
{
    public function testExceptionExtendsJwtException(): void
    {
        $exception = new InvalidSignatureException();

        $this->assertInstanceOf(JwtException::class, $exception);
    }

    public function testDefaultMessage(): void
    {
        $exception = new InvalidSignatureException();

        $this->assertEquals(
            'Token signature verification failed. The public key could not validate this token.',
            $exception->getMessage()
        );
    }

    public function testCustomMessage(): void
    {
        $customMessage = 'Custom signature error message';
        $exception = new InvalidSignatureException($customMessage);

        $this->assertEquals($customMessage, $exception->getMessage());
    }

    public function testErrorKey(): void
    {
        $exception = new InvalidSignatureException();

        $this->assertEquals('invalidSignature', $exception->getErrorKey());
    }
}
