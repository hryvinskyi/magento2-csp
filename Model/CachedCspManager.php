<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\Cache\CspPolicyCacheStrategyInterface;
use Hryvinskyi\Csp\Api\CachedCspManagerInterface;
use Magento\Csp\Api\Data\PolicyInterface;
use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;

class CachedCspManager implements CachedCspManagerInterface
{
    /**
     * Collection of CSP policies
     * @var PolicyInterface[]
     */
    protected array $policies = [];

    /**
     * Flag to track if policies have been loaded from cache
     */
    private bool $policiesLoaded = false;

    /**
     * Flag to track if policies have been modified since last cache save
     */
    private bool $policiesModified = false;

    public function __construct(
        private readonly CspPolicyCacheStrategyInterface $cacheStrategy,
        private readonly DynamicCollector $dynamicCollector
    ) {
    }

    /**
     * @inheritDoc
     */
    public function addPolicy(PolicyInterface $policy, string $key): self
    {
        $this->ensurePoliciesLoaded();
        $this->policies[$key] = $policy;
        $this->policiesModified = true;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function removePolicy(string $key): self
    {
        $this->ensurePoliciesLoaded();

        if (isset($this->policies[$key])) {
            unset($this->policies[$key]);
            $this->policiesModified = true;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasPolicy(string $key): bool
    {
        $this->ensurePoliciesLoaded();
        return isset($this->policies[$key]);
    }

    /**
     * @inheritDoc
     */
    public function getPolicy(string $key): ?PolicyInterface
    {
        $this->ensurePoliciesLoaded();
        return $this->policies[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getAllPolicies(): array
    {
        $this->ensurePoliciesLoaded();
        return $this->policies;
    }

    /**
     * @inheritDoc
     */
    public function addScriptSrc(array $hosts, bool $self = false, ?string $key = null): self
    {
        return $this->addFetchPolicy('script-src', $hosts, [], $self, $key);
    }

    /**
     * @inheritDoc
     */
    public function addStyleSrc(array $hosts, bool $self = false, ?string $key = null): self
    {
        return $this->addFetchPolicy('style-src', $hosts, [], $self, $key);
    }

    /**
     * @inheritDoc
     */
    public function addImgSrc(array $hosts, bool $self = false, ?string $key = null): self
    {
        return $this->addFetchPolicy('img-src', $hosts, [], $self, $key);
    }

    /**
     * @inheritDoc
     */
    public function addFontSrc(array $hosts, bool $self = false, ?string $key = null): self
    {
        return $this->addFetchPolicy('font-src', $hosts, [], $self, $key);
    }

    /**
     * @inheritDoc
     */
    public function addConnectSrc(array $hosts, bool $self = false, ?string $key = null): self
    {
        return $this->addFetchPolicy('connect-src', $hosts, [], $self, $key);
    }

    /**
     * @inheritDoc
     */
    public function addFrameSrc(array $hosts, bool $self = false, ?string $key = null): self
    {
        return $this->addFetchPolicy('frame-src', $hosts, [], $self, $key);
    }

    /**
     * @inheritDoc
     */
    public function addCustomPolicy(string $policyType, array $hosts, bool $self = false, ?string $key = null): self
    {
        return $this->addFetchPolicy($policyType, $hosts, [], $self, $key);
    }

    /**
     * Add script-src policy with hash values
     *
     * @param array<string> $hashes
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addScriptSrcHash(array $hashes, bool $self = false, ?string $key = null): self
    {
        return $this->addFetchPolicy('script-src', [], $hashes, $self, $key);
    }

    /**
     * Add style-src policy with hash values
     *
     * @param array<string> $hashes
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addStyleSrcHash(array $hashes, bool $self = false, ?string $key = null): self
    {
        return $this->addFetchPolicy('style-src', [], $hashes, $self, $key);
    }

    /**
     * Add custom policy with hash values
     *
     * @param string $policyType
     * @param array<string> $hashes
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    public function addCustomPolicyHash(string $policyType, array $hashes, bool $self = false, ?string $key = null): self
    {
        return $this->addFetchPolicy($policyType, [], $hashes, $self, $key);
    }

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
    public function addMixedPolicy(string $policyType, array $hosts, array $hashes, bool $self = false, ?string $key = null): self
    {
        return $this->addFetchPolicy($policyType, $hosts, $hashes, $self, $key);
    }

    /**
     * @inheritDoc
     */
    public function clear(): self
    {
        $this->policies = [];
        $this->policiesLoaded = true;
        $this->policiesModified = true;
        $this->cacheStrategy->clear();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function saveToCache(): self
    {
        $this->ensurePoliciesLoaded();
        $this->cacheStrategy->save($this->policies);
        $this->policiesModified = false;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function generatePolicyKey(string $type, array $data): string
    {
        $keyData = [
            'type' => $type,
            'data' => $data
        ];

        return $type . '_' . md5(serialize($keyData));
    }

    /**
     * Add policies to the Magento dynamic collector
     *
     * @return void
     */
    public function applyToCollector(): void
    {
        $this->ensurePoliciesLoaded();
        $this->saveToCacheIfModified();
        foreach ($this->policies as $policy) {
            $this->dynamicCollector->add($policy);
        }
    }

    /**
     * Add a fetch policy
     *
     * @param string $directive
     * @param array<string> $hosts
     * @param array<string> $hashes
     * @param bool $self
     * @param string|null $key
     * @return self
     */
    private function addFetchPolicy(string $directive, array $hosts, array $hashes, bool $self, ?string $key = null): self
    {
        // Create FetchPolicy with proper constructor:
        // FetchPolicy(id, noneAllowed, hostSources, schemeSources, selfAllowed, ...)
        $policy = new FetchPolicy(
            $directive,              // id (becomes the directive name)
            false,                   // noneAllowed
            $hosts,                  // hostSources
            [],                      // schemeSources
            $self,                   // selfAllowed
            false,                   // inlineAllowed
            false,                   // evalAllowed
            [],                      // nonceValues
            $hashes,                 // hashValues
            false,                   // dynamicAllowed
            false                    // eventHandlersAllowed
        );
        
        if ($key === null) {
            $key = $this->generatePolicyKey($directive, [
                'hosts' => $hosts,
                'hashes' => $hashes,
                'self' => $self
            ]);
        }

        $this->addPolicy($policy, $key);

        return $this;
    }

    /**
     * Ensure policies are loaded from cache if needed
     */
    private function ensurePoliciesLoaded(): void
    {
        if (!$this->policiesLoaded) {
            $cachedPolicies = $this->cacheStrategy->load();
            if (!empty($cachedPolicies)) {
                $this->policies = $cachedPolicies;
            }
            $this->policiesLoaded = true;
        }
    }

    /**
     * Save to cache only if policies have been modified
     */
    private function saveToCacheIfModified(): void
    {
        if ($this->policiesModified) {
            $this->saveToCache();
        }
    }
}