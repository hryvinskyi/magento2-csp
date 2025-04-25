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

class LaminasCspHeaderSplitter implements CspHeaderSplitterInterface
{
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
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

        // Split by directives - splitting by semicolon
        $directives = [];
        preg_match_all('/([^;]+)(;|$)/', $headerValue, $matches);
        if (!empty($matches[1])) {
            $directives = array_map('trim', $matches[1]);
        }

        if (empty($directives)) {
            return;
        }

        $headerParts = [];
        $currentPart = '';

        // Extract the report-uri directive
        $reportUri = '';
        if (preg_match('/report-uri\s+([^;]+);?/', $headerValue, $reportUriMatch)) {
            $reportUri = 'report-uri ' . trim($reportUriMatch[1]);
        }

        foreach ($directives as $directive) {
            $directive = trim($directive);
            if (empty($directive) || str_starts_with($directive, 'default-src') || str_starts_with($directive, 'report-uri')) {
                continue;
            }

            if (!empty($reportUri)) {
                $directive .= '; ' . $reportUri;
            }

            // First directive in a new part
            if (empty($currentPart)) {
                $currentPart = $directive;
                continue;
            }

            // Check if adding this directive would exceed max size
            if (strlen($currentPart . '; ' . $directive) <= $maxHeaderSize) {
                $currentPart .= '; ' . $directive;
            } else {
                $headerParts[] = $currentPart;
                $currentPart = $directive;

                // Check if individual directive is too large
                if (strlen($directive) > $maxHeaderSize) {
                    $this->logger->error(
                        sprintf(
                            'CSP directive exceeds max header size (%d bytes): %s',
                            strlen($directive),
                            substr($directive, 0, 50) . '...'
                        )
                    );
                }
            }
        }

        // Add the last part
        if (!empty($currentPart)) {
            $headerParts[] = $currentPart;
        }

        // Set each part as a separate header
        foreach ($headerParts as $i => $part) {
            // Use replace=true only for first part to avoid duplicate header name in the response
            $response->setHeader($headerName, $part, $i === 0);
            $this->logger->debug("Set CSP header part " . ($i + 1) . " for {$headerName}: " . substr($part, 0, 50) . '...');
        }
    }
}