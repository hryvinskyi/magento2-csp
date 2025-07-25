<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Block;

use Hryvinskyi\Csp\Api\Block\BlockCacheDetectorInterface;
use Hryvinskyi\Csp\Api\Block\BlockCspPolicyHandlerInterface;
use Hryvinskyi\Csp\Api\Block\CspPolicyTrackerInterface;
use Hryvinskyi\Csp\Api\Cache\BlockCspPolicyCacheInterface;
use Hryvinskyi\Csp\Api\CachedCspManagerInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Psr\Log\LoggerInterface;

/**
 * Service for handling block-level CSP policy operations
 */
class BlockCspPolicyHandler implements BlockCspPolicyHandlerInterface
{
    public function __construct(
        private readonly BlockCacheDetectorInterface $cacheDetector,
        private readonly CspPolicyTrackerInterface $policyTracker,
        private readonly BlockCspPolicyCacheInterface $blockCache,
        private readonly CachedCspManagerInterface $cspManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handleBlockRenderingStart(AbstractBlock $block): void
    {
        if (!$this->shouldProcessBlock($block)) {
            return;
        }

        if ($this->policyTracker->getCurrentTrackingBlock() === null) {
            if ($this->cacheDetector->isBlockCached($block) === false) {
                // Block will execute normally, start tracking
                $this->policyTracker->startTracking($block);
                $this->logger->debug('Started CSP policy tracking for block', [
                    'block_name' => $block->getNameInLayout()
                ]);
            } else {
                // Block will load from cache, restore CSP policies
                $this->restoreCspPoliciesForBlock($block);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function handleBlockRenderingComplete(AbstractBlock $block): void
    {
        if (!$this->shouldProcessBlock($block)) {
            return;
        }

        if ($this->policyTracker->getCurrentTrackingBlock() === $block->getNameInLayout()) {
            // Block was executed (not cached), save new CSP policies
            $newPolicies = $this->policyTracker->stopTrackingAndGetNewPolicies($block);
            $this->blockCache->saveBlockCspPolicies($block, $newPolicies);

            $this->logger->debug('Saved CSP policies for block after rendering', [
                'block_name' => $block->getNameInLayout(),
                'policies_count' => count($newPolicies)
            ]);

            // Always clean up tracking
            $this->policyTracker->clearTracking($block);
        }
    }

    /**
     * Check if block should be processed for CSP policy caching
     *
     * @param AbstractBlock $block
     * @return bool
     */
    private function shouldProcessBlock(AbstractBlock $block): bool
    {
        return $block->getNameInLayout() && $this->cacheDetector->isBlockCacheable($block);
    }

    /**
     * Restore CSP policies for a block from cache
     *
     * @param AbstractBlock $block
     * @return void
     */
    private function restoreCspPoliciesForBlock(AbstractBlock $block): void
    {
        $policies = $this->blockCache->loadBlockCspPolicies($block);

        if (count($policies) > 0) {
            foreach ($policies as $key => $policy) {
                $this->cspManager->addPolicy($policy, $key);
            }

            $this->logger->debug('Restored CSP policies for cached block', [
                'block_name' => $block->getNameInLayout(),
                'policies_count' => count($policies)
            ]);
        }
    }
}
