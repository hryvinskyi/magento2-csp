<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\Parser;

use Hryvinskyi\Csp\Api\ViolationReportParserInterface;

/**
 * Reads the `report-uri` format: one `{"csp-report": {...}}` object per request.
 */
class LegacyReportParser implements ViolationReportParserInterface
{
    private const MEDIA_TYPES = ['application/csp-report', 'application/json'];
    private const FIELDS = [
        'documentUri' => 'document-uri',
        'blockedUri' => 'blocked-uri',
        'effectiveDirective' => 'effective-directive',
        'violatedDirective' => 'violated-directive',
        'originalPolicy' => 'original-policy',
        'disposition' => 'disposition',
        'referrer' => 'referrer',
        'scriptSample' => 'script-sample',
        'statusCode' => 'status-code',
        'sourceFile' => 'source-file',
        'lineNumber' => 'line-number',
    ];

    /**
     * @param ViolationBuilder $violationBuilder
     */
    public function __construct(private readonly ViolationBuilder $violationBuilder)
    {
    }

    /**
     * @inheritDoc
     */
    public function supports(string $mediaType): bool
    {
        return in_array($mediaType, self::MEDIA_TYPES, true);
    }

    /**
     * @inheritDoc
     */
    public function parse(string $body): array
    {
        try {
            $data = json_decode($body, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('The report is not JSON.', 0, $exception);
        }
        $report = is_array($data) ? ($data['csp-report'] ?? null) : null;
        if (!is_array($report)) {
            throw new \InvalidArgumentException('The report has no "csp-report" object.');
        }
        $violation = $this->violationBuilder->build($report, self::FIELDS);

        return $violation === null ? [] : [$violation];
    }
}
