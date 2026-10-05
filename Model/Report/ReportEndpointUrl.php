<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Model\Store\AdminLocation;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * URLs the browser sends violation reports to: the current store's own secure URL for storefront pages, the admin's
 * own host for admin pages, so reports go to the origin that served the page.
 */
class ReportEndpointUrl
{
    public const STOREFRONT_PATH = 'csp_report_watch';
    public const ADMIN_PATH = 'csp_report_watch/admin/index';

    /**
     * Report URL per store id, computed once.
     *
     * @var array<int, string>
     */
    private array $storefrontUrls = [];

    /**
     * @param StoreManagerInterface $storeManager
     * @param AdminLocation $adminLocation
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly AdminLocation $adminLocation
    ) {
    }

    /**
     * Report URL for pages of the current store.
     *
     * @return string
     */
    public function forStorefront(): string
    {
        $store = $this->storeManager->getStore();
        $storeId = (int)$store->getId();
        if (!isset($this->storefrontUrls[$storeId])) {
            $this->storefrontUrls[$storeId] = $this->secureBaseUrl($store) . self::STOREFRONT_PATH;
        }

        return $this->storefrontUrls[$storeId];
    }

    /**
     * Report URL for admin pages.
     *
     * @return string
     */
    public function forAdmin(): string
    {
        return $this->adminLocation->baseUrl() . self::ADMIN_PATH;
    }

    /**
     * Secure link base URL of a store, "/" when it cannot be built.
     *
     * @param mixed $store
     * @return string
     */
    private function secureBaseUrl(mixed $store): string
    {
        if (!$store instanceof Store) {
            return '/';
        }
        $url = $store->getBaseUrl(UrlInterface::URL_TYPE_LINK, true);

        return is_string($url) && $url !== '' ? rtrim($url, '/') . '/' : '/';
    }
}
