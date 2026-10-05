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
use Hryvinskyi\Csp\Api\CspHeaderProcessorInterface;
use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use Hryvinskyi\Csp\Api\OversizedHeaderNoticeInterface;
use Hryvinskyi\Csp\Api\PolicySplitterInterface;
use Hryvinskyi\Csp\Api\ResponseHeaderLinesInterface;
use Hryvinskyi\Csp\Model\Policy\PolicyOptimizer;
use Hryvinskyi\Csp\Model\Policy\PolicyParser;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use Psr\Log\LoggerInterface;

/**
 * Optimises and, when it is too large, splits each CSP header configured in `headerNames`.
 *
 * A header the response already carries on several lines is left alone. Split parts are kept only if the response
 * sends every one of them as its own line; otherwise the unsplit header is written back.
 */
class CspHeaderProcessor implements CspHeaderProcessorInterface
{
    /**
     * @param OptimizationConfigInterface $optimizationConfig
     * @param HeaderSplittingConfigInterface $splittingConfig
     * @param PolicyParser $parser
     * @param PolicyOptimizer $optimizer
     * @param PolicySplitterInterface $splitter
     * @param ResponseHeaderLinesInterface $headerLines
     * @param OversizedHeaderNoticeInterface $oversizedHeaderNotice
     * @param LoggerInterface $logger
     * @param array<string, string> $headerNames
     */
    public function __construct(
        private readonly OptimizationConfigInterface $optimizationConfig,
        private readonly HeaderSplittingConfigInterface $splittingConfig,
        private readonly PolicyParser $parser,
        private readonly PolicyOptimizer $optimizer,
        private readonly PolicySplitterInterface $splitter,
        private readonly ResponseHeaderLinesInterface $headerLines,
        private readonly OversizedHeaderNoticeInterface $oversizedHeaderNotice,
        private readonly LoggerInterface $logger,
        private readonly array $headerNames = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function processHeaders(HttpResponse $response): void
    {
        $optimize = $this->optimizationConfig->isValueOptimizationEnabled();
        $split = $this->splittingConfig->isHeaderSplittingEnabled();
        if (!$optimize && !$split) {
            return;
        }

        foreach ($this->headerNames as $headerName) {
            $values = $this->headerLines->values($response, $headerName);
            if (count($values) === 1) {
                $this->process($response, $headerName, $values[0], $split);
            }
        }
    }

    /**
     * Rewrite one header line.
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @param string $value
     * @param bool $split
     * @return void
     */
    private function process(HttpResponse $response, string $headerName, string $value, bool $split): void
    {
        $policy = $this->optimizer->optimize($this->parser->parse($value));
        $maxBytes = $this->splittingConfig->getMaxHeaderSize();
        $parts = $split ? $this->parts($response, $headerName, $policy, $maxBytes) : [$policy];

        if (!$this->headerLines->replace($response, $headerName, $this->values($parts))) {
            $this->logger->warning(sprintf(
                '%s not split: the response does not send each part on its own line.',
                $headerName
            ));
            $parts = [$policy];
            $this->headerLines->replace($response, $headerName, $this->values($parts));
        }

        if ($split && count($parts) === 1 && $policy->byteLength() > $maxBytes) {
            $this->oversizedHeaderNotice->record($headerName, $policy->byteLength(), $maxBytes);
        }
    }

    /**
     * Parts to send; the policy alone when it fits, cannot be split, or the response cannot carry several lines.
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @param PolicyInterface $policy
     * @param int $maxBytes
     * @return list<PolicyInterface>
     */
    private function parts(HttpResponse $response, string $headerName, PolicyInterface $policy, int $maxBytes): array
    {
        if ($policy->byteLength() <= $maxBytes) {
            return [$policy];
        }
        if (!$this->headerLines->allowSeveralLines($response, $headerName)) {
            $this->logger->warning(sprintf('%s not split: the response cannot carry several lines of it.', $headerName));

            return [$policy];
        }

        return $this->splitter->split($policy, $maxBytes);
    }

    /**
     * Header values of the parts.
     *
     * @param list<PolicyInterface> $parts
     * @return list<string>
     */
    private function values(array $parts): array
    {
        return array_map(static fn (PolicyInterface $part): string => $part->toHeaderValue(), $parts);
    }
}
