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
 * Interface for handling block CSP policy operations
 */
interface BlockCspPolicyHandlerInterface
{
    /**
     * Handle CSP policies when block starts rendering
     *
     * @param AbstractBlock $block
     * @return void
     */
    public function handleBlockRenderingStart(AbstractBlock $block): void;

    /**
     * Handle CSP policies when block finishes rendering
     *
     * @param AbstractBlock $block
     * @return void
     */
    public function handleBlockRenderingComplete(AbstractBlock $block): void;
}
