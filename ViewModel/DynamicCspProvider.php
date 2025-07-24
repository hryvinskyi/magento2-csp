<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\ViewModel;

use Hryvinskyi\Csp\Api\CachedCspManagerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class DynamicCspProvider implements ArgumentInterface
{
    /**
     * @param CachedCspManagerInterface $cachedCspManager
     */
    public function __construct(private readonly CachedCspManagerInterface $cachedCspManager)
    {
    }

    /**
     * Add a fetch policy for scripts
     *
     * @param array $hosts
     * @param bool $self
     * @param string|null $key
     * @return void
     */
    public function addScriptSrc(array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->cachedCspManager->addScriptSrc($hosts, $self, $key);
    }

    /**
     * Add a fetch policy for styles
     *
     * @param array $hosts
     * @param bool $self
     * @param string|null $key
     * @return void
     */
    public function addStyleSrc(array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->cachedCspManager->addStyleSrc($hosts, $self, $key);
    }

    /**
     * Add a fetch policy for images
     *
     * @param array $hosts
     * @param bool $self
     * @param string|null $key
     * @return void
     */
    public function addImgSrc(array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->cachedCspManager->addImgSrc($hosts, $self, $key);
    }

    /**
     * Add a fetch policy for fonts
     *
     * @param array $hosts
     * @param bool $self
     * @param string|null $key
     * @return void
     */
    public function addFontSrc(array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->cachedCspManager->addFontSrc($hosts, $self, $key);
    }

    /**
     * Add a fetch policy for connect sources
     *
     * @param array $hosts
     * @param bool $self
     * @param string|null $key
     * @return void
     */
    public function addConnectSrc(array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->cachedCspManager->addConnectSrc($hosts, $self, $key);
    }

    /**
     * Add a fetch policy for frames
     *
     * @param array $hosts
     * @param bool $self
     * @param string|null $key
     * @return void
     */
    public function addFrameSrc(array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->cachedCspManager->addFrameSrc($hosts, $self, $key);
    }

    /**
     * Add a custom fetch policy
     *
     * @param string $policyType
     * @param array $hosts
     * @param bool $self
     * @param string|null $key
     * @return void
     */
    public function addCustomPolicy(string $policyType, array $hosts, bool $self = false, ?string $key = null): void
    {
        $this->cachedCspManager->addCustomPolicy($policyType, $hosts, $self, $key);
    }

    /**
     * Add script-src policy with hash values
     *
     * @param array<string> $hashes
     * @param bool $self
     * @param string|null $key
     * @return void
     */
    public function addScriptSrcHash(array $hashes, bool $self = false, ?string $key = null): void
    {
        $this->cachedCspManager->addScriptSrcHash($hashes, $self, $key);
    }

    /**
     * Add style-src policy with hash values
     *
     * @param array<string> $hashes
     * @param bool $self
     * @param string|null $key
     * @return void
     */
    public function addStyleSrcHash(array $hashes, bool $self = false, ?string $key = null): void
    {
        $this->cachedCspManager->addStyleSrcHash($hashes, $self, $key);
    }

    /**
     * Add custom policy with hash values
     *
     * @param string $policyType
     * @param array<string> $hashes
     * @param bool $self
     * @param string|null $key
     * @return void
     */
    public function addCustomPolicyHash(string $policyType, array $hashes, bool $self = false, ?string $key = null): void
    {
        $this->cachedCspManager->addCustomPolicyHash($policyType, $hashes, $self, $key);
    }

    /**
     * Add mixed policy with both hosts and hashes
     *
     * @param string $policyType
     * @param array<string> $hosts
     * @param array<string> $hashes
     * @param bool $self
     * @param string|null $key
     * @return void
     */
    public function addMixedPolicy(string $policyType, array $hosts, array $hashes, bool $self = false, ?string $key = null): void
    {
        $this->cachedCspManager->addMixedPolicy($policyType, $hosts, $hashes, $self, $key);
    }
}
