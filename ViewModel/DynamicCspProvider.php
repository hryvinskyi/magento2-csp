<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\ViewModel;

use Hryvinskyi\Csp\Api\DynamicPolicyRegistryInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Lets templates allow sources for the page they render.
 *
 * Hosts and hashes added here are cached with the block's HTML by Magento's block cache. A `'self'` flag or a scheme
 * added here is not restored from that cache, so keep those in configuration. With Magento's built-in full-page
 * cache, a page served from that cache carries only configured and whitelisted sources; Varnish keeps the header
 * with the page.
 *
 * The `$key` argument of every method is ignored: in 1.x it named an entry of a global policy cache that leaked one
 * page's sources into every page. It stays so 1.x templates keep working, and will be removed in 3.0.0.
 */
class DynamicCspProvider implements ArgumentInterface
{
    /**
     * @param DynamicPolicyRegistryInterface $registry
     */
    public function __construct(private readonly DynamicPolicyRegistryInterface $registry)
    {
    }

    /**
     * Allow script hosts.
     *
     * @param list<string> $hosts
     * @param bool $self
     * @param string|null $key Ignored, kept for 1.x callers
     * @return void
     */
    public function addScriptSrc(array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->registry->allow('script-src', $hosts, [], $self);
    }

    /**
     * Allow stylesheet hosts.
     *
     * @param list<string> $hosts
     * @param bool $self
     * @param string|null $key Ignored, kept for 1.x callers
     * @return void
     */
    public function addStyleSrc(array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->registry->allow('style-src', $hosts, [], $self);
    }

    /**
     * Allow image hosts.
     *
     * @param list<string> $hosts
     * @param bool $self
     * @param string|null $key Ignored, kept for 1.x callers
     * @return void
     */
    public function addImgSrc(array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->registry->allow('img-src', $hosts, [], $self);
    }

    /**
     * Allow font hosts.
     *
     * @param list<string> $hosts
     * @param bool $self
     * @param string|null $key Ignored, kept for 1.x callers
     * @return void
     */
    public function addFontSrc(array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->registry->allow('font-src', $hosts, [], $self);
    }

    /**
     * Allow hosts for fetch, XHR and websockets.
     *
     * @param list<string> $hosts
     * @param bool $self
     * @param string|null $key Ignored, kept for 1.x callers
     * @return void
     */
    public function addConnectSrc(array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->registry->allow('connect-src', $hosts, [], $self);
    }

    /**
     * Allow frame hosts.
     *
     * @param list<string> $hosts
     * @param bool $self
     * @param string|null $key Ignored, kept for 1.x callers
     * @return void
     */
    public function addFrameSrc(array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->registry->allow('frame-src', $hosts, [], $self);
    }

    /**
     * Allow hosts for any fetch directive.
     *
     * @param string $policyType Fetch directive, e.g. "media-src"
     * @param list<string> $hosts
     * @param bool $self
     * @param string|null $key Ignored, kept for 1.x callers
     * @return void
     */
    public function addCustomPolicy(string $policyType, array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->registry->allow($policyType, $hosts, [], $self);
    }

    /**
     * Allow inline scripts by their base64 sha256 digests.
     *
     * @param list<string> $hashes
     * @param bool $self
     * @param string|null $key Ignored, kept for 1.x callers
     * @return void
     */
    public function addScriptSrcHash(array $hashes, bool $self = false, ?string $key = null): void
    {
        $this->registry->allow('script-src', [], $hashes, $self);
    }

    /**
     * Allow inline styles by their base64 sha256 digests.
     *
     * @param list<string> $hashes
     * @param bool $self
     * @param string|null $key Ignored, kept for 1.x callers
     * @return void
     */
    public function addStyleSrcHash(array $hashes, bool $self = false, ?string $key = null): void
    {
        $this->registry->allow('style-src', [], $hashes, $self);
    }

    /**
     * Allow digests for any fetch directive.
     *
     * @param string $policyType
     * @param list<string> $hashes
     * @param bool $self
     * @param string|null $key Ignored, kept for 1.x callers
     * @return void
     */
    public function addCustomPolicyHash(string $policyType, array $hashes, bool $self = false, ?string $key = null): void
    {
        $this->registry->allow($policyType, [], $hashes, $self);
    }

    /**
     * Allow hosts and digests for any fetch directive.
     *
     * @param string $policyType
     * @param list<string> $hosts
     * @param list<string> $hashes
     * @param bool $self
     * @param string|null $key Ignored, kept for 1.x callers
     * @return void
     */
    public function addMixedPolicy(string $policyType, array $hosts, array $hashes, bool $self = false, ?string $key = null): void
    {
        $this->registry->allow($policyType, $hosts, $hashes, $self);
    }
}
