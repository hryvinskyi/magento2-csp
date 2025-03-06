<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Cache;

use Magento\Framework\App\Cache\Type\Collection;
use Magento\PageCache\Model\Cache\Type;

class CacheCleaner implements CacheCleanerInterface
{
    /**
     * @param Collection $cacheTypeCollection
     * @param Type $cacheType
     */
    public function __construct(
        private readonly Collection $cacheTypeCollection,
        private readonly Type $cacheType
    ) {
    }

    /**
     * @inheritDoc
     */
    public function cleanCaches(): void
    {
        $this->cacheTypeCollection->clean();
        $this->cacheType->clean();
    }
}