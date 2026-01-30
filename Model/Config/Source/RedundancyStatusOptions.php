<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options for redundancy status in CSP whitelist grid
 */
class RedundancyStatusOptions implements OptionSourceInterface
{
    public const NOT_APPLICABLE = 0;
    public const UNIQUE = 1;
    public const DUPLICATE = 2;
    public const REDUNDANT = 3;

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::NOT_APPLICABLE, 'label' => __('N/A')],
            ['value' => self::UNIQUE, 'label' => __('Unique')],
            ['value' => self::DUPLICATE, 'label' => __('Duplicate')],
            ['value' => self::REDUNDANT, 'label' => __('Redundant')],
        ];
    }
}
