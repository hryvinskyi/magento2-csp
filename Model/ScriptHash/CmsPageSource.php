<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ScriptHash;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

/**
 * Active CMS pages of a store view, rendered.
 */
class CmsPageSource implements ScriptSourceInterface
{
    /**
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterProvider $filterProvider
     * @param CmsContentRenderer $renderer
     */
    public function __construct(
        private readonly PageRepositoryInterface $pageRepository,
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
        $criteria = $this->searchCriteriaBuilderFactory->create()
            ->addFilter('store_id', $storeId)
            ->addFilter(PageInterface::IS_ACTIVE, 1)
            ->create();
        $contents = [];
        foreach ($this->pageRepository->getList($criteria)->getItems() as $page) {
            if (!$page instanceof PageInterface) {
                continue;
            }
            $rendered = $this->renderer->render($this->filterProvider->getPageFilter(), (string)$page->getContent(), $storeId);
            $contents[] = [
                'label' => sprintf('CMS page "%s" (ID %s)', (string)$page->getTitle(), (string)$page->getId()),
                'html' => $rendered['html'],
                'warning' => $rendered['warning'],
            ];
        }

        return $contents;
    }
}
