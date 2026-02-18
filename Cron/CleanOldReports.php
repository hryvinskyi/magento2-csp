<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Cron;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Hryvinskyi\Csp\Api\ReportCleanupInterface;
use Hryvinskyi\Csp\Model\Config\Source\CleanupMode;
use Psr\Log\LoggerInterface;

class CleanOldReports
{
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ReportCleanupInterface $reportCleanup,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Execute cron job to clean old CSP violation reports
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->isReportCleanupEnabled()) {
            return;
        }

        $mode = $this->config->getReportCleanupMode();
        $threshold = $this->config->getReportCleanupThreshold();

        try {
            $deleted = match ($mode) {
                CleanupMode::MODE_COUNT => $this->reportCleanup->cleanByCount($threshold),
                default => $this->reportCleanup->cleanByDate($threshold),
            };

            if ($deleted > 0) {
                $this->logger->info(
                    sprintf('CSP report cleanup cron: deleted %d records (mode: %s, threshold: %d).', $deleted, $mode, $threshold)
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('CSP report cleanup cron failed: %s', $e->getMessage())
            );
        }
    }
}
