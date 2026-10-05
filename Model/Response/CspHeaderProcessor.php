<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Response;

use Hryvinskyi\Csp\Api\Config\HeaderSplittingConfigInterface;
use Hryvinskyi\Csp\Api\Config\OptimizationConfigInterface;
use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use Hryvinskyi\Csp\Model\Policy\PolicyOptimizer;
use Hryvinskyi\Csp\Model\Policy\PolicyParser;
use Hryvinskyi\Csp\Model\Policy\PolicySplitter;
use Laminas\Http\AbstractMessage;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Header\MultipleHeaderInterface;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use Psr\Log\LoggerInterface;

/**
 * Optimises and, when it is too large, splits the CSP headers Magento rendered into the response.
 *
 * A header the response already carries on several lines is left alone. Split parts are kept only if the response
 * sends every one of them as its own line; otherwise the unsplit header is written back.
 */
class CspHeaderProcessor
{
    private const HEADERS = ['Content-Security-Policy', 'Content-Security-Policy-Report-Only'];

    /**
     * @param OptimizationConfigInterface $optimizationConfig
     * @param HeaderSplittingConfigInterface $splittingConfig
     * @param PolicyParser $parser
     * @param PolicyOptimizer $optimizer
     * @param PolicySplitter $splitter
     * @param LaminasPluginRegistrar $pluginRegistrar
     * @param OversizedHeaderNotice $oversizedHeaderNotice
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly OptimizationConfigInterface $optimizationConfig,
        private readonly HeaderSplittingConfigInterface $splittingConfig,
        private readonly PolicyParser $parser,
        private readonly PolicyOptimizer $optimizer,
        private readonly PolicySplitter $splitter,
        private readonly LaminasPluginRegistrar $pluginRegistrar,
        private readonly OversizedHeaderNotice $oversizedHeaderNotice,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Rewrite the response's CSP headers.
     *
     * @param HttpResponse $response
     * @return void
     */
    public function process(HttpResponse $response): void
    {
        $optimize = $this->optimizationConfig->isValueOptimizationEnabled();
        $splitRequested = $this->splittingConfig->isHeaderSplittingEnabled();
        if (!$optimize && !$splitRequested) {
            return;
        }
        $canSplit = $splitRequested && $this->pluginRegistrar->registerPlugins($response);
        if ($splitRequested && !$canSplit) {
            $this->logger->warning('CSP headers not split: the response cannot carry several lines of a header.');
        }

        foreach (self::HEADERS as $headerName) {
            $values = $this->lines($response, $headerName);
            if (count($values) !== 1) {
                continue;
            }

            $policy = $this->optimizer->optimize($this->parser->parse($values[0]));
            $parts = $canSplit ? $this->splitter->split($policy, $this->splittingConfig->getMaxHeaderSize()) : [$policy];
            $this->replace($response, $headerName, $parts);
            if (count($parts) > 1 && !$this->sentAsSeparateLines($response, $headerName, count($parts))) {
                $this->logger->warning(sprintf(
                    '%s not split: a part holds a directive the response cannot send on its own line.',
                    $headerName
                ));
                $parts = [$policy];
                $this->replace($response, $headerName, $parts);
            }

            $maxBytes = $this->splittingConfig->getMaxHeaderSize();
            if ($splitRequested && count($parts) === 1 && $policy->byteLength() > $maxBytes) {
                $this->oversizedHeaderNotice->record($headerName, $policy->byteLength(), $maxBytes);
            }
        }
    }

    /**
     * Values of every line of the header.
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @return list<string>
     */
    private function lines(HttpResponse $response, string $headerName): array
    {
        if (!$response instanceof AbstractMessage) {
            $header = $response->getHeader($headerName);

            return $header instanceof HeaderInterface ? [$header->getFieldValue()] : [];
        }

        $values = [];
        foreach ($response->getHeaders() as $header) {
            if ($header instanceof HeaderInterface && strcasecmp($header->getFieldName(), $headerName) === 0) {
                $values[] = $header->getFieldValue();
            }
        }

        return $values;
    }

    /**
     * Whether the response sends the header on the expected number of separate lines.
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @param int $expected
     * @return bool
     */
    private function sentAsSeparateLines(HttpResponse $response, string $headerName, int $expected): bool
    {
        if (!$response instanceof AbstractMessage) {
            return false;
        }

        $lines = 0;
        foreach ($response->getHeaders() as $header) {
            if ($header instanceof HeaderInterface && strcasecmp($header->getFieldName(), $headerName) === 0) {
                if (!$header instanceof MultipleHeaderInterface) {
                    return false;
                }
                $lines++;
            }
        }

        return $lines === $expected;
    }

    /**
     * Replace the header with one line per part.
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @param list<PolicyInterface> $parts
     * @return void
     */
    private function replace(HttpResponse $response, string $headerName, array $parts): void
    {
        $response->clearHeader($headerName);
        foreach ($parts as $part) {
            $response->setHeader($headerName, $part->toHeaderValue(), false);
        }
    }
}
