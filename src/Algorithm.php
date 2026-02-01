<?php

declare(strict_types=1);

namespace DevToolbelt\JwtTokenManager;

enum Algorithm: string
{
    // HMAC algorithms
    case HS256 = 'HS256';
    case HS384 = 'HS384';
    case HS512 = 'HS512';

    // RSA algorithms
    case RS256 = 'RS256';
    case RS384 = 'RS384';
    case RS512 = 'RS512';

    // ECDSA algorithms
    case ES256 = 'ES256';
    case ES384 = 'ES384';
    case ES512 = 'ES512';

    // RSA-PSS algorithms
    case PS256 = 'PS256';
    case PS384 = 'PS384';
    case PS512 = 'PS512';

    // EdDSA algorithm
    case EdDSA = 'EdDSA';

    public function isSymmetric(): bool
    {
        return in_array($this, [self::HS256, self::HS384, self::HS512], true);
    }

    public function isAsymmetric(): bool
    {
        return !$this->isSymmetric();
    }

    public function isRSA(): bool
    {
        return in_array($this, [
            self::RS256,
            self::RS384,
            self::RS512,
            self::PS256,
            self::PS384,
            self::PS512
        ], true);
    }

    public function isECDSA(): bool
    {
        return in_array($this, [self::ES256, self::ES384, self::ES512], true);
    }
}
