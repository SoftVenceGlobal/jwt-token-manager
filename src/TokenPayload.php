<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager;

use stdClass;

final class TokenPayload
{
    private stdClass $raw;

    public function __construct(stdClass $payload)
    {
        $this->raw = $payload;
    }

    /**
     * Get the raw payload object.
     */
    public function getRaw(): stdClass
    {
        return $this->raw;
    }

    /**
     * Get the subject (sub) claim - typically user ID.
     */
    public function getSubject(): string
    {
        return $this->raw->sub;
    }

    /**
     * Get the issuer (iss) claim.
     */
    public function getIssuer(): string
    {
        return $this->raw->iss;
    }

    /**
     * Get the audience (aud) claim.
     *
     * @return array<string>
     */
    public function getAudience(): array
    {
        return is_array($this->raw->aud) ? $this->raw->aud : [$this->raw->aud];
    }

    /**
     * Get the JWT ID (jti) claim.
     */
    public function getJti(): string
    {
        return $this->raw->jti;
    }

    /**
     * Get the session ID (sid) claim if present.
     */
    public function getSessionId(): ?string
    {
        return $this->raw->sid ?? null;
    }

    /**
     * Get the token type (typ) claim.
     */
    public function getType(): string
    {
        return $this->raw->typ ?? 'access';
    }

    /**
     * Get the issued at (iat) timestamp.
     */
    public function getIssuedAt(): int
    {
        return $this->raw->iat;
    }

    /**
     * Get the not before (nbf) timestamp.
     */
    public function getNotBefore(): int
    {
        return $this->raw->nbf;
    }

    /**
     * Get the expiration (exp) timestamp.
     */
    public function getExpiration(): int
    {
        return $this->raw->exp;
    }

    /**
     * Check if token is expired.
     */
    public function isExpired(): bool
    {
        return time() > $this->raw->exp;
    }

    /**
     * Get a custom claim by name.
     */
    public function getClaim(string $name): mixed
    {
        return $this->raw->{$name} ?? null;
    }

    /**
     * Check if a claim exists.
     */
    public function hasClaim(string $name): bool
    {
        return isset($this->raw->{$name});
    }

    /**
     * Get all claims as an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return (array) $this->raw;
    }
}
