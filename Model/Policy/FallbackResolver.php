<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy;

use Hryvinskyi\Csp\Api\Data\PolicyInterface;

/**
 * Finds the directive, and its sources, that governs a request type in a policy.
 */
class FallbackResolver
{
    /**
     * @param DirectiveCatalog $catalog
     */
    public function __construct(private readonly DirectiveCatalog $catalog)
    {
    }

    /**
     * Directive that governs the request type, or null when the policy does not restrict it.
     *
     * @param PolicyInterface $policy
     * @param string $requestDirective
     * @return string|null
     */
    public function effectiveDirective(PolicyInterface $policy, string $requestDirective): ?string
    {
        if ($policy->has($requestDirective)) {
            return $requestDirective;
        }
        foreach ($this->catalog->fallbacks($requestDirective) as $fallback) {
            if ($policy->has($fallback)) {
                return $fallback;
            }
        }

        return null;
    }

    /**
     * Sources that govern the request type, or null when the policy does not restrict it.
     *
     * @param PolicyInterface $policy
     * @param string $requestDirective
     * @return list<string>|null
     */
    public function effectiveSources(PolicyInterface $policy, string $requestDirective): ?array
    {
        $directive = $this->effectiveDirective($policy, $requestDirective);

        return $directive === null ? null : $policy->sources($directive);
    }
}
