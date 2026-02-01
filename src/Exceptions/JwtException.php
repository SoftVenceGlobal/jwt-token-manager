<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager\Exceptions;

use Exception;

abstract class JwtException extends Exception
{
    protected string $errorKey = 'jwtGenericError';

    public function getErrorKey(): string
    {
        return $this->errorKey;
    }
}
