<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Api\Config\ReportingConfigInterface;
use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Model\Report\Parser\ParserPool;
use Hryvinskyi\Csp\Model\Report\RateLimiter\ReportRateLimiterInterface;

/**
 * Records the violations of one report request.
 *
 * The body must be within the size limit and of a known format; at most 20 violations of one request and the client's
 * per-minute allowance are considered; each is filtered, sanitized and classified before it is recorded. Whatever is
 * not recorded is counted as rejected, never raised.
 */
class ReportIngestion
{
    private const MAX_VIOLATIONS_PER_REQUEST = 20;

    /**
     * @param ReportingConfigInterface $config
     * @param ParserPool $parserPool
     * @param ReportRateLimiterInterface $rateLimiter
     * @param ViolationFilter $filter
     * @param ViolationSanitizer $sanitizer
     * @param ViolationClassifier $classifier
     * @param ViolationRecorderInterface $recorder
     */
    public function __construct(
        private readonly ReportingConfigInterface $config,
        private readonly ParserPool $parserPool,
        private readonly ReportRateLimiterInterface $rateLimiter,
        private readonly ViolationFilter $filter,
        private readonly ViolationSanitizer $sanitizer,
        private readonly ViolationClassifier $classifier,
        private readonly ViolationRecorderInterface $recorder
    ) {
    }

    /**
     * Record the violations in a report body.
     *
     * @param string $body
     * @param string $contentType
     * @param string $clientKey
     * @param Area $area
     * @param int $storeId
     * @return array{accepted: int, rejected: int}
     */
    public function ingest(string $body, string $contentType, string $clientKey, Area $area, int $storeId): array
    {
        $parser = $this->parserPool->forContentType($contentType);
        if (!$this->config->isReportsEnabled() || strlen($body) > $this->config->getMaxReportBodyBytes() || $parser === null) {
            return ['accepted' => 0, 'rejected' => 1];
        }
        try {
            $violations = $parser->parse($body);
        } catch (\InvalidArgumentException) {
            return ['accepted' => 0, 'rejected' => 1];
        }

        $considered = array_slice($violations, 0, self::MAX_VIOLATIONS_PER_REQUEST);
        $allowed = array_slice($considered, 0, $this->rateLimiter->allow($clientKey, $area, count($considered)));
        $accepted = 0;
        foreach ($allowed as $violation) {
            if (!$this->filter->accepts($violation, $area, $storeId)) {
                continue;
            }
            $sanitized = $this->sanitizer->sanitize($violation);
            $group = $this->classifier->classify($sanitized);
            if ($group !== null && $this->recorder->record($sanitized, $group['policy'], $group['value'], $storeId, $area)) {
                $accepted++;
            }
        }

        return ['accepted' => $accepted, 'rejected' => count($violations) - $accepted];
    }
}
