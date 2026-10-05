<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Plugin\Csp\Api\ModeConfigManagerInterface;

use Hryvinskyi\Csp\Api\Config\ReportingConfigInterface;
use Hryvinskyi\Csp\Model\Report\ReportEndpointUrl;
use Magento\Csp\Api\Data\ModeConfiguredInterface;
use Magento\Csp\Api\ModeConfigManagerInterface;
use Magento\Csp\Model\Mode\Data\ModeConfiguredFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;

/**
 * Applies this module's enforcement and reporting settings to the CSP mode Magento renders.
 *
 * When restrict mode is on for the area the policy is enforced instead of report-only; when module reports are on
 * the report URI points to this module's endpoint on the page's own origin, replacing a configured core value.
 */
class ApplyReportingMode
{
    /**
     * @param ReportingConfigInterface $config
     * @param ReportEndpointUrl $reportEndpointUrl
     * @param State $appState
     * @param ModeConfiguredFactory $modeConfiguredFactory
     */
    public function __construct(
        private readonly ReportingConfigInterface $config,
        private readonly ReportEndpointUrl $reportEndpointUrl,
        private readonly State $appState,
        private readonly ModeConfiguredFactory $modeConfiguredFactory
    ) {
    }

    /**
     * Mode with this module's settings applied.
     *
     * @param ModeConfigManagerInterface $subject
     * @param ModeConfiguredInterface $result
     * @return ModeConfiguredInterface
     */
    public function afterGetConfigured(
        ModeConfigManagerInterface $subject,
        ModeConfiguredInterface $result
    ): ModeConfiguredInterface {
        $admin = $this->appState->getAreaCode() === Area::AREA_ADMINHTML;
        $enforced = $admin
            ? $this->config->isEnabledRestrictModeAdminhtml()
            : $this->config->isEnabledRestrictModeFrontend();
        $reportOnly = $result->isReportOnly() && !$enforced;
        $reportUri = $result->getReportUri();
        if ($this->config->isReportsEnabled()) {
            $reportUri = $admin ? $this->reportEndpointUrl->forAdmin() : $this->reportEndpointUrl->forStorefront();
        }

        if ($reportOnly === $result->isReportOnly() && $reportUri === $result->getReportUri()) {
            return $result;
        }

        return $this->modeConfiguredFactory->create(['reportOnly' => $reportOnly, 'reportUri' => $reportUri]);
    }
}
