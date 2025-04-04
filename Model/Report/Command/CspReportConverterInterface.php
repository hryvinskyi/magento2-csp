<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\Command;

use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;

interface CspReportConverterInterface
{
    /**
     * Convert CSP report to whitelist
     *
     * @param ReportInterface $cspReport
     * @return WhitelistInterface
     */
    public function convert(ReportInterface $cspReport): WhitelistInterface;
}