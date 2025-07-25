<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Block;

use Magento\Framework\View\Element\AbstractBlock;

/**
 * Interface for detecting block cache status
 */
interface BlockCacheDetectorInterface
{
    /**
     * Check if block is cacheable
     *
     * @param AbstractBlock $block
     * @return bool
     */
    public function isBlockCacheable(AbstractBlock $block): bool;

    /**
     * Check if block content was served from cache
     *
     * @param AbstractBlock $block
     * @return bool
     */
    public function isBlockCached(AbstractBlock $block): bool;
}
