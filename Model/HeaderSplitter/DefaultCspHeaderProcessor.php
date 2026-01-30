<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\HeaderSplitter;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Hryvinskyi\Csp\Api\CspHeaderProcessorInterface;
use Hryvinskyi\Csp\Api\CspHeaderSplitterInterface;
use Hryvinskyi\Csp\Api\CspValueOptimizerInterface;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use Psr\Log\LoggerInterface;

class DefaultCspHeaderProcessor implements CspHeaderProcessorInterface
{
    private const CSP_HEADERS = [
        'Content-Security-Policy',
        'Content-Security-Policy-Report-Only'
    ];

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly CspHeaderSplitterInterface $headerSplitter,
        private readonly CspValueOptimizerInterface $valueOptimizer,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function processHeaders(HttpResponse $response): void
    {
        $optimizationEnabled = $this->config->isValueOptimizationEnabled();
        $splittingEnabled = $this->config->isHeaderSplittingEnabled();

        if (!$optimizationEnabled && !$splittingEnabled) {
            return;
        }

        foreach (self::CSP_HEADERS as $headerName) {
            $headerValue = $this->extractHeaderValue($response, $headerName);

            if ($headerValue === null) {
                continue;
            }

            // Apply optimization if enabled
            if ($optimizationEnabled) {
                $originalLength = strlen($headerValue);
                $headerValue = $this->valueOptimizer->optimizeHeader($headerValue);
                $optimizedLength = strlen($headerValue);

                if ($originalLength !== $optimizedLength) {
                    $this->logger->debug(sprintf(
                        'CSP header "%s" optimized: %d bytes -> %d bytes (saved %d bytes)',
                        $headerName,
                        $originalLength,
                        $optimizedLength,
                        $originalLength - $optimizedLength
                    ));
                }
                // Update the header with optimized value if not splitting
                if (!$splittingEnabled) {
                    $this->updateHeader($response, $headerName, $headerValue);
                }
            }

            // Apply splitting if enabled
            if ($splittingEnabled) {
                $this->headerSplitter->splitHeader($response, $headerName, $headerValue);
            }
        }
    }

    /**
     * Update header value in response
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @param string $headerValue
     * @return void
     */
    private function updateHeader(HttpResponse $response, string $headerName, string $headerValue): void
    {
        $response->clearHeader($headerName);
        $response->setHeader($headerName, $headerValue, true);
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
        $headerValue = $response->getHeader($headerName);

        if (!$headerValue) {
            return null;
        }

        if (is_array($headerValue)) {
            $headerValue = $headerValue[0] ?? '';
        }

        if (is_object($headerValue) && method_exists($headerValue, 'getFieldValue')) {
            $headerValue = $headerValue->getFieldValue();
        }

        return $headerValue ?: null;
    }
}