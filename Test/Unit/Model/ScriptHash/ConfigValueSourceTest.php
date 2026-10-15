<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);


namespace Hryvinskyi\Csp\Test\Unit\Model\ScriptHash;

use Hryvinskyi\Csp\Model\ScriptHash\ConfigValueSource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class ConfigValueSourceTest extends TestCase
{
    public function testReadsTheScriptsOfTheConfigurationTheStoreViewApplies(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('', 'store', 3)
            ->willReturn([
                'design' => [
                    'head' => ['includes' => '<SCRIPT>window.store = 3;</SCRIPT>', 'title_prefix' => 'Shop'],
                    'footer' => ['absolute_footer' => '<script src="https://cdn.example.org/a.js"></script>'],
                ],
                'general' => ['locale' => ['code' => 'en_US']],
                'flags' => ['enabled' => 1, 'empty' => null],
            ]);

        $contents = (new ConfigValueSource($scopeConfig))->contents(3);

        $this->assertSame(
            [
                ['label' => 'Configuration "design/head/includes"', 'html' => '<SCRIPT>window.store = 3;</SCRIPT>', 'warning' => null],
                [
                    'label' => 'Configuration "design/footer/absolute_footer"',
                    'html' => '<script src="https://cdn.example.org/a.js"></script>',
                    'warning' => null,
                ],
            ],
            $contents
        );
    }

    public function testAStoreViewWithoutConfigurationHasNoContent(): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn(null);

        $this->assertSame([], (new ConfigValueSource($scopeConfig))->contents(1));
    }
}
