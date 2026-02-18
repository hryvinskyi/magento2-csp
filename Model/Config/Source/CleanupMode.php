<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CleanupMode implements OptionSourceInterface
{
    public const MODE_DATE = 'date';
    public const MODE_COUNT = 'count';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::MODE_DATE, 'label' => __('By Date')],
            ['value' => self::MODE_COUNT, 'label' => __('By Record Count')],
        ];
    }
}
