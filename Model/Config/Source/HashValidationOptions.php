<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class HashValidationOptions implements OptionSourceInterface
{
    public const NOT_APPLICABLE = 0;
    public const NOT_VERIFIED = 1;
    public const VALID = 2;
    public const INVALID = 3;

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::VALID, 'label' => __('Valid')],
            ['value' => self::INVALID, 'label' => __('Invalid')],
            ['value' => self::NOT_VERIFIED, 'label' => __('Not Verified')],
            ['value' => self::NOT_APPLICABLE, 'label' => __('N/A')]
        ];
    }
}