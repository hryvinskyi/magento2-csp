<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Magento\Csp\Model\Policy\FetchPolicy;

/**
 * Directives a whitelist entry may carry: the fetch and source-list directives Magento renders from configuration.
 *
 * A CSP3 sub-directive (`script-src-elem`, `worker-src`, …) is never one of them: adding it to a header stops it
 * falling back to its parent and blocks everything the parent allowed.
 */
class WhitelistableDirectives
{
    /**
     * @param DirectiveCatalog $catalog
     */
    public function __construct(private readonly DirectiveCatalog $catalog)
    {
    }

    /**
     * Every directive an entry may carry.
     *
     * @return list<string>
     */
    public function all(): array
    {
        return FetchPolicy::POLICIES;
    }

    /**
     * Whether an entry may carry the directive.
     *
     * @param string $directive
     * @return bool
     */
    public function isWhitelistable(string $directive): bool
    {
        return in_array($directive, FetchPolicy::POLICIES, true);
    }

    /**
     * The whitelistable directive that decides a request the browser reported under the given effective directive.
     *
     * The effective directive itself when it is whitelistable, otherwise the nearest whitelistable directive it falls
     * back to (`script-src-attr` → `script-src`, `worker-src` → `child-src`); null when there is none.
     *
     * @param string $effectiveDirective
     * @return string|null
     */
    public function governing(string $effectiveDirective): ?string
    {
        $name = strtolower(trim($effectiveDirective));
        foreach ([$name, ...$this->catalog->fallbacks($name)] as $candidate) {
            if ($this->isWhitelistable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
