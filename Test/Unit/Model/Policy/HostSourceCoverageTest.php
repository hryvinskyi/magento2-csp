<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy;

use Hryvinskyi\Csp\Model\Policy\HostSourceCoverage;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use PHPUnit\Framework\TestCase;

class HostSourceCoverageTest extends TestCase
{
    /**
     * @dataProvider cases
     * @param string $wildcard
     * @param string $host
     * @param bool $expected
     */
    public function testCoverage(string $wildcard, string $host, bool $expected): void
    {
        $this->assertSame($expected, (new HostSourceCoverage(new HostSourceParser()))->covers($wildcard, $host));
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function cases(): array
    {
        return [
            'subdomain' => ['*.example.com', 'a.example.com', true],
            'deeper subdomain' => ['*.example.com', 'a.b.example.com', true],
            'narrower wildcard' => ['*.example.com', '*.a.example.com', true],
            'bare parent is not covered' => ['*.example.com', 'example.com', false],
            'same wildcard' => ['*.example.com', '*.example.com', false],
            'look-alike domain' => ['*.example.com', 'aexample.com', false],
            'explicit port not covered' => ['*.example.com', 'a.example.com:8443', false],
            'any port covers port' => ['*.example.com:*', 'a.example.com:8443', true],
            'scheme-less wildcard covers https host' => ['*.example.com', 'https://a.example.com', true],
            'https wildcard does not cover scheme-less host' => ['https://*.example.com', 'a.example.com', false],
            'scheme-less wildcard does not cover http host' => ['*.example.com', 'http://a.example.com', false],
            'websocket not covered by web wildcard' => ['*.example.com', 'wss://a.example.com', false],
            'path below wildcard path' => ['*.example.com/js/', 'a.example.com/js/app.js', true],
            'host without path not covered by path wildcard' => ['*.example.com/js/', 'a.example.com', false],
            'wildcard without path covers paths' => ['*.example.com', 'a.example.com/js/app.js', true],
            'not a wildcard' => ['example.com', 'a.example.com', false],
        ];
    }
}
