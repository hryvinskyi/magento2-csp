<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);


namespace Hryvinskyi\Csp\Test\Integration\Model\ScriptHash;

use Hryvinskyi\Csp\Model\ScriptHash\ConfigValueSource;
use Hryvinskyi\Csp\Test\Integration\ResolvesServices;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use PHPUnit\Framework\TestCase;

/**
 * Hashes the configuration a store view renders, not every row stored for it.
 *
 * @magentoDbIsolation enabled
 */
class ConfigValueSourceTest extends TestCase
{
    use ResolvesServices;

    private const OVERRIDDEN = 'hryvinskyi_csp_test/scan/overridden';
    private const INHERITED = 'hryvinskyi_csp_test/scan/inherited';

    public function testAStoreValueReplacesTheDefaultAndADefaultValueIsInherited(): void
    {
        $this->storeValue('default', 0, self::OVERRIDDEN, '<script>window.scope = "default";</script>');
        $this->storeValue('stores', 1, self::OVERRIDDEN, '<script>window.scope = "store";</script>');
        $this->storeValue('default', 0, self::INHERITED, '<script>window.inherited = true;</script>');
        $this->service(ReinitableConfigInterface::class)->reinit();

        $found = [];
        foreach ($this->service(ConfigValueSource::class)->contents(1) as $content) {
            $found[$content['label']] = $content['html'];
        }

        $this->assertSame('<script>window.scope = "store";</script>', $found['Configuration "' . self::OVERRIDDEN . '"'] ?? null);
        $this->assertSame('<script>window.inherited = true;</script>', $found['Configuration "' . self::INHERITED . '"'] ?? null);
        $this->assertNotContains('<script>window.scope = "default";</script>', $found);
    }

    /**
     * Drop the configuration loaded from the rows of this test, before they are rolled back.
     */
    protected function tearDown(): void
    {
        $this->service(ReinitableConfigInterface::class)->reinit();
        parent::tearDown();
    }

    /**
     * @param string $scope
     * @param int $scopeId
     * @param string $path
     * @param string $value
     * @return void
     */
    private function storeValue(string $scope, int $scopeId, string $path, string $value): void
    {
        $this->connection()->insertOnDuplicate(
            $this->table('core_config_data'),
            ['scope' => $scope, 'scope_id' => $scopeId, 'path' => $path, 'value' => $value],
            ['value']
        );
    }
}
