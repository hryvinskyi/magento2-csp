<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Store;

use Hryvinskyi\Csp\Model\Store\UrlHost;
use PHPUnit\Framework\TestCase;

class UrlHostTest extends TestCase
{
    /**
     * @dataProvider urls
     * @param string $url
     * @param string|null $expected
     */
    public function testNamesTheHostAsACspHostSource(string $url, ?string $expected): void
    {
        $this->assertSame($expected, (new UrlHost())->of($url));
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function urls(): array
    {
        return [
            'lower-cased, default port dropped' => ['https://Shop.Example.com:443/a', 'shop.example.com'],
            'other port kept' => ['https://shop.example.com:80/', 'shop.example.com:80'],
            'punycode name' => ['https://xn--bcher-kva.example/', 'xn--bcher-kva.example'],
            'IPv4 address' => ['http://192.0.2.1:8080/', '192.0.2.1:8080'],
            'bracketed IPv6 address' => ['https://[2001:DB8::1]:8443/', '[2001:db8::1]:8443'],
            'keyword without a host' => ['inline', null],
            'wildcard' => ['https://*.example.com/a.js', null],
            'markup' => ['https://<svg onload=alert(1)>', null],
            'space' => ['https://exa mple.com/', null],
            'underscore' => ['https://a_b.example.com/', null],
            'empty label' => ['https://a..example.com/', null],
            'percent-encoded' => ['https://%3csvg%3e/', null],
            'unterminated IPv6 address' => ['https://[::1', null],
            'bracketed name' => ['https://[example.com]/', null],
        ];
    }

    public function testTellsAUrlWithAnInvalidHostFromOneWithoutAHost(): void
    {
        $urlHost = new UrlHost();

        $this->assertTrue($urlHost->names('https://*.example.com/'));
        $this->assertFalse($urlHost->names('data:image/png;base64,AAAA'));
        $this->assertFalse($urlHost->names('inline'));
    }
}
