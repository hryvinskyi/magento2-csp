<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\Cleanup;

use Magento\Framework\App\ResourceConnection;

/**
 * Deletes and counts violation reports in batches by last report time.
 */
class ReportTable
{
    private const TABLE = 'hryvinskyi_csp_violation_report';
    private const GROUP_TABLE = 'hryvinskyi_csp_violation_report_group';
    private const BATCH_SIZE = 1000;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(private readonly ResourceConnection $resourceConnection)
    {
    }

    /**
     * Delete, in batches, the reports last seen before the time, or at the time with a lower id.
     *
     * @param string $before UTC `Y-m-d H:i:s`
     * @param int|null $idBelow Also delete reports seen exactly at `$before` with an id below this
     * @return int
     */
    public function deleteSeenBefore(string $before, ?int $idBelow = null): int
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf(
            'DELETE FROM %s WHERE updated_at < ?%s LIMIT %d',
            $connection->quoteIdentifier($this->resourceConnection->getTableName(self::TABLE)),
            $idBelow === null ? '' : ' OR (updated_at = ? AND report_id < ?)',
            self::BATCH_SIZE
        );
        $bind = $idBelow === null ? [$before] : [$before, $before, $idBelow];
        $deleted = 0;
        do {
            $batch = $connection->query($sql, $bind)->rowCount();
            $deleted += $batch;
        } while ($batch === self::BATCH_SIZE);

        return $deleted;
    }

    /**
     * Number of reports last seen before the time.
     *
     * @param string $before UTC `Y-m-d H:i:s`
     * @return int
     */
    public function countSeenBefore(string $before): int
    {
        $connection = $this->resourceConnection->getConnection();
        $count = $connection->fetchOne(
            $connection->select()
                ->from($this->resourceConnection->getTableName(self::TABLE), ['COUNT(*)'])
                ->where('updated_at < ?', $before)
        );

        return is_numeric($count) ? (int)$count : 0;
    }

    /**
     * Number of reports.
     *
     * @return int
     */
    public function count(): int
    {
        $connection = $this->resourceConnection->getConnection();
        $count = $connection->fetchOne(
            $connection->select()->from($this->resourceConnection->getTableName(self::TABLE), ['COUNT(*)'])
        );

        return is_numeric($count) ? (int)$count : 0;
    }

    /**
     * Last report time and id of the report at the position, newest first, counting from 0.
     *
     * @param int $position
     * @return array{updated_at: string, report_id: int}|null
     */
    public function newestAt(int $position): ?array
    {
        $connection = $this->resourceConnection->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from($this->resourceConnection->getTableName(self::TABLE), ['updated_at', 'report_id'])
                ->order(['updated_at DESC', 'report_id DESC'])
                ->limit(1, $position)
        );
        if (!is_array($row) || !is_string($row['updated_at'] ?? null) || !is_numeric($row['report_id'] ?? null)) {
            return null;
        }

        return ['updated_at' => $row['updated_at'], 'report_id' => (int)$row['report_id']];
    }

    /**
     * Delete pending report groups that have no reports left; returns how many were deleted.
     *
     * @param int $pendingStatus
     * @return int
     */
    public function deleteEmptyGroups(int $pendingStatus): int
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->query(
            sprintf(
                'DELETE g FROM %s g LEFT JOIN %s r ON r.group_id = g.group_id WHERE g.status = ? AND r.report_id IS NULL',
                $connection->quoteIdentifier($this->resourceConnection->getTableName(self::GROUP_TABLE)),
                $connection->quoteIdentifier($this->resourceConnection->getTableName(self::TABLE))
            ),
            [$pendingStatus]
        )->rowCount();
    }
}
