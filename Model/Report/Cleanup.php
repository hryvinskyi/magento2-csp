<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Api\ReportCleanupInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Report as ReportResource;
use Psr\Log\LoggerInterface;

class Cleanup implements ReportCleanupInterface
{
    private const TABLE_NAME = 'hryvinskyi_csp_violation_report';

    public function __construct(
        private readonly ReportResource $reportResource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function cleanByDate(int $days): int
    {
        $connection = $this->reportResource->getConnection();
        $tableName = $this->reportResource->getTable(self::TABLE_NAME);

        try {
            $deleted = $connection->delete(
                $tableName,
                ['created_at < NOW() - INTERVAL ? DAY' => $days]
            );

            $this->logger->info(
                sprintf('CSP report cleanup (by date): deleted %d records older than %d days.', $deleted, $days)
            );

            return (int)$deleted;
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('CSP report cleanup (by date) failed: %s', $e->getMessage())
            );
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function cleanByCount(int $maxRecords): int
    {
        $connection = $this->reportResource->getConnection();
        $tableName = $this->reportResource->getTable(self::TABLE_NAME);

        try {
            $totalCount = $this->getTotalCount();

            if ($totalCount <= $maxRecords) {
                return 0;
            }

            $deleteCount = $totalCount - $maxRecords;

            $select = $connection->select()
                ->from($tableName, ['report_id'])
                ->order('created_at ASC')
                ->limit($deleteCount);

            $idsToDelete = $connection->fetchCol($select);

            if (empty($idsToDelete)) {
                return 0;
            }

            $deleted = $connection->delete(
                $tableName,
                ['report_id IN (?)' => $idsToDelete]
            );

            $this->logger->info(
                sprintf(
                    'CSP report cleanup (by count): deleted %d records, keeping %d most recent.',
                    $deleted,
                    $maxRecords
                )
            );

            return (int)$deleted;
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('CSP report cleanup (by count) failed: %s', $e->getMessage())
            );
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount(): int
    {
        $connection = $this->reportResource->getConnection();
        $tableName = $this->reportResource->getTable(self::TABLE_NAME);

        $select = $connection->select()
            ->from($tableName, ['cnt' => new \Zend_Db_Expr('COUNT(*)')]);

        return (int)$connection->fetchOne($select);
    }

    /**
     * @inheritDoc
     */
    public function countByDate(int $days): int
    {
        $connection = $this->reportResource->getConnection();
        $tableName = $this->reportResource->getTable(self::TABLE_NAME);

        $select = $connection->select()
            ->from($tableName, ['cnt' => new \Zend_Db_Expr('COUNT(*)')])
            ->where('created_at < NOW() - INTERVAL ? DAY', $days);

        return (int)$connection->fetchOne($select);
    }

    /**
     * @inheritDoc
     */
    public function countByCount(int $maxRecords): int
    {
        $totalCount = $this->getTotalCount();

        if ($totalCount <= $maxRecords) {
            return 0;
        }

        return $totalCount - $maxRecords;
    }
}
