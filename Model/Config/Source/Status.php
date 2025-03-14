<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Config\Source;

use Hryvinskyi\Csp\Api\Data\Status as StatusEnum;
use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => StatusEnum::PENDING->getStatusCode(), 'label' => __('Pending')],
            ['value' => StatusEnum::DENIED->getStatusCode(), 'label' => __('Denied')],
            ['value' => StatusEnum::SKIP->getStatusCode(), 'label' => __('Skip')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            StatusEnum::PENDING->getStatusCode() => __('Pending'),
            StatusEnum::DENIED->getStatusCode() => __('Denied'),
            StatusEnum::SKIP->getStatusCode() => __('Skip')
        ];
    }
}