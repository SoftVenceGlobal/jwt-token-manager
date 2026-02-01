<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager\Exceptions;

final class InvalidClaimException extends JwtException
{
    protected string $errorKey = 'invalidClaim';

    public function __construct(
        private readonly string $claimName,
        private readonly mixed $actualValue,
        private readonly mixed $expectedValue = null
    ) {
        $message = sprintf(
            'Invalid claim "%s": got "%s"',
            $this->claimName,
            is_array($this->actualValue) ? json_encode($this->actualValue) : (string) $this->actualValue
        );

        if ($this->expectedValue !== null) {
            $message .= sprintf(
                ', expected "%s"',
                is_array($this->expectedValue) ? json_encode($this->expectedValue) : (string) $this->expectedValue
            );
        }

        parent::__construct($message);
    }

    public function getClaimName(): string
    {
        return $this->claimName;
    }

    public function getActualValue(): mixed
    {
        return $this->actualValue;
    }

    public function getExpectedValue(): mixed
    {
        return $this->expectedValue;
    }
}
