<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Report;

use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Report\Violation;
use Hryvinskyi\Csp\Model\Report\ViolationClassifier;
use Hryvinskyi\Csp\Model\Report\ViolationSanitizer;
use Hryvinskyi\Csp\Model\Store\UrlHost;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;
use Hryvinskyi\Csp\Test\Unit\Support\RealViolationFactory;
use PHPUnit\Framework\TestCase;

class ViolationClassifierTest extends TestCase
{
    /**
     * @dataProvider violations
     * @param string $directive
     * @param string $blocked
     * @param array{policy: string, value: string}|null $expected
     */
    public function testGroupsUnderTheGoverningDirectiveAndBlockedValue(string $directive, string $blocked, ?array $expected): void
    {
        $classifier = new ViolationClassifier(new WhitelistableDirectives(new DirectiveCatalog()), new UrlHost());

        $this->assertSame($expected, $classifier->classify(new Violation('https://shop.example.com/', $blocked, $directive)));
    }

    /**
     * @return array<string, array{string, string, array{policy: string, value: string}|null}>
     */
    public static function violations(): array
    {
        return [
            'script element' => ['script-src-elem', 'https://cdn.example.org/a.js', ['policy' => 'script-src', 'value' => 'cdn.example.org']],
            'event handler' => ['script-src-attr', 'inline', ['policy' => 'script-src', 'value' => 'inline']],
            'worker' => ['worker-src', 'blob:https://shop.example.com/1', ['policy' => 'child-src', 'value' => 'blob:']],
            'image with port' => ['img-src', 'http://img.example.org:8080/a.png', ['policy' => 'img-src', 'value' => 'img.example.org:8080']],
            'default port dropped' => ['img-src', 'https://img.example.org:443/a.png', ['policy' => 'img-src', 'value' => 'img.example.org']],
            'secure websocket' => ['connect-src', 'wss://ws.example.org/socket', ['policy' => 'connect-src', 'value' => 'wss://ws.example.org']],
            'bare data' => ['font-src', 'data', ['policy' => 'font-src', 'value' => 'data:']],
            'eval' => ['script-src', 'eval', ['policy' => 'script-src', 'value' => 'eval']],
            'not rendered by Magento' => ['navigate-to', 'https://example.org/', null],
            'self keyword' => ['img-src', 'self', null],
            'empty' => ['img-src', '', null],
        ];
    }

    public function testSanitizesUrlsAndLengths(): void
    {
        $violation = (new ViolationSanitizer(new RealViolationFactory()))->sanitize(new Violation(
            'https://shop.example.com/checkout?token=secret#step',
            'https://cdn.example.org/a.js?v=1',
            'script-src',
            '',
            '',
            '',
            'https://shop.example.com/?q=' . str_repeat('ä', 300),
            "\u{1F600}" . str_repeat('x', 300),
            200,
            'https://shop.example.com/a.js#L1'
        ));

        $this->assertSame('https://shop.example.com/checkout', $violation->getDocumentUri());
        $this->assertSame('https://cdn.example.org/a.js', $violation->getBlockedUri());
        $this->assertSame('https://shop.example.com/', $violation->getReferrer());
        $this->assertSame('https://shop.example.com/a.js', $violation->getSourceFile());
        $this->assertSame(255, strlen($violation->getScriptSample()));
        $this->assertStringStartsWith("\u{FFFD}", $violation->getScriptSample());
        $this->assertTrue(mb_check_encoding($violation->getScriptSample(), 'UTF-8'));
    }
}
