<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

/**
 * Calculates hash validation status for CSP whitelist entries
 */
interface HashValidationCalculatorInterface
{
    /**
     * Calculate hash validation status for items
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>> Items with 'hash_validation' field added
     */
    public function calculateForItems(array $items): array;
}
