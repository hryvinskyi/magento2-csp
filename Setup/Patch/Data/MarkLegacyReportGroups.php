<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Setup\Patch\Data;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use Hryvinskyi\Csp\Setup\Migration\LegacyReportGroupMergePlan;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Moves report groups recorded before 2.0.0 to store 0 and area `all` under their governing directive.
 *
 * Groups that end up with the same directive and value are merged, their reports moved to the remaining group. The
 * database groups them under the table's collation, so the merged groups never collide on its unique key.
 */
class MarkLegacyReportGroups implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $setup
     * @param LegacyReportGroupMergePlan $mergePlan
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $setup,
        private readonly LegacyReportGroupMergePlan $mergePlan
    ) {
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        $connection = $this->setup->getConnection();
        $groupTable = $this->setup->getTable('hryvinskyi_csp_violation_report_group');
        $reportTable = $this->setup->getTable('hryvinskyi_csp_violation_report');
        $governing = $this->governingExpression($connection, $this->mergePlan->governingDirectives(array_values(array_map(
            fn (mixed $policy): string => $this->toString($policy),
            $connection->fetchCol(sprintf(
                'SELECT DISTINCT CAST(%s AS BINARY) FROM %s',
                $connection->quoteIdentifier('policy'),
                $connection->quoteIdentifier($groupTable)
            ))
        ))));
        $rows = $connection->fetchAll(sprintf(
            'SELECT %1$s, %2$s, %3$s, %4$s, %5$s AS governing_policy,'
            . ' MIN(%1$s) OVER (PARTITION BY %5$s, %6$s) AS merge_key FROM %7$s',
            $connection->quoteIdentifier('group_id'),
            $connection->quoteIdentifier('policy'),
            $connection->quoteIdentifier('status'),
            $connection->quoteIdentifier('count'),
            $governing,
            $connection->quoteIdentifier('value'),
            $connection->quoteIdentifier($groupTable)
        ));
        $current = [];
        foreach ($this->groups($rows) as $group) {
            $current[$group['group_id']] = $group;
        }

        foreach ($this->mergePlan->plan(array_values($current)) as $merge) {
            if ($merge['merged'] !== []) {
                $connection->update($reportTable, ['group_id' => $merge['survivor']], ['group_id IN (?)' => $merge['merged']]);
                $connection->delete($groupTable, ['group_id IN (?)' => $merge['merged']]);
            }
            $survivor = $current[$merge['survivor']];
            if ($merge['merged'] !== [] || $merge['policy'] !== $survivor['policy'] || $merge['status'] !== $survivor['status']) {
                $connection->update(
                    $groupTable,
                    ['policy' => $merge['policy'], 'status' => $merge['status'], 'count' => $merge['count']],
                    ['group_id = ?' => $merge['survivor']]
                );
            }
        }
        $connection->update($groupTable, ['store_id' => StoreIdList::ALL_STORES, 'area' => Area::ALL->value]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * SQL expression of the governing directive of a group: its directive mapped through the given directives.
     *
     * The mapping runs in the database, so directives the table's collation cannot tell apart map alike.
     *
     * @param AdapterInterface $connection
     * @param array<string, string> $governing Recorded directive => governing directive
     * @return string
     */
    private function governingExpression(AdapterInterface $connection, array $governing): string
    {
        $column = $connection->quoteIdentifier('policy');
        if ($governing === []) {
            return $column;
        }
        $cases = '';
        foreach ($governing as $policy => $directive) {
            $cases .= $connection->quoteInto(' WHEN ?', (string)$policy) . $connection->quoteInto(' THEN ?', $directive);
        }

        return sprintf('CASE %1$s%2$s ELSE %1$s END', $column, $cases);
    }

    /**
     * Typed group rows.
     *
     * @param array<mixed> $rows
     * @return list<array{group_id: int, policy: string, merge_key: int, governing_policy: string, status: int, count: int}>
     */
    private function groups(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $groups[] = [
                'group_id' => $this->toInt($row['group_id'] ?? null),
                'policy' => $this->toString($row['policy'] ?? null),
                'merge_key' => $this->toInt($row['merge_key'] ?? null),
                'governing_policy' => $this->toString($row['governing_policy'] ?? null),
                'status' => $this->toInt($row['status'] ?? null),
                'count' => $this->toInt($row['count'] ?? null),
            ];
        }

        return $groups;
    }

    /**
     * @param mixed $value
     * @return int
     */
    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function toString(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }
}
