<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Cache;

use Hryvinskyi\Csp\Api\Cache\CspPolicyCacheStrategyInterface;
use Hryvinskyi\Csp\Api\Serializer\CspPolicySerializerInterface;
use Magento\Framework\App\PageCache\Identifier;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class CspPolicyCacheStrategy implements CspPolicyCacheStrategyInterface
{
    private const CACHE_KEY_PREFIX = 'hryvinskyi_csp_policies_';
    private const CACHE_TAGS = ['hryvinskyi_csp_policies'];
    private const DEFAULT_CACHE_LIFETIME = 3600 * 24 * 365; // 365 days
    
    private bool $policiesLoaded = false;
    private array $cachedPolicies = [];

    public function __construct(
        private readonly FrontendInterface $cache,
        private readonly SerializerInterface $serializer,
        private readonly CspPolicySerializerInterface $policySerializer,
        private readonly Identifier $identifier,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled = true,
        private readonly int $cacheLifetime = self::DEFAULT_CACHE_LIFETIME,
        private readonly string $cacheKeyPrefix = self::CACHE_KEY_PREFIX
    ) {
    }

    /**
     * @inheritDoc
     */
    public function load(): array
    {
        if ($this->policiesLoaded || !$this->isEnabled()) {
            return $this->cachedPolicies;
        }

        try {
            $cacheKey = $this->getCacheKey();
            $cachedData = $this->cache->load($cacheKey);
            if ($cachedData) {
                $policiesData = $this->serializer->unserialize($cachedData);
                $this->cachedPolicies = $this->policySerializer->unserialize($policiesData);

                $this->logger->debug('CSP policies loaded from cache', [
                    'cache_key' => $cacheKey,
                    'policies_count' => count($this->cachedPolicies)
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Failed to load CSP policies from cache', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->policiesLoaded = true;
        return $this->cachedPolicies;
    }

    /**
     * @inheritDoc
     */
    public function save(array $policies): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $cacheKey = $this->getCacheKey();
            $policiesData = $this->policySerializer->serialize($policies);
            $serializedData = $this->serializer->serialize($policiesData);

            $result = $this->cache->save(
                $serializedData,
                $cacheKey,
                $this->getCacheTags(),
                $this->cacheLifetime
            );

            if ($result) {
                $this->cachedPolicies = $policies;
                $this->policiesLoaded = true;

                $this->logger->debug('CSP policies saved to cache', [
                    'cache_key' => $cacheKey,
                    'policies_count' => count($policies)
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to save CSP policies to cache', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $cacheKey = $this->getCacheKey();
            $result = $this->cache->remove($cacheKey);

            if ($result) {
                $this->cachedPolicies = [];
                $this->policiesLoaded = false;

                $this->logger->debug('CSP policies cache cleared', [
                    'cache_key' => $cacheKey
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to clear CSP policies cache', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @inheritDoc
     */
    public function getCacheTags(): array
    {
        return self::CACHE_TAGS;
    }

    /**
     * @inheritDoc
     */
    public function getCacheKey(): string
    {
        return $this->cacheKeyPrefix . $this->identifier->getValue();
    }
}