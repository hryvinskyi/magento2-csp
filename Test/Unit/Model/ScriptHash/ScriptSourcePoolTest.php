<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\ScriptHash;

use Hryvinskyi\Csp\Model\ScriptHash\ScriptSourceInterface;
use Hryvinskyi\Csp\Model\ScriptHash\ScriptSourcePool;
use PHPUnit\Framework\TestCase;

class ScriptSourcePoolTest extends TestCase
{
    public function testListsCodesInTheConfiguredOrderAndReturnsTheirSources(): void
    {
        $page = $this->createStub(ScriptSourceInterface::class);
        $config = $this->createStub(ScriptSourceInterface::class);
        $pool = new ScriptSourcePool(['page' => $page, 'config' => $config]);

        $this->assertSame(['page', 'config'], $pool->codes());
        $this->assertSame($config, $pool->get('config'));
    }

    public function testRefusesAnUnknownCodeNamingTheKnownOnes(): void
    {
        $pool = new ScriptSourcePool(['page' => $this->createStub(ScriptSourceInterface::class)]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown content type "widget". Use one of: page.');

        $pool->get('widget');
    }
}
