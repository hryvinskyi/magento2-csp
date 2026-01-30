<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

/**
 * Extracts domain/value from blocked URI in CSP reports.
 */
interface BlockedUriValueExtractorInterface
{
    /**
     * Extract value and type from blocked URI.
     *
     * @param string $blockedUri
     * @return array{0: string, 1: string} [value, valueType]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function extractValueAndType(string $blockedUri): array;

    /**
     * Extract just the value (host/domain) from blocked URI.
     *
     * @param string $blockedUri
     * @return string
     */
    public function extractValue(string $blockedUri): string;
}
