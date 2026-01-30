<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ReportGroup\Command;

use Hryvinskyi\Csp\Api\BlockedUriValueExtractorInterface;
use Hryvinskyi\Csp\Api\CspReportParserInterface;
use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Model\Report\Command\CspReportConverterInterface;
use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup as ReportGroupResource;
use Magento\Store\Model\StoreManagerInterface;

class SaveFromCspReport implements SaveFromCspReportInterface
{
    public function __construct(
        private readonly ReportGroupResource $resource,
        private readonly StoreManagerInterface $storeManager,
        private readonly CspReportConverterInterface $cspReportConverter,
        private readonly BlockedUriValueExtractorInterface $blockedUriValueExtractor,
        private readonly CspReportParserInterface $cspReportParser
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(ReportGroupInterface $reportGroup, string $json): ReportGroupInterface
    {
        $cspReport = $this->cspReportParser->parse($json);

        $policy = $this->cspReportConverter->normalizePolicy($cspReport['effective-directive']);
        $value = $this->blockedUriValueExtractor->extractValue($cspReport['blocked-uri']);
        $storeId = (int)$this->storeManager->getStore()->getId();

        $groupId = $this->upsertGroup($policy, $value, $storeId);

        if ($groupId) {
            $reportGroup->setGroupId($groupId);
        }
        $reportGroup->setPolicy($policy);
        $reportGroup->setValue($value);
        $reportGroup->setStoreId($storeId);

        return $reportGroup;
    }

    /**
     * Insert or update the group in the database.
     *
     * @param string $policy
     * @param string $value
     * @param int $storeId
     * @return int|null
     */
    private function upsertGroup(string $policy, string $value, int $storeId): ?int
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getMainTable();
        $policyKey = ReportGroupInterface::POLICY;
        $valueKey = ReportGroupInterface::VALUE;
        $storeIdKey = ReportGroupInterface::STORE_ID;
        $countKey = ReportGroupInterface::COUNT;

        $select = $connection->select()
            ->from($tableName, ['group_id', 'status'])
            ->where("$policyKey = ?", $policy)
            ->where("$valueKey = ?", $value)
            ->where("$storeIdKey = ?", $storeId);
        $result = $connection->fetchRow($select);

        $groupId = $result ? (int)$result['group_id'] : null;
        $status = $result ? (int)$result['status'] : null;

        if ($status !== Status::DENIED->value) {
            $query = "INSERT INTO $tableName ($policyKey, $valueKey, $storeIdKey, $countKey)
                    VALUES (?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE $countKey = $countKey + 1";
            $connection->query($query, [$policy, $value, $storeId]);

            if (!$groupId) {
                $select = $connection->select()
                    ->from($tableName, ['group_id'])
                    ->where("$policyKey = ?", $policy)
                    ->where("$valueKey = ?", $value)
                    ->where("$storeIdKey = ?", $storeId);
                $groupId = $connection->fetchOne($select);
                $groupId = $groupId ? (int)$groupId : null;
            }
        } else {
            $groupId = null;
        }

        return $groupId;
    }
}
