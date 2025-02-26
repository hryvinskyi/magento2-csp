<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist\Command;

use Hryvinskyi\Csp\Api\Data\WhitelistSearchResultsInterface;

interface GetAllActiveWhitelistByStoreIdInterface
{
    /**
     * Find all active whitelist by store id
     *
     * @param int $storeId
     * @return WhitelistSearchResultsInterface
     */
    public function execute(int $storeId): WhitelistSearchResultsInterface;
}