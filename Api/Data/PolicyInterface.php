<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Data;

/**
 * One Content-Security-Policy header value: directives in header order, each with its source tokens.
 *
 * Directive names are lower case. Instances are immutable: the "with" methods return a modified copy.
 *
 * @api
 */
interface PolicyInterface
{
    /**
     * Directive names in header order.
     *
     * @return list<string>
     */
    public function names(): array;

    /**
     * Whether the policy contains the directive.
     *
     * @param string $name Lower-case directive name
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Source tokens of a directive; an empty list when the directive is absent or has no value.
     *
     * @param string $name Lower-case directive name
     * @return list<string>
     */
    public function sources(string $name): array;

    /**
     * All directives with their source tokens, in header order.
     *
     * @return array<string, list<string>>
     */
    public function directives(): array;

    /**
     * Copy of the policy with the directive set to the given sources (appended when absent).
     *
     * @param string $name Lower-case directive name
     * @param list<string> $sources
     * @return PolicyInterface
     */
    public function withSources(string $name, array $sources): PolicyInterface;

    /**
     * Copy of the policy without the directive.
     *
     * @param string $name Lower-case directive name
     * @return PolicyInterface
     */
    public function without(string $name): PolicyInterface;

    /**
     * Header value, directives separated by "; ".
     *
     * @return string
     */
    public function toHeaderValue(): string;

    /**
     * Size of the header value in bytes.
     *
     * @return int
     */
    public function byteLength(): int;
}
