<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Model\Whitelist\Command\GetListInterface;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\FilterGroupFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Hryvinskyi\Csp\Api\Data\WhitelistSearchResultsInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist as WhitelistResource;

class WhitelistRepository implements WhitelistRepositoryInterface
{
    public function __construct(
        private readonly WhitelistResource $resource,
        private readonly WhitelistInterfaceFactory $whitelistFactory,
        private readonly GetListInterface $getList,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly FilterGroupFactory $filterGroupFactory,
        private readonly FilterFactory $filterFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(WhitelistInterface $whitelist): WhitelistInterface
    {
        try {
            $this->resource->save($whitelist);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $whitelist;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $whitelistId): WhitelistInterface
    {
        $whitelist = $this->whitelistFactory->create();
        $this->resource->load($whitelist, $whitelistId);
        if (!$whitelist->getId()) {
            throw new NoSuchEntityException(__('Whitelist with id "%1" does not exist.', $whitelistId));
        }

        return $whitelist;
    }

    /**
     * @inheritdoc
     */
    public function findById(int $whitelistId): ?WhitelistInterface
    {
        $whitelist = $this->whitelistFactory->create();
        $this->resource->load($whitelist, $whitelistId);

        if (!$whitelist->getId()) {
            return null;
        }

        return $whitelist;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): WhitelistSearchResultsInterface
    {
        return $this->getList->execute($searchCriteria);
    }

    /**
     * @inheritdoc
     */
    public function getWhitelistByParams(
        string $policy,
        string $valueType,
        string $value,
        string $valueAlgorithm = ''
    ): WhitelistSearchResultsInterface {
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $filterGroupPolicy = $this->filterGroupFactory->create();
        $filterGroupPolicy->setFilters(
            [
                $this->filterFactory->create()
                    ->setField('policy')
                    ->setValue($policy)
                    ->setConditionType('eq')
            ]
        );

        $filterGroupValueType = $this->filterGroupFactory->create();
        $filterGroupValueType->setFilters(
            [
                $this->filterFactory->create()
                    ->setField('value_type')
                    ->setValue($valueType)
                    ->setConditionType('eq')
            ]
        );

        $filterGroupValue = $this->filterGroupFactory->create();
        $filterGroupValue->setFilters(
            [
                $this->filterFactory->create()
                    ->setField('value')
                    ->setValue($value)
                    ->setConditionType('eq')
            ]
        );

        $filterGroupValueAlgorithm = $this->filterGroupFactory->create();

        if ($valueAlgorithm === '') {
            $filterGroupValueAlgorithm->setFilters(
                [
                    $this->filterFactory->create()
                        ->setField('value_algorithm')
                        ->setValue($valueAlgorithm)
                        ->setConditionType('null'),
                    $this->filterFactory->create()
                        ->setField('value_algorithm')
                        ->setValue($valueAlgorithm)
                        ->setConditionType('eq')
                ]
            );
        } else {
            $filterGroupValueAlgorithm->setFilters(
                [
                    $this->filterFactory->create()
                        ->setField('value_algorithm')
                        ->setValue($valueAlgorithm)
                        ->setConditionType('eq')
                ]
            );
        }

        $searchCriteria->setFilterGroups(
            [
                $filterGroupPolicy,
                $filterGroupValueType,
                $filterGroupValue,
                $filterGroupValueAlgorithm
            ]
        );

        return $this->getList->execute($searchCriteria);
    }

    /**
     * @inheritdoc
     */
    public function delete(WhitelistInterface $whitelist): bool
    {
        try {
            $this->resource->delete($whitelist);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $whitelistId): bool
    {
        return $this->delete($this->getById($whitelistId));
    }
}
