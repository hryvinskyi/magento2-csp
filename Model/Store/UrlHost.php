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
 */
class UrlHost
{
    private const DEFAULT_PORTS = ['http' => 80, 'https' => 443, 'ws' => 80, 'wss' => 443];

    /**
     * Host of the URL, or null when it has none.
     *
     * @param string $url
     * @return string|null
     */
    public function of(string $url): ?string
    {
        $parts = parse_url($url);
        $host = is_array($parts) ? ($parts['host'] ?? null) : null;
        if (!is_string($host) || $host === '') {
            return null;
        }
        $scheme = strtolower($parts['scheme'] ?? '');
        $port = $parts['port'] ?? null;
        $explicitPort = is_int($port) && $port !== (self::DEFAULT_PORTS[$scheme] ?? null);

        return strtolower($host) . ($explicitPort ? ':' . $port : '');
    }
}
