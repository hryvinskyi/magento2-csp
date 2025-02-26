<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist\Command;

use Hryvinskyi\Csp\Api\Data\WhitelistSearchResultsInterface;
use Hryvinskyi\Csp\Api\Whitelist\SearchCriteria\AddActiveFilterInterface;
use Hryvinskyi\Csp\Api\Whitelist\SearchCriteria\AddStoreFilterInterface;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class GetAllActiveWhitelistByStoreId implements GetAllActiveWhitelistByStoreIdInterface
{
    public function __construct(
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly AddActiveFilterInterface $addActiveFilter,
        private readonly AddStoreFilterInterface $addStoreFilter,
        private readonly WhitelistRepositoryInterface $whitelistRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(int $storeId): WhitelistSearchResultsInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $this->addActiveFilter->execute($searchCriteria);
        $this->addStoreFilter->execute($searchCriteria, $storeId);

        return $this->whitelistRepository->getList($searchCriteria);
    }
}
