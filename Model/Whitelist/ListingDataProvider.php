<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\HashValidationCalculatorInterface;
use Hryvinskyi\Csp\Api\RedundancyCalculatorInterface;
use Hryvinskyi\Csp\Model\Whitelist\Grid\ComputedColumns;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

/**
 * Whitelist grid rows with their hash validation and redundancy status, both filterable and sortable.
 *
 * When the grid filters or sorts by a computed column, every matching row is loaded and the computed column is
 * filtered, sorted and paged in memory.
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
     * @param ComputedColumns $computedColumns
     * @param array<mixed> $meta
     * @param array<mixed> $data
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
        private readonly ComputedColumns $computedColumns,
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
     * Grid rows and their total.
     *
     * @return array{items: list<array<string, mixed>>, totalRecords: int}
     */
    public function getData()
    {
        $criteria = $this->getSearchCriteria();
        $computed = $this->computedColumns->extract($criteria, self::COMPUTED_COLUMNS);
        if ($computed['filters'] === [] && $computed['sortOrders'] === []) {
            $output = $this->searchResultToOutput($this->reporting->search($criteria));

            $total = $output['totalRecords'] ?? 0;

            return [
                'items' => $this->withComputedColumns($output['items'] ?? []),
                'totalRecords' => is_numeric($total) ? (int)$total : 0,
            ];
        }

        $pageSize = (int)$criteria->getPageSize();
        $currentPage = (int)$criteria->getCurrentPage();
        $criteria->setPageSize(0);
        $criteria->setCurrentPage(1);
        $output = $this->searchResultToOutput($this->reporting->search($criteria));

        return $this->computedColumns->apply(
            $this->withComputedColumns($output['items'] ?? []),
            $computed['filters'],
            $computed['sortOrders'],
            $pageSize,
            $currentPage
        );
    }

    /**
     * Rows with their hash validation and redundancy status.
     *
     * @param mixed $items
     * @return list<array<string, mixed>>
     */
    private function withComputedColumns(mixed $items): array
    {
        $rows = [];
        foreach (is_array($items) ? $items : [] as $item) {
            if (is_array($item)) {
                $rows[] = array_filter($item, 'is_string', ARRAY_FILTER_USE_KEY);
            }
        }
        $rows = $this->redundancyCalculator->calculateForItems($this->hashValidationCalculator->calculateForItems($rows));

        return array_values(array_map(
            static fn (array $row): array => array_filter($row, 'is_string', ARRAY_FILTER_USE_KEY),
            $rows
        ));
    }
}
