<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Cache;

use Hryvinskyi\Csp\Api\Cache\BlockCspPolicyCacheInterface;
use Hryvinskyi\Csp\Api\Serializer\CspPolicySerializerInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Psr\Log\LoggerInterface;

/**
 * Service for caching block-specific CSP policies
 */
class BlockCspPolicyCache implements BlockCspPolicyCacheInterface
{
    private const CACHE_KEY_PREFIX = 'hryvinskyi_csp_policies_block_';
    private const CACHE_TAGS = ['hryvinskyi_csp_block'];
    private const CACHE_LIFETIME = 3600 * 24 * 30; // 30 days

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer,
        private readonly CspPolicySerializerInterface $policySerializer,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function saveBlockCspPolicies(AbstractBlock $block, array $policies): bool
    {
        if (empty($policies)) {
            return true;
        }

        try {
            $cacheKey = $this->generateCacheKey($block);
            $serializedData = $this->serializer->serialize($policies);

            $result = $this->cache->save(
                $serializedData,
                $cacheKey,
                array_merge(self::CACHE_TAGS, [$block->getNameInLayout()]),
                self::CACHE_LIFETIME
            );

            if ($result) {
                $this->logger->debug('Saved CSP policies to block cache', [
                    'block_name' => $block->getNameInLayout(),
                    'policies_count' => count($policies),
                    'cache_key' => $cacheKey
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to save CSP policies to block cache', [
                'block_name' => $block->getNameInLayout(),
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function loadBlockCspPolicies(AbstractBlock $block): array
    {
        try {
            $cacheKey = $this->generateCacheKey($block);
            $cachedData = $this->cache->load($cacheKey);

            if ($cachedData) {
                $policiesData = $this->serializer->unserialize($cachedData);
                $policiesData = $this->policySerializer->unserialize($policiesData);

                $this->logger->debug('Restored CSP policies from block cache', [
                    'block_name' => $block->getNameInLayout(),
                    'policies_count' => count($policiesData),
                    'cache_key' => $cacheKey
                ]);

                return $policiesData;
            }

            return [];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to restore CSP policies from block cache', [
                'block_name' => $block->getNameInLayout(),
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * @inheritDoc
     */
    public function clearBlockCspPolicies(AbstractBlock $block): bool
    {
        try {
            $cacheKey = $this->generateCacheKey($block);
            $result = $this->cache->remove($cacheKey);

            if ($result) {
                $this->logger->debug('Cleared CSP policies cache for block', [
                    'block_name' => $block->getNameInLayout(),
                    'cache_key' => $cacheKey
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to clear CSP policies cache for block', [
                'block_name' => $block->getNameInLayout(),
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate cache key for block-specific CSP policies
     *
     * @param AbstractBlock $block
     * @return string
     * @throws RuntimeException
     */
    private function generateCacheKey(AbstractBlock $block): string
    {
        // Use the block's own cache key to ensure proper cache invalidation
        $blockCacheKey = $block->getCacheKey();
        return self::CACHE_KEY_PREFIX . md5($blockCacheKey);
    }
}
