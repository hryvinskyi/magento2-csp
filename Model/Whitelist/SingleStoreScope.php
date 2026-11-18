<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Store scope of an entry saved from the admin form while single-store mode is on.
 *
 * In single-store mode the form hides the store view picker and submits no store ids, so a new entry that reaches the
 * save without a scope is given every store. A saved entry keeps the stores it has: one that lost them is left for the
 * validator to refuse rather than widened.
 */
class SingleStoreScope
{
    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(private readonly StoreManagerInterface $storeManager)
    {
    }

    /**
     * Give a new entry with no stores every store when single-store mode is on; leave any other entry as it is.
     *
     * @param WhitelistInterface $entry
     * @return void
     */
    public function applyTo(WhitelistInterface $entry): void
    {
        if ($entry->getRuleId() === null
            && $entry->getStoreIds() === []
            && $this->storeManager->isSingleStoreMode()
        ) {
            $entry->setStoreIds([StoreIdList::ALL_STORES]);
        }
    }
}
