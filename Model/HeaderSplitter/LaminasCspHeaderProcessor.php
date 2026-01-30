<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\HeaderSplitter;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Hryvinskyi\Csp\Api\CspHeaderSplitterInterface;
use Hryvinskyi\Csp\Api\CspValueOptimizerInterface;
use Hryvinskyi\Csp\Api\LaminasPluginRegistrarInterface;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use Psr\Log\LoggerInterface;

/**
 * Laminas-based CSP header processor with plugin registration.
 */
class LaminasCspHeaderProcessor extends AbstractCspHeaderProcessor
{
    public function __construct(
        ConfigInterface $config,
        CspHeaderSplitterInterface $headerSplitter,
        CspValueOptimizerInterface $valueOptimizer,
        LoggerInterface $logger,
        private readonly LaminasPluginRegistrarInterface $pluginRegistrar
    ) {
        parent::__construct($config, $headerSplitter, $valueOptimizer, $logger);
    }

    /**
     * @inheritdoc
     */
    protected function beforeProcessHeaders(HttpResponse $response, bool $splittingEnabled): void
    {
        if (!$splittingEnabled) {
            return;
        }

        $pluginsRegistered = $this->pluginRegistrar->registerPlugins($response);

        if (!$pluginsRegistered) {
            $this->logger->warning(
                'Failed to register Laminas CSP header plugins. Header splitting may not work correctly.'
            );
        }
    }
}
