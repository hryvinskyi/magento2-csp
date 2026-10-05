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
        $wildcards = array_values(array_filter($sources, fn (string $token): bool => $this->isWildcardHost($token)));

        $kept = [];
        foreach ($sources as $token) {
            if (SourceKind::of($token) !== SourceKind::Host) {
                $kept[] = $token;
                continue;
            }
            if ($hasStar && $this->isCoveredByStar($token)) {
                continue;
            }
            if ($this->coveredByAny($token, $wildcards)) {
                continue;
            }
            $kept[] = $token;
        }

        return $kept;
    }

    /**
     * Whether any wildcard covers the token.
     *
     * @param string $token
     * @param list<string> $wildcards
     * @return bool
     */
    private function coveredByAny(string $token, array $wildcards): bool
    {
        foreach ($wildcards as $wildcard) {
            if ($this->coverage->covers($wildcard, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the token is a "*.domain" host source.
     *
     * @param string $token
     * @return bool
     */
    private function isWildcardHost(string $token): bool
    {
        $parts = $this->hostSourceParser->parse($token);

        return $parts !== null && $this->hostSourceParser->isWildcardHost($parts);
    }

    /**
     * Whether a bare `*` allows every request the host source allows.
     *
     * @param string $token
     * @return bool
     */
    private function isCoveredByStar(string $token): bool
    {
        $parts = $this->hostSourceParser->parse($token);

        return $parts !== null && in_array($parts['scheme'], self::STAR_SCHEMES, true);
    }
}
