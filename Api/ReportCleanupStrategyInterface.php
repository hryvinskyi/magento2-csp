<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

/**
 * One way of deciding which violation reports are old enough to delete.
 *
 * @api
 */
interface ReportCleanupStrategyInterface
{
    /**
     * Delete the reports the threshold selects; returns how many were deleted.
     *
     * @param int $threshold
     * @return int
     */
    public function clean(int $threshold): int;

    /**
     * How many reports the threshold selects.
     *
     * @param int $threshold
     * @return int
     */
    public function countAffected(int $threshold): int;
}
