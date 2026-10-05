<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Value implements OptionSourceInterface
{
    /**
     * @param array<string, string> $values Value => label
     */
    public function __construct(private readonly array $values)
    {
    }

    /**
     * Options getter
     *
     * @return list<array{value: string, label: mixed}>
     */
    public function toOptionArray(): array
    {
        $result = [];

        foreach ($this->values as $key => $value) {
            $result[] = [
                'value' => $key,
                'label' => $value,
            ];
        }

        return $result;
    }
}