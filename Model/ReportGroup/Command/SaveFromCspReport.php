<?php
/**
 * Copyright (c) 2025. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ReportGroup\Command;

use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Model\Report\Command\CspReportConverterInterface;
use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup as ReportGroupResource;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class SaveFromCspReport implements SaveFromCspReportInterface
{
    public function __construct(
        private readonly ReportGroupResource $resource,
        private readonly StoreManagerInterface $storeManager,
        private readonly CspReportConverterInterface $cspReportConverter
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(ReportGroupInterface $reportGroup, string $json): ReportGroupInterface
    {
        $data = $this->parseJson($json);
        $this->validateCspReportData($data);

        $policy = $this->cspReportConverter->normalizePolicy($data['csp-report']['effective-directive']);
        $value = $this->extractValue($data['csp-report']['blocked-uri']);
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
     * @param string $json
     * @return array
     * @throws LocalizedException
     */
    private function parseJson(string $json): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new LocalizedException(__('Invalid JSON data: %1', $e->getMessage()));
        }
        return $data;
    }

    /**
     * @param array $data
     * @throws LocalizedException
     */
    private function validateCspReportData(array $data): void
    {
        if (!isset($data['csp-report'], $data['csp-report']['effective-directive'], $data['csp-report']['blocked-uri'])) {
            throw new LocalizedException(__('Invalid CSP report data'));
        }
    }

    /**
     * @param string $blockedUri
     * @return string|null
     */
    private function extractValue(string $blockedUri): ?string
    {
        try {
            if (filter_var($blockedUri, FILTER_VALIDATE_URL) || str_contains($blockedUri, '.')) {
                return parse_url($blockedUri, PHP_URL_HOST) ?: $blockedUri;
            }
            return $blockedUri;
        } catch (\Throwable) {
            return $blockedUri;
        }
    }

    /**
     * Insert or update the group in the database.
     *
     * @param string $policy
     * @param string $value
     * @param int $storeId
     * @return int|null
     * @throws LocalizedException
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
                $groupId = $groupId ? (int)$groupId: null;
            }
        } else {
            $groupId = null;
        }

        return $groupId;
    }
}
