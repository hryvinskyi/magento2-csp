<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Data;

/**
 * Digest algorithm of a hash source.
 *
 * @api
 */
enum HashAlgorithm: string
{
    case SHA256 = 'sha256';
    case SHA384 = 'sha384';
    case SHA512 = 'sha512';

    /**
     * Length of the digest in base64, padding included.
     *
     * @return int
     */
    public function encodedLength(): int
    {
        return match ($this) {
            self::SHA256 => 44,
            self::SHA384 => 64,
            self::SHA512 => 88,
        };
    }
}
