<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

/**
 * Calculates redundancy status for CSP whitelist entries
 */
interface RedundancyCalculatorInterface
{
    /**
     * Calculate redundancy status for items based on all whitelist entries
     *
     * @param array<int, array<string, mixed>> $items Items to calculate redundancy for
     * @return array<int, array<string, mixed>> Items with 'redundancy_status' field added
     */
    public function calculateForItems(array $items): array;
}
