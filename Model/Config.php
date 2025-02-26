<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    public const XML_PATH_CSP_RULES_ENABLED = 'csp/general/enabled_rules';
    public const XML_PATH_CSP_REPORTS_ENABLED = 'csp/general/enabled_reports';
    public const XML_PATH_CSP_RESTRICT_MODE_ADMINHTML = 'csp/general/enable_restrict_mode_adminhtml';
    public const XML_PATH_CSP_RESTRICT_MODE_FRONTEND = 'csp/general/enable_restrict_mode_frontend';
    public const XML_PATH_CSP_ADD_ALL_STOREFRONT_URLS = 'csp/general/add_all_storefront_urls';


    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    /**
     * @inheritDoc
     */
    public function isRulesEnabled($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CSP_RULES_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function isReportsEnabled($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CSP_REPORTS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function isEnabledRestrictModeAdminhtml($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CSP_RESTRICT_MODE_ADMINHTML,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function isEnabledRestrictModeFrontend($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CSP_RESTRICT_MODE_FRONTEND,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function isAddAllStorefrontUrls(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CSP_ADD_ALL_STOREFRONT_URLS);
    }
}
