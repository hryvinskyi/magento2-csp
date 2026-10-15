<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\ScriptHash;

use Hryvinskyi\Csp\Model\ScriptHash\InlineScriptExtractor;
use PHPUnit\Framework\TestCase;

class InlineScriptExtractorTest extends TestCase
{
    public function testKeepsOnlyScriptsAHashCanAllow(): void
    {
        $html = <<<'HTML'
            <p>Text</p>
            <script>window.a = 1;</script>
            <script type="text/javascript" defer>window.b = 2;</script>
            <script type=module>import x from "./x.js";</script>
            <script src="https://cdn.example.org/a.js"></script>
            <script nonce="abc">window.c = 3;</script>
            <script type="text/x-magento-init">{"*": {}}</script>
            <script type="application/ld+json">{"@type": "Thing"}</script>
            <script>   </script>
            <script>var key = "FORMKEY123";</script>
            <script data-src-like="x">window.d = 4;</script>
            HTML;

        $result = (new InlineScriptExtractor())->extract($html, ['FORMKEY123']);

        $this->assertSame(
            ['window.a = 1;', 'window.b = 2;', 'import x from "./x.js";', 'window.d = 4;'],
            $result['scripts']
        );
        $this->assertSame(
            [
                'external script',
                'script with a nonce',
                'script of type "text/x-magento-init"',
                'script of type "application/ld+json"',
                'empty script',
                'script with a per-request value such as the form key',
            ],
            $result['skipped']
        );
    }
}
