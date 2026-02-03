<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager\Tests\Unit;

use stdClass;
use DevToolbelt\JwtTokenManager\JwtConfig;
use DevToolbelt\JwtTokenManager\JwtTokenManager;
use DevToolbelt\JwtTokenManager\Tests\TestCase;

final class TokenPayloadTest extends TestCase
{
    private const FIXTURES_PATH = __DIR__ . '/../fixtures';

    public function testGetRawReturnsStdClassPayload(): void
    {
        $privateKey = file_get_contents(self::FIXTURES_PATH . '/private.key');
        $publicKey = file_get_contents(self::FIXTURES_PATH . '/public.key');

        $config = new JwtConfig(
            privateKey: $privateKey,
            publicKey: $publicKey,
            issuer: 'https://api.example.com'
        );

        $manager = new JwtTokenManager($config);
        $token = $manager->encode('user-raw', ['role' => 'admin']);
        $payload = $manager->decode($token);

        $raw = $payload->getRaw();

        $this->assertInstanceOf(stdClass::class, $raw);
        $this->assertEquals('user-raw', $raw->sub);
        $this->assertEquals('admin', $raw->role);
    }
}
