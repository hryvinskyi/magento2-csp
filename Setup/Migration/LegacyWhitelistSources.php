<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Setup\Migration;

use Hryvinskyi\Csp\Api\Data\ValueType;
use Hryvinskyi\Csp\Model\Whitelist\SourceValueRules;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;

/**
 * Tells which sources a whitelist entry saved before 2.0.0 put into the policy, and which of them the whitelist rules
 * allow.
 *
 * 1.x copied the value into the header as it was, so a value with whitespace added one source per word. Each word is
 * brought into canonical form and checked as an entry of its own; a word is allowed once even when it repeats. A value
 * without whitespace is checked as stored, the way the policy reads it. Nothing is allowed under a directive that
 * cannot be whitelisted.
 */
class LegacyWhitelistSources
{
    /**
     * @param WhitelistableDirectives $directives
     * @param SourceValueRules $sourceValueRules
     */
    public function __construct(
        private readonly WhitelistableDirectives $directives,
        private readonly SourceValueRules $sourceValueRules
    ) {
    }

    /**
     * Whether the value holds several sources, the sources an entry may carry, and the words refused.
     *
     * @param string $policy
     * @param string $valueType
     * @param string $algorithm
     * @param string $value
     * @return array{split: bool, allowed: list<string>, refused: list<string>}
     */
    public function sources(string $policy, string $valueType, string $algorithm, string $value): array
    {
        $words = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $split = count($words) > 1;
        if (!$this->directives->isWhitelistable($policy)) {
            return ['split' => $split, 'allowed' => [], 'refused' => $split ? $words : [$value]];
        }
        if (!$split) {
            $refused = $this->sourceValueRules->valueError($valueType, $algorithm, $value) !== null;

            return ['split' => false, 'allowed' => $refused ? [] : [$value], 'refused' => $refused ? [$value] : []];
        }

        $allowed = [];
        $refused = [];
        foreach ($words as $word) {
            $source = $valueType === ValueType::HASH->value
                ? $this->sourceValueRules->normalizeHash($word)
                : $this->sourceValueRules->normalizeHost($word);
            if ($this->sourceValueRules->valueError($valueType, $algorithm, $source) !== null) {
                $refused[] = $word;
                continue;
            }
            $allowed[] = $source;
        }

        return ['split' => true, 'allowed' => array_values(array_unique($allowed)), 'refused' => $refused];
    }
}
