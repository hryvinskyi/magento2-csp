<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Collector;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Magento\Csp\Api\PolicyCollectorInterface;
use Magento\Csp\Model\Collector\MergerInterface;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\Url\ScopeResolverInterface;
use Magento\Framework\UrlInterface;

class StoreUrlCollector implements PolicyCollectorInterface
{
    private array $storeUrls;

    public function __construct(
        private readonly ScopeResolverInterface $scopeResolver,
        private readonly ConfigInterface $config,
        private readonly MergerInterface $merger
    ) {
    }

    /**
     * Collect policies
     *
     * @param array $defaultPolicies
     * @return array
     */
    public function collect(array $defaultPolicies = []): array
    {
        if ($this->config->isAddAllStorefrontUrls() === false) {
            return $defaultPolicies;
        }

        $policies = $defaultPolicies;
        $storeUrls = $this->getStoreUrls();

        foreach (FetchPolicy::POLICIES as $directive) {
            $policy = $this->createFetchPolicy($directive, $storeUrls);
            if (array_key_exists($directive, $policies)) {
                if ($this->merger->canMerge($policies[$directive], $policy)) {
                    $policies[$directive] = $this->merger->merge($policies[$directive], $policy);
                } else {
                    throw new \RuntimeException('Cannot merge a policy of ' . get_class($policy));
                }
            } else {
                $policies[$directive] = $policy;
            }
        }

        return $policies;
    }

    /**
     * Store URLs
     *
     * @return array
     */
    public function getStoreUrls(): array
    {
        if (!empty($this->storeUrls)) {
            return $this->storeUrls;
        }

        $this->storeUrls = $this->fetchAndProcessStoreUrls();
        return $this->storeUrls;
    }

    /**
     * Create fetch policy
     *
     * @param string $policyId
     * @param array $hosts
     * @return FetchPolicy
     */
    private function createFetchPolicy(string $policyId, array $hosts): FetchPolicy
    {
        return new FetchPolicy(
            $policyId,
            false, // selfAllowed
            $hosts,
            [],    // schemes
            false, // blobAllowed
            $policyId === 'script-src' ? true : false, // dataAllowed
            false, // evalAllowed
            [],    // hashes
            [],    // nonces
            false, // nonceAllowed
            false  // noneAllowed
        );
    }

    /**
     * Fetch and process store URLs
     *
     * @return array
     */
    private function fetchAndProcessStoreUrls(): array
    {
        try {
            $baseUrls = $this->collectBaseUrls();
            $domains = $this->extractDomains($baseUrls);
            return array_unique($domains);
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Collect base URLs from all scopes
     *
     * @return array
     */
    private function collectBaseUrls(): array
    {
        $baseUrls = [];
        $urlTypes = [
            UrlInterface::URL_TYPE_LINK => true,
            UrlInterface::URL_TYPE_MEDIA => true,
            UrlInterface::URL_TYPE_STATIC => true
        ];

        foreach ($this->scopeResolver->getScopes() as $scope) {
            $baseUrls[] = $scope->getBaseUrl();
            foreach ($urlTypes as $type => $secure) {
                $baseUrls[] = $scope->getBaseUrl($type, $secure);
            }
        }

        return $baseUrls;
    }

    /**
     * Extract domains from URLs
     *
     * @param array $urls
     * @return array
     */
    private function extractDomains(array $urls): array
    {
        $domains = [];
        foreach ($urls as $url) {
            if (preg_match('#//([^/]*)/#', (string) $url, $matches)) {
                $domains[] = $matches[1];
            }
        }
        return $domains;
    }
}