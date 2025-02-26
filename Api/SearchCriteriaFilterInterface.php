<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface SearchCriteriaFilterInterface
{
    /**
     * @param SearchCriteriaInterface $searchCriteria
     */
    public function execute(SearchCriteriaInterface $searchCriteria): void;
}
