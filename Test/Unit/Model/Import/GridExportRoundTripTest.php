<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Import;

use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Hryvinskyi\Csp\Model\Config\Source\Algorithm;
use Hryvinskyi\Csp\Model\Config\Source\Area as AreaOptions;
use Hryvinskyi\Csp\Model\Config\Source\Directive;
use Hryvinskyi\Csp\Model\Config\Source\Value;
use Hryvinskyi\Csp\Model\Import\AreaResolver;
use Hryvinskyi\Csp\Model\Import\CsvRowReader;
use Hryvinskyi\Csp\Model\Import\RowReaderPool;
use Hryvinskyi\Csp\Model\Import\WhitelistImporter;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\Grid\Collection as GridCollection;
use Hryvinskyi\Csp\Model\Whitelist;
use Hryvinskyi\Csp\Model\Whitelist\EntryDataMapper;
use Hryvinskyi\Csp\Model\Whitelist\EntryNormalizer;
use Hryvinskyi\Csp\Model\Whitelist\EntryValidator;
use Hryvinskyi\Csp\Model\Whitelist\SourceValueRules;
use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use Hryvinskyi\Csp\Model\Whitelist\StoreScopeText;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;
use Hryvinskyi\Csp\Test\Unit\Support\CreatesWhitelistEntries;
use Hryvinskyi\Csp\Test\Unit\Support\InMemoryWhitelistRepository;
use Magento\Framework\Api\AttributeValue;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\MetadataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Whitelist grid rows go through core's export row writer and come back through the whitelist import unchanged.
 */
class GridExportRoundTripTest extends TestCase
{
    use CreatesWhitelistEntries;

    /**
     * Columns of the whitelist listing that the export writes, by field, with their labels.
     */
    private const COLUMNS = [
        'rule_id' => 'ID',
        'identifier' => 'Identifier',
        'status' => 'Status',
        'policy' => 'Policy',
        'value_type' => 'Value Type',
        'value_algorithm' => 'Value Algorithm',
        'value' => 'Value',
        'store_id' => 'Store View',
        'area' => 'Area',
        'created_at' => 'Created',
        'updated_at' => 'Modified',
        'hash_validation' => 'Hash Valid',
        'redundancy_status' => 'Redundancy',
    ];

    private StoreManagerInterface $storeManager;

    private string $file = '';

    protected function setUp(): void
    {
        $stores = [];
        foreach ([0 => ['admin', 'Admin'], 1 => ['default', 'Default Store View'], 2 => ['de', 'German']] as $id => [$code, $name]) {
            $store = $this->createStub(StoreInterface::class);
            $store->method('getId')->willReturn($id);
            $store->method('getCode')->willReturn($code);
            $store->method('getName')->willReturn($name);
            $stores[$id] = $store;
        }
        $this->storeManager = $this->createStub(StoreManagerInterface::class);
        $this->storeManager->method('getStores')->willReturn($stores);
    }

    protected function tearDown(): void
    {
        if ($this->file !== '') {
            unlink($this->file);
        }
    }

    public function testExportedRowsImportAsTheEntriesTheyCameFrom(): void
    {
        $digest = base64_encode(hash('sha256', 'alert(1)', true));
        $entries = [
            ['CDN', 1, 'script-src', 'host', '', 'cdn.example.com', [1, 2], 'frontend'],
            ['Fonts', 0, 'font-src', 'host', '', 'https://fonts.example.org', [0], 'all'],
            ['Inline', 1, 'script-src', 'hash', 'sha256', $digest, [2], 'adminhtml'],
            ['Orphan', 1, 'img-src', 'host', '', 'img.example.org', [], 'all'],
        ];
        $exporter = new MetadataProvider(
            $this->createStub(Filter::class),
            $this->createStub(TimezoneInterface::class),
            $this->createStub(ResolverInterface::class)
        );
        $options = $this->options();
        $lines = [array_values(self::COLUMNS)];
        foreach ($entries as $index => $entry) {
            $lines[] = array_values(array_map(
                static fn (string|\Stringable|null $cell): string => (string)$cell,
                $exporter->getRowData($this->gridRow($index + 1, ...$entry), array_keys(self::COLUMNS), $options)
            ));
        }

        $this->assertSame(
            ['CDN', 'Enabled', 'default,de', 'Storefront'],
            [$lines[1][1], $lines[1][2], $lines[1][7], $lines[1][8]]
        );
        $this->assertSame(['All Store Views', 'Storefront and Admin'], [$lines[2][7], $lines[2][8]]);

        $repository = $this->repository();
        $result = $this->importer($repository)->import($this->csv($lines), 'csv');

        $this->assertSame(3, $result['created']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringStartsWith('Row 4:', $result['errors'][0]);
        $this->assertSame(
            array_slice($entries, 0, 3),
            array_values(array_map(
                static fn (Whitelist $entry): array => [
                    $entry->getIdentifier(),
                    $entry->getStatus(),
                    $entry->getPolicy(),
                    $entry->getValueType(),
                    $entry->getValueAlgorithm(),
                    $entry->getValue(),
                    $entry->getStoreIds(),
                    $entry->getArea(),
                ],
                array_filter($repository->entries, static fn ($entry): bool => $entry instanceof Whitelist)
            ))
        );
    }

    /**
     * A row of the whitelist grid collection as its export reads it.
     *
     * @param int $ruleId
     * @param string $identifier
     * @param int $status
     * @param string $policy
     * @param string $valueType
     * @param string $algorithm
     * @param string $value
     * @param list<int> $storeIds
     * @param string $area
     * @return Document
     */
    private function gridRow(
        int $ruleId,
        string $identifier,
        int $status,
        string $policy,
        string $valueType,
        string $algorithm,
        string $value,
        array $storeIds,
        string $area
    ): Document {
        $attributeValues = $this->createStub(AttributeValueFactory::class);
        $attributeValues->method('create')->willReturnCallback(static fn (): AttributeValue => new AttributeValue());
        $row = new Document($attributeValues);
        $row->setData([
            'rule_id' => (string)$ruleId,
            'identifier' => $identifier,
            'status' => (string)$status,
            'policy' => $policy,
            'value_type' => $valueType,
            'value_algorithm' => $algorithm,
            'value' => $value,
            GridCollection::STORE_ID_LIST => $storeIds,
            'store_id' => (new StoreScopeText($this->storeManager))->format($storeIds),
            'area' => $area,
            'created_at' => '2026-10-01 10:00:00',
            'updated_at' => '2026-10-02 10:00:00',
        ]);

        return $row;
    }

    /**
     * Column options as core's export flattens those of the whitelist listing.
     *
     * @return array<string, array<int|string, mixed>>
     */
    private function options(): array
    {
        $flatten = new class (
            $this->createStub(Filter::class),
            $this->createStub(TimezoneInterface::class),
            $this->createStub(ResolverInterface::class)
        ) extends MetadataProvider {
            /**
             * @param array<mixed> $options
             * @return array<int|string, mixed>
             */
            public function flatten(array $options): array
            {
                return $this->getOptionsArray($options);
            }
        };
        $storeViews = [
            ['label' => __('All Store Views'), 'value' => '0'],
            ['label' => 'Main Website', 'value' => [
                ['label' => '    Main Website Store', 'value' => [
                    ['label' => '        Default Store View', 'value' => '1'],
                    ['label' => '        German', 'value' => '2'],
                ]],
            ]],
        ];

        return [
            'status' => $flatten->flatten([['value' => 0, 'label' => 'Disabled'], ['value' => 1, 'label' => 'Enabled']]),
            'policy' => $flatten->flatten((new Directive())->toOptionArray()),
            'value_type' => $flatten->flatten((new Value(['host' => 'host', 'hash' => 'hash']))->toOptionArray()),
            'value_algorithm' => $flatten->flatten(
                (new Algorithm(['sha256' => 'sha256', 'sha384' => 'sha384', 'sha512' => 'sha512']))->toOptionArray()
            ),
            'store_id' => $flatten->flatten($storeViews),
            'area' => $flatten->flatten((new AreaOptions())->toOptionArray()),
        ];
    }

    /**
     * @return InMemoryWhitelistRepository
     */
    private function repository(): InMemoryWhitelistRepository
    {
        return new InMemoryWhitelistRepository(new EntryValidator(
            new WhitelistableDirectives(new DirectiveCatalog()),
            new SourceValueRules(new HostSourceParser()),
            $this->storeManager
        ));
    }

    /**
     * @param InMemoryWhitelistRepository $repository
     * @return WhitelistImporter
     */
    private function importer(InMemoryWhitelistRepository $repository): WhitelistImporter
    {
        $factory = $this->createStub(WhitelistInterfaceFactory::class);
        $factory->method('create')->willReturnCallback(
            fn (): Whitelist => $this->whitelistEntry(['rule_id' => null, 'identifier' => null, 'value' => null, 'store_id' => null, 'area' => null, 'status' => null])
        );

        return new WhitelistImporter(
            new RowReaderPool(['csv' => new CsvRowReader(new Csv(new File()))]),
            $factory,
            $repository,
            new EntryDataMapper(new StoreIdList()),
            new EntryNormalizer(new SourceValueRules(new HostSourceParser()), new StoreIdList()),
            new StoreScopeText($this->storeManager),
            new AreaResolver(new AreaOptions())
        );
    }

    /**
     * CSV file of the lines, as core's export writes it.
     *
     * @param list<list<string>> $lines
     * @return string
     */
    private function csv(array $lines): string
    {
        $this->file = sys_get_temp_dir() . '/hryvinskyi-csp-export-' . bin2hex(random_bytes(6)) . '.csv';
        $handle = fopen($this->file, 'w');
        $this->assertIsResource($handle);
        foreach ($lines as $line) {
            fputcsv($handle, $line);
        }
        fclose($handle);

        return $this->file;
    }
}
