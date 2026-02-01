<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager\Exceptions;

final class InvalidSignatureException extends JwtException
{
    protected string $errorKey = 'invalidSignature';

    public function __construct(string $message = 'Token signature verification failed. The public key could not validate this token.')
    {
        parent::__construct($message);
    }
}
