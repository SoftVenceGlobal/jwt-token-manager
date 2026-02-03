<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager\Tests\Unit;

use DevToolbelt\JwtTokenManager\Exceptions\JwtException;
use DevToolbelt\JwtTokenManager\Exceptions\MissingClaimsException;
use DevToolbelt\JwtTokenManager\Tests\TestCase;

final class MissingClaimsExceptionTest extends TestCase
{
    public function testExceptionExtendsJwtException(): void
    {
        $exception = new MissingClaimsException(['iss', 'sub']);

        $this->assertInstanceOf(JwtException::class, $exception);
    }

    public function testMessageAndMissingClaims(): void
    {
        $exception = new MissingClaimsException(['iss', 'sub']);

        $this->assertEquals('Token is missing required claims: iss, sub', $exception->getMessage());
        $this->assertEquals(['iss', 'sub'], $exception->getMissingClaims());
        $this->assertEquals('missingClaims', $exception->getErrorKey());
    }
}
