<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Store;

use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Reads storefront hosts from the secure and unsecure link, static and media base URLs of the store views.
 */
class StoreHostsProvider implements StoreHostsProviderInterface
{
    private const URL_TYPES = [UrlInterface::URL_TYPE_LINK, UrlInterface::URL_TYPE_STATIC, UrlInterface::URL_TYPE_MEDIA];

    /**
     * @var array<int, list<string>>
     */
    private array $hostsByStore = [];

    /**
     * @param StoreManagerInterface $storeManager
     * @param UrlHost $urlHost
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlHost $urlHost,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function storefrontHosts(): array
    {
        $hosts = [];
        foreach ($this->storeManager->getStores() as $store) {
            $hosts = [...$hosts, ...$this->hostsOf($store)];
        }

        return array_values(array_unique($hosts));
    }

    /**
     * @inheritDoc
     */
    public function storeHosts(int $storeId): array
    {
        try {
            $store = $this->storeManager->getStore($storeId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException) {
            return [];
        }

        return $this->hostsOf($store);
    }

    /**
     * Hosts of one store view; a base URL that cannot be built is logged and skipped.
     *
     * @param StoreInterface $store
     * @return list<string>
     */
    private function hostsOf(StoreInterface $store): array
    {
        $storeId = (int)$store->getId();
        if (isset($this->hostsByStore[$storeId])) {
            return $this->hostsByStore[$storeId];
        }
        $hosts = [];
        if ($store instanceof Store) {
            foreach (self::URL_TYPES as $type) {
                foreach ([false, true] as $secure) {
                    try {
                        $url = $store->getBaseUrl($type, $secure);
                        $host = is_string($url) ? $this->urlHost->of($url) : null;
                    } catch (\Exception $exception) {
                        $this->logger->warning(
                            'A store base URL could not be read for the CSP store hosts.',
                            ['store_id' => $storeId, 'type' => $type, 'exception' => $exception]
                        );
                        continue;
                    }
                    if ($host !== null) {
                        $hosts[] = $host;
                    }
                }
            }
        }

        return $this->hostsByStore[$storeId] = array_values(array_unique($hosts));
    }
}
