<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Plugin\Framework\App\Response\HttpInterface;

use Hryvinskyi\Csp\Api\Config\ReportingConfigInterface;
use Magento\Framework\App\Response\HttpInterface;

/**
 * Removes the `report-to` directive from CSP headers while this module collects reports.
 *
 * A browser that supports the Reporting API ignores `report-uri` when `report-to` is present and sends batched
 * reports to the Report-To endpoint instead; without the directive every browser posts each violation to
 * `report-uri`, which this module receives.
 */
class FixSendCspReport
{
    private const CSP_HEADERS = ['content-security-policy', 'content-security-policy-report-only'];

    /**
     * @param ReportingConfigInterface $config
     */
    public function __construct(private readonly ReportingConfigInterface $config)
    {
    }

    /**
     * Header value without `report-to`.
     *
     * @param HttpInterface $subject
     * @param mixed $name
     * @param mixed $value
     * @param mixed $replace
     * @return array{mixed, mixed, mixed}
     */
    public function beforeSetHeader(HttpInterface $subject, mixed $name, mixed $value, mixed $replace = false): array
    {
        if (!is_string($name)
            || !is_string($value)
            || !in_array(strtolower($name), self::CSP_HEADERS, true)
            || !$this->config->isReportsEnabled()
        ) {
            return [$name, $value, $replace];
        }

        $withoutReportTo = preg_replace('/\s*\breport-to\s+[^;]*(?:;|$)/i', '', $value);

        return [$name, is_string($withoutReportTo) ? trim($withoutReportTo) : $value, $replace];
    }
}
