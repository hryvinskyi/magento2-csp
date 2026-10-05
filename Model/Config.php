<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\Config\HeaderSplittingConfigInterface;
use Hryvinskyi\Csp\Api\Config\OptimizationConfigInterface;
use Hryvinskyi\Csp\Api\Config\ReportCleanupConfigInterface;
use Hryvinskyi\Csp\Api\Config\ReportingConfigInterface;
use Hryvinskyi\Csp\Api\Config\RulesConfigInterface;
use Hryvinskyi\Logger\Model\DebugConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Reads the module configuration.
 */
class Config implements
    RulesConfigInterface,
    OptimizationConfigInterface,
    HeaderSplittingConfigInterface,
    ReportingConfigInterface,
    ReportCleanupConfigInterface,
    DebugConfigInterface
{
    public const XML_PATH_CSP_RULES_ENABLED = 'csp/general/enabled_rules';
    public const XML_PATH_CSP_REPORTS_ENABLED = 'csp/general/enabled_reports';
    public const XML_PATH_CSP_RESTRICT_MODE_ADMINHTML = 'csp/general/enable_restrict_mode_adminhtml';
    public const XML_PATH_CSP_RESTRICT_MODE_FRONTEND = 'csp/general/enable_restrict_mode_frontend';
    public const XML_PATH_CSP_ADD_ALL_STOREFRONT_URLS = 'csp/general/add_all_storefront_urls';
    public const XML_PATH_CSP_HEADER_SPLITTING_ENABLED = 'csp/general/enable_header_splitting';
    public const XML_PATH_CSP_MAX_HEADER_SIZE = 'csp/general/max_header_size';
    public const XML_PATH_CSP_DEBUG_MODE_ENABLED = 'csp/general/debug_mode_enabled';
    public const XML_PATH_CSP_VALUE_OPTIMIZATION_ENABLED = 'csp/general/enable_value_optimization';
    public const XML_PATH_CSP_REDUNDANT_WILDCARD_REMOVAL_ENABLED = 'csp/general/enable_redundant_wildcard_removal';
    public const XML_PATH_CSP_REDUNDANT_DIRECTIVE_REMOVAL_ENABLED = 'csp/general/enable_default_src_consolidation';
    public const XML_PATH_CSP_SUBDOMAIN_WILDCARD_CONSOLIDATION_ENABLED =
        'csp/general/enable_subdomain_wildcard_consolidation';
    public const XML_PATH_CSP_SUBDOMAIN_WILDCARD_THRESHOLD = 'csp/general/subdomain_wildcard_threshold';
    public const XML_PATH_CSP_SUBDOMAIN_WILDCARD_PARENTS = 'csp/general/subdomain_wildcard_parents';
    public const XML_PATH_CSP_SCHEME_STRIPPING_ENABLED = 'csp/general/enable_scheme_path_stripping';
    public const XML_PATH_CSP_REPORT_CLEANUP_ENABLED = 'csp/report_cleanup/enabled';
    public const XML_PATH_CSP_REPORT_CLEANUP_MODE = 'csp/report_cleanup/mode';
    public const XML_PATH_CSP_REPORT_CLEANUP_THRESHOLD = 'csp/report_cleanup/threshold';
    public const XML_PATH_CSP_REPORT_RATE_LIMIT = 'csp/report_ingestion/rate_limit';
    public const XML_PATH_CSP_REPORT_MAX_BODY_BYTES = 'csp/report_ingestion/max_body_bytes';

    private const DEFAULT_MAX_HEADER_SIZE = 4096;
    private const DEFAULT_SUBDOMAIN_WILDCARD_THRESHOLD = 3;
    private const MIN_SUBDOMAIN_WILDCARD_THRESHOLD = 2;
    private const DEFAULT_CLEANUP_MODE = 'date';
    private const DEFAULT_CLEANUP_THRESHOLD = 30;
    private const DEFAULT_REPORT_RATE_LIMIT = 120;
    private const DEFAULT_MAX_REPORT_BODY_BYTES = 65536;
    private const MIN_PARENT_LABELS = 2;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    /**
     * @inheritDoc
     */
    public function isRulesEnabled(?int $storeId = null): bool
    {
        return $this->storeFlag(self::XML_PATH_CSP_RULES_ENABLED, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function isReportsEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CSP_REPORTS_ENABLED);
    }

    /**
     * @inheritDoc
     */
    public function isEnabledRestrictModeAdminhtml(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CSP_RESTRICT_MODE_ADMINHTML);
    }

    /**
     * @inheritDoc
     */
    public function isEnabledRestrictModeFrontend(?int $storeId = null): bool
    {
        return $this->storeFlag(self::XML_PATH_CSP_RESTRICT_MODE_FRONTEND, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function isAddAllStorefrontUrls(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CSP_ADD_ALL_STOREFRONT_URLS);
    }

    /**
     * @inheritDoc
     */
    public function isHeaderSplittingEnabled(?int $storeId = null): bool
    {
        return $this->storeFlag(self::XML_PATH_CSP_HEADER_SPLITTING_ENABLED, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getMaxHeaderSize(?int $storeId = null): int
    {
        $size = $this->storeInt(self::XML_PATH_CSP_MAX_HEADER_SIZE, $storeId);

        return $size > 0 ? $size : self::DEFAULT_MAX_HEADER_SIZE;
    }

    /**
     * @inheritDoc
     */
    public function isDebug(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CSP_DEBUG_MODE_ENABLED);
    }

    /**
     * @inheritDoc
     */
    public function isValueOptimizationEnabled(?int $storeId = null): bool
    {
        return $this->storeFlag(self::XML_PATH_CSP_VALUE_OPTIMIZATION_ENABLED, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function isRedundantWildcardRemovalEnabled(?int $storeId = null): bool
    {
        return $this->storeFlag(self::XML_PATH_CSP_REDUNDANT_WILDCARD_REMOVAL_ENABLED, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function isRedundantDirectiveRemovalEnabled(?int $storeId = null): bool
    {
        return $this->storeFlag(self::XML_PATH_CSP_REDUNDANT_DIRECTIVE_REMOVAL_ENABLED, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function isSubdomainWildcardConsolidationEnabled(?int $storeId = null): bool
    {
        return $this->storeFlag(self::XML_PATH_CSP_SUBDOMAIN_WILDCARD_CONSOLIDATION_ENABLED, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getSubdomainWildcardThreshold(?int $storeId = null): int
    {
        $threshold = $this->storeInt(self::XML_PATH_CSP_SUBDOMAIN_WILDCARD_THRESHOLD, $storeId);
        if ($threshold <= 0) {
            return self::DEFAULT_SUBDOMAIN_WILDCARD_THRESHOLD;
        }

        return max(self::MIN_SUBDOMAIN_WILDCARD_THRESHOLD, $threshold);
    }

    /**
     * @inheritDoc
     */
    public function getSubdomainWildcardParents(?int $storeId = null): array
    {
        $value = $this->storeString(self::XML_PATH_CSP_SUBDOMAIN_WILDCARD_PARENTS, $storeId);
        $parents = [];
        foreach (preg_split('/[\s,]+/', strtolower($value), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $parent) {
            $parent = ltrim($parent, '*.');
            if (count(explode('.', $parent)) >= self::MIN_PARENT_LABELS && !in_array($parent, $parents, true)) {
                $parents[] = $parent;
            }
        }

        return $parents;
    }

    /**
     * @inheritDoc
     */
    public function isSchemeStrippingEnabled(?int $storeId = null): bool
    {
        return $this->storeFlag(self::XML_PATH_CSP_SCHEME_STRIPPING_ENABLED, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function isReportCleanupEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CSP_REPORT_CLEANUP_ENABLED);
    }

    /**
     * @inheritDoc
     */
    public function getReportCleanupMode(): string
    {
        $mode = $this->defaultString(self::XML_PATH_CSP_REPORT_CLEANUP_MODE);

        return $mode !== '' ? $mode : self::DEFAULT_CLEANUP_MODE;
    }

    /**
     * @inheritDoc
     */
    public function getReportCleanupThreshold(): int
    {
        $threshold = (int)$this->defaultString(self::XML_PATH_CSP_REPORT_CLEANUP_THRESHOLD);

        return $threshold > 0 ? $threshold : self::DEFAULT_CLEANUP_THRESHOLD;
    }

    /**
     * @inheritDoc
     */
    public function getReportRateLimit(): int
    {
        $limit = (int)$this->defaultString(self::XML_PATH_CSP_REPORT_RATE_LIMIT);

        return $limit > 0 ? $limit : self::DEFAULT_REPORT_RATE_LIMIT;
    }

    /**
     * @inheritDoc
     */
    public function getMaxReportBodyBytes(): int
    {
        $bytes = (int)$this->defaultString(self::XML_PATH_CSP_REPORT_MAX_BODY_BYTES);

        return $bytes > 0 ? $bytes : self::DEFAULT_MAX_REPORT_BODY_BYTES;
    }

    /**
     * Store-scoped flag.
     *
     * @param string $path
     * @param int|null $storeId
     * @return bool
     */
    private function storeFlag(string $path, ?int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Store-scoped integer, 0 when not set or not numeric.
     *
     * @param string $path
     * @param int|null $storeId
     * @return int
     */
    private function storeInt(string $path, ?int $storeId): int
    {
        return (int)$this->storeString($path, $storeId);
    }

    /**
     * Store-scoped string, empty when not set.
     *
     * @param string $path
     * @param int|null $storeId
     * @return string
     */
    private function storeString(string $path, ?int $storeId): string
    {
        $value = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);

        return is_scalar($value) ? trim((string)$value) : '';
    }

    /**
     * Default-scope string, empty when not set.
     *
     * @param string $path
     * @return string
     */
    private function defaultString(string $path): string
    {
        $value = $this->scopeConfig->getValue($path);

        return is_scalar($value) ? trim((string)$value) : '';
    }
}
