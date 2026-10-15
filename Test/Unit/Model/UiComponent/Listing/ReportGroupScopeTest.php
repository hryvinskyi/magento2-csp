<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);


namespace Hryvinskyi\Csp\Test\Unit\Model\UiComponent\Listing;

use Hryvinskyi\Csp\Model\ResourceModel\Report\Collection;
use Hryvinskyi\Csp\Model\ResourceModel\Report\CollectionFactory;
use Hryvinskyi\Csp\Model\UiComponent\Listing\ReportGroupScope;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class ReportGroupScopeTest extends TestCase
{
    /**
     * @dataProvider pageParameters
     * @param mixed $pageGroupId
     * @param string $expected
     */
    public function testMassActionUrlNamesTheGroupOfTheGridPageOrAll(mixed $pageGroupId, string $expected): void
    {
        $this->assertSame($expected, $this->scope($this->createStub(Collection::class))->urlValue($pageGroupId));
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function pageParameters(): array
    {
        return [
            'group' => ['5', '5'],
            'no group' => [null, 'all'],
            'zero' => ['0', 'all'],
            'not a number' => ['5 OR 1', 'all'],
            'array' => [['5'], 'all'],
        ];
    }

    public function testMassActionRequestIsLimitedToTheGroupItNames(): void
    {
        $scope = $this->scope($this->createStub(Collection::class));

        $this->assertSame(7, $scope->requestedGroupId($this->request('7')));
        $this->assertNull($scope->requestedGroupId($this->request('all')));
    }

    /**
     * @dataProvider invalidScopes
     * @param mixed $scopeValue
     */
    public function testMassActionRequestWithoutAValidScopeIsRefused(mixed $scopeValue): void
    {
        $this->expectException(LocalizedException::class);

        $this->scope($this->createStub(Collection::class))->requestedGroupId($this->request($scopeValue));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidScopes(): array
    {
        return [
            'missing' => [null],
            'empty' => [''],
            'negative' => ['-3'],
            'not a number' => ['abc'],
        ];
    }

    public function testReportsOfAGroupAreFilteredByIt(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('addFieldToFilter')->with('group_id', ['eq' => 7])->willReturnSelf();

        $this->assertSame($collection, $this->scope($collection)->reports(7));
    }

    public function testReportsOfEveryGroupAreNotFiltered(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->never())->method('addFieldToFilter');

        $this->assertSame($collection, $this->scope($collection)->reports(null));
    }

    /**
     * @param Collection $collection
     * @return ReportGroupScope
     */
    private function scope(Collection $collection): ReportGroupScope
    {
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        return new ReportGroupScope($factory);
    }

    /**
     * @param mixed $scopeValue
     * @return RequestInterface
     */
    private function request(mixed $scopeValue): RequestInterface
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnMap([[ReportGroupScope::PARAM, null, $scopeValue]]);

        return $request;
    }
}
