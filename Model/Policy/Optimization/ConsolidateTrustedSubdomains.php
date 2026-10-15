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
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;

/**
 * Replaces subdomains of a parent domain the admin trusts with one `*.parent` wildcard.
 *
 * This widens the policy on purpose: the wildcard allows every subdomain of the parent. Only parents listed in the
 * configuration are consolidated, so registry suffixes (co.uk) and shared hosting domains (cloudfront.net) never are
 * unless the admin lists them. Hosts are grouped by scheme, and the wildcard keeps it; hosts with a port or a path,
 * and schemes other than http(s), are left alone.
 */
class ConsolidateTrustedSubdomains implements PolicyOptimizationStepInterface
{
    private const WEB_SCHEMES = [null, 'http', 'https'];

    /**
     * @param OptimizationConfigInterface $config
     * @param DirectiveCatalog $catalog
     * @param HostSourceParser $hostSourceParser
     */
    public function __construct(
        private readonly OptimizationConfigInterface $config,
        private readonly DirectiveCatalog $catalog,
        private readonly HostSourceParser $hostSourceParser
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->config->isSubdomainWildcardConsolidationEnabled()
            && $this->config->getSubdomainWildcardParents() !== [];
    }

    /**
     * @inheritDoc
     */
    public function apply(PolicyInterface $policy): PolicyInterface
    {
        $parents = $this->config->getSubdomainWildcardParents();
        $threshold = $this->config->getSubdomainWildcardThreshold();

        foreach ($policy->names() as $name) {
            if (!$this->catalog->isSourceListDirective($name)) {
                continue;
            }
            $sources = $policy->sources($name);
            $consolidated = $this->consolidate($sources, $parents, $threshold);
            if ($consolidated !== $sources) {
                $policy = $policy->withSources($name, $consolidated);
            }
        }

        return $policy;
    }

    /**
     * Sources with each trusted parent's subdomains replaced by its wildcard, at the first subdomain's position.
     *
     * @param list<string> $sources
     * @param list<string> $parents
     * @param int $threshold
     * @return list<string>
     */
    private function consolidate(array $sources, array $parents, int $threshold): array
    {
        $byParent = [];
        foreach ($sources as $index => $token) {
            $wildcard = $this->wildcardFor($token, $parents);
            if ($wildcard !== null) {
                $byParent[$wildcard][] = $index;
            }
        }

        $replaced = [];
        $wildcards = [];
        foreach ($byParent as $wildcard => $indexes) {
            if (count($indexes) < $threshold) {
                continue;
            }
            foreach ($indexes as $position => $index) {
                $replaced[$index] = $position === 0 && !in_array($wildcard, $sources, true) ? $wildcard : null;
            }
            $wildcards[] = $wildcard;
        }

        if ($wildcards === []) {
            return $sources;
        }

        $result = [];
        foreach ($sources as $index => $token) {
            if (!array_key_exists($index, $replaced)) {
                $result[] = $token;
                continue;
            }
            $replacement = $replaced[$index];
            if ($replacement !== null) {
                $result[] = $replacement;
            }
        }

        return $result;
    }

    /**
     * Wildcard that replaces the token: the token's scheme and `*.` + the longest trusted parent it is a subdomain of;
     * null when no trusted parent applies.
     *
     * @param string $token
     * @param list<string> $parents
     * @return string|null
     */
    private function wildcardFor(string $token, array $parents): ?string
    {
        $parts = $this->hostSourceParser->parse($token);
        if ($parts === null
            || $parts['port'] !== null
            || $parts['path'] !== null
            || !in_array($parts['scheme'], self::WEB_SCHEMES, true)
            || $this->hostSourceParser->isWildcardHost($parts)
        ) {
            return null;
        }

        $match = null;
        foreach ($parents as $parent) {
            if (str_ends_with($parts['host'], '.' . $parent)
                && ($match === null || strlen($parent) > strlen($match))
            ) {
                $match = $parent;
            }
        }

        return $match === null ? null : ($parts['scheme'] !== null ? $parts['scheme'] . '://' : '') . '*.' . $match;
    }
}
