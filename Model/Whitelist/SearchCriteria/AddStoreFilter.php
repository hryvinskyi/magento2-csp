<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist\SearchCriteria;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\Whitelist\SearchCriteria\AddStoreFilterInterface;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\FilterGroupFactory;
use Magento\Framework\Api\SearchCriteriaInterface;

class AddStoreFilter implements AddStoreFilterInterface
{
    public function __construct(
        private readonly FilterGroupFactory $filterGroupFactory,
        private readonly FilterFactory $filterFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(SearchCriteriaInterface $searchCriteria, int $storeId): void
    {
        $filterGroups = $searchCriteria->getFilterGroups();
        $filterGroup = $this->filterGroupFactory->create();
        $filterGroup->setFilters(
            [
                $this->filterFactory->create()
                    ->setValue(0)
                    ->setConditionType('eq')
                    ->setField(WhitelistInterface::STORE_IDS),
                $this->filterFactory->create()
                    ->setValue($storeId)
                    ->setConditionType('eq')
                    ->setField(WhitelistInterface::STORE_IDS),
                $this->filterFactory->create()
                    ->setValue('%,' . $storeId)
                    ->setConditionType('like')
                    ->setField(WhitelistInterface::STORE_IDS),
                $this->filterFactory->create()
                    ->setValue($storeId . ',%')
                    ->setConditionType('like')
                    ->setField(WhitelistInterface::STORE_IDS),
                $this->filterFactory->create()
                    ->setValue('%,' . $storeId . ',%')
                    ->setConditionType('like')
                    ->setField(WhitelistInterface::STORE_IDS),
            ]
        );

        $filterGroups[] = $filterGroup;
        $searchCriteria->setFilterGroups($filterGroups);
    }
}