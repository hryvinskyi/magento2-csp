<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy;

/**
 * Splits a CSP host-source expression into scheme, host, port and path.
 *
 * Follows the host-source grammar of CSP Level 3: optional scheme, a host made of letters, digits and hyphens with an
 * optional leading "*." wildcard label, an optional port (digits or "*") and an optional path.
 */
class HostSourceParser
{
    private const PATTERN = '~^(?:(?<scheme>[a-z][a-z0-9+.\-]*)://)?'
        . '(?<host>\*|(?:\*\.)?[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?)*)'
        . '(?::(?<port>[0-9]{1,5}|\*))?'
        . '(?<path>/[^\s;,\'"]*)?$~i';

    /**
     * Parts of a host-source, or null when the token is not one. Scheme and host are lower-cased.
     *
     * @param string $token
     * @return array{scheme: string|null, host: string, port: string|null, path: string|null}|null
     */
    public function parse(string $token): ?array
    {
        if (preg_match(self::PATTERN, $token, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
            return null;
        }

        $scheme = $matches['scheme'];

        return [
            'scheme' => $scheme === null ? null : strtolower($scheme),
            'host' => strtolower($matches['host']),
            'port' => $matches['port'],
            'path' => $matches['path'],
        ];
    }

    /**
     * Whether the token is a valid host-source.
     *
     * @param string $token
     * @return bool
     */
    public function isHostSource(string $token): bool
    {
        return $this->parse($token) !== null;
    }

    /**
     * Whether the host part is a "*.domain" wildcard.
     *
     * @param array{scheme: string|null, host: string, port: string|null, path: string|null} $parts
     * @return bool
     */
    public function isWildcardHost(array $parts): bool
    {
        return str_starts_with($parts['host'], '*.');
    }

    /**
     * Rebuild a host-source token from its parts.
     *
     * @param array{scheme: string|null, host: string, port: string|null, path: string|null} $parts
     * @return string
     */
    public function compose(array $parts): string
    {
        return ($parts['scheme'] !== null ? $parts['scheme'] . '://' : '')
            . $parts['host']
            . ($parts['port'] !== null ? ':' . $parts['port'] : '')
            . ($parts['path'] ?? '');
    }
}
