<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface WhitelistSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Whitelist list.
     *
     * @return \Hryvinskyi\Csp\Api\Data\WhitelistInterface[]
     */
    public function getItems(): array;

    /**
     * Set Whitelist list.
     *
     * @param \Hryvinskyi\Csp\Api\Data\WhitelistInterface[] $items
     *
     * @return $this
     */
    public function setItems(?array $items = null): WhitelistSearchResultsInterface;
}
