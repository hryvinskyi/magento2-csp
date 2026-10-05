<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ScriptHash;

/**
 * Content a store view renders that may hold inline scripts.
 */
interface ScriptSourceInterface
{
    /**
     * HTML the store view renders, one item per entity.
     *
     * @param int $storeId
     * @return list<array{label: string, html: string, warning: string|null}>
     */
    public function contents(int $storeId): array;
}
