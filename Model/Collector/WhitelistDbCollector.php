<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Collector;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Hryvinskyi\Csp\Model\Whitelist\Command\GetAllActiveWhitelistByStoreIdInterface;
use Magento\Csp\Api\PolicyCollectorInterface;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class WhitelistDbCollector implements PolicyCollectorInterface
{
    private array $rules = [];

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly State $appState,
        private readonly StoreManagerInterface $storeManager,
        private readonly GetAllActiveWhitelistByStoreIdInterface $getAllActiveWhitelistByStoreId
    ) {
    }

    /**
     * @inheritDoc
     */
    public function collect(array $defaultPolicies = []): array
    {
        if ($this->config->isRulesEnabled() === false) {
            return $defaultPolicies;
        }

        foreach ($this->getRules() as $policyId => $valuesByType) {
            $defaultPolicies[] = new FetchPolicy(
                $policyId,
                false,
                $valuesByType['host'] ?? [],
                [],
                false,
                $policyId === 'script-src' ? true : false,
                false,
                [],
                $valuesByType['hash'] ?? [],
                false,
                false
            );
        }

        return $defaultPolicies;
    }

    /**
     * Get rules and build the rules map if not yet generated
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getRules(): array
    {
        // get from memory
        if (!empty($this->rules)) {
            return $this->rules;
        }

        $storeId = $this->appState->getAreaCode() === 'adminhtml' ? 0 : (int)$this->storeManager->getStore()->getId();
        return $this->rules = $this->buildPolicyMap($storeId);
    }

    /**
     * Build policy map from rules stores in database
     *
     * @param int $storeId
     * @return array
     */
    public function buildPolicyMap(int $storeId): array
    {
        $result = [];
        $whitelists = $this->getAllActiveWhitelistByStoreId->execute($storeId)->getItems();
        foreach ($whitelists as $whitelist) {
            $valueType = $whitelist->getValueType();
            $key = $valueType === 'host' ? $whitelist->getIdentifier() : $whitelist->getValue();
            $value = $valueType === 'host' ? $whitelist->getValue() : $whitelist->getValueAlgorithm();
            $result[$whitelist->getPolicy()][$valueType][$key] = $value;
        }

        return $result;
    }
}