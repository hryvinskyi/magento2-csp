<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Store;

/**
 * Host of a URL as a CSP host source names it: lower-case, with the port only when it is not the scheme's default.
 *
 * Only a syntactically valid host counts: a name of letters, digits, hyphens and dots (an internationalized name in
 * its punycode form), an IPv4 address, or a bracketed IPv6 address. A browser never reports anything else, so a
 * wildcard or markup in the host marks a forged URL.
 */
class UrlHost
{
    private const DEFAULT_PORTS = ['http' => 80, 'https' => 443, 'ws' => 80, 'wss' => 443];
    private const HOST_NAME_PATTERN = '/^(?=.{1,253}$)[a-z0-9-]{1,63}(?:\.[a-z0-9-]{1,63})*$/';

    /**
     * Host of the URL, or null when it has none or the one it has is not a valid host.
     *
     * @param string $url
     * @return string|null
     */
    public function of(string $url): ?string
    {
        $parts = parse_url($url);
        $host = is_array($parts) ? strtolower($parts['host'] ?? '') : '';
        if (!$this->isValid($host)) {
            return null;
        }
        $scheme = strtolower($parts['scheme'] ?? '');
        $port = $parts['port'] ?? null;
        $explicitPort = is_int($port) && $port !== (self::DEFAULT_PORTS[$scheme] ?? null);

        return $host . ($explicitPort ? ':' . $port : '');
    }

    /**
     * Whether the URL names a host at all, valid or not.
     *
     * @param string $url
     * @return bool
     */
    public function names(string $url): bool
    {
        return (string)parse_url($url, PHP_URL_HOST) !== '';
    }

    /**
     * Whether the lower-case host is a host name, an IPv4 address or a bracketed IPv6 address.
     *
     * @param string $host
     * @return bool
     */
    private function isValid(string $host): bool
    {
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            return filter_var(substr($host, 1, -1), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        }

        return preg_match(self::HOST_NAME_PATTERN, $host) === 1;
    }
}
