<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

interface ConfigInterface
{
    /**
     * Is enabled rules
     *
     * @param $store
     * @return bool
     */
    public function isRulesEnabled($store = null): bool;

    /**
     * Is enabled report
     *
     * @param $store
     * @return bool
     */
    public function isReportsEnabled($store = null): bool;

    /**
     * Is enabled restrict mode for adminhtml
     *
     * @param $store
     * @return bool
     */
    public function isEnabledRestrictModeAdminhtml($store = null): bool;

    /**
     * Is enabled restrict mode for frontend
     *
     * @param $store
     * @return bool
     */
    public function isEnabledRestrictModeFrontend($store = null): bool;

    /**
     * Is add all storefront urls
     *
     * @return bool
     */
    public function isAddAllStorefrontUrls(): bool;

    /**
     * Is header splitting enabled
     *
     * @return bool
     */
    public function isHeaderSplittingEnabled(): bool;

    /**
     * Get max header size for splitting
     *
     * @return int
     */
    public function getMaxHeaderSize(): int;

    /**
     * Check if CSP value optimization (deduplication) is enabled
     *
     * @return bool
     */
    public function isValueOptimizationEnabled(): bool;

    /**
     * Check if redundant wildcard removal is enabled
     *
     * @return bool
     */
    public function isRedundantWildcardRemovalEnabled(): bool;

    /**
     * Check if default-src consolidation is enabled
     *
     * @return bool
     */
    public function isDefaultSrcConsolidationEnabled(): bool;

    /**
     * Check if subdomain-to-wildcard consolidation is enabled
     *
     * @return bool
     */
    public function isSubdomainWildcardConsolidationEnabled(): bool;

    /**
     * Get the minimum number of subdomains required to consolidate into a wildcard
     *
     * @return int
     */
    public function getSubdomainWildcardThreshold(): int;

    /**
     * Check if scheme and path stripping is enabled
     *
     * @return bool
     */
    public function isSchemePathStrippingEnabled(): bool;

    /**
     * Check if automatic report cleanup is enabled
     *
     * @return bool
     */
    public function isReportCleanupEnabled(): bool;

    /**
     * Get report cleanup mode ('date' or 'count')
     *
     * @return string
     */
    public function getReportCleanupMode(): string;

    /**
     * Get report cleanup threshold (days for date mode, max records for count mode)
     *
     * @return int
     */
    public function getReportCleanupThreshold(): int;
}
