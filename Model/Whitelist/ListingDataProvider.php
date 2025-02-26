<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

class ListingDataProvider extends DataProvider
{
    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getData(): array
    {
        $data = parent::getData();

        foreach ($data['items'] as &$item) {
            if (isset($item[WhitelistInterface::STORE_IDS]) && is_string($item[WhitelistInterface::STORE_IDS])) {
                $item[WhitelistInterface::STORE_IDS] = explode(',', $item[WhitelistInterface::STORE_IDS]);
            }
        }

        return $data;
    }
}