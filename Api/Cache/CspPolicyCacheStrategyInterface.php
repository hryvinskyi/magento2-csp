<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Cache;

use Magento\Csp\Api\Data\PolicyInterface;

interface CspPolicyCacheStrategyInterface
{
    /**
     * Load CSP policies from cache
     *
     * @return PolicyInterface[]
     */
    public function load(): array;

    /**
     * Save CSP policies to cache
     *
     * @param PolicyInterface[] $policies
     * @return bool
     */
    public function save(array $policies): bool;

    /**
     * Clear CSP policies from cache
     *
     * @return bool
     */
    public function clear(): bool;

    /**
     * Check if cache is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Get cache tags
     *
     * @return string[]
     */
    public function getCacheTags(): array;

    /**
     * Get cache key
     *
     * @return string
     */
    public function getCacheKey(): string;
}