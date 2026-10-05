<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\ValueType;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\RedundancyCalculatorInterface;
use Hryvinskyi\Csp\Model\Config\Source\RedundancyStatusOptions;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\CollectionFactory;

/**
 * Adds the redundancy status of each host entry to whitelist grid rows, comparing it with the enabled host entries
 * of its directive in its stores and area.
 */
class RedundancyCalculator implements RedundancyCalculatorInterface
{
    /**
     * @var array<string, list<array{rule_id: int, value: string, stores: list<int>, area: string}>>|null
     */
    private ?array $enabledHostsByPolicy = null;

    /**
     * @param CollectionFactory $collectionFactory
     * @param RedundancyRules $redundancyRules
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly RedundancyRules $redundancyRules
    ) {
    }

    /**
     * @inheritDoc
     */
    public function calculateForItems(array $items): array
    {
        foreach ($items as $key => $item) {
            $items[$key]['redundancy_status'] = $this->statusOf($item);
        }

        return $items;
    }

    /**
     * Redundancy status of one grid row.
     *
     * @param array<string, mixed> $item
     * @return int
     */
    private function statusOf(array $item): int
    {
        $value = $item[WhitelistInterface::VALUE] ?? null;
        $policy = $item[WhitelistInterface::POLICY] ?? null;
        if (($item[WhitelistInterface::VALUE_TYPE] ?? null) !== ValueType::HOST->value
            || !is_string($value) || $value === ''
            || !is_string($policy) || $policy === ''
        ) {
            return RedundancyStatusOptions::NOT_APPLICABLE;
        }
        $area = $item[WhitelistInterface::AREA] ?? null;
        $ruleId = $item[WhitelistInterface::RULE_ID] ?? null;
        $entry = [
            'rule_id' => is_numeric($ruleId) ? (int)$ruleId : 0,
            'value' => $value,
            'stores' => $this->storeIds($item[WhitelistInterface::STORE_ID] ?? []),
            'area' => is_string($area) && $area !== '' ? $area : Area::ALL->value,
        ];

        return $this->redundancyRules->status($entry, $this->enabledHosts()[$policy] ?? []);
    }

    /**
     * Enabled host entries grouped by directive.
     *
     * @return array<string, list<array{rule_id: int, value: string, stores: list<int>, area: string}>>
     */
    private function enabledHosts(): array
    {
        if ($this->enabledHostsByPolicy !== null) {
            return $this->enabledHostsByPolicy;
        }
        $collection = $this->collectionFactory->create()
            ->addActiveFilter()
            ->addFieldToFilter(WhitelistInterface::VALUE_TYPE, ['eq' => ValueType::HOST->value]);
        $entries = [];
        foreach ($collection->getItems() as $item) {
            $entries[(string)$item->getPolicy()][] = [
                'rule_id' => (int)$item->getRuleId(),
                'value' => (string)$item->getValue(),
                'stores' => array_values($item->getStoreIds()),
                'area' => $item->getArea(),
            ];
        }

        return $this->enabledHostsByPolicy = $entries;
    }

    /**
     * Store ids of a grid row.
     *
     * @param mixed $storeIds
     * @return list<int>
     */
    private function storeIds(mixed $storeIds): array
    {
        if (!is_array($storeIds)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $id): int => (int)$id,
            array_filter($storeIds, static fn (mixed $id): bool => is_numeric($id))
        ));
    }
}
