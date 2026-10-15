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
use Psr\Log\LoggerInterface;

/**
 * Copies the comma-separated `store_ids` of every whitelist entry into the store link table, keeping where 1.x
 * applied the entry.
 *
 * Ids of stores that no longer exist are dropped. A list that holds store 0, or no store id at all, applies to every
 * store (1.x compared the column with store 0 as a number, so text without an id matched every store). A list of
 * deleted stores only applied nowhere in 1.x: such an entry gets no store link and is disabled, so it still applies
 * nowhere and shows as disabled in the grid until a store view is chosen for it.
 */
class MigrateWhitelistStoreIds implements DataPatchInterface
{
    private const BATCH_SIZE = 500;

    /**
     * @param ModuleDataSetupInterface $setup
     * @param StoreIdList $storeIdList
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $setup,
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
        $existingStores = array_map(
            static fn (mixed $id): int => is_numeric($id) ? (int)$id : -1,
            $connection->fetchCol($connection->select()->from($this->setup->getTable('store'), ['store_id']))
        );
        $entryTable = $this->setup->getTable('hryvinskyi_csp_whitelist');
        $links = [];
        $storeless = [];
        $entries = $connection->fetchPairs($connection->select()->from($entryTable, ['rule_id', 'store_ids']));
        foreach ($entries as $ruleId => $storeIds) {
            $listed = $this->storeIdList->parse(is_scalar($storeIds) ? (string)$storeIds : '');
            $ids = $listed === [] ? [StoreIdList::ALL_STORES] : array_values(array_intersect($listed, $existingStores));
            if ($ids === []) {
                $storeless[] = (int)$ruleId;
                continue;
            }
            foreach ($this->storeIdList->normalize($ids) as $storeId) {
                $links[] = ['rule_id' => (int)$ruleId, 'store_id' => $storeId];
            }
        }

        $table = $this->setup->getTable('hryvinskyi_csp_whitelist_store');
        foreach (array_chunk($links, self::BATCH_SIZE) as $batch) {
            $connection->insertOnDuplicate($table, $batch, ['store_id']);
        }
        if ($storeless !== []) {
            $connection->update($entryTable, ['status' => 0], ['rule_id IN (?)' => $storeless]);
            $this->logger->warning(
                'CSP whitelist entries scoped only to deleted store views were disabled and left without stores.',
                ['rule_ids' => $storeless]
            );
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
