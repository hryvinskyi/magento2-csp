<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistSearchResultsInterface;

/**
 * Whitelist entries. Saving normalizes and validates an entry and refreshes the cached policy sources.
 *
 * @api
 */
interface WhitelistRepositoryInterface
{
    /**
     * Save an entry after bringing it into canonical form.
     *
     * Fails when the entry breaks a whitelist rule, or when another entry has the same natural key.
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
     * Entry with the given natural key: directive, value type, algorithm, value and area.
     *
     * @param string $policy
     * @param string $valueType
     * @param string $valueAlgorithm
     * @param string $value
     * @param string $area
     *
     * @return \Hryvinskyi\Csp\Api\Data\WhitelistInterface|null
     */
    public function findByNaturalKey(
        string $policy,
        string $valueType,
        string $valueAlgorithm,
        string $value,
        string $area
    ): ?WhitelistInterface;

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
