<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\BlockedUriValueExtractorInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * @inheritDoc
 */
class BlockedUriValueExtractor implements BlockedUriValueExtractorInterface
{
    private const SPECIAL_URI_MAPPINGS = [
        'data' => 'data:',
        'blob' => 'blob:',
        'unsafe-inline' => 'unsafe-inline',
        'inline' => 'unsafe-inline',
    ];

    /**
     * @inheritDoc
     */
    public function extractValueAndType(string $blockedUri): array
    {
        if (isset(self::SPECIAL_URI_MAPPINGS[$blockedUri])) {
            return [self::SPECIAL_URI_MAPPINGS[$blockedUri], 'host'];
        }

        if (filter_var($blockedUri, FILTER_VALIDATE_URL) || str_contains($blockedUri, '.')) {
            $host = parse_url($blockedUri, PHP_URL_HOST);
            if ($host) {
                return [$host, 'host'];
            }
        }

        throw new LocalizedException(
            __('Cannot convert CSP report: unsupported or hash value type detected. Only host supported.')
        );
    }

    /**
     * @inheritDoc
     */
    public function extractValue(string $blockedUri): string
    {
        if (filter_var($blockedUri, FILTER_VALIDATE_URL) || str_contains($blockedUri, '.')) {
            $host = parse_url($blockedUri, PHP_URL_HOST);
            if ($host) {
                return $host;
            }
        }

        return $blockedUri;
    }
}
