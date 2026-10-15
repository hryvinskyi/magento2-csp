<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Config\Source;

use Hryvinskyi\Csp\Api\Data\Status as StatusEnum;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;

/**
 * Report group statuses with their labels.
 */
class Status implements OptionSourceInterface
{
    /**
     * Status options.
     *
     * @return list<array{value: int, label: Phrase}>
     */
    public function toOptionArray(): array
    {
        return array_map(
            fn (StatusEnum $status): array => ['value' => $status->value, 'label' => $this->label($status)],
            StatusEnum::cases()
        );
    }

    /**
     * Label of a status.
     *
     * @param StatusEnum $status
     * @return Phrase
     */
    public function label(StatusEnum $status): Phrase
    {
        return match ($status) {
            StatusEnum::PENDING => __('Pending'),
            StatusEnum::DENIED => __('Denied'),
            StatusEnum::SKIP => __('Skipped'),
        };
    }
}
