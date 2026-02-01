<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager\Exceptions;

final class MissingClaimsException extends JwtException
{
    protected string $errorKey = 'missingClaims';

    /**
     * @param array<string> $claims
     */
    public function __construct(
        private readonly array $claims
    ) {
        parent::__construct('Token is missing required claims: ' . implode(', ', $claims));
    }

    /**
     * @return array<string>
     */
    public function getMissingClaims(): array
    {
        return $this->claims;
    }
}
