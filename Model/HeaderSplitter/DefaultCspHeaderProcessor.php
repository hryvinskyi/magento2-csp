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
use Magento\Framework\App\Response\HttpInterface as HttpResponse;

class DefaultCspHeaderProcessor implements CspHeaderProcessorInterface
{
    private const CSP_HEADERS = [
        'Content-Security-Policy',
        'Content-Security-Policy-Report-Only'
    ];

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly CspHeaderSplitterInterface $headerSplitter
    ) {
    }

    /**
     * @inheritdoc
     */
    public function processHeaders(HttpResponse $response): void
    {
        if (!$this->config->isHeaderSplittingEnabled()) {
            return;
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