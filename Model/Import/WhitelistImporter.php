<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Import;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Model\Whitelist\EntryDataMapper;
use Hryvinskyi\Csp\Model\Whitelist\EntryNormalizer;
use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use Magento\Framework\Exception\LocalizedException;

/**
 * Imports whitelist rows; a row whose directive, value and area match an entry extends that entry's stores and updates
 * its name, status and script instead of adding a duplicate.
 *
 * Rows name their fields by code (`policy`) or by the grid's column label (`Policy`). Without an area a row applies to
 * every area, without stores to every store, without a status it is enabled. A row that fails is reported and skipped.
 */
class WhitelistImporter
{
    private const LABELS = [
        'Identifier' => WhitelistInterface::IDENTIFIER,
        'Status' => WhitelistInterface::STATUS,
        'Policy' => WhitelistInterface::POLICY,
        'Value Type' => WhitelistInterface::VALUE_TYPE,
        'Value Algorithm' => WhitelistInterface::VALUE_ALGORITHM,
        'Value' => WhitelistInterface::VALUE,
        'Store View' => self::STORES,
        'Area' => WhitelistInterface::AREA,
        'Script Content' => WhitelistInterface::SCRIPT_CONTENT,
    ];
    private const STORES = 'store_ids';
    private const STATUSES = ['1' => 1, 'enabled' => 1, '0' => 0, 'disabled' => 0];

    /**
     * @param RowReaderPool $readerPool
     * @param WhitelistInterfaceFactory $whitelistFactory
     * @param WhitelistRepositoryInterface $whitelistRepository
     * @param EntryDataMapper $entryDataMapper
     * @param EntryNormalizer $entryNormalizer
     * @param StoreIdList $storeIdList
     */
    public function __construct(
        private readonly RowReaderPool $readerPool,
        private readonly WhitelistInterfaceFactory $whitelistFactory,
        private readonly WhitelistRepositoryInterface $whitelistRepository,
        private readonly EntryDataMapper $entryDataMapper,
        private readonly EntryNormalizer $entryNormalizer,
        private readonly StoreIdList $storeIdList
    ) {
    }

    /**
     * Import the file.
     *
     * @param string $path
     * @param string $extension
     * @return array{created: int, updated: int, errors: list<string>}
     * @throws LocalizedException When the file cannot be read as a whitelist export
     */
    public function import(string $path, string $extension): array
    {
        $reader = $this->readerPool->forExtension($extension);
        if ($reader === null) {
            throw new LocalizedException(__('Import a CSV or XML file.'));
        }
        try {
            $rows = $reader->read($path);
        } catch (\InvalidArgumentException $exception) {
            throw new LocalizedException(__('The file cannot be imported: %1', $exception->getMessage()), $exception);
        }

        $result = ['created' => 0, 'updated' => 0, 'errors' => []];
        foreach ($rows as $index => $row) {
            try {
                $result[$this->importRow($this->fields($row))]++;
            } catch (LocalizedException $exception) {
                $result['errors'][] = (string)__('Row %1: %2', $index + 1, $exception->getMessage());
            }
        }

        return $result;
    }

    /**
     * Save one row, merging it into the entry with the same natural key.
     *
     * @param array<string, string> $fields
     * @return 'created'|'updated'
     * @throws LocalizedException
     */
    private function importRow(array $fields): string
    {
        $status = self::STATUSES[strtolower(trim($fields[WhitelistInterface::STATUS] ?? '1'))] ?? null;
        if ($status === null) {
            throw new LocalizedException(__('The status must be 1 (enabled) or 0 (disabled).'));
        }
        $stores = trim($fields[self::STORES] ?? (string)StoreIdList::ALL_STORES);
        if ($stores !== '' && $this->storeIdList->parse($stores) === []) {
            throw new LocalizedException(__('List the store ids separated by commas, 0 for every store.'));
        }

        $entry = $this->whitelistFactory->create();
        $this->entryDataMapper->apply($entry, [
            ...$fields,
            WhitelistInterface::AREA => ($fields[WhitelistInterface::AREA] ?? '') !== '' ? $fields[WhitelistInterface::AREA] : Area::ALL->value,
            WhitelistInterface::STATUS => (string)$status,
            WhitelistInterface::STORE_ID => $stores,
        ]);
        $this->entryNormalizer->normalize($entry);

        $existing = $this->whitelistRepository->findByNaturalKey(
            (string)$entry->getPolicy(),
            (string)$entry->getValueType(),
            (string)$entry->getValueAlgorithm(),
            (string)$entry->getValue(),
            $entry->getArea()
        );
        if ($existing === null) {
            $this->whitelistRepository->save($entry);

            return 'created';
        }
        $existing->setStoreIds([...$existing->getStoreIds(), ...$entry->getStoreIds()]);
        $existing->setIdentifier((string)$entry->getIdentifier());
        $existing->setStatus($status);
        if ((string)$entry->getScriptContent() !== '') {
            $existing->setScriptContent((string)$entry->getScriptContent());
        }
        $this->whitelistRepository->save($existing);

        return 'updated';
    }

    /**
     * Row fields by code, grid labels translated.
     *
     * @param array<string, string> $row
     * @return array<string, string>
     */
    private function fields(array $row): array
    {
        $fields = [];
        foreach ($row as $name => $value) {
            $fields[self::LABELS[$name] ?? $name] = $value;
        }

        return $fields;
    }
}
