<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\Command;

use Hryvinskyi\Csp\Api\CspReportParserInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Report as ReportResource;
use Magento\Framework\Exception\LocalizedException;

class SaveFromCspReport implements SaveFromCspReportInterface
{
    public function __construct(
        private readonly ReportResource $resource,
        private readonly CspReportParserInterface $cspReportParser
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(int $groupId, string $jsonData): void
    {
        $cspReport = $this->cspReportParser->parseNormalized($jsonData);

        if (!isset($cspReport['status_code'])) {
            throw new LocalizedException(__('Invalid CSP report status code'));
        }

        $this->insertReport($cspReport, $groupId);
    }

    /**
     * Insert or update report in the database.
     *
     * @param array $cspReport
     * @param int $groupId
     * @return void
     */
    private function insertReport(array $cspReport, int $groupId): void
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getMainTable();

        $query = "INSERT INTO $tableName (blocked_uri, disposition, document_uri, effective_directive, original_policy, referrer, script_sample, status_code, violated_directive, source_file, line_number, count, group_id)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
          ON DUPLICATE KEY UPDATE count = count + 1";

        $connection->query(
            $query,
            [
                $cspReport['blocked_uri'] ?? '',
                $cspReport['disposition'] ?? null,
                $cspReport['document_uri'] ?? '',
                $cspReport['effective_directive'] ?? '',
                $cspReport['original_policy'] ?? null,
                $cspReport['referrer'] ?? null,
                $cspReport['script_sample'] ?? null,
                $cspReport['status_code'],
                $cspReport['violated_directive'] ?? '',
                $cspReport['source_file'] ?? '',
                $cspReport['line_number'] ?? 0,
                $groupId,
            ]
        );
    }
}
