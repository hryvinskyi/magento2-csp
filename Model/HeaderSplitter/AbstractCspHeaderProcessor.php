<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
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

/**
 * Abstract CSP header processor with common functionality.
 */
abstract class AbstractCspHeaderProcessor implements CspHeaderProcessorInterface
{
    protected const CSP_HEADERS = [
        'Content-Security-Policy',
        'Content-Security-Policy-Report-Only'
    ];

    public function __construct(
        protected readonly ConfigInterface $config,
        protected readonly CspHeaderSplitterInterface $headerSplitter,
        protected readonly CspValueOptimizerInterface $valueOptimizer,
        protected readonly LoggerInterface $logger
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

        $this->beforeProcessHeaders($response, $splittingEnabled);

        foreach (static::CSP_HEADERS as $headerName) {
            $headerValue = $this->extractHeaderValue($response, $headerName);

            if ($headerValue === null) {
                continue;
            }

            $headerValue = $this->optimizeHeaderIfEnabled(
                $response,
                $headerName,
                $headerValue,
                $optimizationEnabled,
                $splittingEnabled
            );

            if ($splittingEnabled) {
                $this->headerSplitter->splitHeader($response, $headerName, $headerValue);
            }
        }
    }

    /**
     * Hook method called before processing headers. Override in child classes for custom setup.
     *
     * @param HttpResponse $response
     * @param bool $splittingEnabled
     * @return void
     */
    protected function beforeProcessHeaders(HttpResponse $response, bool $splittingEnabled): void
    {
    }

    /**
     * Optimize header value if optimization is enabled.
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @param string $headerValue
     * @param bool $optimizationEnabled
     * @param bool $splittingEnabled
     * @return string
     */
    private function optimizeHeaderIfEnabled(
        HttpResponse $response,
        string $headerName,
        string $headerValue,
        bool $optimizationEnabled,
        bool $splittingEnabled
    ): string {
        if (!$optimizationEnabled) {
            return $headerValue;
        }

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

        if (!$splittingEnabled) {
            $this->updateHeader($response, $headerName, $headerValue);
        }

        return $headerValue;
    }

    /**
     * Update header value in response.
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @param string $headerValue
     * @return void
     */
    protected function updateHeader(HttpResponse $response, string $headerName, string $headerValue): void
    {
        $response->clearHeader($headerName);
        $response->setHeader($headerName, $headerValue, true);
    }

    /**
     * Extract header value from response.
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @return string|null
     */
    protected function extractHeaderValue(HttpResponse $response, string $headerName): ?string
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
