<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\Command;


interface SaveFromCspReportInterface
{
    /**
     * Store CSP report data from JSON into the Report model.
     *
     * @param string $jsonData JSON-encoded CSP report data
     * @return void The ID of the saved report
     * @throws \Magento\Framework\Exception\LocalizedException If JSON data is invalid or missing required structure
     */
    public function execute(int $groupId, string $jsonData): void;
}