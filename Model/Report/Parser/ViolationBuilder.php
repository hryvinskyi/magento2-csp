<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\Parser;

use Hryvinskyi\Csp\Api\Data\ViolationInterface;
use Hryvinskyi\Csp\Api\Data\ViolationInterfaceFactory;

/**
 * Turns the fields of one reported violation into a violation, whichever format named them.
 */
class ViolationBuilder
{
    /**
     * @param ViolationInterfaceFactory $violationFactory
     */
    public function __construct(private readonly ViolationInterfaceFactory $violationFactory)
    {
    }

    /**
     * Violation from report fields mapped to violation fields, or null without a blocked resource or a directive.
     *
     * @param array<mixed> $fields
     * @param array<string, string> $names Violation field => key in the report
     * @return ViolationInterface|null
     */
    public function build(array $fields, array $names): ?ViolationInterface
    {
        $text = static function (string $field) use ($fields, $names): string {
            $value = $fields[$names[$field] ?? ''] ?? null;

            return is_scalar($value) ? trim((string)$value) : '';
        };
        $number = static function (string $field) use ($fields, $names): ?int {
            $value = $fields[$names[$field] ?? ''] ?? null;

            return is_numeric($value) ? (int)$value : null;
        };

        $violated = $text('violatedDirective');
        $effective = $text('effectiveDirective');
        if ($effective === '' && $violated !== '') {
            $effective = (string)strtok($violated, ' ');
        }
        $blocked = $text('blockedUri');
        if ($blocked === '' || $effective === '') {
            return null;
        }

        return $this->violationFactory->create([
            'documentUri' => $text('documentUri'),
            'blockedUri' => $blocked,
            'effectiveDirective' => strtolower($effective),
            'violatedDirective' => $violated,
            'originalPolicy' => $text('originalPolicy'),
            'disposition' => $text('disposition'),
            'referrer' => $text('referrer'),
            'scriptSample' => $text('scriptSample'),
            'statusCode' => $number('statusCode') ?? 0,
            'sourceFile' => $text('sourceFile'),
            'lineNumber' => $number('lineNumber'),
        ]);
    }
}
