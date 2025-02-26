<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Config\Source;

use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\Data\OptionSourceInterface;

class Directive implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $result = [];

        foreach (FetchPolicy::POLICIES as $directive) {
            $result[] = [
                'value' => $directive,
                'label' => __($directive),
            ];
        }

        return $result;
    }
}