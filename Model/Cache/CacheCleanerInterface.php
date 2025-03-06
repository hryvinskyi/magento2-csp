<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Cache;

/**
 * Interface for cache cleaning operations
 */
interface CacheCleanerInterface
{
    /**
     * Clean all necessary caches
     *
     * @return void
     */
    public function cleanCaches(): void;
}