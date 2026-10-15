<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist\Grid;

use Magento\Framework\Api\Search\SearchCriteriaInterface;

/**
 * Filters, sorts and pages grid rows by columns computed after loading, which the database cannot query.
 */
class ComputedColumns
{
    /**
     * Take the filters and sort orders on the given columns out of the criteria.
     *
     * @param SearchCriteriaInterface $criteria
     * @param list<string> $columns
     * @return array{filters: array<string, array{value: mixed, condition: string}>, sortOrders: list<array{field: string, direction: string}>}
     */
    public function extract(SearchCriteriaInterface $criteria, array $columns): array
    {
        $filters = [];
        foreach ($criteria->getFilterGroups() as $group) {
            $kept = [];
            foreach ($group->getFilters() ?? [] as $filter) {
                if (in_array($filter->getField(), $columns, true)) {
                    $filters[$filter->getField()] = [
                        'value' => $filter->getValue(),
                        'condition' => $filter->getConditionType() ?: 'eq',
                    ];
                } else {
                    $kept[] = $filter;
                }
            }
            $group->setFilters($kept);
        }

        $sortOrders = [];
        $keptOrders = [];
        foreach ($criteria->getSortOrders() ?? [] as $sortOrder) {
            $field = $sortOrder->getField();
            if (in_array($field, $columns, true)) {
                $sortOrders[] = [
                    'field' => $field,
                    'direction' => strtoupper((string)$sortOrder->getDirection()) === 'DESC' ? 'DESC' : 'ASC',
                ];
            } else {
                $keptOrders[] = $sortOrder;
            }
        }
        $criteria->setSortOrders($keptOrders);

        return ['filters' => $filters, 'sortOrders' => $sortOrders];
    }

    /**
     * One page of the rows matching every filter, sorted.
     *
     * @param list<array<string, mixed>> $rows
     * @param array<string, array{value: mixed, condition: string}> $filters
     * @param list<array{field: string, direction: string}> $sortOrders
     * @param int $pageSize 0 for every row
     * @param int $currentPage
     * @return array{items: list<array<string, mixed>>, totalRecords: int}
     */
    public function apply(array $rows, array $filters, array $sortOrders, int $pageSize, int $currentPage): array
    {
        $rows = array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->matchesAll($row, $filters)
        ));
        if ($sortOrders !== []) {
            usort($rows, static function (array $a, array $b) use ($sortOrders): int {
                foreach ($sortOrders as $sortOrder) {
                    $comparison = ($a[$sortOrder['field']] ?? 0) <=> ($b[$sortOrder['field']] ?? 0);
                    if ($comparison !== 0) {
                        return $sortOrder['direction'] === 'DESC' ? -$comparison : $comparison;
                    }
                }

                return 0;
            });
        }
        $total = count($rows);
        if ($pageSize > 0) {
            $rows = array_slice($rows, (max($currentPage, 1) - 1) * $pageSize, $pageSize);
        }

        return ['items' => $rows, 'totalRecords' => $total];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, array{value: mixed, condition: string}> $filters
     * @return bool
     */
    private function matchesAll(array $row, array $filters): bool
    {
        foreach ($filters as $field => $filter) {
            $value = $this->comparable($row[$field] ?? null);
            $expected = is_array($filter['value'])
                ? array_map(fn (mixed $item): string => $this->comparable($item), $filter['value'])
                : [$this->comparable($filter['value'])];
            $matches = match ($filter['condition']) {
                'neq', 'nin' => !in_array($value, $expected, true),
                default => in_array($value, $expected, true),
            };
            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function comparable(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }
}
