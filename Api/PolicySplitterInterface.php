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
 * Splits a policy into several policies, each sent as its own header line, that a browser enforces together.
 *
 * The parts must together allow exactly what the policy allows and each must fit the limit; when that is not
 * possible the policy itself is returned. The preference checks both for whichever splitting strategy is
 * configured as its `strategy` argument and falls back to the unsplit policy when either does not hold.
 *
 * @api
 */
interface PolicySplitterInterface
{
    /**
     * Parts of at most the given size, or a list holding only the policy.
     *
     * @param PolicyInterface $policy
     * @param int $maxBytes
     * @return list<PolicyInterface>
     */
    public function split(PolicyInterface $policy, int $maxBytes): array;
}
