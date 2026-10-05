<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Config;

/**
 * Settings of the sources this module adds to the policy.
 *
 * @api
 */
interface RulesConfigInterface
{
    /**
     * Whether whitelist entries from the admin are added to the policy.
     *
     * @param int|null $storeId Current store when null
     * @return bool
     */
    public function isRulesEnabled(?int $storeId = null): bool;

    /**
     * Whether the hosts of every store are added to the storefront policy.
     *
     * @return bool
     */
    public function isAddAllStorefrontUrls(): bool;
}
