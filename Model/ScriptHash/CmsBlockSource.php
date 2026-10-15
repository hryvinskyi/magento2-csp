<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ScriptHash;

use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Store\Model\Store;

/**
 * Active CMS blocks of a store view, rendered.
 */
class CmsBlockSource implements ScriptSourceInterface
{
    /**
     * @param BlockRepositoryInterface $blockRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterProvider $filterProvider
     * @param CmsContentRenderer $renderer
     */
    public function __construct(
        private readonly BlockRepositoryInterface $blockRepository,
        private readonly SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        private readonly FilterProvider $filterProvider,
        private readonly CmsContentRenderer $renderer
    ) {
    }

    /**
     * @inheritDoc
     */
    public function contents(int $storeId): array
    {
        // The repository's store filter leaves out blocks of all store views unless their store is asked for too.
        $criteria = $this->searchCriteriaBuilderFactory->create()
            ->addFilter('store_id', [Store::DEFAULT_STORE_ID, $storeId], 'in')
            ->addFilter(BlockInterface::IS_ACTIVE, 1)
            ->create();
        $contents = [];
        foreach ($this->blockRepository->getList($criteria)->getItems() as $block) {
            $rendered = $this->renderer->render($this->filterProvider->getBlockFilter(), (string)$block->getContent(), $storeId);
            $contents[] = [
                'label' => sprintf('CMS block "%s" (ID %s)', (string)$block->getTitle(), (string)$block->getId()),
                'html' => $rendered['html'],
                'warning' => $rendered['warning'],
            ];
        }

        return $contents;
    }
}
