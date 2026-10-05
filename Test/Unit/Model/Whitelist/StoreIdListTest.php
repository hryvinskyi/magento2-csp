<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist;

use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use PHPUnit\Framework\TestCase;

class StoreIdListTest extends TestCase
{
    /**
     * @dataProvider lists
     * @param string $value
     * @param list<int> $parsed
     * @param list<int> $normalized
     */
    public function testParsesAndNormalizes(string $value, array $parsed, array $normalized): void
    {
        $list = new StoreIdList();

        $this->assertSame($parsed, $list->parse($value));
        $this->assertSame($normalized, $list->normalize($list->parse($value)));
    }

    /**
     * @return array<string, array{string, list<int>, list<int>}>
     */
    public static function lists(): array
    {
        return [
            'all stores' => ['0', [0], [0]],
            'all stores among others' => ['2,0,1', [2, 0, 1], [0]],
            'stores sorted' => [' 3, 1 ,3', [3, 1], [1, 3]],
            'garbage dropped' => ['1,,abc,-2,2.5, 4', [1, 4], [1, 4]],
            'empty' => ['', [], [0]],
        ];
    }
}
