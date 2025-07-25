<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Cache;

use Magento\Csp\Api\Data\PolicyInterface;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * Interface for managing block-specific CSP policy caching
 */
interface BlockCspPolicyCacheInterface
{
    /**
     * Save CSP policies for a specific block
     *
     * @param AbstractBlock $block
     * @param array $policies
     * @return bool
     */
    public function saveBlockCspPolicies(AbstractBlock $block, array $policies): bool;

    /**
     * Load CSP policies for a specific block
     *
     * @param AbstractBlock $block
     * @return PolicyInterface[]
     */
    public function loadBlockCspPolicies(AbstractBlock $block): array;

    /**
     * Clear CSP policies cache for a specific block
     *
     * @param AbstractBlock $block
     * @return bool
     */
    public function clearBlockCspPolicies(AbstractBlock $block): bool;
}
