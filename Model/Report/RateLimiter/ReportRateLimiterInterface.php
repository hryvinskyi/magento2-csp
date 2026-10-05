<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\RateLimiter;

use Hryvinskyi\Csp\Api\Data\Area;

/**
 * Limits how many violations one client may report per minute.
 */
interface ReportRateLimiterInterface
{
    /**
     * Take up to the requested number of violations from the client's allowance; returns how many may be recorded.
     *
     * @param string $clientKey
     * @param Area $area
     * @param int $requested
     * @return int
     */
    public function allow(string $clientKey, Area $area, int $requested): int;
}
