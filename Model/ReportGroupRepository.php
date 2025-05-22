<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Model\ReportGroup\Command\SaveFromCspReportInterface;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\FilterGroupFactory;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Api\Data\ReportGroupInterfaceFactory;
use Hryvinskyi\Csp\Api\Data\ReportGroupSearchResultsInterface;
use Hryvinskyi\Csp\Api\Data\ReportGroupSearchResultsInterfaceFactory;
use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup as ReportGroupResource;
use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup\CollectionFactory;

class ReportGroupRepository implements ReportGroupRepositoryInterface
{
    public function __construct(
        private readonly ReportGroupResource $resource,
        private readonly ReportGroupInterfaceFactory $entityFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly ReportGroupSearchResultsInterfaceFactory $searchResultFactory,
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
    public function save(ReportGroupInterface $reportGroup): ReportGroupInterface
    {
        try {
            $this->resource->save($reportGroup);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $reportGroup;
    }

    /**
     * @inheritdoc
     */
    public function saveFromCspReport(string $json): ReportGroupInterface
    {
        try {
            $reportGroup = $this->entityFactory->create();
            $this->saveFromCspReport->execute($reportGroup, $json);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $reportGroup;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $reportGroupId): ReportGroupInterface
    {
        $reportGroup = $this->entityFactory->create();
        $this->resource->load($reportGroup, $reportGroupId);
        if (!$reportGroup->getId()) {
            throw new NoSuchEntityException(__('ReportGroup with id "%1" does not exist.', $reportGroupId));
        }

        return $reportGroup;
    }

    /**
     * @inheritdoc
     */
    public function findById(int $reportGroupId): ?ReportGroupInterface
    {
        $reportGroup = $this->entityFactory->create();
        $this->resource->load($reportGroup, $reportGroupId);

        if (!$reportGroup->getId()) {
            return null;
        }

        return $reportGroup;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ReportGroupSearchResultsInterface
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
    public function delete(ReportGroupInterface $reportGroup): bool
    {
        try {
            $this->resource->delete($reportGroup);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteByValueAndPolicy(string $value, string $policy): bool
    {

        $filterPolicy = $this->filterFactory->create()
            ->setField(ReportGroupInterface::POLICY)
            ->setConditionType('eq')
            ->setValue($policy);

        $filterValue = $this->filterFactory->create()
            ->setField(ReportGroupInterface::VALUE)
            ->setConditionType('eq')
            ->setValue($value);

        $searchCriteria = $this->searchCriteriaBuilder->create()
            ->setFilterGroups([
                $this->filterGroupFactory->create()
                    ->setFilters([$filterValue]),
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
    public function deleteById(int $reportGroupId): bool
    {
        return $this->delete($this->getById($reportGroupId));
    }
}
