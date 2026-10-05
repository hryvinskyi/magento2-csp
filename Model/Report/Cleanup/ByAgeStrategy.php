<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\Cleanup;

use Hryvinskyi\Csp\Api\ReportCleanupStrategyInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Deletes reports not seen for more than the threshold in days.
 */
class ByAgeStrategy implements ReportCleanupStrategyInterface
{
    private const SECONDS_PER_DAY = 86400;

    /**
     * @param ReportTable $reportTable
     * @param DateTime $dateTime
     */
    public function __construct(
        private readonly ReportTable $reportTable,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * @inheritDoc
     */
    public function clean(int $threshold): int
    {
        return $this->reportTable->deleteSeenBefore($this->cutoff($threshold));
    }

    /**
     * @inheritDoc
     */
    public function countAffected(int $threshold): int
    {
        return $this->reportTable->countSeenBefore($this->cutoff($threshold));
    }

    /**
     * UTC time the threshold in days reaches back to.
     *
     * @param int $days
     * @return string
     */
    private function cutoff(int $days): string
    {
        return (string)$this->dateTime->gmtDate('Y-m-d H:i:s', $this->dateTime->gmtTimestamp() - $days * self::SECONDS_PER_DAY);
    }
}
