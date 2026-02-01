<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager\Exceptions;

final class ExpiredTokenException extends JwtException
{
    protected string $errorKey = 'expiredToken';

    public function __construct(string $message = 'Token has expired')
    {
        parent::__construct($message);
    }
}
