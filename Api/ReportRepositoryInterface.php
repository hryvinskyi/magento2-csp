<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Api\Data\ReportSearchResultsInterface;

interface ReportRepositoryInterface
{
    /**
     * Save Report
     *
     * @param \Hryvinskyi\Csp\Api\Data\ReportInterface $report
     *
     * @return \Hryvinskyi\Csp\Api\Data\ReportInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(ReportInterface $report): ReportInterface;

    /**
     * Save from CSP Report
     *
     * @param string $json
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveFromCspReport(string $json): bool;

    /**
     * Get Report by id.
     *
     * @param int $reportId
     *
     * @return \Hryvinskyi\Csp\Api\Data\ReportInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $reportId): ReportInterface;

    /**
     * Find Report by id.
     *
     * @param int $reportId
     *
     * @return \Hryvinskyi\Csp\Api\Data\ReportInterface|null
     */
    public function findById(int $reportId): ?ReportInterface;

    /**
     * Retrieve Report matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return \Hryvinskyi\Csp\Api\Data\ReportSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ReportSearchResultsInterface;

    /**
     * Delete Report
     *
     * @param \Hryvinskyi\Csp\Api\Data\ReportInterface $report
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(ReportInterface $report): bool;

    /**
     * Delete Report by ID.
     *
     * @param int $reportId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $reportId): bool;
}
