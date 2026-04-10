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

    // ==================== Scheme and Path Stripping Tests ====================

    public function testSchemeStrippingRemovesHttps(): void
    {
        $this->configMock->method('isSchemePathStrippingEnabled')->willReturn(true);

        $input = "script-src 'self' https://cdn.example.com https://api.example.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('cdn.example.com', $result);
        $this->assertStringNotContainsString('https://cdn.example.com', $result);
    }

    public function testSchemeStrippingRemovesHttp(): void
    {
        $this->configMock->method('isSchemePathStrippingEnabled')->willReturn(true);

        $input = "script-src http://cdn.example.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('cdn.example.com', $result);
        $this->assertStringNotContainsString('http://cdn.example.com', $result);
    }

    public function testPathStrippingRemovesPaths(): void
    {
        $this->configMock->method('isSchemePathStrippingEnabled')->willReturn(true);

        $input = "script-src https://example.com/api/v1/endpoint";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('example.com', $result);
        $this->assertStringNotContainsString('/api/v1/endpoint', $result);
    }

    public function testSchemeStrippingPreservesPort(): void
    {
        $this->configMock->method('isSchemePathStrippingEnabled')->willReturn(true);

        $input = "script-src https://example.com:8080/path";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('example.com:8080', $result);
        $this->assertStringNotContainsString('https://', $result);
        $this->assertStringNotContainsString('/path', $result);
    }

    public function testSchemeStrippingPreservesKeywords(): void
    {
        $this->configMock->method('isSchemePathStrippingEnabled')->willReturn(true);

        $input = "script-src 'self' 'unsafe-inline' data: blob: https:";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString("'self'", $result);
        $this->assertStringContainsString("'unsafe-inline'", $result);
        $this->assertStringContainsString('data:', $result);
        $this->assertStringContainsString('blob:', $result);
        $this->assertStringContainsString('https:', $result);
    }

    public function testSchemeStrippingPreservesHashes(): void
    {
        $this->configMock->method('isSchemePathStrippingEnabled')->willReturn(true);

        $hash = "'sha256-abc123def456='";
        $input = "script-src {$hash} https://example.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString($hash, $result);
    }

    public function testSchemeStrippingPreservesNonces(): void
    {
        $this->configMock->method('isSchemePathStrippingEnabled')->willReturn(true);

        $nonce = "'nonce-abc123def456'";
        $input = "script-src {$nonce} https://example.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString($nonce, $result);
    }

    public function testSchemeStrippingDisabledPreservesSchemes(): void
    {
        $this->configMock->method('isSchemePathStrippingEnabled')->willReturn(false);

        $input = "script-src https://cdn.example.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('https://cdn.example.com', $result);
    }

    public function testSchemeStrippingDeduplicatesAfterStrip(): void
    {
        $this->configMock->method('isSchemePathStrippingEnabled')->willReturn(true);

        $input = "script-src https://example.com example.com http://example.com";
        $result = $this->optimizer->optimizeHeader($input);

        // After stripping schemes, all three become "example.com" — deduplication removes extras
        $this->assertSame(1, substr_count($result, 'example.com'));
    }

    // ==================== Subdomain-to-Wildcard Consolidation Tests ====================

    public function testSubdomainConsolidationCreatesWildcard(): void
    {
        $this->configMock->method('isSubdomainWildcardConsolidationEnabled')->willReturn(true);
        $this->configMock->method('getSubdomainWildcardThreshold')->willReturn(3);

        $input = "script-src api.google.com maps.google.com fonts.google.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('*.google.com', $result);
        $this->assertStringNotContainsString('api.google.com', $result);
        $this->assertStringNotContainsString('maps.google.com', $result);
        $this->assertStringNotContainsString('fonts.google.com', $result);
    }

    public function testSubdomainConsolidationBelowThreshold(): void
    {
        $this->configMock->method('isSubdomainWildcardConsolidationEnabled')->willReturn(true);
        $this->configMock->method('getSubdomainWildcardThreshold')->willReturn(3);

        $input = "script-src api.google.com maps.google.com";
        $result = $this->optimizer->optimizeHeader($input);

        // Only 2 subdomains, threshold is 3 — no wildcard
        $this->assertStringNotContainsString('*.google.com', $result);
        $this->assertStringContainsString('api.google.com', $result);
        $this->assertStringContainsString('maps.google.com', $result);
    }

    public function testSubdomainConsolidationDisabledPreservesAll(): void
    {
        $this->configMock->method('isSubdomainWildcardConsolidationEnabled')->willReturn(false);

        $input = "script-src api.google.com maps.google.com fonts.google.com analytics.google.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringNotContainsString('*.google.com', $result);
        $this->assertStringContainsString('api.google.com', $result);
    }

    public function testSubdomainConsolidationSkipsExistingWildcard(): void
    {
        $this->configMock->method('isSubdomainWildcardConsolidationEnabled')->willReturn(true);
        $this->configMock->method('getSubdomainWildcardThreshold')->willReturn(3);
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);

        $input = "script-src *.google.com api.google.com maps.google.com fonts.google.com";
        $result = $this->optimizer->optimizeHeader($input);

        // Wildcard already exists — subdomains should just be dropped (by wildcard removal)
        $this->assertStringContainsString('*.google.com', $result);
        $this->assertSame(1, substr_count($result, '*.google.com'));
    }

    public function testSubdomainConsolidationSkipsPortBearingHosts(): void
    {
        $this->configMock->method('isSubdomainWildcardConsolidationEnabled')->willReturn(true);
        $this->configMock->method('getSubdomainWildcardThreshold')->willReturn(3);

        $input = "script-src api.example.com:8080 maps.example.com:8080 fonts.example.com:8080";
        $result = $this->optimizer->optimizeHeader($input);

        // Port-bearing hosts should NOT be consolidated into wildcards
        $this->assertStringNotContainsString('*.example.com', $result);
        $this->assertStringContainsString('api.example.com:8080', $result);
    }

    public function testSubdomainConsolidationPreservesKeywords(): void
    {
        $this->configMock->method('isSubdomainWildcardConsolidationEnabled')->willReturn(true);
        $this->configMock->method('getSubdomainWildcardThreshold')->willReturn(3);

        $input = "script-src 'self' 'unsafe-inline' api.google.com maps.google.com fonts.google.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString("'self'", $result);
        $this->assertStringContainsString("'unsafe-inline'", $result);
        $this->assertStringContainsString('*.google.com', $result);
    }

    public function testSubdomainConsolidationMultipleParentDomains(): void
    {
        $this->configMock->method('isSubdomainWildcardConsolidationEnabled')->willReturn(true);
        $this->configMock->method('getSubdomainWildcardThreshold')->willReturn(3);

        $input = "script-src a.google.com b.google.com c.google.com x.facebook.com y.facebook.com z.facebook.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('*.google.com', $result);
        $this->assertStringContainsString('*.facebook.com', $result);
    }

    public function testSubdomainConsolidationTwoPartDomainsNotGrouped(): void
    {
        $this->configMock->method('isSubdomainWildcardConsolidationEnabled')->willReturn(true);
        $this->configMock->method('getSubdomainWildcardThreshold')->willReturn(3);

        // Two-part domains (example.com) have no parent to group under
        $input = "script-src example.com google.com facebook.com";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('example.com', $result);
        $this->assertStringContainsString('google.com', $result);
        $this->assertStringContainsString('facebook.com', $result);
    }

    // ==================== Default-Src Consolidation Tests ====================

    public function testDefaultSrcConsolidationRemovesFullyCommonDirectives(): void
    {
        $this->configMock->method('isDefaultSrcConsolidationEnabled')->willReturn(true);

        // All three directives have IDENTICAL values — all can be removed
        $input = "script-src 'self' cdn.example.com; style-src 'self' cdn.example.com; img-src 'self' cdn.example.com";
        $result = $this->optimizer->optimizeHeader($input);

        // Shared values should be in default-src
        $this->assertStringContainsString('default-src', $result);
        $this->assertMatchesRegularExpression("/default-src[^;]*'self'/", $result);
        $this->assertMatchesRegularExpression('/default-src[^;]*cdn\.example\.com/', $result);

        // Individual directives removed (all values were common)
        $this->assertStringNotContainsString('script-src', $result);
        $this->assertStringNotContainsString('style-src', $result);
        $this->assertStringNotContainsString('img-src', $result);
    }

    public function testDefaultSrcConsolidationKeepsDirectivesWithUniqueValues(): void
    {
        $this->configMock->method('isDefaultSrcConsolidationEnabled')->willReturn(true);

        // Each directive has unique values — none can be fully removed
        $input = "script-src 'self' cdn.example.com 'unsafe-eval'; style-src 'self' cdn.example.com 'unsafe-inline'; img-src 'self' cdn.example.com data:";
        $result = $this->optimizer->optimizeHeader($input);

        // Directives with unique values must keep ALL their values
        // because explicit directives do NOT inherit from default-src
        $this->assertMatchesRegularExpression("/script-src[^;]*'self'/", $result);
        $this->assertMatchesRegularExpression("/script-src[^;]*'unsafe-eval'/", $result);
        $this->assertMatchesRegularExpression("/script-src[^;]*cdn\.example\.com/", $result);
        $this->assertMatchesRegularExpression("/style-src[^;]*'unsafe-inline'/", $result);
        $this->assertMatchesRegularExpression('/img-src[^;]*data:/', $result);
    }

    public function testDefaultSrcConsolidationMixedRemovableAndNot(): void
    {
        $this->configMock->method('isDefaultSrcConsolidationEnabled')->willReturn(true);

        // script-src has unique 'unsafe-eval', but style-src and img-src are fully common
        $input = "script-src 'self' cdn.example.com 'unsafe-eval'; style-src 'self' cdn.example.com; img-src 'self' cdn.example.com";
        $result = $this->optimizer->optimizeHeader($input);

        // default-src gets the common values
        $this->assertMatchesRegularExpression("/default-src[^;]*'self'/", $result);
        $this->assertMatchesRegularExpression('/default-src[^;]*cdn\.example\.com/', $result);

        // style-src and img-src are removed (fully common)
        $this->assertStringNotContainsString('style-src', $result);
        $this->assertStringNotContainsString('img-src', $result);

        // script-src kept with ALL its values (has unique 'unsafe-eval')
        $this->assertMatchesRegularExpression("/script-src[^;]*'self'/", $result);
        $this->assertMatchesRegularExpression("/script-src[^;]*cdn\.example\.com/", $result);
        $this->assertMatchesRegularExpression("/script-src[^;]*'unsafe-eval'/", $result);
    }

    public function testDefaultSrcConsolidationDisabledKeepsAll(): void
    {
        $this->configMock->method('isDefaultSrcConsolidationEnabled')->willReturn(false);

        $input = "script-src 'self' cdn.example.com; style-src 'self' cdn.example.com";
        $result = $this->optimizer->optimizeHeader($input);

        // No default-src created
        $this->assertStringNotContainsString('default-src', $result);
        $this->assertStringContainsString('script-src', $result);
        $this->assertStringContainsString('style-src', $result);
    }

    public function testDefaultSrcConsolidationSkipsWhenExistingDefaultSrcHasNone(): void
    {
        $this->configMock->method('isDefaultSrcConsolidationEnabled')->willReturn(true);

        $input = "default-src 'none'; script-src 'self' cdn.example.com; style-src 'self' cdn.example.com";
        $result = $this->optimizer->optimizeHeader($input);

        // Should not consolidate since default-src has 'none'
        $this->assertMatchesRegularExpression("/default-src 'none'/", $result);
        $this->assertStringContainsString('script-src', $result);
        $this->assertStringContainsString('style-src', $result);
    }

    public function testDefaultSrcConsolidationSkipsNonFallbackDirectives(): void
    {
        $this->configMock->method('isDefaultSrcConsolidationEnabled')->willReturn(true);

        // frame-ancestors and base-uri do NOT fall back to default-src
        $input = "frame-ancestors 'self' cdn.example.com; base-uri 'self' cdn.example.com; script-src 'self' other.com";
        $result = $this->optimizer->optimizeHeader($input);

        // Only 1 eligible directive (script-src), needs at least 2 — no consolidation
        $this->assertStringNotContainsString('default-src', $result);
    }

    public function testDefaultSrcConsolidationWithNoCommonValues(): void
    {
        $this->configMock->method('isDefaultSrcConsolidationEnabled')->willReturn(true);

        $input = "script-src cdn-a.example.com; style-src cdn-b.example.com; img-src cdn-c.example.com";
        $result = $this->optimizer->optimizeHeader($input);

        // No common values — no default-src created
        $this->assertStringNotContainsString('default-src', $result);
    }

    public function testDefaultSrcConsolidationSkipsWhenNoDirectiveFullyCommon(): void
    {
        $this->configMock->method('isDefaultSrcConsolidationEnabled')->willReturn(true);

        // All directives have unique values — none can be removed
        $input = "script-src 'self' unique-a.com; style-src 'self' unique-b.com";
        $result = $this->optimizer->optimizeHeader($input);

        // No consolidation possible — no directive is fully common
        $this->assertStringNotContainsString('default-src', $result);
    }

    public function testDefaultSrcConsolidationOutputOrderDefaultSrcFirst(): void
    {
        $this->configMock->method('isDefaultSrcConsolidationEnabled')->willReturn(true);

        $input = "script-src 'self' cdn.example.com; style-src 'self' cdn.example.com; img-src 'self' cdn.example.com";
        $result = $this->optimizer->optimizeHeader($input);

        // default-src should appear first in the output
        $this->assertStringStartsWith('default-src', $result);
    }

    // ==================== URI-Value Directive Tests ====================

    public function testReportUriNotStripped(): void
    {
        $this->configMock->method('isSchemePathStrippingEnabled')->willReturn(true);

        $input = "script-src 'self' https://cdn.example.com; report-uri https://example.com/csp_report";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('report-uri https://example.com/csp_report', $result);
    }

    public function testReportUriPreservedWithAllOptimizations(): void
    {
        $this->configMock->method('isSchemePathStrippingEnabled')->willReturn(true);
        $this->configMock->method('isRedundantWildcardRemovalEnabled')->willReturn(true);
        $this->configMock->method('isDefaultSrcConsolidationEnabled')->willReturn(true);
        $this->configMock->method('isSubdomainWildcardConsolidationEnabled')->willReturn(true);
        $this->configMock->method('getSubdomainWildcardThreshold')->willReturn(3);

        $input = "script-src 'self'; style-src 'self'; report-uri https://example.com/csp_report_watch";
        $result = $this->optimizer->optimizeHeader($input);

        $this->assertStringContainsString('report-uri https://example.com/csp_report_watch', $result);
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
