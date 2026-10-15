<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Setup\Patch\Data;

use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use Hryvinskyi\Csp\Setup\Migration\LegacyWhitelistSources;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Brings whitelist entries saved before 2.0.0 under the whitelist rules without dropping a source 1.x allowed.
 *
 * 1.x put an entry's value into the header as it was, so `*.a.com *.b.com` allowed two sources. Such an entry becomes
 * one entry per allowed source, with the same directive, type, algorithm, area, status and stores. A source that
 * another entry already holds (directive, type, algorithm, value and area equal, as the table compares them) is
 * merged into that entry: its stores grow by the stores of the split entry, and a disabled entry takes over the
 * source with the split entry's stores when the split entry was enabled. The split entry keeps the first source when
 * every word was allowed; otherwise it is disabled and keeps its value, so the words that were refused stay visible.
 * An entry whose directive or value the rules refuse outright is disabled. Everything done is logged.
 */
class SplitLegacyWhitelistValues implements DataPatchInterface
{
    private const ENTRY_TABLE = 'hryvinskyi_csp_whitelist';
    private const STORE_TABLE = 'hryvinskyi_csp_whitelist_store';
    private const ENABLED = 1;
    private const DISABLED = 0;

    /**
     * @param ModuleDataSetupInterface $setup
     * @param LegacyWhitelistSources $legacySources
     * @param StoreIdList $storeIdList
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $setup,
        private readonly LegacyWhitelistSources $legacySources,
        private readonly StoreIdList $storeIdList,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        $connection = $this->setup->getConnection();
        $select = $connection->select()
            ->from(
                $this->setup->getTable(self::ENTRY_TABLE),
                ['rule_id', 'identifier', 'policy', 'value_type', 'value_algorithm', 'value', 'area', 'status', 'script_content']
            )
            ->order('rule_id');
        foreach ($this->entries($connection->fetchAll($select)) as $entry) {
            $sources = $this->legacySources->sources(
                $entry['policy'],
                $entry['value_type'],
                $entry['value_algorithm'],
                $entry['value']
            );
            if ($sources['split']) {
                $this->split($connection, $entry, $sources['allowed'], $sources['refused']);
                continue;
            }
            if ($sources['refused'] !== [] && $entry['status'] === self::ENABLED) {
                $this->setStatus($connection, $entry['rule_id'], self::DISABLED);
                $this->logger->warning(
                    'CSP whitelist entry disabled: its directive or value is not allowed in a whitelist entry.',
                    ['rule_id' => $entry['rule_id'], 'policy' => $entry['policy'], 'value' => $entry['value']]
                );
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [MigrateWhitelistStoreIds::class];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Give each allowed source of the entry an entry of its own.
     *
     * @param AdapterInterface $connection
     * @param array{rule_id: int, identifier: string, policy: string, value_type: string, value_algorithm: string, value: string, area: string, status: int, script_content: string|null} $entry
     * @param list<string> $allowed
     * @param list<string> $refused
     * @return void
     */
    private function split(AdapterInterface $connection, array $entry, array $allowed, array $refused): void
    {
        $stores = $this->storeIds($connection, $entry['rule_id']);
        $kept = false;
        $placed = [];
        foreach ($allowed as $source) {
            $existing = $this->findByKey($connection, $entry, $source);
            if ($existing !== null) {
                $this->mergeInto($connection, $existing, $entry['status'], $stores);
                $placed[$source] = $existing['rule_id'];
                continue;
            }
            if ($refused === [] && !$kept) {
                $connection->update(
                    $this->setup->getTable(self::ENTRY_TABLE),
                    ['value' => $source],
                    ['rule_id = ?' => $entry['rule_id']]
                );
                $kept = true;
                $placed[$source] = $entry['rule_id'];
                continue;
            }
            $placed[$source] = $this->copy($connection, $entry, $source, $stores);
        }
        if (!$kept) {
            $this->setStatus($connection, $entry['rule_id'], self::DISABLED);
        }
        $this->logger->warning(
            'CSP whitelist entry with several sources in its value split into one entry per source.',
            [
                'rule_id' => $entry['rule_id'],
                'value' => $entry['value'],
                'entries_by_source' => $placed,
                'refused' => $refused,
                'disabled' => !$kept,
            ]
        );
    }

    /**
     * Let an existing entry allow a source where the split entry allowed it.
     *
     * @param AdapterInterface $connection
     * @param array{rule_id: int, status: int} $existing
     * @param int $status Status of the split entry
     * @param list<int> $stores Stores of the split entry
     * @return void
     */
    private function mergeInto(AdapterInterface $connection, array $existing, int $status, array $stores): void
    {
        if ($status !== self::ENABLED && $existing['status'] === self::ENABLED) {
            return;
        }
        if ($status === self::ENABLED && $existing['status'] !== self::ENABLED) {
            $this->setStatus($connection, $existing['rule_id'], self::ENABLED);
            $this->replaceStores($connection, $existing['rule_id'], $stores);

            return;
        }
        $this->replaceStores(
            $connection,
            $existing['rule_id'],
            $this->storeIdList->normalize([...$this->storeIds($connection, $existing['rule_id']), ...$stores])
        );
    }

    /**
     * Insert a copy of the entry that allows one source, with the entry's stores; its id.
     *
     * @param AdapterInterface $connection
     * @param array{rule_id: int, identifier: string, policy: string, value_type: string, value_algorithm: string, value: string, area: string, status: int, script_content: string|null} $entry
     * @param string $source
     * @param list<int> $stores
     * @return int
     */
    private function copy(AdapterInterface $connection, array $entry, string $source, array $stores): int
    {
        $table = $this->setup->getTable(self::ENTRY_TABLE);
        $connection->insert($table, [
            'identifier' => $entry['identifier'],
            'policy' => $entry['policy'],
            'value_type' => $entry['value_type'],
            'value_algorithm' => $entry['value_algorithm'],
            'value' => $source,
            'area' => $entry['area'],
            'status' => $entry['status'],
            'script_content' => $entry['script_content'],
        ]);
        $insertedId = $connection->fetchOne('SELECT LAST_INSERT_ID()');
        $ruleId = $this->toInt($insertedId);
        $this->replaceStores($connection, $ruleId, $stores);

        return $ruleId;
    }

    /**
     * The entry with the entry's directive, type, algorithm and area that allows the source, if one exists.
     *
     * The split entry itself is found once it holds a source the table cannot tell from this one.
     *
     * @param AdapterInterface $connection
     * @param array{rule_id: int, identifier: string, policy: string, value_type: string, value_algorithm: string, value: string, area: string, status: int, script_content: string|null} $entry
     * @param string $source
     * @return array{rule_id: int, status: int}|null
     */
    private function findByKey(AdapterInterface $connection, array $entry, string $source): ?array
    {
        $row = $connection->fetchRow(
            $connection->select()
                ->from($this->setup->getTable(self::ENTRY_TABLE), ['rule_id', 'status'])
                ->where('policy = ?', $entry['policy'])
                ->where('value_type = ?', $entry['value_type'])
                ->where('value_algorithm = ?', $entry['value_algorithm'])
                ->where('value = ?', $source)
                ->where('area = ?', $entry['area'])
        );
        if (!is_array($row)) {
            return null;
        }

        return ['rule_id' => $this->toInt($row['rule_id'] ?? null), 'status' => $this->toInt($row['status'] ?? null)];
    }

    /**
     * Store ids the entry is linked to.
     *
     * @param AdapterInterface $connection
     * @param int $ruleId
     * @return list<int>
     */
    private function storeIds(AdapterInterface $connection, int $ruleId): array
    {
        return array_values(array_map(
            fn (mixed $storeId): int => $this->toInt($storeId),
            $connection->fetchCol(
                $connection->select()
                    ->from($this->setup->getTable(self::STORE_TABLE), ['store_id'])
                    ->where('rule_id = ?', $ruleId)
            )
        ));
    }

    /**
     * Link the entry to exactly the given stores.
     *
     * @param AdapterInterface $connection
     * @param int $ruleId
     * @param list<int> $stores
     * @return void
     */
    private function replaceStores(AdapterInterface $connection, int $ruleId, array $stores): void
    {
        $table = $this->setup->getTable(self::STORE_TABLE);
        $connection->delete($table, ['rule_id = ?' => $ruleId]);
        if ($stores === []) {
            return;
        }
        $connection->insertMultiple(
            $table,
            array_map(static fn (int $storeId): array => ['rule_id' => $ruleId, 'store_id' => $storeId], $stores)
        );
    }

    /**
     * @param AdapterInterface $connection
     * @param int $ruleId
     * @param int $status
     * @return void
     */
    private function setStatus(AdapterInterface $connection, int $ruleId, int $status): void
    {
        $connection->update($this->setup->getTable(self::ENTRY_TABLE), ['status' => $status], ['rule_id = ?' => $ruleId]);
    }

    /**
     * Typed entry rows.
     *
     * @param array<mixed> $rows
     * @return list<array{rule_id: int, identifier: string, policy: string, value_type: string, value_algorithm: string, value: string, area: string, status: int, script_content: string|null}>
     */
    private function entries(array $rows): array
    {
        $entries = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $scriptContent = $row['script_content'] ?? null;
            $entries[] = [
                'rule_id' => $this->toInt($row['rule_id'] ?? null),
                'identifier' => $this->toString($row['identifier'] ?? null),
                'policy' => $this->toString($row['policy'] ?? null),
                'value_type' => $this->toString($row['value_type'] ?? null),
                'value_algorithm' => $this->toString($row['value_algorithm'] ?? null),
                'value' => $this->toString($row['value'] ?? null),
                'area' => $this->toString($row['area'] ?? null),
                'status' => $this->toInt($row['status'] ?? null),
                'script_content' => is_scalar($scriptContent) ? (string)$scriptContent : null,
            ];
        }

        return $entries;
    }

    /**
     * @param mixed $value
     * @return int
     */
    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function toString(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }
}
