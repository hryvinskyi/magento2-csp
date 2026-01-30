<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

/**
 * Provides domain matching utilities for CSP wildcard operations
 */
interface DomainMatcherInterface
{
    /**
     * Check if value is a wildcard domain (e.g., *.example.com)
     *
     * @param string $value
     * @return bool
     */
    public function isWildcard(string $value): bool;

    /**
     * Check if a domain matches a wildcard pattern
     *
     * @param string $domain Domain to check (e.g., www.example.com)
     * @param string $wildcard Wildcard pattern (e.g., *.example.com)
     * @return bool
     */
    public function domainMatchesWildcard(string $domain, string $wildcard): bool;

    /**
     * Check if a wildcard is covered by a broader wildcard
     *
     * @param string $wildcard Wildcard to check (e.g., *.sub.example.com)
     * @param string $broaderWildcard Potentially broader wildcard (e.g., *.example.com)
     * @return bool
     */
    public function isWildcardCoveredByBroader(string $wildcard, string $broaderWildcard): bool;

    /**
     * Extract domain from a host value (strips protocol, port, path)
     *
     * @param string $host
     * @return string
     */
    public function extractDomain(string $host): string;
}
