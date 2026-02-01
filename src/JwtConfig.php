<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager;

final class JwtConfig
{
    private const DEFAULT_TTL_MINUTES = 60;
    private const DEFAULT_REFRESH_TTL_MINUTES = 20160;
    private const DEFAULT_REQUIRED_CLAIMS = ['iss', 'jti', 'exp', 'iat', 'typ', 'sub'];

    public function __construct(
        private readonly string $privateKey,
        private readonly string $publicKey,
        private readonly string $issuer,
        private readonly Algorithm $algorithm = Algorithm::RS256,
        private readonly int $ttlMinutes = self::DEFAULT_TTL_MINUTES,
        private readonly int $refreshTtlMinutes = self::DEFAULT_REFRESH_TTL_MINUTES,
        private readonly ?array $audience = null,
        private readonly array $requiredClaims = self::DEFAULT_REQUIRED_CLAIMS
    ) {
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getAlgorithm(): Algorithm
    {
        return $this->algorithm;
    }

    public function getAlgorithmValue(): string
    {
        return $this->algorithm->value;
    }

    public function getTtlMinutes(): int
    {
        return $this->ttlMinutes;
    }

    public function getTtlSeconds(): int
    {
        return $this->ttlMinutes * 60;
    }

    public function getRefreshTtlMinutes(): int
    {
        return $this->refreshTtlMinutes;
    }

    public function getRefreshTtlSeconds(): int
    {
        return $this->refreshTtlMinutes * 60;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function getAudience(): ?array
    {
        return $this->audience;
    }

    public function getRequiredClaims(): array
    {
        return $this->requiredClaims;
    }

    public static function fromArray(array $config): self
    {
        $algorithm = isset($config['algorithm'])
            ? (is_string($config['algorithm']) ? Algorithm::from($config['algorithm']) : $config['algorithm'])
            : Algorithm::RS256;

        return new self(
            privateKey: $config['private_key'],
            publicKey: $config['public_key'],
            issuer: $config['issuer'],
            algorithm: $algorithm,
            ttlMinutes: $config['ttl_minutes'] ?? self::DEFAULT_TTL_MINUTES,
            refreshTtlMinutes: $config['refresh_ttl_minutes'] ?? self::DEFAULT_REFRESH_TTL_MINUTES,
            audience: isset($config['audience']) ? (array) $config['audience'] : null,
            requiredClaims: $config['required_claims'] ?? self::DEFAULT_REQUIRED_CLAIMS
        );
    }

    public static function fromKeyFiles(
        string $privateKeyPath,
        string $publicKeyPath,
        string $issuer,
        Algorithm $algorithm = Algorithm::RS256,
        int $ttlMinutes = self::DEFAULT_TTL_MINUTES,
        int $refreshTtlMinutes = self::DEFAULT_REFRESH_TTL_MINUTES,
        ?array $audience = null,
        array $requiredClaims = self::DEFAULT_REQUIRED_CLAIMS
    ): self {
        return new self(
            privateKey: file_get_contents($privateKeyPath),
            publicKey: file_get_contents($publicKeyPath),
            issuer: $issuer,
            algorithm: $algorithm,
            ttlMinutes: $ttlMinutes,
            refreshTtlMinutes: $refreshTtlMinutes,
            audience: $audience,
            requiredClaims: $requiredClaims
        );
    }
}
