<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist;

use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;
use PHPUnit\Framework\TestCase;

class WhitelistableDirectivesTest extends TestCase
{
    /**
     * @dataProvider effectiveDirectives
     * @param string $effective
     * @param string|null $expected
     */
    public function testGoverningDirective(string $effective, ?string $expected): void
    {
        $this->assertSame($expected, (new WhitelistableDirectives(new DirectiveCatalog()))->governing($effective));
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function effectiveDirectives(): array
    {
        return [
            'script element' => ['script-src-elem', 'script-src'],
            'script attribute' => ['script-src-attr', 'script-src'],
            'style element' => ['style-src-elem', 'style-src'],
            'style attribute' => ['style-src-attr', 'style-src'],
            'worker' => ['worker-src', 'child-src'],
            'fenced frame' => ['fenced-frame-src', 'frame-src'],
            'itself' => ['img-src', 'img-src'],
            'case and spaces' => [' Script-Src-Elem ', 'script-src'],
            'source list without fallback' => ['frame-ancestors', 'frame-ancestors'],
            'default' => ['default-src', 'default-src'],
            'not rendered by Magento' => ['navigate-to', null],
            'unknown' => ['prefetch-src', null],
        ];
    }

    public function testSubDirectivesAreNotWhitelistable(): void
    {
        $directives = new WhitelistableDirectives(new DirectiveCatalog());

        $this->assertTrue($directives->isWhitelistable('script-src'));
        $this->assertFalse($directives->isWhitelistable('script-src-elem'));
        $this->assertFalse($directives->isWhitelistable('worker-src'));
        $this->assertContains('frame-ancestors', $directives->all());
    }
}
