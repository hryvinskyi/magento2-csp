<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\HeaderSplitter;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Hryvinskyi\Csp\Model\HeaderSplitter\LaminasCspHeaderSplitter;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Hryvinskyi\Csp\Model\HeaderSplitter\LaminasCspHeaderSplitter
 */
class LaminasCspHeaderSplitterTest extends TestCase
{
    private LaminasCspHeaderSplitter $splitter;
    private MockObject|ConfigInterface $configMock;
    private MockObject|LoggerInterface $loggerMock;
    private MockObject|HttpResponse $responseMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(ConfigInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->responseMock = $this->createMock(HttpResponse::class);

        $this->configMock->method('getMaxHeaderSize')->willReturn(200);

        $this->splitter = new LaminasCspHeaderSplitter(
            $this->configMock,
            $this->loggerMock
        );
    }

    // ==================== No Splitting Needed ====================

    public function testNoSplitWhenHeaderUnderMaxSize(): void
    {
        $headerValue = "script-src 'self' https://example.com";

        $this->responseMock->expects($this->never())->method('clearHeader');
        $this->responseMock->expects($this->never())->method('setHeader');

        $this->splitter->splitHeader(
            $this->responseMock,
            'Content-Security-Policy',
            $headerValue
        );
    }

    // ==================== Default-src Preservation ====================

    public function testDefaultSrcIncludedInEveryPart(): void
    {
        $defaultSrc = "default-src 'self'";
        $scriptSrc = 'script-src ' . str_repeat('https://a.com ', 8);
        $styleSrc = 'style-src ' . str_repeat('https://b.com ', 8);
        $imgSrc = 'img-src ' . str_repeat('https://c.com ', 8);
        $headerValue = $defaultSrc . '; ' . $scriptSrc . '; ' . $styleSrc . '; ' . $imgSrc;

        $setHeaderCalls = [];
        $this->responseMock->expects($this->once())->method('clearHeader');
        $this->responseMock->method('setHeader')
            ->willReturnCallback(function (string $name, string $value, bool $replace) use (&$setHeaderCalls) {
                $setHeaderCalls[] = ['name' => $name, 'value' => $value, 'replace' => $replace];
                return $this->responseMock;
            });

        $this->splitter->splitHeader(
            $this->responseMock,
            'Content-Security-Policy',
            $headerValue
        );

        $this->assertNotEmpty($setHeaderCalls, 'Expected at least one setHeader call');
        foreach ($setHeaderCalls as $i => $call) {
            $this->assertStringContainsString(
                "default-src 'self'",
                $call['value'],
                "Part $i should contain default-src"
            );
        }
    }

    public function testReportUriIncludedInEveryPart(): void
    {
        $reportUri = 'report-uri https://report.example.com/csp';
        $scriptSrc = 'script-src ' . str_repeat('https://a.com ', 8);
        $styleSrc = 'style-src ' . str_repeat('https://b.com ', 8);
        $imgSrc = 'img-src ' . str_repeat('https://c.com ', 8);
        $headerValue = $scriptSrc . '; ' . $styleSrc . '; ' . $imgSrc . '; ' . $reportUri;

        $setHeaderCalls = [];
        $this->responseMock->expects($this->once())->method('clearHeader');
        $this->responseMock->method('setHeader')
            ->willReturnCallback(function (string $name, string $value, bool $replace) use (&$setHeaderCalls) {
                $setHeaderCalls[] = ['name' => $name, 'value' => $value, 'replace' => $replace];
                return $this->responseMock;
            });

        $this->splitter->splitHeader(
            $this->responseMock,
            'Content-Security-Policy',
            $headerValue
        );

        $this->assertNotEmpty($setHeaderCalls, 'Expected at least one setHeader call');
        foreach ($setHeaderCalls as $i => $call) {
            $this->assertStringContainsString(
                'report-uri https://report.example.com/csp',
                $call['value'],
                "Part $i should contain report-uri"
            );
        }
    }

    public function testBothDefaultSrcAndReportUriInEveryPart(): void
    {
        $defaultSrc = "default-src 'self'";
        $reportUri = 'report-uri https://report.example.com/csp';
        $scriptSrc = 'script-src ' . str_repeat('https://a.com ', 8);
        $styleSrc = 'style-src ' . str_repeat('https://b.com ', 8);
        $imgSrc = 'img-src ' . str_repeat('https://c.com ', 8);
        $headerValue = $defaultSrc . '; ' . $scriptSrc . '; ' . $styleSrc . '; ' . $imgSrc . '; ' . $reportUri;

        $setHeaderCalls = [];
        $this->responseMock->expects($this->once())->method('clearHeader');
        $this->responseMock->method('setHeader')
            ->willReturnCallback(function (string $name, string $value, bool $replace) use (&$setHeaderCalls) {
                $setHeaderCalls[] = ['name' => $name, 'value' => $value, 'replace' => $replace];
                return $this->responseMock;
            });

        $this->splitter->splitHeader(
            $this->responseMock,
            'Content-Security-Policy',
            $headerValue
        );

        $this->assertNotEmpty($setHeaderCalls, 'Expected at least one setHeader call');
        foreach ($setHeaderCalls as $i => $call) {
            $this->assertStringContainsString(
                "default-src 'self'",
                $call['value'],
                "Part $i should contain default-src"
            );
            $this->assertStringContainsString(
                'report-uri https://report.example.com/csp',
                $call['value'],
                "Part $i should contain report-uri"
            );
        }
    }

    // ==================== Edge Cases ====================

    public function testOnlyDefaultSrcAndReportUri(): void
    {
        // Build a header that exceeds max size (200) but only has default-src and report-uri
        $defaultSrc = "default-src 'self' https://example.com https://other.com https://another.com https://more.com https://extra.com https://sixth.com https://seventh.com";
        $reportUri = 'report-uri https://report.example.com/csp-violations-endpoint-that-is-long';
        $headerValue = $defaultSrc . '; ' . $reportUri;

        // Verify it actually exceeds the limit
        $this->assertGreaterThan(200, mb_strlen($headerValue, '8bit'));

        $setHeaderCalls = [];
        $this->responseMock->expects($this->once())->method('clearHeader');
        $this->responseMock->method('setHeader')
            ->willReturnCallback(function (string $name, string $value, bool $replace) use (&$setHeaderCalls) {
                $setHeaderCalls[] = ['name' => $name, 'value' => $value, 'replace' => $replace];
                return $this->responseMock;
            });

        $this->splitter->splitHeader(
            $this->responseMock,
            'Content-Security-Policy',
            $headerValue
        );

        $this->assertCount(1, $setHeaderCalls, 'Should restore as a single header');
        $this->assertStringContainsString('default-src', $setHeaderCalls[0]['value']);
        $this->assertStringContainsString('report-uri', $setHeaderCalls[0]['value']);
        $this->assertTrue($setHeaderCalls[0]['replace'], 'Single header should use replace=true');
    }

    public function testEmptyDirectivesAfterParsing(): void
    {
        // Header exceeds max size but contains only semicolons/whitespace
        // The regex won't match empty segments between semicolons, resulting in empty directives
        $headerValue = str_repeat('; ', 120);

        $this->responseMock->method('clearHeader')->willReturnSelf();
        // With no real directives, the code restores an empty header via the only-default/report path
        $this->responseMock->method('setHeader')->willReturnSelf();

        // Should not crash
        $this->splitter->splitHeader(
            $this->responseMock,
            'Content-Security-Policy',
            $headerValue
        );

        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }

    // ==================== Splitting Behavior ====================

    public function testSplitIntoMultipleParts(): void
    {
        // Create a header well over 200 bytes with multiple directives
        $scriptSrc = 'script-src https://cdn1.example.com https://cdn2.example.com https://cdn3.example.com';
        $styleSrc = 'style-src https://styles1.example.com https://styles2.example.com https://styles3.example.com';
        $imgSrc = 'img-src https://images1.example.com https://images2.example.com https://images3.example.com';
        $headerValue = $scriptSrc . '; ' . $styleSrc . '; ' . $imgSrc;

        $setHeaderCalls = [];
        $this->responseMock->expects($this->once())->method('clearHeader');
        $this->responseMock->method('setHeader')
            ->willReturnCallback(function (string $name, string $value, bool $replace) use (&$setHeaderCalls) {
                $setHeaderCalls[] = ['name' => $name, 'value' => $value, 'replace' => $replace];
                return $this->responseMock;
            });

        $this->splitter->splitHeader(
            $this->responseMock,
            'Content-Security-Policy',
            $headerValue
        );

        $this->assertGreaterThan(1, count($setHeaderCalls), 'Should split into multiple parts');
    }

    public function testFirstPartUsesReplaceTrue(): void
    {
        $scriptSrc = 'script-src https://cdn1.example.com https://cdn2.example.com https://cdn3.example.com';
        $styleSrc = 'style-src https://styles1.example.com https://styles2.example.com https://styles3.example.com';
        $imgSrc = 'img-src https://images1.example.com https://images2.example.com https://images3.example.com';
        $headerValue = $scriptSrc . '; ' . $styleSrc . '; ' . $imgSrc;

        $setHeaderCalls = [];
        $this->responseMock->method('clearHeader')->willReturnSelf();
        $this->responseMock->method('setHeader')
            ->willReturnCallback(function (string $name, string $value, bool $replace) use (&$setHeaderCalls) {
                $setHeaderCalls[] = ['name' => $name, 'value' => $value, 'replace' => $replace];
                return $this->responseMock;
            });

        $this->splitter->splitHeader(
            $this->responseMock,
            'Content-Security-Policy',
            $headerValue
        );

        $this->assertNotEmpty($setHeaderCalls, 'Expected at least one setHeader call');
        $this->assertTrue($setHeaderCalls[0]['replace'], 'First part should use replace=true');
    }

    public function testSubsequentPartsUseReplaceFalse(): void
    {
        $scriptSrc = 'script-src https://cdn1.example.com https://cdn2.example.com https://cdn3.example.com';
        $styleSrc = 'style-src https://styles1.example.com https://styles2.example.com https://styles3.example.com';
        $imgSrc = 'img-src https://images1.example.com https://images2.example.com https://images3.example.com';
        $headerValue = $scriptSrc . '; ' . $styleSrc . '; ' . $imgSrc;

        $setHeaderCalls = [];
        $this->responseMock->method('clearHeader')->willReturnSelf();
        $this->responseMock->method('setHeader')
            ->willReturnCallback(function (string $name, string $value, bool $replace) use (&$setHeaderCalls) {
                $setHeaderCalls[] = ['name' => $name, 'value' => $value, 'replace' => $replace];
                return $this->responseMock;
            });

        $this->splitter->splitHeader(
            $this->responseMock,
            'Content-Security-Policy',
            $headerValue
        );

        $this->assertGreaterThan(1, count($setHeaderCalls), 'Expected multiple setHeader calls');
        for ($i = 1; $i < count($setHeaderCalls); $i++) {
            $this->assertFalse(
                $setHeaderCalls[$i]['replace'],
                "Part $i should use replace=false"
            );
        }
    }

    // ==================== Size Limit ====================

    public function testOversizedSingleDirectiveLogsError(): void
    {
        // Create one directive that alone exceeds maxHeaderSize (200)
        $oversizedDirective = 'script-src ' . str_repeat('https://very-long-domain-name.example.com ', 10);
        $otherDirective = 'style-src https://example.com';
        $headerValue = $otherDirective . '; ' . $oversizedDirective;

        $this->responseMock->method('clearHeader')->willReturnSelf();
        $this->responseMock->method('setHeader')->willReturnSelf();

        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('CSP directive exceeds max header size'));

        $this->splitter->splitHeader(
            $this->responseMock,
            'Content-Security-Policy',
            $headerValue
        );
    }
}
