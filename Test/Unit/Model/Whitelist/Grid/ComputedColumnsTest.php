<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist\Grid;

use Hryvinskyi\Csp\Model\Whitelist\Grid\ComputedColumns;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SortOrder;
use PHPUnit\Framework\TestCase;

class ComputedColumnsTest extends TestCase
{
    private const COLUMNS = ['hash_validation', 'redundancy_status'];

    public function testTakesComputedFiltersAndSortOrdersOutOfTheCriteria(): void
    {
        $criteria = new SearchCriteria([
            SearchCriteria::FILTER_GROUPS => [
                new FilterGroup(['filters' => [
                    new Filter(['field' => 'policy', 'value' => 'img-src', 'condition_type' => 'eq']),
                    new Filter(['field' => 'redundancy_status', 'value' => '2', 'condition_type' => '']),
                ]]),
            ],
            SearchCriteria::SORT_ORDERS => [
                new SortOrder(['field' => 'rule_id', 'direction' => 'ASC']),
                new SortOrder(['field' => 'hash_validation', 'direction' => 'desc']),
            ],
        ]);

        $computed = (new ComputedColumns())->extract($criteria, self::COLUMNS);

        $this->assertSame(['redundancy_status' => ['value' => '2', 'condition' => 'eq']], $computed['filters']);
        $this->assertSame([['field' => 'hash_validation', 'direction' => 'DESC']], $computed['sortOrders']);
        $this->assertSame(['policy'], array_map(
            static fn (Filter $filter): string => (string)$filter->getField(),
            $criteria->getFilterGroups()[0]->getFilters() ?? []
        ));
        $this->assertSame(['rule_id'], array_map(
            static fn (SortOrder $order): string => (string)$order->getField(),
            $criteria->getSortOrders() ?? []
        ));
    }

    public function testFiltersSortsAndPagesRows(): void
    {
        $rows = [
            ['rule_id' => 1, 'redundancy_status' => 1, 'hash_validation' => 0],
            ['rule_id' => 2, 'redundancy_status' => 2, 'hash_validation' => 3],
            ['rule_id' => 3, 'redundancy_status' => 2, 'hash_validation' => 1],
            ['rule_id' => 4, 'redundancy_status' => 3, 'hash_validation' => 2],
        ];
        $columns = new ComputedColumns();

        $page = $columns->apply(
            $rows,
            ['redundancy_status' => ['value' => ['2', '3'], 'condition' => 'in']],
            [['field' => 'hash_validation', 'direction' => 'DESC']],
            2,
            1
        );
        $this->assertSame(3, $page['totalRecords']);
        $this->assertSame([2, 4], array_column($page['items'], 'rule_id'));

        $excluded = $columns->apply($rows, ['redundancy_status' => ['value' => '2', 'condition' => 'neq']], [], 0, 1);
        $this->assertSame([1, 4], array_column($excluded['items'], 'rule_id'));
    }
}
