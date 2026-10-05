<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Config;

/**
 * Settings of the scheduled violation report cleanup.
 *
 * @api
 */
interface ReportCleanupConfigInterface
{
    /**
     * Whether old violation reports are removed by cron.
     *
     * @return bool
     */
    public function isReportCleanupEnabled(): bool;

    /**
     * Code of the cleanup strategy.
     *
     * @return string
     */
    public function getReportCleanupMode(): string;

    /**
     * Days to keep (age mode) or reports to keep (count mode).
     *
     * @return int
     */
    public function getReportCleanupThreshold(): int;
}
