<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager\Tests\Unit;

use DevToolbelt\JwtTokenManager\Exceptions\InvalidClaimException;
use DevToolbelt\JwtTokenManager\Exceptions\JwtException;
use DevToolbelt\JwtTokenManager\Tests\TestCase;

final class InvalidClaimExceptionTest extends TestCase
{
    public function testExceptionExtendsJwtException(): void
    {
        $exception = new InvalidClaimException('iss', 'actual', 'expected');

        $this->assertInstanceOf(JwtException::class, $exception);
    }

    public function testMessageIncludesExpectedValueWhenProvided(): void
    {
        $exception = new InvalidClaimException('aud', ['app-a'], ['app-b']);

        $this->assertEquals(
            'Invalid claim "aud": got "["app-a"]", expected "["app-b"]"',
            $exception->getMessage()
        );
    }

    public function testMessageOmitsExpectedValueWhenNull(): void
    {
        $exception = new InvalidClaimException('typ', 'refresh', null);

        $this->assertEquals('Invalid claim "typ": got "refresh"', $exception->getMessage());
    }

    public function testAccessorsReturnValues(): void
    {
        $exception = new InvalidClaimException('iss', 'https://api.example.com', 'https://api.other.com');

        $this->assertEquals('iss', $exception->getClaimName());
        $this->assertEquals('https://api.example.com', $exception->getActualValue());
        $this->assertEquals('https://api.other.com', $exception->getExpectedValue());
        $this->assertEquals('invalidClaim', $exception->getErrorKey());
    }
}
