<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Observer;

use Hryvinskyi\Csp\Api\Block\BlockCspPolicyHandlerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Element\AbstractBlock as MagentoAbstractBlock;

class BlockHtmlAfterObserver implements ObserverInterface
{
    public function __construct(
        private readonly BlockCspPolicyHandlerInterface $blockCspPolicyHandler
    ) {
    }

    /**
     * Observer for block HTML generation after event
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $block = $observer->getEvent()->getBlock();
        
        if ($block instanceof MagentoAbstractBlock) {
            $this->blockCspPolicyHandler->handleBlockRenderingComplete($block);
        }
    }
}
