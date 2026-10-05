<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Checks a whitelist entry against the rules every saved entry obeys.
 *
 * The directive is one Magento renders, the value is valid for its type, the stores exist, the area and the status
 * are known.
 */
class EntryValidator
{
    private const MAX_LENGTH = 255;

    /**
     * @param WhitelistableDirectives $directives
     * @param SourceValueRules $sourceValueRules
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly WhitelistableDirectives $directives,
        private readonly SourceValueRules $sourceValueRules,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Reasons the entry cannot be saved; empty when it can.
     *
     * @param WhitelistInterface $entry
     * @return list<Phrase>
     */
    public function validate(WhitelistInterface $entry): array
    {
        $errors = [];
        $identifier = (string)$entry->getIdentifier();
        if ($identifier === '' || mb_strlen($identifier) > self::MAX_LENGTH) {
            $errors[] = __('Enter a name of at most %1 characters.', self::MAX_LENGTH);
        }
        $policy = (string)$entry->getPolicy();
        if (!$this->directives->isWhitelistable($policy)) {
            $errors[] = __(
                '"%1" cannot be whitelisted. Choose one of: %2.',
                $policy,
                implode(', ', $this->directives->all())
            );
        }
        $valueError = $this->valueError($entry);
        if ($valueError !== null) {
            $errors[] = $valueError;
        }
        if (Area::tryFrom($entry->getArea()) === null) {
            $errors[] = __('Choose the area the entry applies to.');
        }
        if (!in_array($entry->getStatus(), [0, 1], true)) {
            $errors[] = __('The status must be enabled or disabled.');
        }
        $unknownStores = array_diff($entry->getStoreIds(), $this->existingStoreIds());
        if ($unknownStores !== []) {
            $errors[] = __('These stores do not exist: %1.', implode(', ', $unknownStores));
        }

        return $errors;
    }

    /**
     * Why the value cannot be saved for the entry's type, or null when it can.
     *
     * @param WhitelistInterface $entry
     * @return Phrase|null
     */
    private function valueError(WhitelistInterface $entry): ?Phrase
    {
        $value = (string)$entry->getValue();
        if (strlen($value) > self::MAX_LENGTH) {
            return __('The value may be at most %1 characters long.', self::MAX_LENGTH);
        }

        return $this->sourceValueRules->valueError(
            (string)$entry->getValueType(),
            (string)$entry->getValueAlgorithm(),
            $value
        );
    }

    /**
     * Ids of every store, store 0 included.
     *
     * @return list<int>
     */
    private function existingStoreIds(): array
    {
        return array_values(array_map(
            static fn (mixed $id): int => (int)$id,
            array_keys($this->storeManager->getStores(true))
        ));
    }
}
