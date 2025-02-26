<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\Command;

use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Report as ReportResource;
use Magento\Framework\Exception\LocalizedException;

class SaveFromCspReport implements SaveFromCspReportInterface
{
    public function __construct(private readonly ReportResource $resource) {
    }

    /**
     * @inheritDoc
     */
    public function execute(string $jsonData): void
    {
        // Parse JSON data
        $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);

        if ($data === null) {
            throw new LocalizedException(__('Invalid JSON data'));
        }

        // Check for 'csp-report' key
        if (!isset($data['csp-report']) || !is_array($data['csp-report'])) {
            throw new LocalizedException(__('Invalid CSP report data'));
        }

        $cspReport = $data['csp-report'];

        if (!isset($cspReport['status-code']) || $cspReport['status-code'] != 200) {
            throw new LocalizedException(__('Invalid CSP report status code'));
        }

        // Normalize keys from hyphenated to underscore (e.g., 'document-uri' -> 'document_uri')
        $dataToInsert = [];
        foreach ($cspReport as $key => $value) {
            $dataToInsert[str_replace('-', '_', $key)] = $value;
        }

        // Build and execute the INSERT ... ON DUPLICATE KEY UPDATE query
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getMainTable();

        $query = "INSERT INTO $tableName (blocked_uri, disposition, document_uri, effective_directive, original_policy, referrer, script_sample, status_code, violated_directive, source_file, line_number, count)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
          ON DUPLICATE KEY UPDATE count = count + 1";

        $connection->query(
            $query,
            [
                $dataToInsert['blocked_uri'],
                $dataToInsert['disposition'],
                $dataToInsert['document_uri'],
                $dataToInsert['effective_directive'],
                $dataToInsert['original_policy'],
                $dataToInsert['referrer'],
                $dataToInsert['script_sample'],
                $dataToInsert['status_code'],
                $dataToInsert['violated_directive'],
                $dataToInsert['source_file'],
                $dataToInsert['line_number'],
            ]
        );
    }
}