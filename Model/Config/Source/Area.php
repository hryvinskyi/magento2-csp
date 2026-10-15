<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Config\Source;

use Hryvinskyi\Csp\Api\Data\Area as AreaCode;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Areas a whitelist entry can apply to.
 */
class Area implements OptionSourceInterface
{
    /**
     * Area options.
     *
     * @return list<array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => AreaCode::ALL->value, 'label' => __('Storefront and Admin')],
            ['value' => AreaCode::FRONTEND->value, 'label' => __('Storefront')],
            ['value' => AreaCode::ADMINHTML->value, 'label' => __('Admin')],
        ];
    }
}
