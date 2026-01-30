<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Parses and validates CSP report JSON data.
 */
interface CspReportParserInterface
{
    /**
     * Parse JSON string and return the csp-report data.
     *
     * @param string $json
     * @return array
     * @throws LocalizedException
     */
    public function parse(string $json): array;

    /**
     * Parse JSON string and return the csp-report data with normalized keys (hyphens to underscores).
     *
     * @param string $json
     * @return array
     * @throws LocalizedException
     */
    public function parseNormalized(string $json): array;
}
