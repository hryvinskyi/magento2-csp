<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\HeaderSplitter;

use Hryvinskyi\Csp\Api\LaminasPluginRegistrarInterface;
use Laminas\Http\AbstractMessage;
use Laminas\Http\Header\ContentSecurityPolicy;
use Laminas\Http\Header\ContentSecurityPolicyReportOnly;
use Laminas\Loader\PluginClassLoader;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use Psr\Log\LoggerInterface;

class LaminasPluginRegistrar implements LaminasPluginRegistrarInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function registerPlugins(HttpResponse $response): bool
    {
        if (!$response instanceof AbstractMessage) {
            $this->logger->debug('Response is not an instance of AbstractMessage. Cannot register Laminas plugins.');
            return false;
        }

        $headers = $response->getHeaders();
        if (!method_exists($headers, 'getPluginClassLoader')) {
            $this->logger->debug('Headers object does not have getPluginClassLoader method.');
            return false;
        }

        $pluginClassLoader = $headers->getPluginClassLoader();
        if (!$pluginClassLoader instanceof PluginClassLoader) {
            $this->logger->debug('Plugin class loader is not an instance of PluginClassLoader.');
            return false;
        }

        $pluginClassLoader->registerPlugins([
            'ContentSecurityPolicy' => ContentSecurityPolicy::class,
            'ContentSecurityPolicyReportOnly' => ContentSecurityPolicyReportOnly::class,
        ]);
        $this->logger->debug('Successfully registered Laminas CSP header plugins.');

        return true;
    }
}