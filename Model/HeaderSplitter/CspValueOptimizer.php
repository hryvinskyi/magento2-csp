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
        $optimizedDirectives = [];

        foreach ($directives as $directiveName => $values) {
            $optimizedValues = $this->optimizeDirectiveValues($directiveName, $values);
            if (!empty($optimizedValues)) {
                $optimizedDirectives[] = $directiveName . ' ' . implode(' ', $optimizedValues);
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

        // Step 1: Remove exact duplicates
        $values = $this->removeDuplicates($values);

        // Step 2: Remove redundant wildcard-covered entries (if enabled)
        if ($this->config->isRedundantWildcardRemovalEnabled()) {
            $values = $this->removeRedundantWildcards($values);
        }

        // Step 3: Sort for consistent output (keywords first, then alphabetical)
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
