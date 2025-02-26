<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Algorithm implements OptionSourceInterface
{
    public function __construct(private readonly array $algorithms)
    {
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $result = [
            [
                'value' => '',
                'label' => __('Please select'),
            ]
        ];

        foreach ($this->algorithms as $key => $algorithm) {
            $result[] = [
                'value' => $key,
                'label' => $algorithm,
            ];
        }

        return $result;
    }
}