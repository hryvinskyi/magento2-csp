<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\HeaderSplitter;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Hryvinskyi\Csp\Api\DomainMatcherInterface;
use Hryvinskyi\Csp\Model\DomainMatcher;
use Hryvinskyi\Csp\Model\HeaderSplitter\CspValueOptimizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Hryvinskyi\Csp\Model\HeaderSplitter\CspValueOptimizer
 */
class CspValueOptimizerTest extends TestCase
{
    private CspValueOptimizer $optimizer;
    private MockObject|LoggerInterface $loggerMock;
    private MockObject|ConfigInterface $configMock;
    private DomainMatcherInterface $domainMatcher;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->configMock = $this->createMock(ConfigInterface::class);
        $this->domainMatcher = new DomainMatcher();

        $this->optimizer = new CspValueOptimizer(
            $this->loggerMock,
            $this->configMock,
            $this->domainMatcher
        );
    }

    // ==================== removeDuplicates Tests ====================

    public function testRemoveDuplicatesEmptyArray(): void
    {
        $result = $this->optimizer->removeDuplicates([]);
        $this->assertSame([], $result);
    }

    public function testRemoveDuplicatesNoDuplicates(): void
    {
        $input = ['https://example.com', 'https://other.com'];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame(['https://example.com', 'https://other.com'], $result);
    }

    public function testRemoveDuplicatesExactDuplicates(): void
    {
        $input = ['data:', 'https://example.com', 'data:', "'self'"];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame(['data:', 'https://example.com', "'self'"], $result);
    }

    public function testRemoveDuplicatesMultipleDuplicates(): void
    {
        $input = ['data:', 'data:', 'data:', "'self'", "'self'"];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame(['data:', "'self'"], $result);
    }

    public function testRemoveDuplicatesCaseInsensitiveDomains(): void
    {
        $input = ['https://Example.com', 'https://example.com'];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame(['https://Example.com'], $result);
    }

    public function testRemoveDuplicatesTrailingSlashNormalization(): void
    {
        $input = ['https://example.com/', 'https://example.com'];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame(['https://example.com/'], $result);
    }

    public function testRemoveDuplicatesKeywordsCaseInsensitive(): void
    {
        $input = ["'self'", "'SELF'", "'unsafe-inline'"];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame(["'self'", "'unsafe-inline'"], $result);
    }

    // ==================== Hash Tests ====================

    public function testRemoveDuplicatesPreservesSha256Hashes(): void
    {
        $hash1 = "'sha256-abc123def456ghijklmnop='";
        $hash2 = "'sha256-xyz789uvw012stqrponmlk='";
        $input = [$hash1, $hash2, "'self'"];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame([$hash1, $hash2, "'self'"], $result);
    }

    public function testRemoveDuplicatesPreservesSha384Hashes(): void
    {
        $hash = "'sha384-oqVuAfXRKap7fdgcCY5uykM6+R9GqQ8K/uxy9rx7HNQlGYl1kPzQho1wx4JwY8wC'";
        $input = [$hash, "'self'", 'https://example.com'];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame([$hash, "'self'", 'https://example.com'], $result);
    }

    public function testRemoveDuplicatesPreservesSha512Hashes(): void
    {
        $hash = "'sha512-vSsar3708Jvp9Szi2NWZZ02Bqp1qRCFpbcTZPdBhnWgs5WtNZKnvCXdhztmeD2cmW192CF5bDufKRpayrW/isg=='";
        $input = [$hash, "'unsafe-inline'"];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame([$hash, "'unsafe-inline'"], $result);
    }

    public function testRemoveDuplicatesHashesAreCaseSensitive(): void
    {
        // Hash values must be preserved exactly - they are case-sensitive
        $hash1 = "'sha256-ABCdef123='";
        $hash2 = "'sha256-abcDEF123='";
        $input = [$hash1, $hash2];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame([$hash1, $hash2], $result);
    }

    public function testRemoveDuplicatesIdenticalHashesRemoved(): void
    {
        $hash = "'sha256-abc123='";
        $input = [$hash, "'self'", $hash];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame([$hash, "'self'"], $result);
    }

    public function testRemoveDuplicatesMultipleHashAlgorithms(): void
    {
        $sha256 = "'sha256-abc123='";
        $sha384 = "'sha384-def456='";
        $sha512 = "'sha512-ghi789='";
        $input = [$sha256, $sha384, $sha512, "'self'"];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame([$sha256, $sha384, $sha512, "'self'"], $result);
    }

    // ==================== Nonce Tests ====================

    public function testRemoveDuplicatesPreservesNonces(): void
    {
        $nonce = "'nonce-abc123def456'";
        $input = [$nonce, "'self'", 'https://example.com'];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame([$nonce, "'self'", 'https://example.com'], $result);
    }

    public function testRemoveDuplicatesNoncesAreCaseSensitive(): void
    {
        $nonce1 = "'nonce-ABCdef123'";
        $nonce2 = "'nonce-abcDEF123'";
        $input = [$nonce1, $nonce2];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame([$nonce1, $nonce2], $result);
    }

    public function testRemoveDuplicatesIdenticalNoncesRemoved(): void
    {
        $nonce = "'nonce-abc123'";
        $input = [$nonce, "'self'", $nonce];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertSame([$nonce, "'self'"], $result);
    }

    // ==================== Google/DoubleClick Domain Tests ====================

    public function testRemoveDuplicatesGoogleAnalyticsDomains(): void
    {
        $input = [
            '*.google-analytics.com',
            'www.google-analytics.com',
            'analytics.google.com',
            '*.analytics.google.com',
        ];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertCount(4, $result);
    }

    public function testRemoveDuplicatesDoubleClickDomains(): void
    {
        $input = [
            '*.doubleclick.net',
            '*.g.doubleclick.net',
            'googleads.g.doubleclick.net',
            'ad.doubleclick.net',
            'bid.g.doubleclick.net',
        ];
        $result = $this->optimizer->removeDuplicates($input);
        $this->assertCount(5, $result);
    }

    // ==================== removeRedundantWildcards Tests ====================

    /**
     * @dataProvider removeRedundantWildcardsDataProvider
     */
    public function testRemoveRedundantWildcards(array $input, array $expected): void
    {
        $result = $this->optimizer->removeRedundantWildcards($input);
        sort($result);
        sort($expected);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{input: array<int, string>, expected: array<int, string>}>
     */
    public static function removeRedundantWildcardsDataProvider(): array
    {
        return [
            'empty array' => [
                'input' => [],
                'expected' => [],
            ],
            'no wildcards' => [
                'input' => ['https://example.com', 'https://other.com'],
                'expected' => ['https://example.com', 'https://other.com'],
            ],
            'wildcard covers subdomain' => [
                'input' => ['*.example.com', 'www.example.com', 'api.example.com'],
                'expected' => ['*.example.com'],
            ],
            'wildcard does not cover base domain' => [
                'input' => ['*.example.com', 'example.com'],
                'expected' => ['*.example.com', 'example.com'],
            ],
            'multiple wildcards with redundant subdomain wildcards' => [
                'input' => ['*.example.com', '*.sub.example.com', 'www.example.com'],
                'expected' => ['*.example.com'],
            ],
            'preserves keywords' => [
                'input' => ["'self'", "'unsafe-inline'", '*.example.com', 'www.example.com'],
                'expected' => ["'self'", "'unsafe-inline'", '*.example.com'],
            ],
            'preserves scheme sources' => [
                'input' => ['data:', 'blob:', '*.example.com', 'cdn.example.com'],
                'expected' => ['data:', 'blob:', '*.example.com'],
            ],
            'different domains not affected' => [
                'input' => ['*.example.com', 'www.other.com', 'api.different.com'],
                'expected' => ['*.example.com', 'www.other.com', 'api.different.com'],
            ],
            'universal wildcard makes all redundant' => [
                'input' => ['*', '*.example.com', 'www.example.com', "'self'"],
                'expected' => ['*', "'self'"],
            ],
        ];
    }

    public function testRemoveRedundantWildcardsGoogleAnalytics(): void
    {
        $input = [
            '*.google-analytics.com',
            'www.google-analytics.com',
            'ssl.google-analytics.com',
            "'self'",
        ];
        $result = $this->optimizer->removeRedundantWildcards($input);
        sort($result);

        $this->assertContains('*.google-analytics.com', $result);
        $this->assertContains("'self'", $result);
        $this->assertNotContains('www.google-analytics.com', $result);
        $this->assertNotContains('ssl.google-analytics.com', $result);
    }

    public function testRemoveRedundantWildcardsDoubleClick(): void
    {
        $input = [
            '*.doubleclick.net',
            '*.g.doubleclick.net',
            'googleads.g.doubleclick.net',
            'ad.doubleclick.net',
            'bid.g.doubleclick.net',
        ];
        $result = $this->optimizer->removeRedundantWildcards($input);
        sort($result);

        // *.doubleclick.net covers all others
        $this->assertContains('*.doubleclick.net', $result);
        $this->assertNotContains('*.g.doubleclick.net', $result);
        $this->assertNotContains('googleads.g.doubleclick.net', $result);
        $this->assertNotContains('ad.doubleclick.net', $result);
        $this->assertNotContains('bid.g.doubleclick.net', $result);
    }

    public function testRemoveRedundantWildcardsGoogleTagManager(): void
    {
        $input = [
            '*.googletagmanager.com',
            'www.googletagmanager.com',
            'googletagmanager.com',
            "'self'",
        ];
        $result = $this->optimizer->removeRedundantWildcards($input);
        sort($result);

        $this->assertContains('*.googletagmanager.com', $result);
        $this->assertContains("'self'", $result);
        // Base domain is NOT covered by wildcard
        $this->assertContains('googletagmanager.com', $result);
        $this->assertNotContains('www.googletagmanager.com', $result);
    }

    public function testRemoveRedundantWildcardsPayPal(): void
    {
        $input = [
            '*.paypal.com',
            'www.paypal.com',
            'www.sandbox.paypal.com',
            'c.paypal.com',
            'checkout.paypal.com',
        ];
        $result = $this->optimizer->removeRedundantWildcards($input);

        $this->assertContains('*.paypal.com', $result);
        $this->assertNotContains('www.paypal.com', $result);
        $this->assertNotContains('www.sandbox.paypal.com', $result);
        $this->assertNotContains('c.paypal.com', $result);
        $this->assertNotContains('checkout.paypal.com', $result);
    }

    public function testRemoveRedundantWildcardsKlaviyo(): void
    {
        $input = [
            '*.klaviyo.com',
            'static.klaviyo.com',
            'static-forms.klaviyo.com',
            'fast.a.klaviyo.com',
            'a.klaviyo.com',
        ];
        $result = $this->optimizer->removeRedundantWildcards($input);

        $this->assertContains('*.klaviyo.com', $result);
        $this->assertNotContains('static.klaviyo.com', $result);
        $this->assertNotContains('static-forms.klaviyo.com', $result);
        $this->assertNotContains('fast.a.klaviyo.com', $result);
        $this->assertNotContains('a.klaviyo.com', $result);
    }

    public function testRemoveRedundantWildcardsPreservesHashes(): void
    {
        $hash = "'sha256-abc123def456='";
        $input = [
            $hash,
            '*.example.com',
            'www.example.com',
            "'self'",
        ];
        $result = $this->optimizer->removeRedundantWildcards($input);

        $this->assertContains($hash, $result);
        $this->assertContains('*.example.com', $result);
        $this->assertContains("'self'", $result);
        $this->assertNotContains('www.example.com', $result);
    }

    public function testRemoveRedundantWildcardsPreservesNonces(): void
    {
        $nonce = "'nonce-abc123'";
        $input = [
            $nonce,
            '*.example.com',
            'www.example.com',
            "'self'",
        ];
        $result = $this->optimizer->removeRedundantWildcards($input);

        $this->assertContains($nonce, $result);
        $this->assertContains('*.example.com', $result);
        $this->assertContains("'self'", $result);
        $this->assertNotContains('www.example.com', $result);
    }

    // ==================== optimizeDirectiveValues Tests ====================

    /**
     * @dataProvider optimizeDirectiveValuesDataProvider
     */
    public function testOptimizeDirectiveValues(
        array $input,
        array $expectedWithWildcardRemoval,
        array $expectedWithoutWildcardRemoval
    ): void {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);
        $result = $this->optimizer->optimizeDirectiveValues('script-src', $input);
        $this->assertValuesOptimized($result, $expectedWithWildcardRemoval);

        $this->setUp();
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(false);
        $result = $this->optimizer->optimizeDirectiveValues('script-src', $input);
        $this->assertValuesOptimized($result, $expectedWithoutWildcardRemoval);
    }

    /**
     * @return array<string, array{input: array<int, string>, expectedWithWildcardRemoval: array<int, string>, expectedWithoutWildcardRemoval: array<int, string>}>
     */
    public static function optimizeDirectiveValuesDataProvider(): array
    {
        return [
            'empty array' => [
                'input' => [],
                'expectedWithWildcardRemoval' => [],
                'expectedWithoutWildcardRemoval' => [],
            ],
            'removes duplicates and redundant wildcards' => [
                'input' => ["'self'", 'data:', 'data:', '*.example.com', 'www.example.com', "'self'"],
                'expectedWithWildcardRemoval' => ["'self'", 'data:', '*.example.com'],
                'expectedWithoutWildcardRemoval' => ["'self'", 'data:', '*.example.com', 'www.example.com'],
            ],
            'preserves unique values' => [
                'input' => ["'self'", '*.cdn.com', 'api.other.com'],
                'expectedWithWildcardRemoval' => ["'self'", '*.cdn.com', 'api.other.com'],
                'expectedWithoutWildcardRemoval' => ["'self'", '*.cdn.com', 'api.other.com'],
            ],
        ];
    }

    // ==================== optimizeHeader Tests ====================

    public function testOptimizeHeaderWithEmptyString(): void
    {
        $result = $this->optimizer->optimizeHeader('');
        $this->assertSame('', $result);
    }

    public function testOptimizeHeaderPreservesDirectiveStructure(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        $input = "script-src 'self' https://example.com; style-src 'self' 'unsafe-inline'; img-src data: blob:";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('script-src', $result);
        $this->assertStringContainsString('style-src', $result);
        $this->assertStringContainsString('img-src', $result);

        $directives = explode(';', $result);
        $this->assertCount(3, $directives);
    }

    public function testOptimizeHeaderRemovesDuplicateData(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        $input = "script-src 'self' data: https://example.com data: 'unsafe-inline'";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertSame(1, substr_count($result, 'data:'));
    }

    public function testOptimizeHeaderWithWildcardRemoval(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        $input = "script-src 'self' *.example.com www.example.com api.example.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('*.example.com', $result);
        $this->assertStringNotContainsString('www.example.com', $result);
        $this->assertStringNotContainsString('api.example.com', $result);
    }

    public function testOptimizeHeaderWithoutWildcardRemoval(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(false);

        $input = "script-src 'self' *.example.com www.example.com api.example.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('*.example.com', $result);
        $this->assertStringContainsString('www.example.com', $result);
        $this->assertStringContainsString('api.example.com', $result);
    }

    public function testOptimizeHeaderWithHashes(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        $sha256 = "'sha256-abc123def456ghijklmnop='";
        $sha384 = "'sha384-xyz789uvw012stqrponmlk='";
        $sha512 = "'sha512-mno456pqr789stuvwxyzab='";

        $input = "script-src 'self' {$sha256} {$sha384} {$sha512} https://example.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString($sha256, $result);
        $this->assertStringContainsString($sha384, $result);
        $this->assertStringContainsString($sha512, $result);
    }

    public function testOptimizeHeaderWithNonces(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        $nonce1 = "'nonce-abc123def456'";
        $nonce2 = "'nonce-xyz789uvw012'";

        $input = "script-src 'self' {$nonce1} {$nonce2} https://example.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString($nonce1, $result);
        $this->assertStringContainsString($nonce2, $result);
    }

    public function testOptimizeHeaderGoogleServices(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        $input = "script-src 'self' *.google-analytics.com www.google-analytics.com " .
            "*.googletagmanager.com www.googletagmanager.com googletagmanager.com " .
            "*.doubleclick.net *.g.doubleclick.net googleads.g.doubleclick.net";

        $result = $this->optimizer->optimizeHeader($input);

        // Wildcards should remain
        $this->assertStringContainsString('*.google-analytics.com', $result);
        $this->assertStringContainsString('*.googletagmanager.com', $result);
        $this->assertStringContainsString('*.doubleclick.net', $result);

        // Base domain not covered by wildcard
        $this->assertStringContainsString('googletagmanager.com', $result);

        // Redundant subdomains should be removed
        $this->assertStringNotContainsString('www.google-analytics.com', $result);
        $this->assertStringNotContainsString('www.googletagmanager.com', $result);
        $this->assertStringNotContainsString('*.g.doubleclick.net', $result);
        $this->assertStringNotContainsString('googleads.g.doubleclick.net', $result);
    }

    public function testOptimizeHeaderConnectSrcRealWorld(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        $input = "connect-src 'self' " .
            "*.google-analytics.com www.google-analytics.com analytics.google.com " .
            "*.googletagmanager.com *.doubleclick.net *.g.doubleclick.net " .
            "googleads.g.doubleclick.net ad.doubleclick.net " .
            "*.paypal.com www.paypal.com www.sandbox.paypal.com " .
            "*.klaviyo.com static.klaviyo.com fast.a.klaviyo.com";

        $result = $this->optimizer->optimizeHeader($input);

        // Count only the wildcards that should remain
        $this->assertStringContainsString('*.google-analytics.com', $result);
        $this->assertStringContainsString('*.googletagmanager.com', $result);
        $this->assertStringContainsString('*.doubleclick.net', $result);
        $this->assertStringContainsString('*.paypal.com', $result);
        $this->assertStringContainsString('*.klaviyo.com', $result);

        // analytics.google.com is a different domain (subdomain of google.com, not google-analytics.com)
        $this->assertStringContainsString('analytics.google.com', $result);
    }

    public function testOptimizeHeaderImgSrcRealWorld(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        $input = "img-src 'self' data: blob: " .
            "*.gstatic.com ssl.gstatic.com www.gstatic.com maps.gstatic.com " .
            "*.facebook.com www.facebook.com connect.facebook.net " .
            "*.youtube.com i.ytimg.com *.ytimg.com " .
            "*.google.com www.google.com maps.google.com";

        $result = $this->optimizer->optimizeHeader($input);

        // Single data: and blob:
        $this->assertSame(1, substr_count($result, 'data:'));
        $this->assertSame(1, substr_count($result, 'blob:'));

        // Wildcards should remain
        $this->assertStringContainsString('*.gstatic.com', $result);
        $this->assertStringContainsString('*.facebook.com', $result);
        $this->assertStringContainsString('*.youtube.com', $result);
        $this->assertStringContainsString('*.ytimg.com', $result);
        $this->assertStringContainsString('*.google.com', $result);

        // connect.facebook.net is .net not .com
        $this->assertStringContainsString('connect.facebook.net', $result);

        // Redundant entries should be removed
        $this->assertStringNotContainsString('ssl.gstatic.com', $result);
        $this->assertStringNotContainsString('www.gstatic.com', $result);
        $this->assertStringNotContainsString('www.facebook.com', $result);
        $this->assertStringNotContainsString('www.google.com', $result);
        $this->assertStringNotContainsString('i.ytimg.com', $result);
    }

    public function testOptimizeHeaderFrameAncestorsWithRedundantWildcards(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        $input = "frame-ancestors *.hsdcoilovers.com *.wisefab.co.uk *.driftworks.com " .
            "www.driftworks.com www.workwheelsuk.com www.hsdcoilovers.com www.wisefab.co.uk 'self'";

        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('*.hsdcoilovers.com', $result);
        $this->assertStringContainsString('*.driftworks.com', $result);
        $this->assertStringNotContainsString('www.hsdcoilovers.com', $result);
        $this->assertStringNotContainsString('www.driftworks.com', $result);
        $this->assertStringNotContainsString('www.wisefab.co.uk', $result);
        $this->assertStringContainsString('www.workwheelsuk.com', $result);
    }

    public function testOptimizeHeaderFontSrcRealWorld(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        $input = "font-src 'self' data: data: " .
            "maxcdn.bootstrapcdn.com fonts.gstatic.com " .
            "*.alothemes.com *.magepow.com *.cloudfront.net " .
            "*.reviews.io *.reviews.co.uk cdnjs.cloudflare.com " .
            "cdn.buttonizer.io use.typekit.net cdn.jsdelivr.net";

        $result = $this->optimizer->optimizeHeader($input);

        // Only one data:
        $this->assertSame(1, substr_count($result, 'data:'));

        // All unique domains preserved
        $this->assertStringContainsString('maxcdn.bootstrapcdn.com', $result);
        $this->assertStringContainsString('fonts.gstatic.com', $result);
        $this->assertStringContainsString('*.cloudfront.net', $result);
    }

    public function testOptimizeHeaderComplexRealWorldCsp(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        // Real-world complex CSP header
        $input = "script-src 'self' 'unsafe-inline' 'unsafe-eval' " .
            "'sha256-abc123=' 'nonce-xyz789' " .
            "*.google-analytics.com www.google-analytics.com " .
            "*.googletagmanager.com www.googletagmanager.com googletagmanager.com " .
            "*.doubleclick.net *.g.doubleclick.net googleads.g.doubleclick.net " .
            "*.paypal.com www.paypal.com www.sandbox.paypal.com " .
            "*.youtube.com www.youtube.com " .
            "cdn.jsdelivr.net cdnjs.cloudflare.com";

        $result = $this->optimizer->optimizeHeader($input);

        // Keywords preserved
        $this->assertStringContainsString("'self'", $result);
        $this->assertStringContainsString("'unsafe-inline'", $result);
        $this->assertStringContainsString("'unsafe-eval'", $result);

        // Hash and nonce preserved
        $this->assertStringContainsString("'sha256-abc123='", $result);
        $this->assertStringContainsString("'nonce-xyz789'", $result);

        // Wildcards preserved
        $this->assertStringContainsString('*.google-analytics.com', $result);
        $this->assertStringContainsString('*.googletagmanager.com', $result);
        $this->assertStringContainsString('*.doubleclick.net', $result);
        $this->assertStringContainsString('*.paypal.com', $result);
        $this->assertStringContainsString('*.youtube.com', $result);

        // Base domain (not covered by wildcard)
        $this->assertStringContainsString('googletagmanager.com', $result);

        // Unique CDN domains preserved
        $this->assertStringContainsString('cdn.jsdelivr.net', $result);
        $this->assertStringContainsString('cdnjs.cloudflare.com', $result);

        // Redundant entries removed
        $this->assertStringNotContainsString('www.google-analytics.com', $result);
        $this->assertStringNotContainsString('www.googletagmanager.com', $result);
        $this->assertStringNotContainsString('*.g.doubleclick.net', $result);
        $this->assertStringNotContainsString('googleads.g.doubleclick.net', $result);
        $this->assertStringNotContainsString('www.paypal.com', $result);
        $this->assertStringNotContainsString('www.youtube.com', $result);
    }

    // ==================== Logging Tests ====================

    public function testLogsWarningForUniversalWildcard(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        $this->loggerMock->expects($this->atLeastOnce())
            ->method('warning')
            ->with($this->stringContains('unrestricted wildcard'));

        $input = "form-action * https://example.com *.paypal.com";
        $this->optimizer->optimizeHeader($input);
    }

    public function testLogsDebugForRemovedRedundantEntries(): void
    {
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        $this->loggerMock->expects($this->atLeastOnce())
            ->method('debug')
            ->with($this->stringContains('Removed redundant'));

        $input = "script-src *.example.com www.example.com";
        $this->optimizer->optimizeHeader($input);
    }

    // ==================== Helper Methods ====================

    /**
     * Verifies that the optimized values contain the expected values
     *
     * @param array<int, string> $actual
     * @param array<int, string> $expected
     */
    private function assertValuesOptimized(array $actual, array $expected): void
    {
        $actualSorted = $actual;
        $expectedSorted = $expected;
        sort($actualSorted);
        sort($expectedSorted);

        $this->assertEquals(
            $expectedSorted,
            $actualSorted,
            sprintf(
                "Expected values:\n%s\n\nActual values:\n%s",
                implode(', ', $expected),
                implode(', ', $actual)
            )
        );
    }
}
