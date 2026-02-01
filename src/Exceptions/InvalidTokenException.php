<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager\Exceptions;

final class InvalidTokenException extends JwtException
{
    protected string $errorKey = 'invalidToken';

    public function __construct(string $message = 'Token is invalid')
    {
        parent::__construct($message);
    }
}
