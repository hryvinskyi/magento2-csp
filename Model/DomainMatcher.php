<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\DomainMatcherInterface;

/**
 * Provides domain matching utilities for CSP wildcard operations
 */
class DomainMatcher implements DomainMatcherInterface
{
    /**
     * @inheritDoc
     */
    public function isWildcard(string $value): bool
    {
        return str_starts_with($value, '*.');
    }

    /**
     * @inheritDoc
     */
    public function domainMatchesWildcard(string $domain, string $wildcard): bool
    {
        if (!$this->isWildcard($wildcard)) {
            return false;
        }

        $wildcardDomain = strtolower(substr($wildcard, 2));
        $domainLower = strtolower($this->extractDomain($domain));

        return str_ends_with($domainLower, '.' . $wildcardDomain);
    }

    /**
     * @inheritDoc
     */
    public function isWildcardCoveredByBroader(string $wildcard, string $broaderWildcard): bool
    {
        if (!$this->isWildcard($wildcard) || !$this->isWildcard($broaderWildcard)) {
            return false;
        }

        if ($wildcard === $broaderWildcard) {
            return false;
        }

        $wildcardDomain = strtolower(substr($wildcard, 2));
        $broaderDomain = strtolower(substr($broaderWildcard, 2));

        return str_ends_with('.' . $wildcardDomain, '.' . $broaderDomain);
    }

    /**
     * @inheritDoc
     */
    public function extractDomain(string $host): string
    {
        $domain = preg_replace('#^https?://#i', '', $host);
        $domain = preg_replace('#:\d+.*$#', '', $domain ?? $host);
        $domain = preg_replace('#/.*$#', '', $domain ?? $host);

        return $domain ?? $host;
    }
}
