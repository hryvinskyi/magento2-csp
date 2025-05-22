<?php
/**
 * Copyright (c) 2025. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ReportGroup\Command;

use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Api\Data\ReportInterface;

interface SaveFromCspReportInterface
{
    /**
     * Store CSP report data from JSON into the Report Group model.
     *
     * @param ReportGroupInterface $reportGroup
     * @param string $json
     * @return ReportGroupInterface
     * @throws \Magento\Framework\Exception\LocalizedException If JSON data is invalid or missing required structure
     */
    public function execute(ReportGroupInterface $reportGroup, string $json): ReportGroupInterface;
}