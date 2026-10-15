<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy\Optimization;

use Hryvinskyi\Csp\Api\Config\OptimizationConfigInterface;
use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use Hryvinskyi\Csp\Api\PolicyOptimizationStepInterface;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\HostSourceCoverage;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\SourceKind;

/**
 * Removes host sources that a wildcard in the same directive already allows.
 *
 * A bare `*` covers host sources without a scheme or with http(s), not ws(s) or other schemes; keywords, nonces,
 * hashes and scheme sources are never removed, so `'unsafe-inline'` stays inactive next to a nonce or hash.
 */
class RemoveCoveredHosts implements PolicyOptimizationStepInterface
{
    private const STAR_SCHEMES = [null, 'http', 'https'];

    /**
     * @param OptimizationConfigInterface $config
     * @param DirectiveCatalog $catalog
     * @param HostSourceParser $hostSourceParser
     * @param HostSourceCoverage $coverage
     */
    public function __construct(
        private readonly OptimizationConfigInterface $config,
        private readonly DirectiveCatalog $catalog,
        private readonly HostSourceParser $hostSourceParser,
        private readonly HostSourceCoverage $coverage
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->config->isRedundantWildcardRemovalEnabled();
    }

    /**
     * @inheritDoc
     */
    public function apply(PolicyInterface $policy): PolicyInterface
    {
        foreach ($policy->names() as $name) {
            if (!$this->catalog->isSourceListDirective($name)) {
                continue;
            }
            $sources = $policy->sources($name);
            $kept = $this->withoutCovered($sources);
            if ($kept !== $sources) {
                $policy = $policy->withSources($name, $kept);
            }
        }

        return $policy;
    }

    /**
     * Sources without the hosts another source covers.
     *
     * @param list<string> $sources
     * @return list<string>
     */
    private function withoutCovered(array $sources): array
    {
        $hasStar = in_array('*', $sources, true);
        $hosts = [];
        $wildcards = [];
        foreach ($sources as $index => $token) {
            $parts = SourceKind::of($token) === SourceKind::Host ? $this->hostSourceParser->parse($token) : null;
            if ($parts === null) {
                continue;
            }
            $hosts[$index] = $parts;
            if ($this->hostSourceParser->isWildcardHost($parts)) {
                $wildcards[substr($parts['host'], 1)][$token] = $parts;
            }
        }

        $kept = [];
        foreach ($sources as $index => $token) {
            $parts = $hosts[$index] ?? null;
            if ($parts !== null && $this->isCovered($token, $parts, $hasStar, $wildcards)) {
                continue;
            }
            $kept[] = $token;
        }

        return $kept;
    }

    /**
     * Whether `*` or another wildcard of the directive allows every request the host source allows.
     *
     * @param string $token
     * @param array{scheme: string|null, host: string, port: string|null, path: string|null} $parts
     * @param bool $hasStar
     * @param array<string, array<string, array{scheme: string|null, host: string, port: string|null, path: string|null}>> $wildcards
     *        Wildcards by their ".domain" suffix, then by token
     * @return bool
     */
    private function isCovered(string $token, array $parts, bool $hasStar, array $wildcards): bool
    {
        if ($hasStar && in_array($parts['scheme'], self::STAR_SCHEMES, true)) {
            return true;
        }
        foreach ($this->parentSuffixes($parts['host']) as $suffix) {
            foreach ($wildcards[$suffix] ?? [] as $wildcardToken => $wildcard) {
                if ($wildcardToken !== $token && $this->coverage->coversParts($wildcard, $parts)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The ".domain" suffixes a wildcard needs to cover the host: ".example.com" and ".com" for "a.example.com" or
     * "*.a.example.com".
     *
     * @param string $host
     * @return list<string>
     */
    private function parentSuffixes(string $host): array
    {
        $bare = str_starts_with($host, '*.') ? substr($host, 1) : '.' . $host;
        $suffixes = [];
        $position = strpos($bare, '.', 1);
        while ($position !== false) {
            $suffixes[] = substr($bare, $position);
            $position = strpos($bare, '.', $position + 1);
        }

        return $suffixes;
    }
}
