<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Hryvinskyi\Csp\Api\Data\PolicyInterface;

/**
 * One header optimisation step. Steps are registered in the `steps` argument of
 * `Hryvinskyi\Csp\Model\Policy\PolicyOptimizer` and run in sort order.
 *
 * A step must keep what the policy allows, unless it is an explicit widening the admin opted into.
 *
 * @api
 */
interface PolicyOptimizationStepInterface
{
    /**
     * Whether the step runs for the current store.
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Optimised copy of the policy.
     *
     * @param PolicyInterface $policy
     * @return PolicyInterface
     */
    public function apply(PolicyInterface $policy): PolicyInterface;
}
