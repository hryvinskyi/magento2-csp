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

    private const LETTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const SCHEME_CHARACTERS = self::LETTERS . '0123456789+.-';

    /**
     * Kind of a source token as it appears in a header.
     *
     * @param string $token
     * @return self
     */
    public static function of(string $token): self
    {
        if ($token === '*') {
            return self::Wildcard;
        }
        if (str_starts_with($token, "'")) {
            $prefix = strtolower(substr($token, 0, 8));
            if (str_starts_with($prefix, "'nonce-")) {
                return self::Nonce;
            }

            return in_array($prefix, ["'sha256-", "'sha384-", "'sha512-"], true) ? self::Hash : self::Keyword;
        }

        return self::isScheme($token) ? self::Scheme : self::Host;
    }

    /**
     * Whether the token is a scheme source: a letter, then letters, digits, "+", "." or "-", then ":".
     *
     * @param string $token
     * @return bool
     */
    private static function isScheme(string $token): bool
    {
        $length = strlen($token);

        return $length >= 2
            && $token[$length - 1] === ':'
            && strspn($token, self::LETTERS, 0, 1) === 1
            && strspn($token, self::SCHEME_CHARACTERS, 1) === $length - 2;
    }
}
