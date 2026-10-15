<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Collector;

use Hryvinskyi\Csp\Api\Config\RulesConfigInterface;
use Hryvinskyi\Csp\Model\Store\StoreHostsProviderInterface;
use Magento\Csp\Api\PolicyCollectorInterface;
use Magento\Csp\Model\Policy\FetchPolicyFactory;

/**
 * Allows the hosts of every store view in the directives a page uses to load resources from another store view.
 */
class StoreUrlCollector implements PolicyCollectorInterface
{
    /**
     * Directives a page needs store hosts in; never base-uri, form-action, frame-ancestors or object-src.
     */
    private const DIRECTIVES = [
        'default-src',
        'script-src',
        'style-src',
        'img-src',
        'font-src',
        'connect-src',
        'media-src',
    ];

    /**
     * @param RulesConfigInterface $config
     * @param StoreHostsProviderInterface $storeHostsProvider
     * @param FetchPolicyFactory $fetchPolicyFactory
     */
    public function __construct(
        private readonly RulesConfigInterface $config,
        private readonly StoreHostsProviderInterface $storeHostsProvider,
        private readonly FetchPolicyFactory $fetchPolicyFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function collect(array $defaultPolicies = []): array
    {
        if (!$this->config->isAddAllStorefrontUrls()) {
            return $defaultPolicies;
        }
        $hosts = $this->storeHostsProvider->storefrontHosts();
        if ($hosts === []) {
            return $defaultPolicies;
        }

        foreach (self::DIRECTIVES as $directive) {
            $defaultPolicies[] = $this->fetchPolicyFactory->create([
                'id' => $directive,
                'noneAllowed' => false,
                'hostSources' => $hosts,
            ]);
        }

        return $defaultPolicies;
    }
}
