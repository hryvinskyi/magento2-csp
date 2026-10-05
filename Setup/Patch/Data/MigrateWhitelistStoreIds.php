<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Setup\Patch\Data;

use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Copies the comma-separated `store_ids` of every whitelist entry into the store link table.
 *
 * Ids of stores that no longer exist are dropped; an entry left without stores, or one that listed store 0, applies
 * to every store.
 */
class MigrateWhitelistStoreIds implements DataPatchInterface
{
    private const BATCH_SIZE = 500;

    /**
     * @param ModuleDataSetupInterface $setup
     * @param StoreIdList $storeIdList
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $setup,
        private readonly StoreIdList $storeIdList
    ) {
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        $connection = $this->setup->getConnection();
        $existingStores = array_map(
            static fn (mixed $id): int => is_numeric($id) ? (int)$id : -1,
            $connection->fetchCol($connection->select()->from($this->setup->getTable('store'), ['store_id']))
        );
        $links = [];
        $entries = $connection->fetchPairs(
            $connection->select()->from($this->setup->getTable('hryvinskyi_csp_whitelist'), ['rule_id', 'store_ids'])
        );
        foreach ($entries as $ruleId => $storeIds) {
            $ids = array_values(array_intersect(
                $this->storeIdList->parse(is_scalar($storeIds) ? (string)$storeIds : ''),
                $existingStores
            ));
            foreach ($this->storeIdList->normalize($ids) as $storeId) {
                $links[] = ['rule_id' => (int)$ruleId, 'store_id' => $storeId];
            }
        }

        $table = $this->setup->getTable('hryvinskyi_csp_whitelist_store');
        foreach (array_chunk($links, self::BATCH_SIZE) as $batch) {
            $connection->insertOnDuplicate($table, $batch, ['store_id']);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
