<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistSearchResultsInterface;

interface WhitelistRepositoryInterface
{
    /**
     * Save Whitelist
     *
     * @param \Hryvinskyi\Csp\Api\Data\WhitelistInterface $whitelist
     *
     * @return \Hryvinskyi\Csp\Api\Data\WhitelistInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(WhitelistInterface $whitelist): WhitelistInterface;

    /**
     * Get Whitelist by id.
     *
     * @param int $whitelistId
     *
     * @return \Hryvinskyi\Csp\Api\Data\WhitelistInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $whitelistId): WhitelistInterface;

    /**
     * Find Whitelist by id.
     *
     * @param int $whitelistId
     *
     * @return \Hryvinskyi\Csp\Api\Data\WhitelistInterface|null
     */
    public function findById(int $whitelistId): ?WhitelistInterface;

    /**
     * Retrieve Whitelist matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return \Hryvinskyi\Csp\Api\Data\WhitelistSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): WhitelistSearchResultsInterface;

    /**
     * Delete Whitelist
     *
     * @param \Hryvinskyi\Csp\Api\Data\WhitelistInterface $whitelist
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(WhitelistInterface $whitelist): bool;

    /**
     * Delete Whitelist by ID.
     *
     * @param int $whitelistId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $whitelistId): bool;
}
