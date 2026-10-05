<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Config;

/**
 * Settings of policy enforcement and violation report collection.
 *
 * @api
 */
interface ReportingConfigInterface
{
    /**
     * Whether violation reports are sent to this module.
     *
     * @return bool
     */
    public function isReportsEnabled(): bool;

    /**
     * Whether the admin policy is enforced instead of report-only.
     *
     * @return bool
     */
    public function isEnabledRestrictModeAdminhtml(): bool;

    /**
     * Whether the storefront policy is enforced instead of report-only.
     *
     * @param int|null $storeId Current store when null
     * @return bool
     */
    public function isEnabledRestrictModeFrontend(?int $storeId = null): bool;

    /**
     * Violations accepted from one client per minute.
     *
     * @return int
     */
    public function getReportRateLimit(): int;

    /**
     * Largest report body accepted, in bytes.
     *
     * @return int
     */
    public function getMaxReportBodyBytes(): int;
}
