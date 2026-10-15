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
     * Nothing is thrown while the page renders: a directive Magento cannot send dynamically (anything outside
     * `Magento\Csp\Model\Policy\FetchPolicy::POLICIES`, such as `worker-src` or `script-src-elem`) skips the whole
     * allowance, and a host or hash that is not valid is skipped on its own. Each skip is logged.
     *
     * @param string $directive Fetch directive, e.g. "script-src"
     * @param array<string> $hosts Host sources ("cdn.example.com", "https://*.example.com") or scheme sources ("data:")
     * @param array<int|string, string> $hashes Base64-encoded digests of inline content: a list of sha256 digests, or
     *     digest => algorithm ("sha256", "sha384", "sha512") as Magento's fetch policy takes them
     * @param bool $self Whether to allow the page's own origin
     * @return void
     */
    public function allow(string $directive, array $hosts = [], array $hashes = [], bool $self = false): void;
}
