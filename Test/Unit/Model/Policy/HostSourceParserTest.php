<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy;

use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use PHPUnit\Framework\TestCase;

class HostSourceParserTest extends TestCase
{
    private HostSourceParser $parser;

    protected function setUp(): void
    {
        $this->parser = new HostSourceParser();
    }

    public function testParsesEveryPart(): void
    {
        $this->assertSame(
            ['scheme' => 'https', 'host' => 'cdn.example.com', 'port' => '8443', 'path' => '/Lib/x.js'],
            $this->parser->parse('HTTPS://CDN.Example.com:8443/Lib/x.js')
        );
    }

    public function testParsesBareAndWildcardHosts(): void
    {
        $this->assertSame(
            ['scheme' => null, 'host' => 'example.com', 'port' => null, 'path' => null],
            $this->parser->parse('example.com')
        );
        $this->assertSame(
            ['scheme' => 'wss', 'host' => '*.example.com', 'port' => '*', 'path' => null],
            $this->parser->parse('wss://*.example.com:*')
        );
    }

    /**
     * @dataProvider invalidTokens
     * @param string $token
     */
    public function testRejectsTokensThatAreNotHostSources(string $token): void
    {
        $this->assertNull($this->parser->parse($token));
        $this->assertFalse($this->parser->isHostSource($token));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidTokens(): array
    {
        return [
            'keyword' => ["'self'"],
            'space' => ['example.com evil.com'],
            'semicolon' => ['example.com;script-src'],
            'comma' => ['a.com,b.com'],
            'quote' => ["example.com'"],
            'line break' => ["example.com\r\nX-Header: 1"],
            'wildcard in the middle' => ['cdn.*.example.com'],
            'underscore' => ['my_host.example.com'],
            'empty' => [''],
        ];
    }

    public function testComposeRebuildsTheToken(): void
    {
        $parts = $this->parser->parse('wss://ws.example.com:443/socket');
        $this->assertNotNull($parts);

        $this->assertSame('wss://ws.example.com:443/socket', $this->parser->compose($parts));
        $this->assertFalse($this->parser->isWildcardHost($parts));
    }
}
