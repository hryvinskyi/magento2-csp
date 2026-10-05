<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Api\Data\ReportGroupInterfaceFactory;
use Hryvinskyi\Csp\Api\Data\ReportGroupSearchResultsInterface;
use Hryvinskyi\Csp\Api\Data\ReportGroupSearchResultsInterfaceFactory;
use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup as ReportGroupResource;
use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;

/**
 * @inheritDoc
 */
class ReportGroupRepository implements ReportGroupRepositoryInterface
{
    /**
     * @param ReportGroupResource $resource
     * @param ReportGroupInterfaceFactory $entityFactory
     * @param CollectionFactory $collectionFactory
     * @param ReportGroupSearchResultsInterfaceFactory $searchResultFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        private readonly ReportGroupResource $resource,
        private readonly ReportGroupInterfaceFactory $entityFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly ReportGroupSearchResultsInterfaceFactory $searchResultFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(ReportGroupInterface $reportGroup): ReportGroupInterface
    {
        try {
            $this->resource->save($this->model($reportGroup));
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('%1', $exception->getMessage()), $exception);
        }

        return $reportGroup;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $reportGroupId): ReportGroupInterface
    {
        $entity = $this->findById($reportGroupId);
        if ($entity === null) {
            throw new NoSuchEntityException(__('Record %1 does not exist.', $reportGroupId));
        }

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function findById(int $reportGroupId): ?ReportGroupInterface
    {
        $entity = $this->entityFactory->create();
        $model = $this->model($entity);
        $this->resource->load($model, $reportGroupId);

        return $model->getId() === null ? null : $entity;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ReportGroupSearchResultsInterface
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
    public function delete(ReportGroupInterface $reportGroup): bool
    {
        try {
            $this->resource->delete($this->model($reportGroup));
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('%1', $exception->getMessage()), $exception);
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

    /**
     * The entity as the model the resource model persists.
     *
     * @param ReportGroupInterface $entity
     * @return AbstractModel
     * @throws \InvalidArgumentException
     */
    private function model(ReportGroupInterface $entity): AbstractModel
    {
        if (!$entity instanceof AbstractModel) {
            throw new \InvalidArgumentException(sprintf('%s cannot be persisted by this repository.', $entity::class));
        }

        return $entity;
    }
}
