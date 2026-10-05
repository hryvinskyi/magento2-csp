<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Magento\Framework\App\Cache\Type\Collection as CollectionCache;
use Magento\Framework\App\Cache\TypeListInterface;

/**
 * Drops cached whitelist entries after a change and marks the full-page cache invalid, so the admin is told that
 * cached pages still carry the old policy.
 */
class WhitelistCacheInvalidator
{
    private const FULL_PAGE_CACHE = 'full_page';

    /**
     * @param CollectionCache $cache
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        private readonly CollectionCache $cache,
        private readonly TypeListInterface $cacheTypeList
    ) {
    }

    /**
     * Clean the cached entries and invalidate the full-page cache.
     *
     * @return void
     */
    public function invalidate(): void
    {
        $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, [ActiveEntries::CACHE_TAG]);
        $this->cacheTypeList->invalidate(self::FULL_PAGE_CACHE);
    }
}
