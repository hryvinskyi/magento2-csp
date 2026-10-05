<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Conversion;

use Hryvinskyi\Csp\Model\Policy\SourceKind;
use Hryvinskyi\Csp\Model\Whitelist\SourceValueRules;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;

/**
 * Values a report group may never become an allowance for.
 *
 * Never inline code or eval, never every host (`*`, `https:`), and never a scheme such as `data:` or `blob:` in a
 * directive that loads code, frames or plugins, or decides where forms and relative URLs go.
 */
class UnsafeSourceRules
{
    private const CODE_DIRECTIVES = [
        'default-src',
        'script-src',
        'child-src',
        'frame-src',
        'object-src',
        'base-uri',
        'form-action',
    ];
    private const NETWORK_SCHEMES = ['http:', 'https:', 'ws:', 'wss:'];

    /**
     * Blocked values browsers report for inline code and eval, and their keyword names in older groups.
     */
    private const CODE_KEYWORDS = [
        'inline',
        'eval',
        'wasm-eval',
        'trusted-types-policy',
        'trusted-types-sink',
        'unsafe-inline',
        'unsafe-eval',
        'unsafe-hashes',
    ];

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
     * Why the value may not be allowed for the directive, or null when it may.
     *
     * @param string $policy
     * @param string $value Canonical host value
     * @return RefusalReason|null
     */
    public function refusal(string $policy, string $value): ?RefusalReason
    {
        if (!$this->directives->isWhitelistable($policy)) {
            return RefusalReason::NotWhitelistable;
        }
        $kind = SourceKind::of($value);
        if ($kind === SourceKind::Keyword || in_array($value, self::CODE_KEYWORDS, true)) {
            return RefusalReason::InlineOrEval;
        }
        if ($kind === SourceKind::Wildcard || in_array($value, self::NETWORK_SCHEMES, true)) {
            return RefusalReason::EveryHost;
        }
        if ($kind === SourceKind::Scheme && in_array($policy, self::CODE_DIRECTIVES, true)) {
            return RefusalReason::UnsafeScheme;
        }

        return $this->sourceValueRules->hostError($value) === null ? null : RefusalReason::InvalidValue;
    }
}
