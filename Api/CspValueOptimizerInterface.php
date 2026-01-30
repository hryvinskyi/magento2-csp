<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

/**
 * Interface for optimizing CSP header values by removing duplicates and redundant entries
 */
interface CspValueOptimizerInterface
{
    /**
     * Optimize a complete CSP header value by processing all directives
     *
     * @param string $headerValue The full CSP header value containing all directives
     * @return string The optimized CSP header value
     */
    public function optimizeHeader(string $headerValue): string;

    /**
     * Optimize values for a single CSP directive
     *
     * @param string $directiveName The CSP directive name (e.g., 'script-src', 'img-src')
     * @param array<int, string> $values Array of directive values
     * @return array<int, string> Optimized array of directive values
     */
    public function optimizeDirectiveValues(string $directiveName, array $values): array;

    /**
     * Remove exact duplicate values from an array
     *
     * @param array<int, string> $values Array of values to deduplicate
     * @return array<int, string> Array with duplicates removed
     */
    public function removeDuplicates(array $values): array;

    /**
     * Remove values that are redundant due to wildcard coverage
     *
     * @param array<int, string> $values Array of values to check for redundancy
     * @return array<int, string> Array with redundant entries removed
     */
    public function removeRedundantWildcards(array $values): array;
}
