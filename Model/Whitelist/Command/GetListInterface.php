<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist\Command;

use Hryvinskyi\Csp\Api\Data\WhitelistSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Find Whitelists by SearchCriteria command (Service Provider Interface - SPI)
 *
 * Separate command interface to which Repository proxies initial GetList call, could be considered as SPI - Interfaces
 * that you should extend and implement to customize current behaviour, but NOT expected to be used (called) in the code
 * of business logic directly
 *
 * @see \Hryvinskyi\Csp\Api\WhitelistRepositoryInterface
 * @api
 */
interface GetListInterface
{
    /**
     * Find Whitelists by given SearchCriteria. SearchCriteria is not required because load all sources is useful case
     *
     * @param SearchCriteriaInterface|null $searchCriteria
     *
     * @return WhitelistSearchResultsInterface
     */
    public function execute(SearchCriteriaInterface $searchCriteria = null): WhitelistSearchResultsInterface;
}
