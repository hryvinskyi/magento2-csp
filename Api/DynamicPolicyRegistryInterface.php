<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

/**
 * Allows sources for the page being rendered, from a template or a block.
 *
 * Allowances go to Magento's dynamic policy collector, so Magento's block cache stores the hosts and hashes with the
 * cached HTML and restores them when the block is served from cache.
 *
 * @api
 */
interface DynamicPolicyRegistryInterface
{
    /**
     * Allow sources for one fetch directive on the current page.
     *
     * @param string $directive Fetch directive, e.g. "script-src"
     * @param list<string> $hosts Host sources ("cdn.example.com", "https://*.example.com") or scheme sources ("data:")
     * @param list<string> $hashes Base64-encoded sha256 digests of inline content
     * @param bool $self Whether to allow the page's own origin
     * @return void
     * @throws \InvalidArgumentException When the directive is not a fetch directive or a source is not valid
     */
    public function allow(string $directive, array $hosts = [], array $hashes = [], bool $self = false): void;
}
