<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Config;

/**
 * Settings of the header optimisation steps.
 *
 * @api
 */
interface OptimizationConfigInterface
{
    /**
     * Whether header values are optimised at all (duplicates are then always removed).
     *
     * @param int|null $storeId Current store when null
     * @return bool
     */
    public function isValueOptimizationEnabled(?int $storeId = null): bool;

    /**
     * Whether the https:// scheme is removed from host sources on HTTPS pages.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isSchemeStrippingEnabled(?int $storeId = null): bool;

    /**
     * Whether hosts covered by a wildcard in the same directive are removed.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isRedundantWildcardRemovalEnabled(?int $storeId = null): bool;

    /**
     * Whether directives that allow exactly what their fallback allows are removed.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isRedundantDirectiveRemovalEnabled(?int $storeId = null): bool;

    /**
     * Whether subdomains of trusted parent domains are replaced with a wildcard.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isSubdomainWildcardConsolidationEnabled(?int $storeId = null): bool;

    /**
     * Minimum number of subdomains of one trusted parent that are replaced with a wildcard (at least 2).
     *
     * @param int|null $storeId
     * @return int
     */
    public function getSubdomainWildcardThreshold(?int $storeId = null): int;

    /**
     * Parent domains the admin trusts with every subdomain: lower case, without a leading "*." or ".", at least two
     * labels.
     *
     * @param int|null $storeId
     * @return list<string>
     */
    public function getSubdomainWildcardParents(?int $storeId = null): array;
}
