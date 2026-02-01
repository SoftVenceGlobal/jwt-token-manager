# JWT Token Manager

[![Latest Stable Version](https://poser.pugx.org/dev-toolbelt/jwt-token-manager/v/stable)](https://packagist.org/packages/dev-toolbelt/jwt-token-manager)
[![Total Downloads](https://poser.pugx.org/dev-toolbelt/jwt-token-manager/downloads)](https://packagist.org/packages/dev-toolbelt/jwt-token-manager)
[![License](https://poser.pugx.org/dev-toolbelt/jwt-token-manager/license)](https://packagist.org/packages/dev-toolbelt/jwt-token-manager)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)

A **framework-agnostic** PHP library for encoding, decoding, and validating JSON Web Tokens (JWT) with support for RSA, HMAC, ECDSA, and EdDSA algorithms.

Built with simplicity and security in mind, this package provides an easy way to manage JWT tokens without coupling your application to any specific framework.

## Features

- **Framework Agnostic** - Use with Laravel, Symfony, Yii, Slim, or any PHP application
- **Multiple Algorithms** - Support for HS256, HS384, HS512, RS256, RS384, RS512, ES256, ES384, ES512, PS256, PS384, PS512, and EdDSA
- **Type-Safe Configuration** - Strongly typed `Algorithm` enum and configuration objects
- **Protected Claims** - Critical claims (iss, sub, iat, exp, jti, sid) cannot be overridden for security
- **Flexible Validation** - Configurable required claims and audience validation
- **Session Tracking** - Built-in session ID (sid) and JWT ID (jti) generation using UUID v7
- **Comprehensive Exceptions** - Specific exceptions for expired, invalid, and malformed tokens

## Requirements

- PHP 8.1 or higher
- OpenSSL extension (for RSA/ECDSA algorithms)

## Installation

Install via Composer:

```bash
composer require dev-toolbelt/jwt-token-manager
```

## Quick Start

### 1. Generate RSA Keys (if you don't have them)

Before using the library, you'll need an RSA key pair for signing and verifying tokens. If you already have keys, skip to step 2.

#### Linux / macOS

Open your terminal and run:

```bash
# Generate private key (2048-bit)
openssl genpkey -algorithm RSA -out private.key -pkeyopt rsa_keygen_bits:2048

# Extract public key
openssl rsa -pubout -in private.key -out public.key
```

#### Windows

**Option 1: Using Git Bash (recommended)**

If you have Git installed, open Git Bash and use the same commands as Linux/macOS:

```bash
openssl genpkey -algorithm RSA -out private.key -pkeyopt rsa_keygen_bits:2048
openssl rsa -pubout -in private.key -out public.key
```

**Option 2: Using WSL (Windows Subsystem for Linux)**

```bash
wsl openssl genpkey -algorithm RSA -out private.key -pkeyopt rsa_keygen_bits:2048
wsl openssl rsa -pubout -in private.key -out public.key
```

**Option 3: Using OpenSSL for Windows**

1. Download OpenSSL from [slproweb.com/products/Win32OpenSSL.html](https://slproweb.com/products/Win32OpenSSL.html)
2. Install and add to PATH
3. Open Command Prompt and run the same commands

#### Production Keys (4096-bit)

For production environments, consider using stronger 4096-bit keys:

```bash
openssl genpkey -algorithm RSA -out private.key -pkeyopt rsa_keygen_bits:4096
openssl rsa -pubout -in private.key -out public.key
```

> **Security Note:** Keep your private key secure and never commit it to version control. Add `*.key` to your `.gitignore` file.

### 2. Basic Usage

```php
use DevToolbelt\JwtTokenManager\JwtConfig;
use DevToolbelt\JwtTokenManager\JwtTokenManager;

// Create configuration
$config = new JwtConfig(
    privateKey: file_get_contents('/path/to/private.key'),
    publicKey: file_get_contents('/path/to/public.key'),
    issuer: 'https://api.yourapp.com'
);

// Initialize the manager
$manager = new JwtTokenManager($config);

// Generate a token
$token = $manager->encode('user-123', [
    'name' => 'John Doe',
    'role' => 'admin'
]);

// Decode and validate the token
$payload = $manager->decode($token);

echo $payload->getSubject();      // "user-123"
echo $payload->getClaim('name');  // "John Doe"
echo $payload->getClaim('role');  // "admin"
```

## Configuration

### Basic Configuration

```php
use DevToolbelt\JwtTokenManager\JwtConfig;
use DevToolbelt\JwtTokenManager\Algorithm;

$config = new JwtConfig(
    privateKey: $privateKey,
    publicKey: $publicKey,
    issuer: 'https://api.yourapp.com',
    algorithm: Algorithm::RS256,        // Default: RS256
    ttlMinutes: 60,                     // Default: 60 (1 hour)
    refreshTtlMinutes: 20160,           // Default: 20160 (14 days)
    audience: ['https://app.yourapp.com'],
    requiredClaims: ['iss', 'sub', 'exp', 'iat', 'jti', 'typ']
);
```

### Using Factory Methods

```php
// From key files
$config = JwtConfig::fromKeyFiles(
    privateKeyPath: '/path/to/private.key',
    publicKeyPath: '/path/to/public.key',
    issuer: 'https://api.yourapp.com'
);

// From array (useful for framework config files)
$config = JwtConfig::fromArray([
    'private_key' => $privateKey,
    'public_key' => $publicKey,
    'issuer' => 'https://api.yourapp.com',
    'algorithm' => 'RS256',
    'ttl_minutes' => 60,
    'audience' => ['https://app.yourapp.com']
]);
```

### Supported Algorithms

| Algorithm | Type | Description |
|-----------|------|-------------|
| `HS256`, `HS384`, `HS512` | HMAC | Symmetric key algorithms |
| `RS256`, `RS384`, `RS512` | RSA | Asymmetric RSA algorithms |
| `ES256`, `ES384`, `ES512` | ECDSA | Elliptic Curve algorithms |
| `PS256`, `PS384`, `PS512` | RSA-PSS | RSA with PSS padding |
| `EdDSA` | EdDSA | Edwards-curve Digital Signature |

```php
use DevToolbelt\JwtTokenManager\Algorithm;

// Check algorithm properties
Algorithm::RS256->isAsymmetric();  // true
Algorithm::HS256->isSymmetric();   // true
Algorithm::RS256->isRSA();         // true
Algorithm::ES256->isECDSA();       // true
```

## Usage

### Generating Tokens

```php
$manager = new JwtTokenManager($config);

// Simple token with just a subject
$token = $manager->encode('user-123');

// Token with custom claims
$token = $manager->encode('user-123', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'roles' => ['admin', 'editor'],
    'tenant_id' => 'tenant-456'
]);

// Get the generated session ID and JWT ID
$sessionId = $manager->getLastSessionId();  // UUID v7
$jti = $manager->getLastJti();              // UUID v7
```

### Token Claims

The generated token includes these standard claims:

| Claim | Description | Customizable |
|-------|-------------|--------------|
| `iss` | Issuer (from config) | No |
| `sub` | Subject (user identifier) | No |
| `aud` | Audience (from config) | Yes |
| `iat` | Issued at timestamp | No |
| `exp` | Expiration timestamp | No |
| `nbf` | Not before timestamp | Yes |
| `jti` | Unique JWT ID (UUID v7) | No |
| `sid` | Session ID (UUID v7) | No |
| `typ` | Token type (default: "access") | Yes |

### Decoding Tokens

```php
try {
    $payload = $manager->decode($token);

    // Standard claim accessors
    $payload->getSubject();      // sub claim
    $payload->getIssuer();       // iss claim
    $payload->getAudience();     // aud claim (array)
    $payload->getExpiration();   // exp claim (timestamp)
    $payload->getIssuedAt();     // iat claim (timestamp)
    $payload->getNotBefore();    // nbf claim (timestamp)
    $payload->getJti();          // jti claim
    $payload->getSessionId();    // sid claim
    $payload->getType();         // typ claim

    // Custom claims
    $payload->getClaim('role');  // Get any custom claim
    $payload->hasClaim('role');  // Check if claim exists

    // Get all claims as array
    $allClaims = $payload->toArray();

    // Check expiration
    if ($payload->isExpired()) {
        // Token has expired
    }

} catch (ExpiredTokenException $e) {
    // Token has expired
} catch (InvalidTokenException $e) {
    // Token is invalid (bad signature, malformed, etc.)
} catch (InvalidClaimException $e) {
    // A claim validation failed
    $e->getClaimName();      // Which claim failed
    $e->getActualValue();    // What value was received
    $e->getExpectedValue();  // What was expected
} catch (MissingClaimsException $e) {
    // Required claims are missing
    $e->getMissingClaims();  // Array of missing claim names
}
```

### Refresh Tokens

```php
// Generate a refresh token
$refreshToken = $manager->generateRefreshToken();  // SHA1 hash

// Get TTL values
$accessTtl = $manager->getTokenTtl();        // In seconds
$refreshTtl = $manager->getRefreshTokenTtl(); // In seconds
```

### Overriding Optional Claims

Some claims can be overridden via custom claims for flexibility:

```php
// Override the token type for refresh tokens
$refreshToken = $manager->encode('user-123', [
    'typ' => 'refresh'
]);

// Override not-before time
$token = $manager->encode('user-123', [
    'nbf' => time() + 3600  // Token valid in 1 hour
]);

// Override audience for specific consumers
$token = $manager->encode('user-123', [
    'aud' => ['https://mobile-app.yourapp.com']
]);
```

> **Note**: Protected claims (`iss`, `sub`, `iat`, `exp`, `jti`, `sid`) cannot be overridden for security reasons.

## Framework Integration Examples

### Laravel

```php
// config/jwt.php
return [
    'private_key' => storage_path('keys/private.key'),
    'public_key' => storage_path('keys/public.key'),
    'issuer' => env('APP_URL'),
    'audience' => [env('FRONTEND_URL')],
    'ttl_minutes' => 60,
];

// app/Providers/AppServiceProvider.php
use DevToolbelt\JwtTokenManager\JwtConfig;
use DevToolbelt\JwtTokenManager\JwtTokenManager;

public function register(): void
{
    $this->app->singleton(JwtTokenManager::class, function ($app) {
        $config = JwtConfig::fromKeyFiles(
            privateKeyPath: config('jwt.private_key'),
            publicKeyPath: config('jwt.public_key'),
            issuer: config('jwt.issuer'),
            audience: config('jwt.audience'),
            ttlMinutes: config('jwt.ttl_minutes')
        );

        return new JwtTokenManager($config);
    });
}
```

### Symfony

```yaml
# config/services.yaml
services:
    DevToolbelt\JwtTokenManager\JwtConfig:
        factory: ['DevToolbelt\JwtTokenManager\JwtConfig', 'fromKeyFiles']
        arguments:
            $privateKeyPath: '%kernel.project_dir%/config/keys/private.key'
            $publicKeyPath: '%kernel.project_dir%/config/keys/public.key'
            $issuer: '%env(APP_URL)%'

    DevToolbelt\JwtTokenManager\JwtTokenManager:
        arguments:
            $config: '@DevToolbelt\JwtTokenManager\JwtConfig'
```

### Slim / PHP-DI

```php
use DevToolbelt\JwtTokenManager\JwtConfig;
use DevToolbelt\JwtTokenManager\JwtTokenManager;
use Psr\Container\ContainerInterface;

return [
    JwtTokenManager::class => function (ContainerInterface $c) {
        $config = JwtConfig::fromKeyFiles(
            privateKeyPath: __DIR__ . '/../keys/private.key',
            publicKeyPath: __DIR__ . '/../keys/public.key',
            issuer: $_ENV['APP_URL']
        );

        return new JwtTokenManager($config);
    },
];
```

## Testing

```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Code quality
composer phpcs        # Check code style
composer phpcs:fix    # Fix code style
composer phpstan      # Static analysis
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please make sure to update tests as appropriate and follow the existing code style.

## Security

If you discover any security-related issues, please email dersonsena@gmail.com instead of using the issue tracker.

## Credits

- [Kilderson Sena](https://github.com/dersonsena)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

Made with ❤️ by [Dev Toolbelt](https://github.com/Dev-Toolbelt)
