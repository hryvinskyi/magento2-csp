<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy;

use Hryvinskyi\Csp\Api\Config\OptimizationConfigInterface;
use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use Hryvinskyi\Csp\Api\PolicyOptimizationStepInterface;

/**
 * Runs the enabled optimisation steps over a policy, in their configured order.
 */
class PolicyOptimizer
{
    /**
     * @param OptimizationConfigInterface $config
     * @param array<string, PolicyOptimizationStepInterface> $steps Ordered by sortOrder in di.xml
     */
    public function __construct(
        private readonly OptimizationConfigInterface $config,
        private readonly array $steps = []
    ) {
    }

    /**
     * Optimised copy of the policy; the policy itself when optimisation is off.
     *
     * @param PolicyInterface $policy
     * @return PolicyInterface
     */
    public function optimize(PolicyInterface $policy): PolicyInterface
    {
        if (!$this->config->isValueOptimizationEnabled()) {
            return $policy;
        }
        foreach ($this->steps as $step) {
            if ($step->isEnabled()) {
                $policy = $step->apply($policy);
            }
        }

        return $policy;
    }
}
