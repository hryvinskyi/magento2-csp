<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Magento\Csp\Api\Data\PolicyInterface;

interface CachedCspManagerInterface
{
    /**
     * Add a CSP policy
     *
     * @param PolicyInterface $policy
     * @param string $key
     * @return self
     */
    public function addPolicy(PolicyInterface $policy, string $key): self;

    /**
     * Remove a CSP policy
     *
     * @param string $key
     * @return self
     */
    public function removePolicy(string $key): self;

    /**
     * Check if policy exists
     *
     * @param string $key
     * @return bool
     */
    public function hasPolicy(string $key): bool;

    /**
     * Get a specific policy
     *
     * @param string $key
     * @return PolicyInterface|null
     */
    public function getPolicy(string $key): ?PolicyInterface;

    /**
     * Get all policies
     *
     * @return PolicyInterface[]
     */
    public function getAllPolicies(): array;

    /**
     * Add a fetch policy for scripts
     *
     * @param array<string> $hosts
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addScriptSrc(array $hosts, bool $self = false, ?string $key = null): self;

    /**
     * Add a fetch policy for styles
     *
     * @param array<string> $hosts
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addStyleSrc(array $hosts, bool $self = false, ?string $key = null): self;

    /**
     * Add a fetch policy for images
     *
     * @param array<string> $hosts
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addImgSrc(array $hosts, bool $self = false, ?string $key = null): self;

    /**
     * Add a fetch policy for fonts
     *
     * @param array<string> $hosts
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addFontSrc(array $hosts, bool $self = false, ?string $key = null): self;

    /**
     * Add a fetch policy for connect sources
     *
     * @param array<string> $hosts
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addConnectSrc(array $hosts, bool $self = false, ?string $key = null): self;

    /**
     * Add a fetch policy for frames
     *
     * @param array<string> $hosts
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addFrameSrc(array $hosts, bool $self = false, ?string $key = null): self;

    /**
     * Add a custom fetch policy
     *
     * @param string $policyType
     * @param array<string> $hosts
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addCustomPolicy(string $policyType, array $hosts, bool $self = false, ?string $key = null): self;

    /**
     * Add script-src policy with hash values
     *
     * @param array<string> $hashes
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addScriptSrcHash(array $hashes, bool $self = false, ?string $key = null): self;

    /**
     * Add style-src policy with hash values
     *
     * @param array<string> $hashes
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addStyleSrcHash(array $hashes, bool $self = false, ?string $key = null): self;

    /**
     * Add custom policy with hash values
     *
     * @param string $policyType
     * @param array<string> $hashes
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addCustomPolicyHash(string $policyType, array $hashes, bool $self = false, ?string $key = null): self;

    /**
     * Add mixed policy with both hosts and hashes
     *
     * @param string $policyType
     * @param array<string> $hosts
     * @param array<string> $hashes
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addMixedPolicy(string $policyType, array $hosts, array $hashes, bool $self = false, ?string $key = null): self;

    /**
     * Clear all policies and cache
     *
     * @return self
     */
    public function clear(): self;

    /**
     * Save policies to cache
     *
     * @return self
     */
    public function saveToCache(): self;

    /**
     * Generate a key for a policy
     *
     * @param string $type
     * @param array<string, mixed> $data
     * @return string
     */
    public function generatePolicyKey(string $type, array $data): string;
}