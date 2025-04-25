<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\HeaderSplitter;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Hryvinskyi\Csp\Api\CspHeaderSplitterInterface;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use Psr\Log\LoggerInterface;

class DefaultHeaderSplitter implements CspHeaderSplitterInterface
{
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function splitHeader(
        HttpResponse $response,
        string $headerName,
        string $headerValue
    ): void {
        $maxHeaderSize = $this->config->getMaxHeaderSize();

        if (mb_strlen($headerValue, '8bit') <= $maxHeaderSize) {
            return;
        }

        // Clear the original header
        $response->clearHeader($headerName);

        // Split the header into multiple parts
        $this->splitCspHeader($response, $headerName, $headerValue, $maxHeaderSize);
    }

    /**
     * Split a CSP header into multiple headers
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @param string $headerValue
     * @param int $maxHeaderSize
     * @return void
     */
    private function splitCspHeader(
        HttpResponse $response,
        string $headerName,
        string $headerValue,
        int $maxHeaderSize
    ): void {
        $directives = preg_split('/;\s*/', $headerValue, -1, PREG_SPLIT_NO_EMPTY);
        $currentHeader = '';
        $isFirst = true;

        foreach ($directives as $directive) {
            $directive = trim($directive);

            if ($currentHeader === '') {
                $currentHeader = $directive;
                continue;
            }

            $testHeader = $currentHeader . '; ' . $directive;
            if (strlen($testHeader) > $maxHeaderSize) {
                // Set the current header and start a new one
                $response->setHeader($headerName, $currentHeader, $isFirst);
                $isFirst = false;
                $currentHeader = $directive;

                // If individual directive is too large, log an error
                if (strlen($directive) > $maxHeaderSize) {
                    $this->logger->error(
                        sprintf(
                            'CSP directive exceeds max header size (%d bytes): %s',
                            strlen($directive),
                            substr($directive, 0, 100) . '...'
                        )
                    );
                }
            } else {
                $currentHeader = $testHeader;
            }
        }

        // Set the last header part if not empty
        if ($currentHeader !== '') {
            $response->setHeader($headerName, $currentHeader, $isFirst);
        }
    }
}