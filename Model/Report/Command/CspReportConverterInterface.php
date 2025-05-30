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
use Magento\Framework\Exception\LocalizedException;

interface CspReportConverterInterface
{
    /**
     * Convert CSP report to whitelist
     *
     * @param ReportInterface $cspReport
     * @return WhitelistInterface
     * @throws LocalizedException
     */
    public function convert(ReportInterface $cspReport): WhitelistInterface;

    /**
     * Convert CSP report from array to whitelist
     *
     * @param array $cspReport
     * @return WhitelistInterface
     * @throws LocalizedException
     */
    public function convertFromArray(array $cspReport): WhitelistInterface;

    /**
     * Normalize the policy name
     *
     * @param string $policy
     * @return string
     */
    public function normalizePolicy(string $policy): string;
}