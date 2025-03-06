<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Model\Report\Command\SaveFromCspReportInterface;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\FilterGroupFactory;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Api\Data\ReportInterfaceFactory;
use Hryvinskyi\Csp\Api\Data\ReportSearchResultsInterface;
use Hryvinskyi\Csp\Api\Data\ReportSearchResultsInterfaceFactory;
use Hryvinskyi\Csp\Model\ResourceModel\Report as ReportResource;
use Hryvinskyi\Csp\Model\ResourceModel\Report\CollectionFactory;

class ReportRepository implements ReportRepositoryInterface
{
    public function __construct(
        private readonly ReportResource $resource,
        private readonly ReportInterfaceFactory $reportFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly ReportSearchResultsInterfaceFactory $searchResultFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SaveFromCspReportInterface $saveFromCspReport,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly FilterGroupFactory $filterGroupFactory,
        private readonly FilterFactory $filterFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(ReportInterface $report): ReportInterface
    {
        try {
            $this->resource->save($report);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $report;
    }

    /**
     * @inheritDoc
     */
    public function saveFromCspReport(string $json): bool
    {
        try {
            $this->saveFromCspReport->execute($json);
            return true;
        } catch (LocalizedException $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function getById(int $reportId): ReportInterface
    {
        $report = $this->reportFactory->create();
        $this->resource->load($report, $reportId);
        if (!$report->getId()) {
            throw new NoSuchEntityException(__('Report with id "%1" does not exist.', $reportId));
        }

        return $report;
    }

    /**
     * @inheritdoc
     */
    public function findById(int $reportId): ?ReportInterface
    {
        $report = $this->reportFactory->create();
        $this->resource->load($report, $reportId);

        if (!$report->getId()) {
            return null;
        }

        return $report;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ReportSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        return $this->searchResultFactory
            ->create()
            ->setSearchCriteria($searchCriteria)
            ->setItems($collection->getItems())
            ->setTotalCount($collection->getSize());
    }

    /**
     * @inheritdoc
     */
    public function delete(ReportInterface $report): bool
    {
        try {
            $this->resource->delete($report);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteByDomainAndPolicy(string $domain, string $policy): bool
    {
        $filterHttpsDomain = $this->filterFactory->create()
            ->setField(ReportInterface::BLOCKED_URI)
            ->setConditionType('like')
            ->setValue('https://' . $domain . '%');

        $filterHttpDomain = $this->filterFactory->create()
            ->setField(ReportInterface::BLOCKED_URI)
            ->setConditionType('like')
            ->setValue('http://' . $domain . '%');

        $filterWssDomain = $this->filterFactory->create()
            ->setField(ReportInterface::BLOCKED_URI)
            ->setConditionType('like')
            ->setValue('wss://' . $domain . '%');

        $filterPolicy = $this->filterFactory->create()
            ->setField(ReportInterface::EFFECTIVE_DIRECTIVE)
            ->setConditionType('eq')
            ->setValue($policy);

        $searchCriteria = $this->searchCriteriaBuilder->create()
            ->setFilterGroups([
                $this->filterGroupFactory->create()
                    ->setFilters([$filterHttpsDomain, $filterHttpDomain, $filterWssDomain]),
                $this->filterGroupFactory->create()
                    ->setFilters([$filterPolicy])
            ]);

        foreach ($this->getList($searchCriteria)->getItems() as $report) {
            $this->delete($report);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $reportId): bool
    {
        return $this->delete($this->getById($reportId));
    }
}
