<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration\Model\ScriptHash;

use Hryvinskyi\Csp\Model\ScriptHash\StoreContentScanner;
use Hryvinskyi\Csp\Test\Integration\ResolvesServices;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use PHPUnit\Framework\TestCase;

/**
 * Scans rendered CMS content from a console context, which has no area of its own.
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class StoreContentScannerTest extends TestCase
{
    use ResolvesServices;

    public function testHashesTheScriptAsTheStorefrontRendersIt(): void
    {
        $block = $this->service(BlockInterfaceFactory::class)->create();
        $block->setIdentifier('hryvinskyi-csp-scanner-test');
        $block->setTitle('Scanner test');
        $block->setIsActive(true);
        $block->setContent('<script>window.url = "{{store url=""}}";</script>');
        if ($block instanceof \Magento\Framework\DataObject) {
            $block->setData('store_id', [0]);
        }
        $this->service(BlockRepositoryInterface::class)->save($block);

        $entities = $this->service(StoreContentScanner::class)->scan(1, ['block']);

        $scripts = [];
        foreach ($entities as $entity) {
            if (str_contains($entity['label'], 'Scanner test')) {
                $scripts = $entity['scripts'];
            }
        }
        $this->assertCount(1, $scripts);
        $this->assertStringNotContainsString('{{store', $scripts[0]);
        $this->assertStringContainsString('http', $scripts[0]);
    }
}
