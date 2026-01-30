<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;

interface WhitelistManagerInterface
{
    /**
     * Result codes
     */
    public const RESULT_SUCCESS = 1;
    public const RESULT_EXISTS = 2;
    public const RESULT_NOT_SAVED = 3;
    public const RESULT_REDUNDANT = 4;

    /**
     * Process a new whitelist
     *
     * @param WhitelistInterface $whitelist
     * @param ReportInterface $report
     * @return int Result code
     */
    public function processNewWhitelist(
        WhitelistInterface $whitelist,
        ReportInterface $report
    ): int;
}