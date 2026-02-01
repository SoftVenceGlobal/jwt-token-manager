<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager;

use Exception;
use DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use UnexpectedValueException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\SignatureInvalidException;
use DevToolbelt\JwtTokenManager\Exceptions\ExpiredTokenException;
use DevToolbelt\JwtTokenManager\Exceptions\InvalidClaimException;
use DevToolbelt\JwtTokenManager\Exceptions\InvalidSignatureException;
use DevToolbelt\JwtTokenManager\Exceptions\InvalidTokenException;
use DevToolbelt\JwtTokenManager\Exceptions\MissingClaimsException;

/**
 * JWT Token Manager for encoding, decoding and validating JSON Web Tokens.
 *
 * This class provides a complete solution for JWT management including:
 * - Token generation with configurable claims
 * - Token decoding and validation
 * - Refresh token generation
 * - Session and JTI tracking
 */
final class JwtTokenManager
{
    private ?string $lastSessionId = null;
    private ?string $lastJti = null;

    /**
     * Create a new JwtTokenManager instance.
     *
     * @param JwtConfig $config The JWT configuration containing keys, algorithm, and validation rules
     */
    public function __construct(
        private readonly JwtConfig $config
    ) {
    }

    /**
     * Decode and validate a JWT token.
     *
     * Performs the following validations:
     * - Token signature verification
     * - Token expiration (exp claim)
     * - Token not-before time (nbf claim)
     * - Issuer validation (iss claim)
     * - Audience validation (aud claim) - if configured
     * - Token type validation (typ claim)
     * - Required claims presence
     *
     * @param string $token The raw JWT token (without "Bearer " prefix)
     * @return TokenPayload The decoded payload wrapped in a TokenPayload object
     * @throws ExpiredTokenException When the token has expired
     * @throws InvalidSignatureException When the public key cannot validate the token signature
     * @throws InvalidTokenException When the token is malformed or is not yet valid
     * @throws InvalidClaimException When a claim value doesn't match the expected value
     * @throws MissingClaimsException When required claims are missing from the token
     */
    public function decode(string $token): TokenPayload
    {
        try {
            $payload = JWT::decode(
                $token,
                new Key($this->config->getPublicKey(), $this->config->getAlgorithmValue())
            );

            $this->validateRequiredClaims($payload);
            $this->validateIssuer($payload);
            $this->validateAudience($payload);
            $this->validateTokenType($payload);

            return new TokenPayload($payload);
        } catch (ExpiredException) {
            throw new ExpiredTokenException();
        } catch (BeforeValidException) {
            throw new InvalidTokenException('Token is not yet valid');
        } catch (SignatureInvalidException) {
            throw new InvalidSignatureException();
        } catch (DomainException $e) {
            throw new InvalidTokenException($e->getMessage());
        } catch (UnexpectedValueException $e) {
            throw new InvalidTokenException($e->getMessage());
        }
    }

    /**
     * Generate a JWT access token.
     *
     * Creates a new JWT with the following claims:
     *
     * Protected claims (cannot be overridden):
     * - iss: Issuer (from config)
     * - sub: Subject (the provided subject parameter)
     * - iat: Issued at (current timestamp)
     * - exp: Expiration (current timestamp + TTL from config)
     * - jti: JWT ID (unique UUID v7)
     * - sid: Session ID (unique UUID v7)
     *
     * Optional claims with defaults (can be overridden via $customClaims):
     * - aud: Audience (default: from config, if set)
     * - typ: Token type (default: "access")
     * - nbf: Not before (default: current timestamp - 5 seconds for clock skew)
     *
     * @param string $subject The subject identifier (typically user ID or external_id)
     * @param array<string, mixed> $customClaims Additional claims to include in the token payload.
     *                                           Can override optional claims (aud, typ, nbf).
     * @return string The encoded JWT token string
     * @throws Exception
     */
    public function encode(string $subject, array $customClaims = []): string
    {
        $now = new DateTimeImmutable('now', $this->config->getDateTimeZone());
        $timestamp = $now->getTimestamp();

        $this->lastSessionId = Uuid::uuid7()->toString();
        $this->lastJti = Uuid::uuid7()->toString();

        $optionalClaims = [
            'aud' => $this->config->getAudience(),
            'typ' => 'access',
            'nbf' => $timestamp - 5,
        ];

        $protectedClaims = [
            'iss' => $this->config->getIssuer(),
            'sub' => $subject,
            'iat' => $timestamp,
            'exp' => $timestamp + $this->config->getTtlSeconds(),
            'jti' => $this->lastJti,
            'sid' => $this->lastSessionId,
        ];

        $payload = array_filter($optionalClaims, fn ($value) => $value !== null);
        $payload = array_merge($payload, $customClaims);
        $payload = array_merge($payload, $protectedClaims);

        return JWT::encode($payload, $this->config->getPrivateKey(), $this->config->getAlgorithmValue());
    }

    /**
     * Generate a refresh token.
     *
     * Creates a simple refresh token using SHA1 hash of the current timestamp.
     * This token should be stored securely and associated with the user session.
     *
     * @return string A 40-character hexadecimal SHA1 hash string
     */
    public function generateRefreshToken(): string
    {
        return sha1((string) time());
    }

    /**
     * Get the access token time-to-live in seconds.
     *
     * @return int The TTL in seconds as configured in JwtConfig
     */
    public function getTokenTtl(): int
    {
        return $this->config->getTtlSeconds();
    }

    /**
     * Get the refresh token time-to-live in seconds.
     *
     * @return int The refresh TTL in seconds as configured in JwtConfig
     */
    public function getRefreshTokenTtl(): int
    {
        return $this->config->getRefreshTtlSeconds();
    }

    /**
     * Get the session ID from the last generated token.
     *
     * The session ID (sid) is a UUID v7 generated during token encoding.
     * It can be used to track user sessions and implement token revocation.
     *
     * @return string|null The session ID or null if no token has been generated yet
     */
    public function getLastSessionId(): ?string
    {
        return $this->lastSessionId;
    }

    /**
     * Get the JTI (JWT ID) from the last generated token.
     *
     * The JTI is a unique identifier (UUID v7) for each token.
     * It can be used for token blacklisting and preventing replay attacks.
     *
     * @return string|null The JWT ID or null if no token has been generated yet
     */
    public function getLastJti(): ?string
    {
        return $this->lastJti;
    }

    /**
     * Validate the issuer claim against the configured issuer.
     *
     * @param object $payload The decoded JWT payload
     * @throws InvalidClaimException When the issuer doesn't match the expected value
     */
    private function validateIssuer(object $payload): void
    {
        $expectedIssuer = $this->config->getIssuer();

        if (!isset($payload->iss) || $payload->iss !== $expectedIssuer) {
            throw new InvalidClaimException('iss', $payload->iss ?? null, $expectedIssuer);
        }
    }

    /**
     * Validate the audience claim against the configured audiences.
     *
     * If no audience is configured, validation is skipped.
     * The token's audience can be a string or array; at least one must match.
     *
     * @param object $payload The decoded JWT payload
     * @throws InvalidClaimException When the audience doesn't match any expected value
     */
    private function validateAudience(object $payload): void
    {
        $expectedAudiences = $this->config->getAudience();

        if ($expectedAudiences === null) {
            return;
        }

        if (!isset($payload->aud)) {
            throw new InvalidClaimException('aud', null, $expectedAudiences);
        }

        $tokenAudiences = is_array($payload->aud) ? $payload->aud : [$payload->aud];

        $hasValidAudience = false;
        foreach ($expectedAudiences as $expectedAudience) {
            if (in_array($expectedAudience, $tokenAudiences, true)) {
                $hasValidAudience = true;
                break;
            }
        }

        if (!$hasValidAudience) {
            throw new InvalidClaimException('aud', $payload->aud, $expectedAudiences);
        }
    }

    /**
     * Validate that the token type is "access".
     *
     * @param object $payload The decoded JWT payload
     * @throws InvalidClaimException When the token type is not "access"
     */
    private function validateTokenType(object $payload): void
    {
        if (!isset($payload->typ) || $payload->typ !== 'access') {
            throw new InvalidClaimException('typ', $payload->typ ?? null, 'access');
        }
    }

    /**
     * Validate that all required claims are present and non-empty.
     *
     * Required claims are defined in the JwtConfig. Each claim must exist
     * and have a non-empty value (not null, empty string, or empty array).
     *
     * @param object $payload The decoded JWT payload
     * @throws MissingClaimsException When one or more required claims are missing or empty
     */
    private function validateRequiredClaims(object $payload): void
    {
        $missingClaims = [];
        $requiredClaims = $this->config->getRequiredClaims();

        foreach ($requiredClaims as $claim) {
            if (!isset($payload->{$claim}) || $payload->{$claim} === '' || $payload->{$claim} === []) {
                $missingClaims[] = $claim;
            }
        }

        if (!empty($missingClaims)) {
            throw new MissingClaimsException($missingClaims);
        }
    }
}
