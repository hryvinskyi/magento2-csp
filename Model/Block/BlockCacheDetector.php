<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Block;

use Hryvinskyi\Csp\Api\Block\BlockCacheDetectorInterface;
use Magento\Framework\App\Cache\Type\Block;
use Magento\Framework\View\Element\AbstractBlock;
use Psr\Log\LoggerInterface;

/**
 * Service for detecting block cache status
 */
class BlockCacheDetector implements BlockCacheDetectorInterface
{
    public function __construct(
        private readonly Block $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isBlockCacheable(AbstractBlock $block): bool
    {
        try {
            $cacheLifetime = $this->getCacheLifetimeFromBlock($block);
            return $cacheLifetime !== null && $cacheLifetime !== false;
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to determine if block is cacheable', [
                'block_name' => $block->getNameInLayout(),
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function isBlockCached(AbstractBlock $block): bool
    {
        if (!$this->isBlockCacheable($block)) {
            return false;
        }

        try {
            $cacheKey = $block->getCacheKey();
            $cachedContent = $this->cache->load($cacheKey);
            return !empty($cachedContent);
        } catch (\Throwable $e) {
            $this->logger->debug('Failed to check if block is cached', [
                'block_name' => $block->getNameInLayout(),
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cache lifetime from block using reflection
     *
     * @param AbstractBlock $block
     * @return int|bool|null
     * @throws \ReflectionException
     */
    private function getCacheLifetimeFromBlock(AbstractBlock $block)
    {
        $reflection = new \ReflectionClass($block);
        $method = $reflection->getMethod('getCacheLifetime');
        $method->setAccessible(true);

        return $method->invoke($block);
    }
}
