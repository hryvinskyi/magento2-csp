<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Api\ReportCleanupInterface;
use Hryvinskyi\Csp\Api\ReportCleanupStrategyInterface;
use Hryvinskyi\Csp\Model\Report\Cleanup\ReportTable;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
class Cleanup implements ReportCleanupInterface
{
    /**
     * @param ReportTable $reportTable
     * @param LoggerInterface $logger
     * @param array<string, ReportCleanupStrategyInterface> $strategies Mode code => strategy
     */
    public function __construct(
        private readonly ReportTable $reportTable,
        private readonly LoggerInterface $logger,
        private readonly array $strategies = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function modes(): array
    {
        return array_keys($this->strategies);
    }

    /**
     * @inheritDoc
     */
    public function clean(string $mode, int $threshold): int
    {
        $deleted = $this->strategy($mode, $threshold)->clean($threshold);
        $groups = $this->reportTable->deleteEmptyGroups(Status::PENDING->value);
        if ($deleted > 0 || $groups > 0) {
            $this->logger->info('CSP violation reports cleaned.', [
                'mode' => $mode,
                'threshold' => $threshold,
                'reports' => $deleted,
                'groups' => $groups,
            ]);
        }

        return $deleted;
    }

    /**
     * @inheritDoc
     */
    public function countAffected(string $mode, int $threshold): int
    {
        return $this->strategy($mode, $threshold)->countAffected($threshold);
    }

    /**
     * Strategy of the mode.
     *
     * @param string $mode
     * @param int $threshold
     * @return ReportCleanupStrategyInterface
     * @throws \InvalidArgumentException
     */
    private function strategy(string $mode, int $threshold): ReportCleanupStrategyInterface
    {
        if ($threshold < 1) {
            throw new \InvalidArgumentException('The cleanup threshold must be a positive number.');
        }

        return $this->strategies[$mode]
            ?? throw new \InvalidArgumentException(sprintf('Unknown cleanup mode "%s". Use one of: %s.', $mode, implode(', ', $this->modes())));
    }
}
