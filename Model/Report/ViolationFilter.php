<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\ViolationInterface;
use Hryvinskyi\Csp\Model\Store\AdminLocation;
use Hryvinskyi\Csp\Model\Store\StoreHostsProviderInterface;
use Hryvinskyi\Csp\Model\Store\UrlHost;

/**
 * Accepts a violation only from a page of the receiving store, or of the admin, and not caused by a browser extension.
 *
 * When the admin shares its host with a storefront, an admin page is one whose path starts with the admin path.
 */
class ViolationFilter
{
    private const EXTENSION_SCHEMES = [
        'chrome-extension',
        'moz-extension',
        'safari-extension',
        'safari-web-extension',
        'ms-browser-extension',
        'edge-extension',
    ];

    /**
     * @param StoreHostsProviderInterface $storeHostsProvider
     * @param AdminLocation $adminLocation
     * @param UrlHost $urlHost
     */
    public function __construct(
        private readonly StoreHostsProviderInterface $storeHostsProvider,
        private readonly AdminLocation $adminLocation,
        private readonly UrlHost $urlHost
    ) {
    }

    /**
     * Whether the violation is recorded for the store and area that received it.
     *
     * @param ViolationInterface $violation
     * @param Area $area
     * @param int $storeId
     * @return bool
     */
    public function accepts(ViolationInterface $violation, Area $area, int $storeId): bool
    {
        if ($this->fromExtension($violation->getBlockedUri()) || $this->fromExtension($violation->getSourceFile())) {
            return false;
        }
        $documentHost = $this->urlHost->of($violation->getDocumentUri());
        if ($documentHost === null) {
            return false;
        }
        if ($area !== Area::ADMINHTML) {
            return in_array($documentHost, $this->storeHostsProvider->storeHosts($storeId), true);
        }
        if ($documentHost !== $this->adminLocation->host()) {
            return false;
        }
        if (!in_array($documentHost, $this->storeHostsProvider->storefrontHosts(), true)) {
            return true;
        }
        $path = parse_url($violation->getDocumentUri(), PHP_URL_PATH);

        return is_string($path) && str_starts_with(rtrim($path, '/') . '/', $this->adminLocation->pathPrefix());
    }

    /**
     * Whether the URL belongs to a browser extension.
     *
     * @param string $url
     * @return bool
     */
    private function fromExtension(string $url): bool
    {
        $scheme = strtolower((string)strstr($url, ':', true));

        return in_array($scheme, self::EXTENSION_SCHEMES, true);
    }
}
