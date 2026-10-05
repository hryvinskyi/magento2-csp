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
use Hryvinskyi\Csp\Model\Policy\PolicyEquivalence;

/**
 * Removes fetch directives that allow exactly what the browser would fall back to without them.
 *
 * `img-src 'self' 'unsafe-inline'` next to `default-src 'self' 'unsafe-inline' 'unsafe-eval'` is removed: images
 * ignore inline and eval, so both allow the same requests. A directive is removed only when no request type changes,
 * and `default-src` itself is never modified.
 */
class RemoveRedundantDirectives implements PolicyOptimizationStepInterface
{
    /**
     * @param OptimizationConfigInterface $config
     * @param DirectiveCatalog $catalog
     * @param PolicyEquivalence $equivalence
     */
    public function __construct(
        private readonly OptimizationConfigInterface $config,
        private readonly DirectiveCatalog $catalog,
        private readonly PolicyEquivalence $equivalence
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->config->isRedundantDirectiveRemovalEnabled();
    }

    /**
     * @inheritDoc
     */
    public function apply(PolicyInterface $policy): PolicyInterface
    {
        foreach ($policy->names() as $name) {
            if ($name === DirectiveCatalog::DEFAULT_SRC || !$this->catalog->isFetchDirective($name)) {
                continue;
            }
            $candidate = $policy->without($name);
            if ($this->equivalence->isEquivalent($policy, $candidate)) {
                $policy = $candidate;
            }
        }

        return $policy;
    }
}
