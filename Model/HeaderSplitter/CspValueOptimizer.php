<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\HeaderSplitter;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Hryvinskyi\Csp\Api\CspValueOptimizerInterface;
use Hryvinskyi\Csp\Api\DomainMatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Optimizes CSP header values by removing duplicates and redundant wildcard-covered entries
 */
class CspValueOptimizer implements CspValueOptimizerInterface
{
    /**
     * CSP keywords that should never be deduplicated against each other
     */
    private const CSP_KEYWORDS = [
        "'self'",
        "'unsafe-inline'",
        "'unsafe-eval'",
        "'unsafe-hashes'",
        "'strict-dynamic'",
        "'report-sample'",
        "'wasm-unsafe-eval'",
        "'none'",
        'data:',
        'blob:',
        'mediastream:',
        'filesystem:',
        'ws:',
        'wss:',
        'https:',
        'http:',
    ];

    /**
     * Directives whose values are URIs, not host-sources.
     * These must never have scheme/path stripping or deduplication applied.
     */
    private const URI_VALUE_DIRECTIVES = [
        'report-uri',
        'report-to',
    ];

    /**
     * Directives that inherit from default-src when not explicitly set.
     * frame-ancestors, base-uri, and form-action do NOT fall back to default-src per the CSP spec.
     */
    private const DEFAULT_SRC_FALLBACK_DIRECTIVES = [
        'child-src',
        'connect-src',
        'font-src',
        'frame-src',
        'img-src',
        'manifest-src',
        'media-src',
        'object-src',
        'script-src',
        'style-src',
    ];

    /**
     * @param LoggerInterface $logger
     * @param ConfigInterface $config
     * @param DomainMatcherInterface $domainMatcher
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConfigInterface $config,
        private readonly DomainMatcherInterface $domainMatcher
    ) {
    }

    /**
     * @inheritDoc
     */
    public function optimizeHeader(string $headerValue): string
    {
        if (empty($headerValue)) {
            return $headerValue;
        }

        $directives = $this->parseDirectives($headerValue);

        // Optimize each directive individually (skip URI-value directives like report-uri)
        foreach ($directives as $directiveName => $values) {
            if (in_array($directiveName, self::URI_VALUE_DIRECTIVES, true)) {
                continue;
            }
            $directives[$directiveName] = $this->optimizeDirectiveValues($directiveName, $values);
        }

        // Cross-directive optimization: consolidate common values into default-src
        if ($this->config->isDefaultSrcConsolidationEnabled()) {
            $directives = $this->consolidateIntoDefaultSrc($directives);
        }

        $optimizedDirectives = [];
        foreach ($directives as $directiveName => $values) {
            if (!empty($values)) {
                $optimizedDirectives[] = $directiveName . ' ' . implode(' ', $values);
            }
        }

        return implode('; ', $optimizedDirectives);
    }

    /**
     * @inheritDoc
     */
    public function optimizeDirectiveValues(string $directiveName, array $values): array
    {
        if (empty($values)) {
            return $values;
        }

        // Step 1: Strip schemes and paths from host values (if enabled)
        if ($this->config->isSchemePathStrippingEnabled()) {
            $values = $this->stripSchemesAndPaths($values);
        }

        // Step 2: Remove exact duplicates
        $values = $this->removeDuplicates($values);

        // Step 3: Remove redundant wildcard-covered entries (if enabled)
        if ($this->config->isRedundantWildcardRemovalEnabled()) {
            $values = $this->removeRedundantWildcards($values);
        }

        // Step 4: Consolidate subdomains into wildcards (if enabled)
        if ($this->config->isSubdomainWildcardConsolidationEnabled()) {
            $values = $this->consolidateSubdomainsToWildcards($values);
        }

        // Step 5: Sort for consistent output (keywords first, then alphabetical)
        return $this->sortValues($values);
    }

    /**
     * @inheritDoc
     */
    public function removeDuplicates(array $values): array
    {
        $seen = [];
        $result = [];

        foreach ($values as $value) {
            $normalizedValue = $this->normalizeValue($value);
            if (!isset($seen[$normalizedValue])) {
                $seen[$normalizedValue] = true;
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function removeRedundantWildcards(array $values): array
    {
        // Check for the special "*" universal wildcard first
        if (in_array('*', $values, true)) {
            $this->logger->warning(
                'CSP Optimizer: Found unrestricted wildcard "*" which makes all other domain entries redundant'
            );
            $result = ['*'];
            foreach ($values as $value) {
                if ($this->isKeyword($value)) {
                    $result[] = $value;
                }
            }
            return $result;
        }

        // Separate wildcards from regular hosts
        $wildcards = [];
        $regularHosts = [];
        $keywords = [];
        $others = [];

        foreach ($values as $value) {
            if ($this->isKeyword($value)) {
                $keywords[] = $value;
            } elseif ($this->domainMatcher->isWildcard($value)) {
                $wildcards[] = $value;
            } elseif ($this->isDomainLike($value)) {
                $regularHosts[] = $value;
            } else {
                $others[] = $value;
            }
        }

        // Filter out regular hosts that are covered by wildcards
        $filteredHosts = [];
        foreach ($regularHosts as $host) {
            if (!$this->isCoveredByWildcard($host, $wildcards)) {
                $filteredHosts[] = $host;
            } else {
                $this->logger->debug(sprintf(
                    'CSP Optimizer: Removed redundant entry "%s" (covered by wildcard)',
                    $host
                ));
            }
        }

        // Also check for redundant wildcards (e.g., *.sub.example.com when *.example.com exists)
        $filteredWildcards = $this->removeRedundantWildcardsFromList($wildcards);

        return array_merge($keywords, $filteredWildcards, $filteredHosts, $others);
    }

    /**
     * Strip scheme prefixes and paths from host-like values
     *
     * In CSP, `example.com` matches both http and https origins.
     * Stripping `https://` and paths reduces redundancy.
     *
     * @param array<int, string> $values
     * @return array<int, string>
     */
    private function stripSchemesAndPaths(array $values): array
    {
        $result = [];

        foreach ($values as $value) {
            // Skip keywords, hashes, nonces, scheme-sources, and wildcards
            if ($this->isKeyword($value) || $this->isHash($value) || $this->isNonce($value)) {
                $result[] = $value;
                continue;
            }

            $stripped = $value;

            // Strip scheme (https:// or http://)
            $stripped = preg_replace('#^https?://#i', '', $stripped) ?? $stripped;

            // Strip path component (keep host and optional port)
            $stripped = preg_replace('#(/[^\s]*)$#', '', $stripped) ?? $stripped;

            // Strip trailing slashes
            $stripped = rtrim($stripped, '/');

            if ($stripped !== $value && !empty($stripped)) {
                $this->logger->debug(sprintf(
                    'CSP Optimizer: Stripped scheme/path from "%s" to "%s"',
                    $value,
                    $stripped
                ));
            }

            $result[] = !empty($stripped) ? $stripped : $value;
        }

        return $result;
    }

    /**
     * Consolidate multiple subdomains of the same parent domain into a wildcard
     *
     * When N or more subdomains of the same parent domain exist (e.g. api.google.com,
     * maps.google.com, fonts.google.com), they are replaced with *.google.com.
     *
     * @param array<int, string> $values
     * @return array<int, string>
     */
    private function consolidateSubdomainsToWildcards(array $values): array
    {
        $threshold = $this->config->getSubdomainWildcardThreshold();
        $keywords = [];
        $wildcards = [];
        $others = [];
        /** @var array<string, array<int, string>> $domainGroups */
        $domainGroups = [];

        foreach ($values as $value) {
            if ($this->isKeyword($value) || $this->isHash($value) || $this->isNonce($value)) {
                $keywords[] = $value;
                continue;
            }

            if ($this->domainMatcher->isWildcard($value)) {
                $wildcards[] = $value;
                continue;
            }

            // Skip port-bearing hosts — wildcard *.example.com won't cover example.com:8080
            if (preg_match('/:\d+/', $value)) {
                $others[] = $value;
                continue;
            }

            $domain = $this->domainMatcher->extractDomain($value);
            $parts = explode('.', $domain);

            // Need at least 3 parts (sub.example.com) to extract a parent domain
            if (count($parts) >= 3) {
                $parentDomain = implode('.', array_slice($parts, 1));
                $domainGroups[$parentDomain][] = $value;
            } else {
                $others[] = $value;
            }
        }

        // Process domain groups
        $consolidatedHosts = [];
        foreach ($domainGroups as $parentDomain => $subdomains) {
            $wildcard = '*.' . $parentDomain;

            // Check if a wildcard already covers this parent
            $alreadyCovered = false;
            foreach ($wildcards as $existingWildcard) {
                if ($existingWildcard === $wildcard
                    || $this->domainMatcher->isWildcardCoveredByBroader($wildcard, $existingWildcard)
                ) {
                    $alreadyCovered = true;
                    break;
                }
            }

            if ($alreadyCovered) {
                // Existing wildcard already covers these — drop them
                $this->logger->debug(sprintf(
                    'CSP Optimizer: Dropped %d subdomains of "%s" (already covered by wildcard)',
                    count($subdomains),
                    $parentDomain
                ));
                continue;
            }

            if (count($subdomains) >= $threshold) {
                $wildcards[] = $wildcard;
                $this->logger->debug(sprintf(
                    'CSP Optimizer: Consolidated %d subdomains of "%s" into "%s"',
                    count($subdomains),
                    $parentDomain,
                    $wildcard
                ));
            } else {
                array_push($consolidatedHosts, ...$subdomains);
            }
        }

        return array_merge($keywords, $wildcards, $consolidatedHosts, $others);
    }

    /**
     * Consolidate values shared across multiple fetch directives into default-src
     *
     * When a directive's values are ALL common (shared by every eligible directive),
     * the directive is removed entirely so it falls back to default-src.
     * Directives with unique values are left untouched — default-src is only a fallback
     * and does NOT supplement an explicitly present directive.
     *
     * @param array<string, array<int, string>> $directives
     * @return array<string, array<int, string>>
     */
    private function consolidateIntoDefaultSrc(array $directives): array
    {
        // If default-src already contains 'none', consolidation would create a contradictory policy
        $existingDefaultSrc = $directives['default-src'] ?? [];
        if (in_array("'none'", $existingDefaultSrc, true)) {
            return $directives;
        }

        // Collect only the fallback-eligible directives that are present
        $eligibleDirectives = [];
        foreach (self::DEFAULT_SRC_FALLBACK_DIRECTIVES as $name) {
            if (isset($directives[$name]) && !empty($directives[$name])) {
                $eligibleDirectives[$name] = $directives[$name];
            }
        }

        // Need at least 2 directives to consolidate
        if (count($eligibleDirectives) < 2) {
            return $directives;
        }

        // Find values that appear in ALL eligible directives
        $valueSets = array_map(static function (array $values): array {
            return array_map('strtolower', $values);
        }, $eligibleDirectives);

        $commonValues = array_values(array_intersect(...array_values($valueSets)));

        if (empty($commonValues)) {
            return $directives;
        }

        // Determine which directives consist ENTIRELY of common values (no unique values).
        // Only those can be removed — they'll fall back to default-src correctly.
        // Directives with unique values must keep ALL their values because
        // an explicit directive does NOT inherit from default-src.
        $directivesToRemove = [];
        foreach ($eligibleDirectives as $name => $values) {
            $uniqueValues = array_filter($values, static function (string $value) use ($commonValues): bool {
                return !in_array(strtolower($value), $commonValues, true);
            });

            if (empty($uniqueValues)) {
                $directivesToRemove[] = $name;
            }
        }

        // Only proceed if at least one directive can be fully removed
        if (empty($directivesToRemove)) {
            return $directives;
        }

        // Map common lowercase values back to their original-case forms from the first removable directive
        $firstRemovable = $directives[$directivesToRemove[0]];
        $originalCaseMap = [];
        foreach ($firstRemovable as $value) {
            $lower = strtolower($value);
            if (in_array($lower, $commonValues, true)) {
                $originalCaseMap[$lower] = $value;
            }
        }

        $commonOriginalValues = array_values($originalCaseMap);

        // Add common values to default-src
        $existingDefaultSrcLower = array_map('strtolower', $existingDefaultSrc);

        foreach ($commonOriginalValues as $value) {
            if (!in_array(strtolower($value), $existingDefaultSrcLower, true)) {
                $existingDefaultSrc[] = $value;
                $existingDefaultSrcLower[] = strtolower($value);
            }
        }
        $directives['default-src'] = $existingDefaultSrc;

        // Remove only the fully-common directives
        foreach ($directivesToRemove as $name) {
            unset($directives[$name]);
        }

        $this->logger->debug(sprintf(
            'CSP Optimizer: Consolidated %d values into default-src, removed %d directives (%s)',
            count($commonOriginalValues),
            count($directivesToRemove),
            implode(', ', $directivesToRemove)
        ));

        // Ensure default-src is first in output
        $defaultSrc = $directives['default-src'];
        unset($directives['default-src']);
        $directives = ['default-src' => $defaultSrc] + $directives;

        return $directives;
    }

    /**
     * Parse CSP header into directives with their values
     *
     * @param string $headerValue
     * @return array<string, array<int, string>>
     */
    private function parseDirectives(string $headerValue): array
    {
        $directives = [];
        $parts = array_filter(array_map('trim', explode(';', $headerValue)));

        foreach ($parts as $part) {
            $tokens = preg_split('/\s+/', $part, -1, PREG_SPLIT_NO_EMPTY);
            if (empty($tokens)) {
                continue;
            }

            $directiveName = array_shift($tokens);
            $directives[$directiveName] = $tokens;
        }

        return $directives;
    }

    /**
     * Normalize a value for comparison
     *
     * @param string $value
     * @return string
     */
    private function normalizeValue(string $value): string
    {
        $normalized = rtrim($value, '/');

        if ($this->isHash($value) || $this->isNonce($value)) {
            return $normalized;
        }

        return strtolower($normalized);
    }

    /**
     * Check if value is a CSP keyword
     *
     * @param string $value
     * @return bool
     */
    private function isKeyword(string $value): bool
    {
        return in_array(strtolower($value), array_map('strtolower', self::CSP_KEYWORDS), true);
    }

    /**
     * Check if value is a hash (sha256, sha384, sha512)
     *
     * @param string $value
     * @return bool
     */
    private function isHash(string $value): bool
    {
        return (bool)preg_match("/^'(sha256|sha384|sha512)-[A-Za-z0-9+\/=]+'/", $value);
    }

    /**
     * Check if value is a nonce
     *
     * @param string $value
     * @return bool
     */
    private function isNonce(string $value): bool
    {
        return (bool)preg_match("/^'nonce-[A-Za-z0-9+\/=]+'/", $value);
    }

    /**
     * Check if value looks like a domain
     *
     * @param string $value
     * @return bool
     */
    private function isDomainLike(string $value): bool
    {
        return (bool)preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?)*/', $value);
    }

    /**
     * Check if a host is covered by any wildcard in the list
     *
     * @param string $host
     * @param array<int, string> $wildcards
     * @return bool
     */
    private function isCoveredByWildcard(string $host, array $wildcards): bool
    {
        foreach ($wildcards as $wildcard) {
            if ($this->domainMatcher->domainMatchesWildcard($host, $wildcard)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove wildcards that are covered by broader wildcards
     *
     * @param array<int, string> $wildcards
     * @return array<int, string>
     */
    private function removeRedundantWildcardsFromList(array $wildcards): array
    {
        if (count($wildcards) <= 1) {
            return $wildcards;
        }

        $result = [];

        foreach ($wildcards as $wildcard) {
            $isCovered = false;

            foreach ($wildcards as $otherWildcard) {
                if ($this->domainMatcher->isWildcardCoveredByBroader($wildcard, $otherWildcard)) {
                    $isCovered = true;
                    $this->logger->debug(sprintf(
                        'CSP Optimizer: Removed redundant wildcard "%s" (covered by "%s")',
                        $wildcard,
                        $otherWildcard
                    ));
                    break;
                }
            }

            if (!$isCovered) {
                $result[] = $wildcard;
            }
        }

        return $result;
    }

    /**
     * Sort values for consistent output
     *
     * @param array<int, string> $values
     * @return array<int, string>
     */
    private function sortValues(array $values): array
    {
        usort($values, function (string $a, string $b): int {
            $aIsKeyword = $this->isKeyword($a);
            $bIsKeyword = $this->isKeyword($b);

            if ($aIsKeyword && !$bIsKeyword) {
                return -1;
            }
            if (!$aIsKeyword && $bIsKeyword) {
                return 1;
            }

            $aIsWildcard = $this->domainMatcher->isWildcard($a);
            $bIsWildcard = $this->domainMatcher->isWildcard($b);

            if ($aIsWildcard && !$bIsWildcard) {
                return -1;
            }
            if (!$aIsWildcard && $bIsWildcard) {
                return 1;
            }

            return strcasecmp($a, $b);
        });

        return $values;
    }
}
