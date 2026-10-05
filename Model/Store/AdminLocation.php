<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Store;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Where the admin is served from: its base URL, host and the path its pages start with.
 */
class AdminLocation
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param FrontNameResolver $frontNameResolver
     * @param UrlHost $urlHost
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly FrontNameResolver $frontNameResolver,
        private readonly UrlHost $urlHost
    ) {
    }

    /**
     * Base URL of the admin with a trailing slash: the custom admin URL, or the default store view's secure base URL.
     *
     * @return string
     */
    public function baseUrl(): string
    {
        $custom = $this->scopeConfig->getValue(FrontNameResolver::XML_PATH_CUSTOM_ADMIN_URL);
        if ($this->scopeConfig->isSetFlag(FrontNameResolver::XML_PATH_USE_CUSTOM_ADMIN_URL)
            && is_string($custom)
            && $custom !== ''
        ) {
            return rtrim($custom, '/') . '/';
        }
        $store = $this->storeManager->getDefaultStoreView();
        $url = $store instanceof Store ? $store->getBaseUrl(UrlInterface::URL_TYPE_LINK, true) : null;

        return is_string($url) && $url !== '' ? rtrim($url, '/') . '/' : '/';
    }

    /**
     * Host of the admin, with a port that is not the scheme's default.
     *
     * @return string|null
     */
    public function host(): ?string
    {
        return $this->urlHost->of($this->baseUrl());
    }

    /**
     * Path every admin page starts with, e.g. `/admin/`.
     *
     * @return string
     */
    public function pathPrefix(): string
    {
        $basePath = parse_url($this->baseUrl(), PHP_URL_PATH);
        $frontName = $this->frontNameResolver->getFrontName();

        return rtrim(is_string($basePath) ? $basePath : '/', '/') . '/' . (is_string($frontName) ? $frontName : '') . '/';
    }
}
