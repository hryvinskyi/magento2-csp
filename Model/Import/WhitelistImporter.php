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
use Hryvinskyi\Csp\Model\Whitelist\StoreScopeText;
use Magento\Framework\Exception\LocalizedException;

/**
 * Imports whitelist rows; a row whose directive, value and area match an entry extends that entry's stores and updates
 * its name, status and script instead of adding a duplicate.
 *
 * Rows name their fields by code (`policy`) or by the grid's column label (`Policy`, in English or in the admin's
 * language), so the grid's CSV export imports as it is. Values may be codes or the labels the export writes: the status
 * as 1/0 or Enabled/Disabled, the area as its code or its label, the stores as store view codes, names or ids, or
 * All Store Views (see StoreScopeText). A row without an area column applies to every area, without a store column to
 * every store, without a status to enabled; a row with an empty store cell names no store and is refused unless it
 * extends an existing entry. A row that fails is reported and skipped.
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
     * @param StoreScopeText $storeScopeText
     * @param AreaResolver $areaResolver
     */
    public function __construct(
        private readonly RowReaderPool $readerPool,
        private readonly WhitelistInterfaceFactory $whitelistFactory,
        private readonly WhitelistRepositoryInterface $whitelistRepository,
        private readonly EntryDataMapper $entryDataMapper,
        private readonly EntryNormalizer $entryNormalizer,
        private readonly StoreScopeText $storeScopeText,
        private readonly AreaResolver $areaResolver
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
        $status = $this->status($fields[WhitelistInterface::STATUS] ?? '1');
        if ($status === null) {
            throw new LocalizedException(__('The status must be 1 (enabled) or 0 (disabled).'));
        }
        $areaText = trim($fields[WhitelistInterface::AREA] ?? '');
        $area = $areaText === '' ? Area::ALL : $this->areaResolver->resolve($areaText);
        if ($area === null) {
            throw new LocalizedException(__('"%1" is not an area. Use all, frontend or adminhtml.', $areaText));
        }
        $stores = array_key_exists(self::STORES, $fields)
            ? $this->storeScopeText->parse($fields[self::STORES])
            : [StoreIdList::ALL_STORES];
        if ($stores === null) {
            throw new LocalizedException(__(
                '"%1" names a store view that does not exist. List store view codes, names or ids separated by commas, or All Store Views.',
                $fields[self::STORES] ?? ''
            ));
        }

        $entry = $this->whitelistFactory->create();
        $this->entryDataMapper->apply($entry, [
            ...$fields,
            WhitelistInterface::AREA => $area->value,
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
     * Status a row's text names: 1/0, enabled/disabled, or the grid's label in the admin's language; null for none.
     *
     * @param string $text
     * @return int|null
     */
    private function status(string $text): ?int
    {
        $statuses = self::STATUSES + [
            mb_strtolower((string)__('Enabled')) => 1,
            mb_strtolower((string)__('Disabled')) => 0,
        ];

        return $statuses[mb_strtolower(trim($text))] ?? null;
    }

    /**
     * Row fields by code, grid labels in English or in the admin's language translated.
     *
     * @param array<string, string> $row
     * @return array<string, string>
     */
    private function fields(array $row): array
    {
        $labels = [];
        foreach (self::LABELS as $label => $code) {
            $labels[$label] = $code;
            $labels[(string)__($label)] = $code;
        }
        $fields = [];
        foreach ($row as $name => $value) {
            $fields[$labels[$name] ?? $name] = $value;
        }

        return $fields;
    }
}
