<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\ScriptHash;

use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Hryvinskyi\Csp\Model\CspHashGenerator;
use Hryvinskyi\Csp\Model\ScriptHash\ScriptHashWhitelister;
use Hryvinskyi\Csp\Model\Whitelist;
use Hryvinskyi\Csp\Test\Unit\Support\CreatesWhitelistEntries;
use Hryvinskyi\Csp\Test\Unit\Support\InMemoryWhitelistRepository;
use PHPUnit\Framework\TestCase;

class ScriptHashWhitelisterTest extends TestCase
{
    use CreatesWhitelistEntries;

    public function testCreatesThenExtendsTheHashEntryPerStore(): void
    {
        $repository = new InMemoryWhitelistRepository();
        $factory = $this->createStub(WhitelistInterfaceFactory::class);
        $factory->method('create')->willReturnCallback(fn (): Whitelist => $this->whitelistEntry(['rule_id' => null]));
        $whitelister = new ScriptHashWhitelister(new CspHashGenerator(), $repository, $factory);
        $script = 'window.a = 1;';

        $this->assertSame(
            [ScriptHashWhitelister::CREATED, ScriptHashWhitelister::EXISTS, ScriptHashWhitelister::EXTENDED],
            [$whitelister->whitelist($script, 1), $whitelister->whitelist($script, 1), $whitelister->whitelist($script, 2)]
        );
        $this->assertCount(1, $repository->entries);
        $entry = array_values($repository->entries)[0];
        $this->assertSame(
            ['script-src', 'hash', 'sha256', base64_encode(hash('sha256', $script, true)), [1, 2], 'frontend', $script],
            [
                $entry->getPolicy(), $entry->getValueType(), $entry->getValueAlgorithm(), $entry->getValue(),
                $entry->getStoreIds(), $entry->getArea(), $entry->getScriptContent(),
            ]
        );
    }
}
