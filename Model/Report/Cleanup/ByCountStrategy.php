<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\Cleanup;

use Hryvinskyi\Csp\Api\ReportCleanupStrategyInterface;

/**
 * Keeps the threshold number of most recently seen reports and deletes the rest.
 */
class ByCountStrategy implements ReportCleanupStrategyInterface
{
    /**
     * @param ReportTable $reportTable
     */
    public function __construct(private readonly ReportTable $reportTable)
    {
    }

    /**
     * @inheritDoc
     */
    public function clean(int $threshold): int
    {
        $oldestKept = $this->reportTable->newestAt($threshold - 1);
        if ($oldestKept === null) {
            return 0;
        }

        return $this->reportTable->deleteSeenBefore($oldestKept['updated_at'], $oldestKept['report_id']);
    }

    /**
     * @inheritDoc
     */
    public function countAffected(int $threshold): int
    {
        return max(0, $this->reportTable->count() - $threshold);
    }
}
