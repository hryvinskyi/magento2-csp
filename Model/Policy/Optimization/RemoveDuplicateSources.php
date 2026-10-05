<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy\Optimization;

use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use Hryvinskyi\Csp\Api\PolicyOptimizationStepInterface;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\SourceKind;

/**
 * Removes repeated sources from each source list, keeping the first occurrence.
 *
 * Keywords, schemes and hosts compare case-insensitively; nonces, hashes and URL paths compare exactly.
 */
class RemoveDuplicateSources implements PolicyOptimizationStepInterface
{
    /**
     * @param DirectiveCatalog $catalog
     * @param HostSourceParser $hostSourceParser
     */
    public function __construct(
        private readonly DirectiveCatalog $catalog,
        private readonly HostSourceParser $hostSourceParser
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return true;
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
            $unique = $this->unique($sources);
            if ($unique !== $sources) {
                $policy = $policy->withSources($name, $unique);
            }
        }

        return $policy;
    }

    /**
     * Sources without repeats, in their original order.
     *
     * @param list<string> $sources
     * @return list<string>
     */
    private function unique(array $sources): array
    {
        $seen = [];
        $unique = [];
        foreach ($sources as $token) {
            $key = $this->identity($token);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $token;
        }

        return $unique;
    }

    /**
     * Comparison key of a token.
     *
     * @param string $token
     * @return string
     */
    private function identity(string $token): string
    {
        $kind = SourceKind::of($token);
        if ($kind === SourceKind::Nonce || $kind === SourceKind::Hash) {
            return $token;
        }
        if ($kind !== SourceKind::Host) {
            return strtolower($token);
        }
        $parts = $this->hostSourceParser->parse($token);

        return $parts === null ? $token : $this->hostSourceParser->compose($parts);
    }
}
