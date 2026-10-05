<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy;

/**
 * Kind of a CSP source expression.
 */
enum SourceKind
{
    case Keyword;
    case Nonce;
    case Hash;
    case Scheme;
    case Wildcard;
    case Host;

    /**
     * Kind of a source token as it appears in a header.
     *
     * @param string $token
     * @return self
     */
    public static function of(string $token): self
    {
        $lower = strtolower($token);
        if (str_starts_with($lower, "'nonce-")) {
            return self::Nonce;
        }
        if (preg_match("/^'sha(256|384|512)-/", $lower) === 1) {
            return self::Hash;
        }
        if (str_starts_with($token, "'")) {
            return self::Keyword;
        }
        if ($token === '*') {
            return self::Wildcard;
        }
        if (preg_match('/^[a-z][a-z0-9+.\-]*:$/', $lower) === 1) {
            return self::Scheme;
        }

        return self::Host;
    }
}
