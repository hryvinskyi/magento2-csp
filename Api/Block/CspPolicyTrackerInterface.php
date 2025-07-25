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
 * Interface for tracking CSP policy changes during block rendering
 */
interface CspPolicyTrackerInterface
{
    /**
     * Start tracking CSP policies for a block
     *
     * @param AbstractBlock $block
     * @return void
     */
    public function startTracking(AbstractBlock $block): void;

    /**
     * Stop tracking and return new CSP policies added during block rendering
     *
     * @param AbstractBlock $block
     * @return array
     */
    public function stopTrackingAndGetNewPolicies(AbstractBlock $block): array;

    /**
     * Clear tracking data for a block
     *
     * @param AbstractBlock $block
     * @return void
     */
    public function clearTracking(AbstractBlock $block): void;

    /**
     * Get the currently tracked block name
     *
     * @return string|null
     */
    public function getCurrentTrackingBlock(): ?string;
}
