<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\ViolationInterface;

/**
 * Stores a violation under its report group.
 */
interface ViolationRecorderInterface
{
    /**
     * Count the violation in its group and its report; false when the group is denied and records nothing.
     *
     * @param ViolationInterface $violation
     * @param string $policy
     * @param string $value
     * @param int $storeId
     * @param Area $area
     * @return bool
     */
    public function record(ViolationInterface $violation, string $policy, string $value, int $storeId, Area $area): bool;
}
