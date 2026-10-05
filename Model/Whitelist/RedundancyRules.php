<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Model\Config\Source\RedundancyStatusOptions;
use Hryvinskyi\Csp\Model\Policy\HostSourceCoverage;

/**
 * Decides whether a host entry adds anything to the enabled host entries of the same directive.
 *
 * An entry is a duplicate when an enabled entry with the same value applies wherever it applies, and redundant when
 * an enabled wildcard entry covers every URL it allows wherever it applies. Of two entries with the same value and
 * scope, the one with the higher id is the duplicate.
 */
class RedundancyRules
{
    /**
     * @param HostSourceCoverage $hostSourceCoverage
     */
    public function __construct(private readonly HostSourceCoverage $hostSourceCoverage)
    {
    }

    /**
     * Redundancy status of a host entry among the enabled host entries of its directive.
     *
     * @param array{rule_id: int, value: string, stores: list<int>, area: string} $entry
     * @param list<array{rule_id: int, value: string, stores: list<int>, area: string}> $enabledEntries
     * @return int
     */
    public function status(array $entry, array $enabledEntries): int
    {
        $value = strtolower($entry['value']);
        $redundant = false;
        foreach ($enabledEntries as $other) {
            if ($other['rule_id'] === $entry['rule_id'] || !$this->scopeCovers($other, $entry)) {
                continue;
            }
            $otherValue = strtolower($other['value']);
            if ($otherValue === $value
                && ($other['rule_id'] < $entry['rule_id'] || !$this->scopeCovers($entry, $other))
            ) {
                return RedundancyStatusOptions::DUPLICATE;
            }
            $redundant = $redundant || $this->hostSourceCoverage->covers($otherValue, $value);
        }

        return $redundant ? RedundancyStatusOptions::REDUNDANT : RedundancyStatusOptions::UNIQUE;
    }

    /**
     * Whether the covering entry applies in every store and area the covered entry applies in.
     *
     * @param array{rule_id: int, value: string, stores: list<int>, area: string} $cover
     * @param array{rule_id: int, value: string, stores: list<int>, area: string} $covered
     * @return bool
     */
    private function scopeCovers(array $cover, array $covered): bool
    {
        $areaCovered = $cover['area'] === Area::ALL->value || $cover['area'] === $covered['area'];
        $coverAllStores = $cover['stores'] === [] || in_array(StoreIdList::ALL_STORES, $cover['stores'], true);
        $coveredAllStores = $covered['stores'] === [] || in_array(StoreIdList::ALL_STORES, $covered['stores'], true);
        $storesCovered = $coverAllStores
            || (!$coveredAllStores && array_diff($covered['stores'], $cover['stores']) === []);

        return $areaCovered && $storesCovered;
    }
}
