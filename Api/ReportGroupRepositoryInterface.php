<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Api\Data\ReportGroupSearchResultsInterface;

interface ReportGroupRepositoryInterface
{
    /**
     * Save ReportGroup
     *
     * @param \Hryvinskyi\Csp\Api\Data\ReportGroupInterface $reportGroup
     *
     * @return \Hryvinskyi\Csp\Api\Data\ReportGroupInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(ReportGroupInterface $reportGroup): ReportGroupInterface;

    /**
     * Save ReportGroup from CSP report.
     *
     * @param string $json
     *
     * @return \Hryvinskyi\Csp\Api\Data\ReportGroupInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveFromCspReport(string $json): ReportGroupInterface;

    /**
     * Get ReportGroup by id.
     *
     * @param int $reportGroupId
     *
     * @return \Hryvinskyi\Csp\Api\Data\ReportGroupInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $reportGroupId): ReportGroupInterface;

    /**
     * Find ReportGroup by id.
     *
     * @param int $reportGroupId
     *
     * @return \Hryvinskyi\Csp\Api\Data\ReportGroupInterface|null
     */
    public function findById(int $reportGroupId): ?ReportGroupInterface;

    /**
     * Retrieve ReportGroup matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return \Hryvinskyi\Csp\Api\Data\ReportGroupSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ReportGroupSearchResultsInterface;

    /**
     * Delete ReportGroup
     *
     * @param \Hryvinskyi\Csp\Api\Data\ReportGroupInterface $reportGroup
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(ReportGroupInterface $reportGroup): bool;

    /**
     * Delete ReportGroup by value and policy.
     *
     * @param string $value
     * @param string $policy
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteByValueAndPolicy(string $value, string $policy): bool;

    /**
     * Delete ReportGroup by ID.
     *
     * @param int $reportGroupId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $reportGroupId): bool;
}
