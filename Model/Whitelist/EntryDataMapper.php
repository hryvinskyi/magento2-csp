<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;

/**
 * Copies the editable fields of a whitelist entry from submitted data; every other key is ignored.
 *
 * Store ids may come as a list or as comma-separated text.
 */
class EntryDataMapper
{
    /**
     * @param StoreIdList $storeIdList
     */
    public function __construct(private readonly StoreIdList $storeIdList)
    {
    }

    /**
     * Apply the editable fields present in the data to the entry.
     *
     * @param WhitelistInterface $entry
     * @param array<mixed> $data
     * @return void
     */
    public function apply(WhitelistInterface $entry, array $data): void
    {
        $text = static fn (string $key): ?string => is_scalar($data[$key] ?? null) ? (string)$data[$key] : null;
        $setters = [
            WhitelistInterface::IDENTIFIER => $entry->setIdentifier(...),
            WhitelistInterface::POLICY => $entry->setPolicy(...),
            WhitelistInterface::VALUE_TYPE => $entry->setValueType(...),
            WhitelistInterface::VALUE_ALGORITHM => $entry->setValueAlgorithm(...),
            WhitelistInterface::VALUE => $entry->setValue(...),
            WhitelistInterface::AREA => $entry->setArea(...),
            WhitelistInterface::SCRIPT_CONTENT => $entry->setScriptContent(...),
        ];
        foreach ($setters as $key => $setter) {
            $value = $text($key);
            if ($value !== null) {
                $setter($value);
            }
        }
        $status = $text(WhitelistInterface::STATUS);
        if ($status !== null && is_numeric($status)) {
            $entry->setStatus((int)$status);
        }
        $stores = $data[WhitelistInterface::STORE_ID] ?? null;
        if (is_array($stores)) {
            $stores = implode(',', array_filter($stores, 'is_scalar'));
        }
        if (is_scalar($stores)) {
            $entry->setStoreIds($this->storeIdList->parse((string)$stores));
        }
    }
}
