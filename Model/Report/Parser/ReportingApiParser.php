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
 * Reads the Reporting API format: a JSON array of reports, possibly from several pages, of which only
 * `csp-violation` reports are read.
 */
class ReportingApiParser implements ViolationReportParserInterface
{
    private const MEDIA_TYPE = 'application/reports+json';
    private const REPORT_TYPE = 'csp-violation';
    private const FIELDS = [
        'documentUri' => 'documentURL',
        'blockedUri' => 'blockedURL',
        'effectiveDirective' => 'effectiveDirective',
        'originalPolicy' => 'originalPolicy',
        'disposition' => 'disposition',
        'referrer' => 'referrer',
        'scriptSample' => 'sample',
        'statusCode' => 'statusCode',
        'sourceFile' => 'sourceFile',
        'lineNumber' => 'lineNumber',
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
        return $mediaType === self::MEDIA_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function parse(string $body): array
    {
        try {
            $reports = json_decode($body, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('The report is not JSON.', 0, $exception);
        }
        if (!is_array($reports) || !array_is_list($reports)) {
            throw new \InvalidArgumentException('The report is not a list of reports.');
        }

        $violations = [];
        foreach ($reports as $report) {
            if (!is_array($report) || ($report['type'] ?? null) !== self::REPORT_TYPE || !is_array($report['body'] ?? null)) {
                continue;
            }
            $violation = $this->violationBuilder->build($report['body'], self::FIELDS);
            if ($violation !== null) {
                $violations[] = $violation;
            }
        }

        return $violations;
    }
}
