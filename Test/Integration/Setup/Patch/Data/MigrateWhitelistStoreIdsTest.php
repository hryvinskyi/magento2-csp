<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration\Setup\Patch\Data;

use Hryvinskyi\Csp\Setup\Patch\Data\MigrateWhitelistStoreIds;
use Hryvinskyi\Csp\Test\Integration\ResolvesServices;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class MigrateWhitelistStoreIdsTest extends TestCase
{
    use ResolvesServices;

    public function testCopiesExistingStoresAndNeverWidensAnEntryOfDeletedStores(): void
    {
        $entries = [
            'all-and-one' => '0,1',
            'one-and-deleted' => '1, 999',
            'only-deleted' => '999',
            'empty' => '',
            'no-ids' => 'abc',
        ];
        $ids = [];
        foreach ($entries as $identifier => $storeIds) {
            $this->connection()->insert($this->table('hryvinskyi_csp_whitelist'), [
                'identifier' => $identifier,
                'policy' => 'img-src',
                'value_type' => 'host',
                'value' => $identifier . '.example.com',
                'store_ids' => $storeIds,
                'status' => 1,
            ]);
            $ids[$identifier] = $this->lastInsertId();
        }

        $patch = $this->newInstance(MigrateWhitelistStoreIds::class);
        $patch->apply();
        $patch->apply();

        $this->assertSame(
            ['all-and-one' => ['0'], 'one-and-deleted' => ['1'], 'only-deleted' => [], 'empty' => ['0'], 'no-ids' => ['0']],
            array_map(fn (int $ruleId): array => $this->stores($ruleId), $ids)
        );
        $this->assertSame(
            ['all-and-one' => '1', 'one-and-deleted' => '1', 'only-deleted' => '0', 'empty' => '1', 'no-ids' => '1'],
            array_map(fn (int $ruleId): ?string => $this->entryStatus($ruleId), $ids)
        );
    }

    /**
     * @param int $ruleId
     * @return string|null
     */
    private function entryStatus(int $ruleId): ?string
    {
        $select = $this->connection()->select()
            ->from($this->table('hryvinskyi_csp_whitelist'), ['status'])
            ->where('rule_id = ?', $ruleId);

        return $this->rows($select)[0]['status'] ?? null;
    }

    /**
     * @param int $ruleId
     * @return list<string|null>
     */
    private function stores(int $ruleId): array
    {
        $select = $this->connection()->select()
            ->from($this->table('hryvinskyi_csp_whitelist_store'), ['store_id'])
            ->where('rule_id = ?', $ruleId)
            ->order('store_id');

        return array_column($this->rows($select), 'store_id');
    }
}
