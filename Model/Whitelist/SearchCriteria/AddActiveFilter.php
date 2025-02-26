<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist\SearchCriteria;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\Whitelist\SearchCriteria\AddActiveFilterInterface;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\FilterGroupFactory;
use Magento\Framework\Api\SearchCriteriaInterface;

class AddActiveFilter implements AddActiveFilterInterface
{
    public function __construct(
        private readonly FilterGroupFactory $filterGroupFactory,
        private readonly FilterFactory $filterFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(SearchCriteriaInterface $searchCriteria): void
    {
        $filterGroups = $searchCriteria->getFilterGroups();

        $filterGroup = $this->filterGroupFactory->create();
        $filterGroup->setFilters(
            [
                $this->filterFactory->create()
                    ->setValue(true)
                    ->setConditionType('eq')
                    ->setField(WhitelistInterface::STATUS),
            ]
        );

        $filterGroups[] = $filterGroup;

        $searchCriteria->setFilterGroups($filterGroups);
    }
}