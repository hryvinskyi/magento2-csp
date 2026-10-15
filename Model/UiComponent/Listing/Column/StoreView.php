<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\UiComponent\Listing\Column;

use Magento\Store\Ui\Component\Listing\Column\Store;

/**
 * Store view of a grid row whose store id is a single value, where store 0 means every store view.
 *
 * The core column reads the id as "empty" when the database returns it as the string "0" and shows nothing.
 */
class StoreView extends Store
{
    /**
     * "All Store Views" for store 0, otherwise the store view tree of the core column.
     *
     * @param array<mixed> $item
     * @return \Magento\Framework\Phrase|string
     */
    protected function prepareItem(array $item)
    {
        $storeId = $item[$this->storeKey] ?? null;
        if (is_numeric($storeId) && (int)$storeId === 0) {
            return __('All Store Views');
        }

        return parent::prepareItem($item);
    }
}
