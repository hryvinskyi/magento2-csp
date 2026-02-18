<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

interface ReportCleanupInterface
{
    /**
     * Delete violation reports older than the specified number of days
     *
     * @param int $days Number of days to keep
     *
     * @return int Number of deleted records
     */
    public function cleanByDate(int $days): int;

    /**
     * Delete oldest violation reports, keeping only the specified number of most recent records
     *
     * @param int $maxRecords Maximum number of records to keep
     *
     * @return int Number of deleted records
     */
    public function cleanByCount(int $maxRecords): int;

    /**
     * Get the total number of violation report records
     *
     * @return int
     */
    public function getTotalCount(): int;

    /**
     * Get the number of records that would be deleted by date cleanup (dry-run)
     *
     * @param int $days Number of days to keep
     *
     * @return int Number of records that would be deleted
     */
    public function countByDate(int $days): int;

    /**
     * Get the number of records that would be deleted by count cleanup (dry-run)
     *
     * @param int $maxRecords Maximum number of records to keep
     *
     * @return int Number of records that would be deleted
     */
    public function countByCount(int $maxRecords): int;
}
