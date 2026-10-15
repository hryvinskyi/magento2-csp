<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Report;

use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Model\ReportGroupSearchResults;
use Hryvinskyi\Csp\Model\ReportSearchResults;
use PHPUnit\Framework\TestCase;

class SearchResultsTest extends TestCase
{
    public function testHoldNoItemsUntilTheyAreSet(): void
    {
        $this->assertSame([], (new ReportSearchResults())->getItems());
        $this->assertSame([], (new ReportGroupSearchResults())->getItems());
    }

    public function testHoldTheItemsSet(): void
    {
        $report = $this->createStub(ReportInterface::class);
        $group = $this->createStub(ReportGroupInterface::class);

        $this->assertSame([$report], (new ReportSearchResults())->setItems([$report])->getItems());
        $this->assertSame([$group], (new ReportGroupSearchResults())->setItems([$group])->getItems());
    }
}
