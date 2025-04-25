<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\HeaderSplitter;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Hryvinskyi\Csp\Api\CspHeaderProcessorInterface;
use Hryvinskyi\Csp\Api\CspHeaderSplitterInterface;
use Hryvinskyi\Csp\Api\LaminasPluginRegistrarInterface;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use Psr\Log\LoggerInterface;

class LaminasCspHeaderProcessor implements CspHeaderProcessorInterface
{
    private const CSP_HEADERS = [
        'Content-Security-Policy',
        'Content-Security-Policy-Report-Only'
    ];

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly CspHeaderSplitterInterface $headerSplitter,
        private readonly LaminasPluginRegistrarInterface $pluginRegistrar,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function processHeaders(HttpResponse $response): void
    {
        if (!$this->config->isHeaderSplittingEnabled()) {
            return;
        }

        // Register Laminas HTTP plugins for CSP headers
        $pluginsRegistered = $this->pluginRegistrar->registerPlugins($response);
        if (!$pluginsRegistered) {
            $this->logger->warning('Failed to register Laminas CSP header plugins. Header splitting may not work correctly.');
        }

        foreach (self::CSP_HEADERS as $headerName) {
            $headerValue = $this->extractHeaderValue($response, $headerName);
            if ($headerValue === null) {
                continue;
            }

            $this->headerSplitter->splitHeader($response, $headerName, $headerValue);
        }
    }

    /**
     * Extract header value from response
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @return string|null
     */
    private function extractHeaderValue(HttpResponse $response, string $headerName): ?string
    {
        $header = $response->getHeader($headerName);

        if (!$header) {
            return null;
        }

        if (is_array($header)) {
            $headerValue = $header[0] ?? '';
        } elseif (is_object($header) && method_exists($header, 'getFieldValue')) {
            $headerValue = $header->getFieldValue();
        } else {
            $headerValue = (string)$header;
        }

        return $headerValue ?: null;
    }
}