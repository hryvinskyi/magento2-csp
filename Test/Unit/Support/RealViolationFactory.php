<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Support;

use Hryvinskyi\Csp\Api\Data\ViolationInterface;
use Hryvinskyi\Csp\Api\Data\ViolationInterfaceFactory;
use Hryvinskyi\Csp\Model\Report\Violation;

/**
 * Generated-style violation factory that builds real violations from the constructor arguments it receives.
 */
class RealViolationFactory extends ViolationInterfaceFactory
{
    /**
     * No object manager is needed.
     */
    public function __construct()
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return ViolationInterface
     */
    public function create(array $data = [])
    {
        $text = static fn (string $key): string => is_string($data[$key] ?? null) ? $data[$key] : '';
        $lineNumber = $data['lineNumber'] ?? null;
        $statusCode = $data['statusCode'] ?? 0;

        return new Violation(
            $text('documentUri'),
            $text('blockedUri'),
            $text('effectiveDirective'),
            $text('violatedDirective'),
            $text('originalPolicy'),
            $text('disposition'),
            $text('referrer'),
            $text('scriptSample'),
            is_int($statusCode) ? $statusCode : 0,
            $text('sourceFile'),
            is_int($lineNumber) ? $lineNumber : null
        );
    }
}
