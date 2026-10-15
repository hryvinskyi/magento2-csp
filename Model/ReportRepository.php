<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Api\Data\ReportInterfaceFactory;
use Hryvinskyi\Csp\Api\Data\ReportSearchResultsInterface;
use Hryvinskyi\Csp\Api\Data\ReportSearchResultsInterfaceFactory;
use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Report as ReportResource;
use Hryvinskyi\Csp\Model\ResourceModel\Report\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;

/**
 * @inheritDoc
 */
class ReportRepository implements ReportRepositoryInterface
{
    /**
     * @param ReportResource $resource
     * @param ReportInterfaceFactory $entityFactory
     * @param CollectionFactory $collectionFactory
     * @param ReportSearchResultsInterfaceFactory $searchResultFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        private readonly ReportResource $resource,
        private readonly ReportInterfaceFactory $entityFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly ReportSearchResultsInterfaceFactory $searchResultFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(ReportInterface $report): ReportInterface
    {
        try {
            $this->resource->save($this->model($report));
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('%1', $exception->getMessage()), $exception);
        }

        return $report;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $reportId): ReportInterface
    {
        $entity = $this->findById($reportId);
        if ($entity === null) {
            throw new NoSuchEntityException(__('Record %1 does not exist.', $reportId));
        }

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function findById(int $reportId): ?ReportInterface
    {
        $entity = $this->entityFactory->create();
        $model = $this->model($entity);
        $this->resource->load($model, $reportId);

        return $model->getId() === null ? null : $entity;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ReportSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $results = $this->searchResultFactory->create();
        $results->setSearchCriteria($searchCriteria);
        $results->setItems(array_values($collection->getItems()));
        $results->setTotalCount($collection->getSize());

        return $results;
    }

    /**
     * @inheritdoc
     */
    public function delete(ReportInterface $report): bool
    {
        try {
            $this->resource->delete($this->model($report));
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('%1', $exception->getMessage()), $exception);
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

    /**
     * The entity as the model the resource model persists.
     *
     * @param ReportInterface $entity
     * @return AbstractModel
     * @throws \InvalidArgumentException
     */
    private function model(ReportInterface $entity): AbstractModel
    {
        if (!$entity instanceof AbstractModel) {
            throw new \InvalidArgumentException(sprintf('%s cannot be persisted by this repository.', $entity::class));
        }

        return $entity;
    }
}
