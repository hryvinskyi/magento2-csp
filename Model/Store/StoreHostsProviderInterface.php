<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Store;

/**
 * Hosts this installation serves its storefronts from.
 */
interface StoreHostsProviderInterface
{
    /**
     * Hosts, with a port that is not the scheme's default, of the link, static and media base URLs of every store view.
     *
     * @return list<string>
     */
    public function storefrontHosts(): array;

    /**
     * Hosts, with a port that is not the scheme's default, of the link, static and media base URLs of one store view.
     *
     * @param int $storeId
     * @return list<string>
     */
    public function storeHosts(int $storeId): array;
}
