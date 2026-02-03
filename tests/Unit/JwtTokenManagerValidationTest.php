<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager\Tests\Unit;

use DevToolbelt\JwtTokenManager\JwtConfig;
use DevToolbelt\JwtTokenManager\JwtTokenManager;
use DevToolbelt\JwtTokenManager\Tests\TestCase;
use DevToolbelt\JwtTokenManager\Exceptions\InvalidClaimException;
use DevToolbelt\JwtTokenManager\Exceptions\InvalidTokenException;
use DevToolbelt\JwtTokenManager\Exceptions\MissingClaimsException;

final class JwtTokenManagerValidationTest extends TestCase
{
    private const FIXTURES_PATH = __DIR__ . '/../fixtures';

    private string $privateKey;
    private string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->privateKey = file_get_contents(self::FIXTURES_PATH . '/private.key');
        $this->publicKey = file_get_contents(self::FIXTURES_PATH . '/public.key');
    }

    public function testDecodeThrowsInvalidTokenExceptionForFutureNbf(): void
    {
        $config = new JwtConfig(
            privateKey: $this->privateKey,
            publicKey: $this->publicKey,
            issuer: 'https://api.example.com'
        );

        $manager = new JwtTokenManager($config);
        $token = $manager->encode('user-123', [
            'nbf' => time() + 3600,
        ]);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Token is not yet valid');

        $manager->decode($token);
    }

    public function testDecodeThrowsInvalidTokenExceptionForWrongSegmentCount(): void
    {
        $config = new JwtConfig(
            privateKey: $this->privateKey,
            publicKey: $this->publicKey,
            issuer: 'https://api.example.com'
        );

        $manager = new JwtTokenManager($config);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Wrong number of segments');

        $manager->decode('only.two');
    }

    public function testDecodeThrowsInvalidTokenExceptionForInvalidHeaderJson(): void
    {
        $config = new JwtConfig(
            privateKey: $this->privateKey,
            publicKey: $this->publicKey,
            issuer: 'https://api.example.com'
        );

        $manager = new JwtTokenManager($config);

        $header = $this->base64UrlEncode('{'); // Invalid JSON
        $payload = $this->base64UrlEncode('{}');
        $signature = $this->base64UrlEncode('sig');
        $token = "{$header}.{$payload}.{$signature}";

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Syntax error, malformed JSON');

        $manager->decode($token);
    }

    public function testDecodeThrowsInvalidClaimExceptionForIssuerMismatch(): void
    {
        $issuerConfig = new JwtConfig(
            privateKey: $this->privateKey,
            publicKey: $this->publicKey,
            issuer: 'https://api.example.com'
        );

        $tokenManager = new JwtTokenManager($issuerConfig);
        $token = $tokenManager->encode('user-123');

        $wrongIssuerConfig = new JwtConfig(
            privateKey: $this->privateKey,
            publicKey: $this->publicKey,
            issuer: 'https://api.other.com'
        );

        $wrongIssuerManager = new JwtTokenManager($wrongIssuerConfig);

        try {
            $wrongIssuerManager->decode($token);
            $this->fail('Expected InvalidClaimException was not thrown.');
        } catch (InvalidClaimException $exception) {
            $this->assertEquals('iss', $exception->getClaimName());
            $this->assertEquals('https://api.example.com', $exception->getActualValue());
            $this->assertEquals('https://api.other.com', $exception->getExpectedValue());
        }
    }

    public function testDecodeThrowsInvalidClaimExceptionWhenAudienceIsMissing(): void
    {
        $config = new JwtConfig(
            privateKey: $this->privateKey,
            publicKey: $this->publicKey,
            issuer: 'https://api.example.com',
            audience: ['https://app.example.com']
        );

        $manager = new JwtTokenManager($config);
        $token = $manager->encode('user-123', [
            'aud' => null,
        ]);

        try {
            $manager->decode($token);
            $this->fail('Expected InvalidClaimException was not thrown.');
        } catch (InvalidClaimException $exception) {
            $this->assertEquals('aud', $exception->getClaimName());
            $this->assertNull($exception->getActualValue());
            $this->assertEquals(['https://app.example.com'], $exception->getExpectedValue());
        }
    }

    public function testDecodeThrowsInvalidClaimExceptionWhenAudienceDoesNotMatch(): void
    {
        $audienceConfig = new JwtConfig(
            privateKey: $this->privateKey,
            publicKey: $this->publicKey,
            issuer: 'https://api.example.com',
            audience: ['https://app-a.example.com']
        );

        $tokenManager = new JwtTokenManager($audienceConfig);
        $token = $tokenManager->encode('user-123');

        $differentAudienceConfig = new JwtConfig(
            privateKey: $this->privateKey,
            publicKey: $this->publicKey,
            issuer: 'https://api.example.com',
            audience: ['https://app-b.example.com']
        );

        $differentAudienceManager = new JwtTokenManager($differentAudienceConfig);

        try {
            $differentAudienceManager->decode($token);
            $this->fail('Expected InvalidClaimException was not thrown.');
        } catch (InvalidClaimException $exception) {
            $this->assertEquals('aud', $exception->getClaimName());
            $this->assertEquals(['https://app-a.example.com'], $exception->getActualValue());
            $this->assertEquals(['https://app-b.example.com'], $exception->getExpectedValue());
        }
    }

    public function testDecodeThrowsInvalidClaimExceptionWhenTokenTypeIsNotAccess(): void
    {
        $config = new JwtConfig(
            privateKey: $this->privateKey,
            publicKey: $this->publicKey,
            issuer: 'https://api.example.com'
        );

        $manager = new JwtTokenManager($config);
        $token = $manager->encode('user-123', [
            'typ' => 'refresh',
        ]);

        try {
            $manager->decode($token);
            $this->fail('Expected InvalidClaimException was not thrown.');
        } catch (InvalidClaimException $exception) {
            $this->assertEquals('typ', $exception->getClaimName());
            $this->assertEquals('refresh', $exception->getActualValue());
            $this->assertEquals('access', $exception->getExpectedValue());
        }
    }

    public function testDecodeThrowsMissingClaimsExceptionWhenRequiredClaimIsEmpty(): void
    {
        $config = new JwtConfig(
            privateKey: $this->privateKey,
            publicKey: $this->publicKey,
            issuer: 'https://api.example.com',
            requiredClaims: ['iss', 'sub', 'role']
        );

        $manager = new JwtTokenManager($config);
        $token = $manager->encode('user-123', [
            'role' => '',
        ]);

        try {
            $manager->decode($token);
            $this->fail('Expected MissingClaimsException was not thrown.');
        } catch (MissingClaimsException $exception) {
            $this->assertEquals(['role'], $exception->getMissingClaims());
            $this->assertEquals('Token is missing required claims: role', $exception->getMessage());
        }
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
