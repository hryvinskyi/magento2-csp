<?php
/**
 * Copyright (c) 2025. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class HashValidationOptions implements OptionSourceInterface
{
    public const VALID = 'valid';
    public const INVALID = 'invalid';
    public const NOT_VERIFIED = 'not_verified';
    public const NOT_APPLICABLE = 'n_a';

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