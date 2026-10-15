<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\UiComponent\Listing\Column;

/**
 * Replaces a numeric field of every grid row with what a callback makes of it.
 */
trait MapsRowValues
{
    /**
     * @param array<mixed> $dataSource
     * @param string $field
     * @param callable(int): string $map
     * @return array<mixed>
     */
    private function mapRowValues(array $dataSource, string $field, callable $map): array
    {
        $data = $dataSource['data'] ?? null;
        $items = is_array($data) ? ($data['items'] ?? null) : null;
        if (!is_array($data) || !is_array($items)) {
            return $dataSource;
        }
        foreach ($items as $key => $item) {
            if (is_array($item) && is_numeric($item[$field] ?? null)) {
                $item[$field] = $map((int)$item[$field]);
                $items[$key] = $item;
            }
        }
        $data['items'] = $items;
        $dataSource['data'] = $data;

        return $dataSource;
    }
}
