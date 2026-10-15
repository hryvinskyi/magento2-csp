<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Store scope of a whitelist entry as text a person reads and a file carries: the grid export writes it, the import
 * reads it back.
 *
 * Written as the comma-separated codes of the store views, which stay the same from one installation to another, or
 * "All Store Views" for store 0; nothing for an entry without stores. Read from store view codes, ids or names,
 * "All Store Views" in English or in the admin's language, or 0, compared without regard to case.
 */
class StoreScopeText
{
    private const ALL_STORE_VIEWS = 'All Store Views';

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(private readonly StoreManagerInterface $storeManager)
    {
    }

    /**
     * Text of a store scope; the id of a store that no longer exists stands for itself.
     *
     * @param list<int> $storeIds
     * @return string
     */
    public function format(array $storeIds): string
    {
        if (in_array(StoreIdList::ALL_STORES, $storeIds, true)) {
            return (string)__('All Store Views');
        }
        $stores = $this->stores();

        return implode(',', array_map(
            static fn (int $storeId): string => isset($stores[$storeId]) ? (string)$stores[$storeId]->getCode() : (string)$storeId,
            $storeIds
        ));
    }

    /**
     * Store ids the text names, or null when one of its comma-separated parts names no store.
     *
     * @param string $text
     * @return list<int>|null
     */
    public function parse(string $text): ?array
    {
        $storeIds = [];
        foreach (explode(',', $text) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $storeId = $this->storeId($part);
            if ($storeId === null) {
                return null;
            }
            $storeIds[] = $storeId;
        }

        return array_values(array_unique($storeIds));
    }

    /**
     * Id of the store one part of the text names, or null when it names none.
     *
     * A number is taken as an id, whether or not the store exists; saving the entry checks that.
     *
     * @param string $part
     * @return int|null
     */
    private function storeId(string $part): ?int
    {
        if (ctype_digit($part)) {
            return (int)$part;
        }
        $wanted = mb_strtolower($part);
        $allStoreViews = [mb_strtolower(self::ALL_STORE_VIEWS), mb_strtolower((string)__('All Store Views'))];
        if (in_array($wanted, $allStoreViews, true)) {
            return StoreIdList::ALL_STORES;
        }
        $byName = [];
        foreach ($this->stores() as $storeId => $store) {
            if (mb_strtolower((string)$store->getCode()) === $wanted) {
                return $storeId;
            }
            if (mb_strtolower((string)$store->getName()) === $wanted) {
                $byName[] = $storeId;
            }
        }

        return count($byName) === 1 ? $byName[0] : null;
    }

    /**
     * Store views by id, store 0 left out: it is named only as "All Store Views".
     *
     * @return array<int, StoreInterface>
     */
    private function stores(): array
    {
        $stores = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int)$store->getId();
            if ($storeId !== StoreIdList::ALL_STORES) {
                $stores[$storeId] = $store;
            }
        }

        return $stores;
    }
}
