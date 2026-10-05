<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

/**
 * Store scope of a whitelist entry as a list of store ids, where store 0 stands for every store.
 */
class StoreIdList
{
    public const ALL_STORES = 0;

    /**
     * Store ids from a comma-separated list; tokens that are not whole numbers are dropped.
     *
     * @param string $value
     * @return list<int>
     */
    public function parse(string $value): array
    {
        $ids = [];
        foreach (explode(',', $value) as $token) {
            $token = trim($token);
            if ($token !== '' && ctype_digit($token)) {
                $ids[] = (int)$token;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Sorted unique store ids; every store when the list is empty or holds store 0.
     *
     * @param list<int> $ids
     * @return list<int>
     */
    public function normalize(array $ids): array
    {
        if ($ids === [] || in_array(self::ALL_STORES, $ids, true)) {
            return [self::ALL_STORES];
        }
        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }
}
