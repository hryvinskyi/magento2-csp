<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\DomainMatcherInterface;
use Hryvinskyi\Csp\Api\RedundancyCalculatorInterface;
use Hryvinskyi\Csp\Model\Config\Source\RedundancyStatusOptions;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\CollectionFactory;

/**
 * Calculates redundancy status for CSP whitelist entries
 */
class RedundancyCalculator implements RedundancyCalculatorInterface
{
    /**
     * @var array<string, array<int, array<string, mixed>>>|null
     */
    private ?array $allEntriesGroupedByPolicy = null;

    /**
     * @param CollectionFactory $collectionFactory
     * @param DomainMatcherInterface $domainMatcher
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly DomainMatcherInterface $domainMatcher
    ) {
    }

    /**
     * @inheritDoc
     */
    public function calculateForItems(array $items): array
    {
        $this->loadAllEntries();

        foreach ($items as &$item) {
            $item['redundancy_status'] = $this->calculateItemStatus($item);
        }

        return $items;
    }

    /**
     * Calculate redundancy status for a single item
     *
     * @param array<string, mixed> $item
     * @return int
     */
    private function calculateItemStatus(array $item): int
    {
        $valueType = $item['value_type'] ?? '';

        if ($valueType !== 'host') {
            return RedundancyStatusOptions::NOT_APPLICABLE;
        }

        $policy = $item['policy'] ?? '';
        $value = strtolower(trim($item['value'] ?? ''));
        $ruleId = (int)($item['rule_id'] ?? 0);

        if ($value === '' || $policy === '') {
            return RedundancyStatusOptions::NOT_APPLICABLE;
        }

        $policyEntries = $this->allEntriesGroupedByPolicy[$policy] ?? [];

        if ($this->isDuplicate($ruleId, $value, $policyEntries)) {
            return RedundancyStatusOptions::DUPLICATE;
        }

        if ($this->isRedundant($ruleId, $value, $policyEntries)) {
            return RedundancyStatusOptions::REDUNDANT;
        }

        return RedundancyStatusOptions::UNIQUE;
    }

    /**
     * Load all whitelist entries grouped by policy
     *
     * @return void
     */
    private function loadAllEntries(): void
    {
        if ($this->allEntriesGroupedByPolicy !== null) {
            return;
        }

        $this->allEntriesGroupedByPolicy = [];

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('value_type', 'host');

        foreach ($collection as $item) {
            $policy = $item->getPolicy();
            if (!isset($this->allEntriesGroupedByPolicy[$policy])) {
                $this->allEntriesGroupedByPolicy[$policy] = [];
            }
            $this->allEntriesGroupedByPolicy[$policy][] = [
                'rule_id' => (int)$item->getRuleId(),
                'value' => strtolower(trim($item->getValue())),
            ];
        }
    }

    /**
     * Check if an entry is a duplicate (exact same policy and value exists with lower rule_id)
     *
     * @param int $ruleId
     * @param string $value
     * @param array<int, array<string, mixed>> $policyEntries
     * @return bool
     */
    private function isDuplicate(int $ruleId, string $value, array $policyEntries): bool
    {
        foreach ($policyEntries as $entry) {
            if ($entry['rule_id'] < $ruleId && $entry['value'] === $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an entry is redundant (covered by a wildcard with lower rule_id)
     *
     * @param int $ruleId
     * @param string $value
     * @param array<int, array<string, mixed>> $policyEntries
     * @return bool
     */
    private function isRedundant(int $ruleId, string $value, array $policyEntries): bool
    {
        if ($this->domainMatcher->isWildcard($value)) {
            return $this->isWildcardCoveredByBroaderWildcard($ruleId, $value, $policyEntries);
        }

        return $this->isHostCoveredByWildcard($ruleId, $value, $policyEntries);
    }

    /**
     * Check if a host is covered by any wildcard entry
     *
     * @param int $ruleId
     * @param string $host
     * @param array<int, array<string, mixed>> $policyEntries
     * @return bool
     */
    private function isHostCoveredByWildcard(int $ruleId, string $host, array $policyEntries): bool
    {
        foreach ($policyEntries as $entry) {
            if ($entry['rule_id'] >= $ruleId || !$this->domainMatcher->isWildcard($entry['value'])) {
                continue;
            }

            if ($this->domainMatcher->domainMatchesWildcard($host, $entry['value'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a wildcard is covered by a broader wildcard entry
     *
     * @param int $ruleId
     * @param string $wildcard
     * @param array<int, array<string, mixed>> $policyEntries
     * @return bool
     */
    private function isWildcardCoveredByBroaderWildcard(int $ruleId, string $wildcard, array $policyEntries): bool
    {
        foreach ($policyEntries as $entry) {
            if ($entry['rule_id'] >= $ruleId || !$this->domainMatcher->isWildcard($entry['value'])) {
                continue;
            }

            if ($this->domainMatcher->isWildcardCoveredByBroader($wildcard, $entry['value'])) {
                return true;
            }
        }

        return false;
    }
}
