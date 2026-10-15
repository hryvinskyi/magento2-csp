<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);


namespace Hryvinskyi\Csp\Test\Unit\Model\UiComponent\Listing;

use Hryvinskyi\Csp\Model\ResourceModel\Report\CollectionFactory;
use Hryvinskyi\Csp\Model\UiComponent\Listing\ReportGroupScope;
use Hryvinskyi\Csp\Model\UiComponent\Listing\ReportGroupScopedAction;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use PHPUnit\Framework\TestCase;

class ReportGroupScopedActionTest extends TestCase
{
    /**
     * @dataProvider pages
     * @param string|null $pageGroupId
     * @param string $expectedScope
     */
    public function testActionUrlNamesTheGroupOfTheGridPage(?string $pageGroupId, string $expectedScope): void
    {
        $context = $this->createStub(ContextInterface::class);
        $context->method('getProcessor')->willReturn($this->createStub(Processor::class));
        $context->method('getRequestParam')->willReturnMap([['group_id', null, $pageGroupId]]);
        $context->method('getUrl')->willReturnCallback(
            static fn (string $route, array $params): string => $route . '?' . http_build_query($params)
        );
        $action = new ReportGroupScopedAction(
            $context,
            new ReportGroupScope($this->createStub(CollectionFactory::class)),
            [],
            ['name' => 'delete', 'config' => ['type' => 'delete', 'urlPath' => 'hryvinskyi_csp/report/massDelete']]
        );

        $action->prepare();

        $this->assertSame(
            'hryvinskyi_csp/report/massDelete?report_group=' . $expectedScope,
            $action->getConfiguration()['url'] ?? null
        );
    }

    /**
     * @return array<string, array{string|null, string}>
     */
    public static function pages(): array
    {
        return [
            'grid of one group' => ['12', '12'],
            'grid of every group' => [null, 'all'],
        ];
    }
}
