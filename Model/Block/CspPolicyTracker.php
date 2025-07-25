<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Block;

use Hryvinskyi\Csp\Api\Block\CspPolicyTrackerInterface;
use Hryvinskyi\Csp\Api\CachedCspManagerInterface;
use Hryvinskyi\Csp\Api\Serializer\CspPolicySerializerInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Psr\Log\LoggerInterface;

/**
 * Service for tracking CSP policy changes during block rendering
 */
class CspPolicyTracker implements CspPolicyTrackerInterface
{
    private array $policiesBefore = [];
    private ?string $currentTrackingBlock = null;

    public function __construct(
        private readonly CachedCspManagerInterface $cspManager,
        private readonly CspPolicySerializerInterface $policySerializer,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function startTracking(AbstractBlock $block): void
    {
        $blockName = $block->getNameInLayout();
        if (!$blockName) {
            return;
        }
        $this->currentTrackingBlock = $blockName;

        $this->policiesBefore[$blockName] = array_keys($this->cspManager->getAllPolicies());

        $this->logger->debug('Started tracking CSP policies for block', [
            'block_name' => $blockName,
            'initial_policies_count' => count($this->policiesBefore[$blockName])
        ]);
    }

    /**
     * @inheritDoc
     */
    public function stopTrackingAndGetNewPolicies(AbstractBlock $block): array
    {
        $blockName = $block->getNameInLayout();
        if (!$blockName || !isset($this->policiesBefore[$blockName])) {
            return [];
        }

        try {
            $previousPolicyKeys = $this->policiesBefore[$blockName];
            $currentPolicies = $this->cspManager->getAllPolicies();

            $newPoliciesData = [];
            foreach ($currentPolicies as $key => $policy) {
                if (!in_array($key, $previousPolicyKeys, true)) {
                    $newPoliciesData[$key] = $policy;
                }
            }

            if (count($newPoliciesData) > 0) {
                $newPoliciesData = $this->policySerializer->serialize($newPoliciesData);
            }

            $this->logger->debug('Stopped tracking CSP policies for block', [
                'block_name' => $blockName,
                'new_policies_count' => count($newPoliciesData)
            ]);

            return $newPoliciesData;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get new CSP policies for block', [
                'block_name' => $blockName,
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * @inheritDoc
     */
    public function clearTracking(AbstractBlock $block): void
    {
        $this->currentTrackingBlock = null;
        $blockName = $block->getNameInLayout();
        if ($blockName && isset($this->policiesBefore[$blockName])) {
            unset($this->policiesBefore[$blockName]);
        }
    }

    /**
     * @inheritDoc
     */
    public function getCurrentTrackingBlock(): ?string
    {
        return $this->currentTrackingBlock;
    }
}
