<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy;

use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use Hryvinskyi\Csp\Api\Data\PolicyInterfaceFactory;

/**
 * Parses a Content-Security-Policy header value.
 *
 * Directive names are lower-cased. When a directive repeats, the first occurrence wins and the rest are ignored,
 * as browsers do.
 */
class PolicyParser
{
    /**
     * @param PolicyInterfaceFactory $policyFactory
     */
    public function __construct(private readonly PolicyInterfaceFactory $policyFactory)
    {
    }

    /**
     * Parse a header value into a policy.
     *
     * @param string $headerValue
     * @return PolicyInterface
     */
    public function parse(string $headerValue): PolicyInterface
    {
        $directives = [];
        foreach (explode(';', $headerValue) as $segment) {
            $tokens = preg_split('/[ \t\r\n\f]+/', trim($segment), -1, PREG_SPLIT_NO_EMPTY);
            if ($tokens === false || $tokens === []) {
                continue;
            }

            $name = strtolower(array_shift($tokens));
            if (array_key_exists($name, $directives)) {
                continue;
            }

            $directives[$name] = $tokens;
        }

        return $this->policyFactory->create(['directives' => $directives]);
    }
}
