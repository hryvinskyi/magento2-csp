<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ResourceModel\Whitelist;

use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Store scope of whitelist entries, kept in a link table with one row per entry and store.
 */
class StoreLinks
{
    public const TABLE = 'hryvinskyi_csp_whitelist_store';

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreIdList $storeIdList
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly StoreIdList $storeIdList
    ) {
    }

    /**
     * Store ids of each given entry; an entry without links has none.
     *
     * @param list<int> $ruleIds
     * @return array<int, list<int>>
     */
    public function storeIdsByRule(array $ruleIds): array
    {
        if ($ruleIds === []) {
            return [];
        }
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->table(), ['rule_id', 'store_id'])
            ->where('rule_id IN (?)', $ruleIds)
            ->order('store_id');
        $storeIds = [];
        foreach ($connection->fetchAll($select) as $row) {
            if (is_array($row) && is_numeric($row['rule_id'] ?? null) && is_numeric($row['store_id'] ?? null)) {
                $storeIds[(int)$row['rule_id']][] = (int)$row['store_id'];
            }
        }

        return $storeIds;
    }

    /**
     * Replace the stores of an entry; no stores, or store 0 among them, means every store.
     *
     * @param int $ruleId
     * @param array<int> $storeIds
     * @return void
     */
    public function replace(int $ruleId, array $storeIds): void
    {
        $connection = $this->resourceConnection->getConnection();
        $new = $this->storeIdList->normalize(array_values($storeIds));
        $old = $this->storeIdsByRule([$ruleId])[$ruleId] ?? [];
        $removed = array_values(array_diff($old, $new));
        $added = array_values(array_diff($new, $old));
        if ($removed !== []) {
            $connection->delete($this->table(), ['rule_id = ?' => $ruleId, 'store_id IN (?)' => $removed]);
        }
        if ($added !== []) {
            $connection->insertMultiple(
                $this->table(),
                array_map(static fn (int $storeId): array => ['rule_id' => $ruleId, 'store_id' => $storeId], $added)
            );
        }
    }

    /**
     * Restrict a select over whitelist entries to those that apply to one of the stores: linked to it or to every store.
     *
     * @param Select $select
     * @param list<int> $storeIds
     * @param string $ruleIdColumn
     * @return void
     */
    public function filterByStores(Select $select, array $storeIds, string $ruleIdColumn = 'main_table.rule_id'): void
    {
        $linked = $this->resourceConnection->getConnection()->select()
            ->from($this->table(), ['rule_id'])
            ->where('store_id IN (?)', array_values(array_unique([StoreIdList::ALL_STORES, ...$storeIds])));
        $select->where(sprintf('%s IN (%s)', $ruleIdColumn, $linked->assemble()));
    }

    /**
     * Store ids from a collection filter condition on `store_id`: a value, a list, or `eq`/`in`/`finset`.
     *
     * @param mixed $condition
     * @return list<int>
     */
    public function storeIdsFromCondition(mixed $condition): array
    {
        if (is_array($condition)) {
            $condition = $condition['eq'] ?? $condition['in'] ?? $condition['finset'] ?? array_values($condition);
        }
        $values = is_array($condition) ? $condition : [$condition];

        return array_values(array_map(
            static fn (mixed $id): int => (int)$id,
            array_filter($values, static fn (mixed $id): bool => is_numeric($id))
        ));
    }

    /**
     * @return string
     */
    private function table(): string
    {
        return $this->resourceConnection->getTableName(self::TABLE);
    }
}
