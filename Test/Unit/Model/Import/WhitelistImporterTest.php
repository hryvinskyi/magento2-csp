<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Import;

use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Hryvinskyi\Csp\Model\Import\CsvRowReader;
use Hryvinskyi\Csp\Model\Import\RowReaderPool;
use Hryvinskyi\Csp\Model\Import\WhitelistImporter;
use Hryvinskyi\Csp\Model\Import\XmlRowReader;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Whitelist;
use Hryvinskyi\Csp\Model\Whitelist\EntryDataMapper;
use Hryvinskyi\Csp\Model\Whitelist\EntryNormalizer;
use Hryvinskyi\Csp\Model\Whitelist\EntryValidator;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;
use Hryvinskyi\Csp\Model\Whitelist\SourceValueRules;
use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use Hryvinskyi\Csp\Test\Unit\Support\CreatesWhitelistEntries;
use Hryvinskyi\Csp\Test\Unit\Support\InMemoryWhitelistRepository;
use Magento\Framework\DomDocument\DomDocumentFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class WhitelistImporterTest extends TestCase
{
    use CreatesWhitelistEntries;

    private InMemoryWhitelistRepository $repository;

    /**
     * @var list<string>
     */
    private array $files = [];

    protected function setUp(): void
    {
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn(array_fill_keys([0, 1, 2], $this->createStub(StoreInterface::class)));
        $this->repository = new InMemoryWhitelistRepository(new EntryValidator(
            new WhitelistableDirectives(new DirectiveCatalog()),
            new SourceValueRules(new HostSourceParser()),
            $storeManager
        ));
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            unlink($file);
        }
    }

    public function testImportsAGridExportAndMergesRowsWithTheSameKey(): void
    {
        $this->repository->save($this->whitelistEntry(['rule_id' => 7, 'value' => 'cdn.example.com', 'store_id' => [1], 'area' => 'all']));
        $csv = $this->file('csv', implode("\n", [
            'Identifier,Status,Policy,"Value Type",Value,"Store View"',
            'CDN,Enabled,script-src,host,HTTPS://CDN.example.com,2',
            'CDN,Enabled,script-src,host,cdn.example.com,2',
            'Fonts,1,font-src,host,fonts.example.org,',
            "Bad,1,script-src,host,'unsafe-inline',0",
            'Stores by name,1,img-src,host,img.example.org,Default Store View',
        ]));

        $result = $this->importer()->import($csv, 'csv');

        $this->assertSame([2, 1], [$result['created'], $result['updated']]);
        $this->assertCount(2, $result['errors']);
        $this->assertStringStartsWith('Row 4:', $result['errors'][0]);
        $this->assertSame([1, 2], $this->repository->getById(7)->getStoreIds());
        $this->assertSame(
            [['https://cdn.example.com', [2], 'all'], ['fonts.example.org', [0], 'all']],
            array_values(array_map(
                static fn (Whitelist $entry): array => [$entry->getValue(), $entry->getStoreIds(), $entry->getArea()],
                array_filter($this->repository->entries, static fn ($entry): bool => $entry instanceof Whitelist && $entry->getRuleId() !== 7)
            ))
        );
    }

    public function testReadsXmlWithoutResolvingEntities(): void
    {
        $secret = $this->file('txt', 'secret.example.org');
        $xml = $this->file('xml', '<?xml version="1.0"?>' . "\n"
            . '<!DOCTYPE items [<!ENTITY leak SYSTEM "file://' . $secret . '">]>' . "\n"
            . '<items><whitelist><identifier>Leak</identifier><policy>img-src</policy><value_type>host</value_type>'
            . '<value>&leak;</value><area>frontend</area></whitelist>'
            . '<whitelist><identifier>Images</identifier><policy>img-src</policy><value_type>host</value_type>'
            . '<value>img.example.org</value><area>frontend</area></whitelist></items>');

        $result = $this->importer()->import($xml, 'xml');

        $this->assertSame(1, $result['created']);
        $this->assertCount(1, $result['errors']);
        foreach ($this->repository->entries as $entry) {
            $this->assertStringNotContainsString('secret', (string)$entry->getValue());
        }
    }

    public function testRefusesOtherFormats(): void
    {
        $this->expectException(LocalizedException::class);

        $this->importer()->import($this->file('json', '[]'), 'json');
    }

    /**
     * @return WhitelistImporter
     */
    private function importer(): WhitelistImporter
    {
        $factory = $this->createStub(WhitelistInterfaceFactory::class);
        $factory->method('create')->willReturnCallback(
            fn (): Whitelist => $this->whitelistEntry(['rule_id' => null, 'identifier' => null, 'value' => null, 'store_id' => null, 'area' => null, 'status' => null])
        );

        return new WhitelistImporter(
            new RowReaderPool(['csv' => new CsvRowReader(new Csv(new File())), 'xml' => new XmlRowReader(new DomDocumentFactory())]),
            $factory,
            $this->repository,
            new EntryDataMapper(new StoreIdList()),
            new EntryNormalizer(new SourceValueRules(new HostSourceParser()), new StoreIdList()),
            new StoreIdList()
        );
    }

    /**
     * Temporary file with the contents, removed after the test.
     *
     * @param string $extension
     * @param string $contents
     * @return string
     */
    private function file(string $extension, string $contents): string
    {
        $path = sys_get_temp_dir() . '/hryvinskyi-csp-import-' . bin2hex(random_bytes(6)) . '.' . $extension;
        file_put_contents($path, $contents);
        $this->files[] = $path;

        return $path;
    }
}
