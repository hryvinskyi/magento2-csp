<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Config\Source;

use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use Magento\Store\Ui\Component\Listing\Column\Store\Options;

/**
 * Store views grouped by website and group, preceded by "All Store Views" (store 0).
 */
class StoreViews extends Options
{
    /**
     * Store view options, "All Store Views" first.
     *
     * @return array<mixed>
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $this->currentOptions['All Store Views'] = [
            'label' => __('All Store Views'),
            'value' => (string)StoreIdList::ALL_STORES,
        ];
        $this->generateCurrentOptions();

        return $this->options = array_values($this->currentOptions);
    }
}
