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
 * Outcome of converting one report group, with the entry involved or the reason for refusing.
 */
class ConversionResult
{
    /**
     * @param ConversionOutcome $outcome
     * @param int|null $entryId
     * @param Phrase|null $refusal
     */
    public function __construct(
        public readonly ConversionOutcome $outcome,
        public readonly ?int $entryId = null,
        public readonly ?Phrase $refusal = null
    ) {
    }
}
