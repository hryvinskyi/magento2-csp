<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist;

use Hryvinskyi\Csp\Model\Whitelist\EntryDataMapper;
use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use Hryvinskyi\Csp\Test\Unit\Support\CreatesWhitelistEntries;
use PHPUnit\Framework\TestCase;

class EntryDataMapperTest extends TestCase
{
    use CreatesWhitelistEntries;

    public function testCopiesEditableFieldsOnly(): void
    {
        $entry = $this->whitelistEntry(['rule_id' => 7, 'created_at' => '2026-01-01 00:00:00']);

        (new EntryDataMapper(new StoreIdList()))->apply($entry, [
            'rule_id' => 99,
            'created_at' => '1999-01-01 00:00:00',
            'identifier' => 'Fonts',
            'policy' => 'font-src',
            'value' => 'fonts.example.com',
            'area' => 'frontend',
            'status' => '0',
            'store_id' => ['2', '1'],
            'unknown' => 'x',
        ]);

        $this->assertSame(
            [7, '2026-01-01 00:00:00', 'Fonts', 'font-src', 'fonts.example.com', 'frontend', 0, [2, 1]],
            [
                $entry->getRuleId(), $entry->getCreatedAt(), $entry->getIdentifier(), $entry->getPolicy(),
                $entry->getValue(), $entry->getArea(), $entry->getStatus(), $entry->getStoreIds(),
            ]
        );
        $this->assertNull($entry->getData('unknown'));
    }

    public function testReadsStoresFromCommaSeparatedText(): void
    {
        $entry = $this->whitelistEntry();

        (new EntryDataMapper(new StoreIdList()))->apply($entry, ['store_id' => '3, 4']);

        $this->assertSame([3, 4], $entry->getStoreIds());
    }
}
