<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\ValueType;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;

/**
 * Brings a whitelist entry into canonical form before it is validated and saved.
 */
class EntryNormalizer
{
    /**
     * @param SourceValueRules $sourceValueRules
     * @param StoreIdList $storeIdList
     */
    public function __construct(
        private readonly SourceValueRules $sourceValueRules,
        private readonly StoreIdList $storeIdList
    ) {
    }

    /**
     * Trim the identifier, lower-case directive, type and algorithm, canonicalize the value, sort the stores.
     *
     * @param WhitelistInterface $entry
     * @return void
     */
    public function normalize(WhitelistInterface $entry): void
    {
        $entry->setIdentifier(trim((string)$entry->getIdentifier()));
        $entry->setPolicy(strtolower(trim((string)$entry->getPolicy())));
        $entry->setValueType(strtolower(trim((string)$entry->getValueType())));
        $entry->setArea(strtolower(trim($entry->getArea())));
        $entry->setStoreIds($this->storeIdList->normalize(array_values($entry->getStoreIds())));
        $value = (string)$entry->getValue();
        if ($entry->getValueType() === ValueType::HASH->value) {
            $entry->setValueAlgorithm(strtolower(trim((string)$entry->getValueAlgorithm())));
            $entry->setValue($this->sourceValueRules->normalizeHash($value));

            return;
        }
        $entry->setValueAlgorithm('');
        $entry->setValue($this->sourceValueRules->normalizeHost($value));
    }
}
