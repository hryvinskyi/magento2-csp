<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Conversion;

use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Model\Conversion\ConversionOutcome;
use Hryvinskyi\Csp\Model\Conversion\ConversionResult;
use Hryvinskyi\Csp\Model\Conversion\ConversionResultFactory;
use Hryvinskyi\Csp\Model\Conversion\ReportGroupConverter;
use Hryvinskyi\Csp\Model\Conversion\UnsafeSourceRules;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\HostSourceCoverage;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\ReportGroup;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\Collection;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\CollectionFactory;
use Hryvinskyi\Csp\Model\Whitelist;
use Hryvinskyi\Csp\Model\Whitelist\RedundancyRules;
use Hryvinskyi\Csp\Model\Whitelist\SourceValueRules;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;
use Hryvinskyi\Csp\Test\Unit\Support\CreatesWhitelistEntries;
use Hryvinskyi\Csp\Test\Unit\Support\InMemoryWhitelistRepository;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class ReportGroupConverterTest extends TestCase
{
    use CreatesWhitelistEntries;

    private InMemoryWhitelistRepository $whitelist;

    /**
     * @var list<ReportGroupInterface>
     */
    private array $deletedGroups = [];

    protected function setUp(): void
    {
        $this->whitelist = new InMemoryWhitelistRepository();
        $this->deletedGroups = [];
    }

    public function testCreatesAnEntryForTheGroupsStoreAndAreaAndRemovesTheGroup(): void
    {
        $group = $this->group('script-src', 'cdn.example.org', 2, 'frontend');

        $result = $this->converter()->convert($group);

        $this->assertSame(ConversionOutcome::Created, $result->outcome);
        $entry = $this->whitelist->getById((int)$result->entryId);
        $this->assertSame(
            ['script-src', 'host', 'cdn.example.org', [2], 'frontend', 1],
            [$entry->getPolicy(), $entry->getValueType(), $entry->getValue(), $entry->getStoreIds(), $entry->getArea(), $entry->getStatus()]
        );
        $this->assertSame([$group], $this->deletedGroups);
    }

    public function testExtendsTheEntryWithTheSameKeyToTheGroupsStore(): void
    {
        $this->whitelist->save($this->whitelistEntry(['rule_id' => 7, 'value' => 'cdn.example.org', 'store_id' => [1], 'area' => 'frontend']));

        $result = $this->converter()->convert($this->group('script-src', 'cdn.example.org', 2, 'frontend'));

        $this->assertSame([ConversionOutcome::StoresExtended, 7], [$result->outcome, $result->entryId]);
        $this->assertSame([1, 2], $this->whitelist->getById(7)->getStoreIds());
        $this->assertCount(1, $this->whitelist->entries);
    }

    public function testKeepsTheGroupWhenTheMatchingEntryIsDisabled(): void
    {
        $this->whitelist->save($this->whitelistEntry(['rule_id' => 7, 'value' => 'cdn.example.org', 'area' => 'frontend', 'status' => 0]));

        $result = $this->converter()->convert($this->group('script-src', 'cdn.example.org', 2, 'frontend'));

        $this->assertSame(ConversionOutcome::ExistsDisabled, $result->outcome);
        $this->assertSame(0, $this->whitelist->getById(7)->getStatus());
        $this->assertSame([], $this->deletedGroups);
    }

    public function testAddsNothingWhenAnEnabledEntryAlreadyAllowsTheValue(): void
    {
        $enabled = [
            $this->whitelistEntry(['rule_id' => 3, 'value' => '*.example.org', 'store_id' => [0], 'area' => 'all']),
        ];

        $result = $this->converter($enabled)->convert($this->group('script-src', 'cdn.example.org', 2, 'frontend'));

        $this->assertSame(ConversionOutcome::CoveredByWildcard, $result->outcome);
        $this->assertSame([], $this->whitelist->entries);
        $this->assertCount(1, $this->deletedGroups);
    }

    /**
     * @dataProvider refusedGroups
     * @param string $policy
     * @param string $value
     */
    public function testRefusesUnsafeValuesAndKeepsTheGroup(string $policy, string $value): void
    {
        $result = $this->converter()->convert($this->group($policy, $value, 1, 'frontend'));

        $this->assertSame(ConversionOutcome::Refused, $result->outcome);
        $this->assertNotNull($result->refusal);
        $this->assertSame([], $this->whitelist->entries);
        $this->assertSame([], $this->deletedGroups);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function refusedGroups(): array
    {
        return [
            'inline script' => ['script-src', 'inline'],
            'eval' => ['script-src', 'eval'],
            'legacy keyword value' => ['style-src', 'unsafe-inline'],
            'every host' => ['img-src', '*'],
            'every https host' => ['img-src', 'https:'],
            'data in scripts' => ['script-src', 'data:'],
            'blob in frames' => ['frame-src', 'blob:'],
            'sub-directive' => ['script-src-elem', 'cdn.example.org'],
            'not a source' => ['img-src', 'a b'],
        ];
    }

    public function testAllowsDataForImages(): void
    {
        $result = $this->converter()->convert($this->group('img-src', 'data:', 1, 'frontend'));

        $this->assertSame(ConversionOutcome::Created, $result->outcome);
    }

    /**
     * @param list<Whitelist> $enabledEntries
     * @return ReportGroupConverter
     */
    private function converter(array $enabledEntries = []): ReportGroupConverter
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('addActiveFilter')->willReturnSelf();
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getItems')->willReturn($enabledEntries);
        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);
        $whitelistFactory = $this->createStub(WhitelistInterfaceFactory::class);
        $whitelistFactory->method('create')->willReturnCallback(fn (): Whitelist => $this->whitelistEntry(['rule_id' => null]));
        $groups = $this->createStub(ReportGroupRepositoryInterface::class);
        $groups->method('delete')->willReturnCallback(function (ReportGroupInterface $group): bool {
            $this->deletedGroups[] = $group;

            return true;
        });
        $resultFactory = $this->createStub(ConversionResultFactory::class);
        $resultFactory->method('create')->willReturnCallback(static function (array $data): ConversionResult {
            $outcome = $data['outcome'] ?? null;
            $entryId = $data['entryId'] ?? null;
            $refusal = $data['refusal'] ?? null;
            if (!$outcome instanceof ConversionOutcome) {
                throw new \LogicException('No outcome given.');
            }

            return new ConversionResult($outcome, is_int($entryId) ? $entryId : null, $refusal instanceof Phrase ? $refusal : null);
        });
        $parser = new HostSourceParser();
        $directives = new WhitelistableDirectives(new DirectiveCatalog());

        return new ReportGroupConverter(
            new UnsafeSourceRules($directives, new SourceValueRules($parser)),
            new SourceValueRules($parser),
            new RedundancyRules(new HostSourceCoverage($parser)),
            $collectionFactory,
            $this->whitelist,
            $whitelistFactory,
            $groups,
            $resultFactory
        );
    }

    /**
     * @param string $policy
     * @param string $value
     * @param int $storeId
     * @param string $area
     * @return ReportGroupInterface
     */
    private function group(string $policy, string $value, int $storeId, string $area): ReportGroupInterface
    {
        $group = (new ObjectManager($this))->getObject(ReportGroup::class);
        if (!$group instanceof ReportGroup) {
            throw new \LogicException('No report group was created.');
        }
        $group->setData(['group_id' => 5, 'policy' => $policy, 'value' => $value, 'store_id' => $storeId, 'area' => $area, 'status' => 0]);

        return $group;
    }
}
