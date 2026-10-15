<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);


namespace Hryvinskyi\Csp\Test\Unit\Model\UiComponent\Listing\Column;

use Hryvinskyi\Csp\Model\UiComponent\Listing\Column\StoreView;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\System\Store as SystemStore;
use PHPUnit\Framework\TestCase;

class StoreViewTest extends TestCase
{
    public function testStoreZeroFromTheDatabaseShowsAllStoreViews(): void
    {
        $systemStore = $this->createStub(SystemStore::class);
        $systemStore->method('getStoresStructure')->willReturn([
            ['label' => 'Main Website', 'children' => [['label' => 'Main Store', 'children' => [['label' => 'Default Store View']]]]],
        ]);
        $column = new StoreView(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $systemStore,
            new Escaper(),
            [],
            ['name' => 'store_id']
        );

        $result = $column->prepareDataSource(['data' => ['items' => [
            ['group_id' => '1', 'store_id' => '0'],
            ['group_id' => '2', 'store_id' => 0],
            ['group_id' => '3', 'store_id' => '1'],
        ]]]);

        $data = $result['data'] ?? null;
        $items = is_array($data) ? ($data['items'] ?? null) : null;
        $this->assertIsArray($items);
        $cells = array_map($this->cell(...), array_values($items));
        $this->assertSame('All Store Views', $cells[0]);
        $this->assertSame('All Store Views', $cells[1]);
        $this->assertStringContainsString('Default Store View', $cells[2]);
    }

    /**
     * Store cell of a grid row as text.
     *
     * @param mixed $item
     * @return string
     */
    private function cell(mixed $item): string
    {
        $cell = is_array($item) ? ($item['store_id'] ?? null) : null;

        return is_scalar($cell) || $cell instanceof \Stringable ? (string)$cell : '';
    }
}
