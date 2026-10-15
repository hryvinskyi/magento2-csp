<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Config;

/**
 * Settings of CSP header splitting.
 *
 * @api
 */
interface HeaderSplittingConfigInterface
{
    /**
     * Whether a header larger than the maximum size is split into several headers.
     *
     * @param int|null $storeId Current store when null
     * @return bool
     */
    public function isHeaderSplittingEnabled(?int $storeId = null): bool;

    /**
     * Maximum size of one header value in bytes.
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxHeaderSize(?int $storeId = null): int;
}
