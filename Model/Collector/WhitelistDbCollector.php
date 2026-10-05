<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Collector;

use Hryvinskyi\Csp\Api\Config\RulesConfigInterface;
use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\ValueType;
use Hryvinskyi\Csp\Model\Policy\SourceKind;
use Hryvinskyi\Csp\Model\Whitelist\ActiveEntries;
use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use Magento\Csp\Api\PolicyCollectorInterface;
use Magento\Csp\Model\Policy\FetchPolicyFactory;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adds the enabled whitelist entries of the current store and area to the policy.
 *
 * The admin is not a store: it receives the entries for every store whose area is the admin or every area.
 */
class WhitelistDbCollector implements PolicyCollectorInterface
{
    /**
     * @param RulesConfigInterface $config
     * @param State $appState
     * @param StoreManagerInterface $storeManager
     * @param ActiveEntries $activeEntries
     * @param FetchPolicyFactory $fetchPolicyFactory
     */
    public function __construct(
        private readonly RulesConfigInterface $config,
        private readonly State $appState,
        private readonly StoreManagerInterface $storeManager,
        private readonly ActiveEntries $activeEntries,
        private readonly FetchPolicyFactory $fetchPolicyFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function collect(array $defaultPolicies = []): array
    {
        if (!$this->config->isRulesEnabled()) {
            return $defaultPolicies;
        }

        $admin = $this->appState->getAreaCode() === AppArea::AREA_ADMINHTML;
        $storeId = $admin ? StoreIdList::ALL_STORES : (int)$this->storeManager->getStore()->getId();
        $sources = [];
        foreach ($this->activeEntries->forScope($storeId, $admin ? Area::ADMINHTML : Area::FRONTEND) as $entry) {
            $policy = $entry['policy'];
            $sources[$policy] ??= ['hosts' => [], 'schemes' => [], 'hashes' => []];
            if ($entry['value_type'] === ValueType::HASH->value) {
                $sources[$policy]['hashes'][$entry['value']] = $entry['value_algorithm'];
            } elseif (SourceKind::of($entry['value']) === SourceKind::Scheme) {
                $sources[$policy]['schemes'][] = rtrim($entry['value'], ':');
            } else {
                $sources[$policy]['hosts'][] = $entry['value'];
            }
        }

        foreach ($sources as $policy => $policySources) {
            $defaultPolicies[] = $this->fetchPolicyFactory->create([
                'id' => $policy,
                'noneAllowed' => false,
                'hostSources' => array_values(array_unique($policySources['hosts'])),
                'schemeSources' => array_values(array_unique($policySources['schemes'])),
                'hashValues' => $policySources['hashes'],
            ]);
        }

        return $defaultPolicies;
    }
}
