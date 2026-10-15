<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Cron;

use Hryvinskyi\Csp\Api\Config\ReportCleanupConfigInterface;
use Hryvinskyi\Csp\Api\ReportCleanupInterface;
use Psr\Log\LoggerInterface;

/**
 * Deletes old violation reports daily with the configured mode and threshold.
 */
class CleanOldReports
{
    /**
     * @param ReportCleanupConfigInterface $config
     * @param ReportCleanupInterface $reportCleanup
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ReportCleanupConfigInterface $config,
        private readonly ReportCleanupInterface $reportCleanup,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Clean when enabled; a failure is logged so the other cron jobs still run.
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->isReportCleanupEnabled()) {
            return;
        }
        try {
            $this->reportCleanup->clean($this->config->getReportCleanupMode(), $this->config->getReportCleanupThreshold());
        } catch (\Exception $exception) {
            $this->logger->error('CSP violation report cleanup failed.', ['exception' => $exception]);
        }
    }
}
