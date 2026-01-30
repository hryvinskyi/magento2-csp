<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\HashValidationCalculatorInterface;
use Hryvinskyi\Csp\Api\RedundancyCalculatorInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

/**
 * Data provider for CSP whitelist grid with computed column filtering and sorting
 */
class ListingDataProvider extends DataProvider
{
    private const COMPUTED_COLUMNS = ['hash_validation', 'redundancy_status'];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param RedundancyCalculatorInterface $redundancyCalculator
     * @param HashValidationCalculatorInterface $hashValidationCalculator
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        private readonly RedundancyCalculatorInterface $redundancyCalculator,
        private readonly HashValidationCalculatorInterface $hashValidationCalculator,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        $searchCriteria = $this->getSearchCriteria();
        $computedFilters = $this->extractComputedFilters($searchCriteria);
        $computedSortOrders = $this->extractComputedSortOrders($searchCriteria);

        if (!empty($computedFilters) || !empty($computedSortOrders)) {
            return $this->getDataWithComputedOperations($searchCriteria, $computedFilters, $computedSortOrders);
        }

        return $this->getStandardData();
    }

    /**
     * Get data using standard flow (no computed column filters or sorting)
     *
     * @return array<string, mixed>
     */
    private function getStandardData(): array
    {
        $data = parent::getData();
        $data['items'] = $this->processItems($data['items']);

        return $data;
    }

    /**
     * Get data with computed column filters and/or sorting applied
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param array<string, array{value: mixed, condition: string}> $computedFilters
     * @param array<int, array{field: string, direction: string}> $computedSortOrders
     * @return array<string, mixed>
     */
    private function getDataWithComputedOperations(
        SearchCriteriaInterface $searchCriteria,
        array $computedFilters,
        array $computedSortOrders
    ): array {
        $pageSize = $searchCriteria->getPageSize();
        $currentPage = $searchCriteria->getCurrentPage();

        // Load all items without pagination
        $searchCriteria->setPageSize(0);
        $searchCriteria->setCurrentPage(1);

        $searchResult = $this->reporting->search($searchCriteria);
        $items = [];

        foreach ($searchResult->getItems() as $document) {
            $items[] = $document->getCustomAttributes()
                ? $this->extractDocumentData($document)
                : $document->getData();
        }

        // Process items (store_ids conversion + compute columns)
        $items = $this->processItems($items);

        // Apply computed column filters
        if (!empty($computedFilters)) {
            $items = $this->applyComputedFilters($items, $computedFilters);
        }

        // Apply computed column sorting
        if (!empty($computedSortOrders)) {
            $items = $this->applyComputedSortOrders($items, $computedSortOrders);
        }

        // Calculate pagination
        $totalRecords = count($items);
        $offset = ($currentPage - 1) * $pageSize;
        $paginatedItems = $pageSize > 0
            ? array_slice($items, $offset, $pageSize)
            : $items;

        return [
            'items' => array_values($paginatedItems),
            'totalRecords' => $totalRecords,
        ];
    }

    /**
     * Extract document data from search result
     *
     * @param mixed $document
     * @return array<string, mixed>
     */
    private function extractDocumentData($document): array
    {
        $data = [];
        foreach ($document->getCustomAttributes() as $attribute) {
            $data[$attribute->getAttributeCode()] = $attribute->getValue();
        }
        return $data;
    }

    /**
     * Process items: convert store_ids and calculate computed columns
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function processItems(array $items): array
    {
        foreach ($items as &$item) {
            if (isset($item[WhitelistInterface::STORE_IDS]) && is_string($item[WhitelistInterface::STORE_IDS])) {
                $item[WhitelistInterface::STORE_IDS] = explode(',', $item[WhitelistInterface::STORE_IDS]);
            }
        }

        $items = $this->hashValidationCalculator->calculateForItems($items);
        $items = $this->redundancyCalculator->calculateForItems($items);

        return $items;
    }

    /**
     * Extract computed column filters from search criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return array<string, mixed>
     */
    private function extractComputedFilters(SearchCriteriaInterface $searchCriteria): array
    {
        $computedFilters = [];

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            $remainingFilters = [];
            foreach ($filterGroup->getFilters() as $filter) {
                if (in_array($filter->getField(), self::COMPUTED_COLUMNS, true)) {
                    $computedFilters[$filter->getField()] = [
                        'value' => $filter->getValue(),
                        'condition' => $filter->getConditionType() ?: 'eq',
                    ];
                } else {
                    $remainingFilters[] = $filter;
                }
            }
            // Update filter group to exclude computed filters
            if (count($remainingFilters) !== count($filterGroup->getFilters())) {
                $filterGroup->setFilters($remainingFilters);
            }
        }

        return $computedFilters;
    }

    /**
     * Apply computed column filters to items
     *
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $computedFilters
     * @return array<int, array<string, mixed>>
     */
    private function applyComputedFilters(array $items, array $computedFilters): array
    {
        return array_filter($items, function (array $item) use ($computedFilters): bool {
            foreach ($computedFilters as $field => $filter) {
                $itemValue = $item[$field] ?? null;
                $filterValue = $filter['value'];
                $condition = $filter['condition'];

                if (!$this->matchesFilter($itemValue, $filterValue, $condition)) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Check if item value matches filter condition
     *
     * @param mixed $itemValue
     * @param mixed $filterValue
     * @param string $condition
     * @return bool
     */
    private function matchesFilter($itemValue, $filterValue, string $condition): bool
    {
        return match ($condition) {
            'eq' => $itemValue == $filterValue,
            'neq' => $itemValue != $filterValue,
            'in' => in_array($itemValue, (array)$filterValue, false),
            'nin' => !in_array($itemValue, (array)$filterValue, false),
            default => $itemValue == $filterValue,
        };
    }

    /**
     * Extract computed column sort orders from search criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return array<int, array{field: string, direction: string}>
     */
    private function extractComputedSortOrders(SearchCriteriaInterface $searchCriteria): array
    {
        $computedSortOrders = [];
        $remainingSortOrders = [];

        foreach ($searchCriteria->getSortOrders() ?? [] as $sortOrder) {
            $field = $sortOrder->getField();
            if (in_array($field, self::COMPUTED_COLUMNS, true)) {
                $computedSortOrders[] = [
                    'field' => $field,
                    'direction' => strtoupper($sortOrder->getDirection()) === 'DESC' ? 'DESC' : 'ASC',
                ];
            } else {
                $remainingSortOrders[] = $sortOrder;
            }
        }

        // Update search criteria to exclude computed sort orders
        if (count($remainingSortOrders) !== count($searchCriteria->getSortOrders() ?? [])) {
            $searchCriteria->setSortOrders($remainingSortOrders);
        }

        return $computedSortOrders;
    }

    /**
     * Apply computed column sorting to items
     *
     * @param array<int, array<string, mixed>> $items
     * @param array<int, array{field: string, direction: string}> $sortOrders
     * @return array<int, array<string, mixed>>
     */
    private function applyComputedSortOrders(array $items, array $sortOrders): array
    {
        usort($items, function (array $a, array $b) use ($sortOrders): int {
            foreach ($sortOrders as $sortOrder) {
                $field = $sortOrder['field'];
                $direction = $sortOrder['direction'];

                $valueA = $a[$field] ?? 0;
                $valueB = $b[$field] ?? 0;

                $comparison = $valueA <=> $valueB;

                if ($comparison !== 0) {
                    return $direction === 'DESC' ? -$comparison : $comparison;
                }
            }

            return 0;
        });

        return $items;
    }
}
