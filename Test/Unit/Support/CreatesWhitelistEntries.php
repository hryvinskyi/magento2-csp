<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Support;

use Hryvinskyi\Csp\Model\Whitelist;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Builds whitelist entry models without a database.
 */
trait CreatesWhitelistEntries
{
    /**
     * Entry with the given data on top of a valid storefront host entry.
     *
     * @param array<string, mixed> $data
     * @return Whitelist
     */
    private function whitelistEntry(array $data = []): Whitelist
    {
        $entry = (new ObjectManager($this))->getObject(Whitelist::class);
        if (!$entry instanceof Whitelist) {
            throw new \LogicException('No whitelist entry was created.');
        }
        $entry->setData([
            'identifier' => 'CDN',
            'policy' => 'script-src',
            'value_type' => 'host',
            'value_algorithm' => '',
            'value' => 'cdn.example.com',
            'store_id' => [0],
            'area' => 'all',
            'status' => 1,
            ...$data,
        ]);

        return $entry;
    }
}
