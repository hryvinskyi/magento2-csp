<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Conversion;

use Magento\Framework\Phrase;

/**
 * Why a report group is not turned into a whitelist entry.
 */
enum RefusalReason
{
    case NotWhitelistable;
    case InlineOrEval;
    case EveryHost;
    case UnsafeScheme;
    case InvalidValue;

    /**
     * Explanation for the admin.
     *
     * @param string $policy
     * @param string $value
     * @return Phrase
     */
    public function message(string $policy, string $value): Phrase
    {
        return match ($this) {
            self::NotWhitelistable => __('"%1" is not a directive Magento renders, so it cannot be whitelisted.', $policy),
            self::InlineOrEval => __(
                '"%1" in %2 is inline code or eval. Allow the script by its hash or move it to a file instead.',
                $value,
                $policy
            ),
            self::EveryHost => __('"%1" would allow every host for %2.', $value, $policy),
            self::UnsafeScheme => __('"%1" would let %2 run content of any origin.', $value, $policy),
            self::InvalidValue => __('"%1" is not a host or scheme source.', $value),
        };
    }
}
