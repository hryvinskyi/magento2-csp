<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist;

use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Whitelist\SourceValueRules;
use PHPUnit\Framework\TestCase;

class SourceValueRulesTest extends TestCase
{
    /**
     * @dataProvider hosts
     * @param string $value
     * @param bool $allowed
     */
    public function testHostValues(string $value, bool $allowed): void
    {
        $this->assertSame($allowed, $this->rules()->hostError($value) === null, $value);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function hosts(): array
    {
        return [
            'host' => ['cdn.example.com', true],
            'host with scheme, port and path' => ['https://cdn.example.com:8443/js/', true],
            'wildcard under a domain' => ['*.example.com', true],
            'scheme source' => ['data:', true],
            'secure websocket scheme' => ['wss:', true],
            'empty' => ['', false],
            'every host' => ['*', false],
            'every https host' => ['https://*', false],
            'top-level wildcard' => ['*.com', false],
            'keyword' => ["'unsafe-inline'", false],
            'nonce' => ["'nonce-abc'", false],
            'two sources' => ['a.example.com b.example.com', false],
            'directive injection' => ['a.example.com;script-src *', false],
            'comma' => ['a.example.com,b.example.com', false],
            'control character' => ["a.example.com\x01", false],
            'not punycode' => ['bücher.example', false],
            'not a host' => ['https://', false],
        ];
    }

    public function testNormalizesHosts(): void
    {
        $rules = $this->rules();

        $this->assertSame('https://cdn.example.com:8443/Path', $rules->normalizeHost('  HTTPS://CDN.Example.com:8443/Path '));
        $this->assertSame('xn--bcher-kva.example', $rules->normalizeHost('bücher.example'));
        $this->assertSame('https://*.xn--bcher-kva.example/a', $rules->normalizeHost('https://*.Bücher.example/a'));
        $this->assertSame('data:', $rules->normalizeHost('DATA:'));
    }

    /**
     * @dataProvider hashes
     * @param string $algorithm
     * @param string $value
     * @param bool $allowed
     */
    public function testHashValues(string $algorithm, string $value, bool $allowed): void
    {
        $this->assertSame($allowed, $this->rules()->hashError($algorithm, $value) === null);
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function hashes(): array
    {
        $sha256 = base64_encode(hash('sha256', 'alert(1)', true));
        $sha384 = base64_encode(hash('sha384', 'alert(1)', true));

        return [
            'sha256' => ['sha256', $sha256, true],
            'sha384' => ['sha384', $sha384, true],
            'length of another algorithm' => ['sha256', $sha384, false],
            'unknown algorithm' => ['md5', $sha256, false],
            'not base64' => ['sha256', str_repeat('!', 44), false],
        ];
    }

    public function testNormalizesHashes(): void
    {
        $this->assertSame('abc=', $this->rules()->normalizeHash(" 'sha256-abc=' "));
    }

    /**
     * @return SourceValueRules
     */
    private function rules(): SourceValueRules
    {
        return new SourceValueRules(new HostSourceParser());
    }
}
