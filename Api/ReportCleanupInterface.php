<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

/**
 * Deletes old violation reports with the strategy a mode names, then the pending groups left without reports.
 *
 * @api
 */
interface ReportCleanupInterface
{
    /**
     * Codes of the available modes.
     *
     * @return list<string>
     */
    public function modes(): array;

    /**
     * Delete old reports; returns how many reports were deleted.
     *
     * @param string $mode
     * @param int $threshold Positive
     * @return int
     * @throws \InvalidArgumentException For an unknown mode or a threshold below 1
     */
    public function clean(string $mode, int $threshold): int;

    /**
     * How many reports cleaning would delete.
     *
     * @param string $mode
     * @param int $threshold Positive
     * @return int
     * @throws \InvalidArgumentException For an unknown mode or a threshold below 1
     */
    public function countAffected(string $mode, int $threshold): int;
}
