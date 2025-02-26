<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Plugin\Framework\App\Config\ScopeConfigInterface;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class OverwriteCspConfigValues
{
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Force report_uri configuration to point to CSP Backoffice controller
     *
     * @param ScopeConfigInterface $subject
     * @param mixed $value
     * @param string $path
     * @return mixed
     */
    public function afterGetValue(ScopeConfigInterface $subject, mixed $value, mixed $path): mixed
    {
        $value = $this->handleReportUriConfig($value, $path);
        return $this->handleReportOnlyConfig($value, $path);
    }

    private function handleReportUriConfig(mixed $value, mixed $path): mixed
    {
        if (!str_starts_with($path, 'csp/') || !str_ends_with($path, '/report_uri')) {
            return $value;
        }

        if ($this->config->isReportsEnabled() === false) {
            return $value;
        }

        return $this->storeManager->getDefaultStoreView()?->getBaseUrl() . 'csp_report_watch';
    }

    private function handleReportOnlyConfig(mixed $value, mixed $path): mixed
    {
        if ($path === 'csp/mode/storefront/report_only' && $this->config->isEnabledRestrictModeFrontend()) {
            return 0;
        }

        if ($path === 'csp/mode/admin/report_only' && $this->config->isEnabledRestrictModeAdminhtml()) {
            return 0;
        }

        return $value;
    }
}