<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Api\ViolationReviewInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\FlagManager;

/**
 * Keeps the highest report group id each admin user has seen. Group ids only grow, so a pending group with a higher
 * id was recorded after that user last opened the violation reports.
 */
class ViolationReview implements ViolationReviewInterface
{
    private const GROUP_TABLE = 'hryvinskyi_csp_violation_report_group';
    private const FLAG_CODE_PREFIX = 'hryvinskyi_csp_reviewed_group_';

    /**
     * @param ResourceConnection $resourceConnection
     * @param FlagManager $flagManager
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly FlagManager $flagManager
    ) {
    }

    /**
     * @inheritDoc
     */
    public function markReviewed(int $adminUserId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $newest = $connection->fetchOne(
            $connection->select()->from($this->resourceConnection->getTableName(self::GROUP_TABLE), ['MAX(group_id)'])
        );
        $newestId = is_numeric($newest) ? (int)$newest : 0;
        if ($newestId === $this->reviewedUpTo($adminUserId)) {
            return;
        }
        $this->flagManager->saveFlag($this->flagCode($adminUserId), $newestId);
    }

    /**
     * @inheritDoc
     */
    public function countUnreviewed(int $adminUserId): int
    {
        $connection = $this->resourceConnection->getConnection();
        $count = $connection->fetchOne(
            $connection->select()
                ->from($this->resourceConnection->getTableName(self::GROUP_TABLE), ['COUNT(*)'])
                ->where('status = ?', Status::PENDING->value)
                ->where('group_id > ?', $this->reviewedUpTo($adminUserId))
        );

        return is_numeric($count) ? (int)$count : 0;
    }

    /**
     * Highest group id the admin user has seen; 0 before the first visit.
     *
     * @param int $adminUserId
     * @return int
     */
    private function reviewedUpTo(int $adminUserId): int
    {
        $value = $this->flagManager->getFlagData($this->flagCode($adminUserId));

        return is_numeric($value) ? (int)$value : 0;
    }

    /**
     * @param int $adminUserId
     * @return string
     */
    private function flagCode(int $adminUserId): string
    {
        return self::FLAG_CODE_PREFIX . $adminUserId;
    }
}
