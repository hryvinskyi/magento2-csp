<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model;

use Hryvinskyi\Csp\Model\DomainMatcher;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Csp\Model\DomainMatcher
 */
class DomainMatcherTest extends TestCase
{
    private DomainMatcher $domainMatcher;

    protected function setUp(): void
    {
        $this->domainMatcher = new DomainMatcher();
    }

    // ==================== isWildcard Tests ====================

    /**
     * @dataProvider isWildcardDataProvider
     */
    public function testIsWildcard(string $value, bool $expected): void
    {
        $this->assertSame($expected, $this->domainMatcher->isWildcard($value));
    }

    /**
     * @return array<string, array{value: string, expected: bool}>
     */
    public static function isWildcardDataProvider(): array
    {
        return [
            'wildcard domain' => [
                'value' => '*.example.com',
                'expected' => true,
            ],
            'wildcard subdomain' => [
                'value' => '*.sub.example.com',
                'expected' => true,
            ],
            'regular domain' => [
                'value' => 'www.example.com',
                'expected' => false,
            ],
            'domain with asterisk in middle' => [
                'value' => 'www.*.example.com',
                'expected' => false,
            ],
            'universal wildcard' => [
                'value' => '*',
                'expected' => false,
            ],
            'empty string' => [
                'value' => '',
                'expected' => false,
            ],
            'keyword self' => [
                'value' => "'self'",
                'expected' => false,
            ],
            'scheme source' => [
                'value' => 'https:',
                'expected' => false,
            ],
        ];
    }

    // ==================== domainMatchesWildcard Tests ====================

    /**
     * @dataProvider domainMatchesWildcardDataProvider
     */
    public function testDomainMatchesWildcard(string $domain, string $wildcard, bool $expected): void
    {
        $this->assertSame($expected, $this->domainMatcher->domainMatchesWildcard($domain, $wildcard));
    }

    /**
     * @return array<string, array{domain: string, wildcard: string, expected: bool}>
     */
    public static function domainMatchesWildcardDataProvider(): array
    {
        return [
            'subdomain matches wildcard' => [
                'domain' => 'www.example.com',
                'wildcard' => '*.example.com',
                'expected' => true,
            ],
            'deep subdomain matches wildcard' => [
                'domain' => 'api.v1.example.com',
                'wildcard' => '*.example.com',
                'expected' => true,
            ],
            'base domain does not match wildcard' => [
                'domain' => 'example.com',
                'wildcard' => '*.example.com',
                'expected' => false,
            ],
            'different domain does not match' => [
                'domain' => 'www.other.com',
                'wildcard' => '*.example.com',
                'expected' => false,
            ],
            'case insensitive match' => [
                'domain' => 'WWW.EXAMPLE.COM',
                'wildcard' => '*.example.com',
                'expected' => true,
            ],
            'case insensitive wildcard' => [
                'domain' => 'www.example.com',
                'wildcard' => '*.EXAMPLE.COM',
                'expected' => true,
            ],
            'partial domain name does not match' => [
                'domain' => 'notexample.com',
                'wildcard' => '*.example.com',
                'expected' => false,
            ],
            'subdomain of subdomain matches' => [
                'domain' => 'www.sub.example.com',
                'wildcard' => '*.example.com',
                'expected' => true,
            ],
            'domain with protocol stripped' => [
                'domain' => 'https://www.example.com',
                'wildcard' => '*.example.com',
                'expected' => true,
            ],
            'domain with port stripped' => [
                'domain' => 'www.example.com:8080',
                'wildcard' => '*.example.com',
                'expected' => true,
            ],
            'domain with path stripped' => [
                'domain' => 'www.example.com/path/to/resource',
                'wildcard' => '*.example.com',
                'expected' => true,
            ],
            'non-wildcard pattern returns false' => [
                'domain' => 'www.example.com',
                'wildcard' => 'www.example.com',
                'expected' => false,
            ],
            'google analytics subdomain' => [
                'domain' => 'www.google-analytics.com',
                'wildcard' => '*.google-analytics.com',
                'expected' => true,
            ],
            'doubleclick subdomain' => [
                'domain' => 'googleads.g.doubleclick.net',
                'wildcard' => '*.doubleclick.net',
                'expected' => true,
            ],
            'doubleclick nested wildcard' => [
                'domain' => 'bid.g.doubleclick.net',
                'wildcard' => '*.g.doubleclick.net',
                'expected' => true,
            ],
        ];
    }

    // ==================== isWildcardCoveredByBroader Tests ====================

    /**
     * @dataProvider isWildcardCoveredByBroaderDataProvider
     */
    public function testIsWildcardCoveredByBroader(string $wildcard, string $broaderWildcard, bool $expected): void
    {
        $this->assertSame($expected, $this->domainMatcher->isWildcardCoveredByBroader($wildcard, $broaderWildcard));
    }

    /**
     * @return array<string, array{wildcard: string, broaderWildcard: string, expected: bool}>
     */
    public static function isWildcardCoveredByBroaderDataProvider(): array
    {
        return [
            'subdomain wildcard covered by parent wildcard' => [
                'wildcard' => '*.sub.example.com',
                'broaderWildcard' => '*.example.com',
                'expected' => true,
            ],
            'deep subdomain wildcard covered' => [
                'wildcard' => '*.a.b.example.com',
                'broaderWildcard' => '*.example.com',
                'expected' => true,
            ],
            'same level wildcards not covered' => [
                'wildcard' => '*.example.com',
                'broaderWildcard' => '*.other.com',
                'expected' => false,
            ],
            'parent cannot be covered by child' => [
                'wildcard' => '*.example.com',
                'broaderWildcard' => '*.sub.example.com',
                'expected' => false,
            ],
            'same wildcard not covered by itself' => [
                'wildcard' => '*.example.com',
                'broaderWildcard' => '*.example.com',
                'expected' => false,
            ],
            'case insensitive comparison' => [
                'wildcard' => '*.SUB.EXAMPLE.COM',
                'broaderWildcard' => '*.example.com',
                'expected' => true,
            ],
            'doubleclick nested wildcard' => [
                'wildcard' => '*.g.doubleclick.net',
                'broaderWildcard' => '*.doubleclick.net',
                'expected' => true,
            ],
            'non-wildcard first param returns false' => [
                'wildcard' => 'www.example.com',
                'broaderWildcard' => '*.example.com',
                'expected' => false,
            ],
            'non-wildcard second param returns false' => [
                'wildcard' => '*.sub.example.com',
                'broaderWildcard' => 'example.com',
                'expected' => false,
            ],
        ];
    }

    // ==================== extractDomain Tests ====================

    /**
     * @dataProvider extractDomainDataProvider
     */
    public function testExtractDomain(string $host, string $expected): void
    {
        $this->assertSame($expected, $this->domainMatcher->extractDomain($host));
    }

    /**
     * @return array<string, array{host: string, expected: string}>
     */
    public static function extractDomainDataProvider(): array
    {
        return [
            'plain domain' => [
                'host' => 'www.example.com',
                'expected' => 'www.example.com',
            ],
            'domain with https protocol' => [
                'host' => 'https://www.example.com',
                'expected' => 'www.example.com',
            ],
            'domain with http protocol' => [
                'host' => 'http://www.example.com',
                'expected' => 'www.example.com',
            ],
            'domain with port' => [
                'host' => 'www.example.com:8080',
                'expected' => 'www.example.com',
            ],
            'domain with path' => [
                'host' => 'www.example.com/path/to/resource',
                'expected' => 'www.example.com',
            ],
            'domain with protocol and port' => [
                'host' => 'https://www.example.com:443',
                'expected' => 'www.example.com',
            ],
            'domain with protocol, port and path' => [
                'host' => 'https://www.example.com:443/api/v1',
                'expected' => 'www.example.com',
            ],
            'wildcard domain unchanged' => [
                'host' => '*.example.com',
                'expected' => '*.example.com',
            ],
            'ip address' => [
                'host' => '192.168.1.1',
                'expected' => '192.168.1.1',
            ],
            'ip address with port' => [
                'host' => '192.168.1.1:8080',
                'expected' => '192.168.1.1',
            ],
        ];
    }

    // ==================== Integration Tests ====================

    public function testDoubleClickDomainHierarchy(): void
    {
        // *.doubleclick.net should cover all these:
        $wildcardParent = '*.doubleclick.net';
        $wildcardChild = '*.g.doubleclick.net';
        $subdomain1 = 'googleads.g.doubleclick.net';
        $subdomain2 = 'ad.doubleclick.net';
        $subdomain3 = 'bid.g.doubleclick.net';

        // Child wildcard is covered by parent wildcard
        $this->assertTrue($this->domainMatcher->isWildcardCoveredByBroader($wildcardChild, $wildcardParent));

        // All subdomains match parent wildcard
        $this->assertTrue($this->domainMatcher->domainMatchesWildcard($subdomain1, $wildcardParent));
        $this->assertTrue($this->domainMatcher->domainMatchesWildcard($subdomain2, $wildcardParent));
        $this->assertTrue($this->domainMatcher->domainMatchesWildcard($subdomain3, $wildcardParent));

        // Nested subdomains match child wildcard
        $this->assertTrue($this->domainMatcher->domainMatchesWildcard($subdomain1, $wildcardChild));
        $this->assertTrue($this->domainMatcher->domainMatchesWildcard($subdomain3, $wildcardChild));

        // ad.doubleclick.net does NOT match *.g.doubleclick.net
        $this->assertFalse($this->domainMatcher->domainMatchesWildcard($subdomain2, $wildcardChild));
    }

    public function testGoogleAnalyticsDomainHierarchy(): void
    {
        $wildcard = '*.google-analytics.com';
        $www = 'www.google-analytics.com';
        $ssl = 'ssl.google-analytics.com';
        $baseDomain = 'google-analytics.com';

        $this->assertTrue($this->domainMatcher->domainMatchesWildcard($www, $wildcard));
        $this->assertTrue($this->domainMatcher->domainMatchesWildcard($ssl, $wildcard));
        // Base domain is NOT covered by wildcard
        $this->assertFalse($this->domainMatcher->domainMatchesWildcard($baseDomain, $wildcard));
    }
}
