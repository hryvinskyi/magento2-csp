<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Whitelist\SearchCriteria;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * @api
 */
interface AddStoreFilterInterface
{
    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @param int $storeId
     */
    public function execute(SearchCriteriaInterface $searchCriteria, int $storeId): void;
}
