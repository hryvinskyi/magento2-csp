<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Api\Data\ViolationInterface;
use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use Magento\Framework\App\ResourceConnection;

/**
 * Upserts the report group and the report in two statements, counting repeated violations.
 *
 * A report is keyed by its group, so the same page and resource seen in two stores lands in each store's group. The
 * decision that applies is the strictest of the group's own and of the groups covering it: store 0 for every store,
 * area `all` for both areas, where the groups recorded before 2.0.0 keep the decisions made on them. A group that
 * is still pending takes on a wider Skip, so a value skipped once does not ask for review again in every store.
 */
class ViolationRecorder implements ViolationRecorderInterface
{
    private const GROUP_TABLE = 'hryvinskyi_csp_violation_report_group';
    private const REPORT_TABLE = 'hryvinskyi_csp_violation_report';

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(private readonly ResourceConnection $resourceConnection)
    {
    }

    /**
     * @inheritDoc
     */
    public function record(ViolationInterface $violation, string $policy, string $value, int $storeId, Area $area): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $groupTable = $this->resourceConnection->getTableName(self::GROUP_TABLE);
        $statuses = $connection->fetchCol(
            $connection->select()
                ->from($groupTable, ['status'])
                ->where('policy = ?', $policy)
                ->where('value = ?', $value)
                ->where('store_id IN (?)', array_values(array_unique([$storeId, StoreIdList::ALL_STORES])))
                ->where('area IN (?)', array_values(array_unique([$area->value, Area::ALL->value])))
        );
        $decision = $this->decision($statuses);
        if (!$decision->recordsViolations()) {
            return false;
        }

        $connection->query(
            sprintf(
                'INSERT INTO %s (policy, value, store_id, area, status, count) VALUES (?, ?, ?, ?, ?, 1)'
                . ' ON DUPLICATE KEY UPDATE count = count + 1, group_id = LAST_INSERT_ID(group_id),'
                . ' status = IF(status = ?, VALUES(status), status)',
                $connection->quoteIdentifier($groupTable)
            ),
            [$policy, $value, $storeId, $area->value, $decision->value, Status::PENDING->value]
        );
        $groupId = $connection->fetchOne('SELECT LAST_INSERT_ID()');
        if (!is_numeric($groupId) || (int)$groupId === 0) {
            return false;
        }

        $connection->query(
            sprintf(
                'INSERT INTO %s (group_id, blocked_uri, document_uri, effective_directive, violated_directive,'
                . ' disposition, original_policy, referrer, script_sample, status_code, source_file, line_number, count)'
                . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
                . ' ON DUPLICATE KEY UPDATE count = count + 1, disposition = VALUES(disposition),'
                . ' original_policy = VALUES(original_policy), status_code = VALUES(status_code)',
                $connection->quoteIdentifier($this->resourceConnection->getTableName(self::REPORT_TABLE))
            ),
            [
                (int)$groupId,
                $violation->getBlockedUri(),
                $violation->getDocumentUri(),
                $violation->getEffectiveDirective(),
                $violation->getViolatedDirective(),
                $violation->getDisposition(),
                $violation->getOriginalPolicy(),
                $violation->getReferrer(),
                $violation->getScriptSample(),
                (string)$violation->getStatusCode(),
                $violation->getSourceFile(),
                $violation->getLineNumber(),
            ]
        );

        return true;
    }

    /**
     * Strictest of the statuses read for a violation; Pending when none applies.
     *
     * @param array<mixed> $statuses
     * @return Status
     */
    private function decision(array $statuses): Status
    {
        $decision = Status::PENDING;
        foreach ($statuses as $status) {
            $decision = $decision->stricter(
                is_numeric($status) ? (Status::tryFrom((int)$status) ?? Status::PENDING) : Status::PENDING
            );
        }

        return $decision;
    }
}
