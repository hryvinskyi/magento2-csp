<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy\Split;

use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\FallbackResolver;
use Hryvinskyi\Csp\Model\Policy\Split\DirectiveFamilySplitter;
use Hryvinskyi\Csp\Test\Unit\Model\Policy\ParsesPolicies;
use Hryvinskyi\Csp\Test\Unit\Support\AssertsBrowserDecisions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The strategy alone: its parts are checked against an independent CSP evaluator, without the verifying splitter.
 */
class DirectiveFamilySplitterTest extends TestCase
{
    use ParsesPolicies;
    use AssertsBrowserDecisions;

    /**
     * A policy without `child-src`, so splitting must keep workers on `script-src` explicitly.
     */
    private const HEADER = "default-src 'self' 'unsafe-inline' 'unsafe-eval'; "
        . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.example.com/lib/ https://js.example.com "
        . 'https://www.google.com/recaptcha/ https://a.example.com https://d1abc.cloudfront.net; '
        . "img-src 'self' data: https://cdn.example.com https://*.example.com https://region1.google-analytics.com; "
        . "connect-src 'self' https://region1.google-analytics.com wss://ws.example.com https://cdn.example.com; "
        . "frame-src 'self' https://js.example.com https://www.youtube.com; "
        . "font-src 'self' data: https://fonts.gstatic.com; "
        . "frame-ancestors 'self'; upgrade-insecure-requests; "
        . 'report-uri https://shop.example/csp_report_watch/';

    /**
     * Magento's shape: `child-src` is configured, so nothing re-routes workers.
     */
    private const MAGENTO_HEADER = "default-src 'self' 'unsafe-inline' 'unsafe-eval'; "
        . "child-src 'self' 'unsafe-inline' http: https: blob:; "
        . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.example.com/lib/ https://js.example.com "
        . 'https://www.google.com/recaptcha/ https://a.example.com https://d1abc.cloudfront.net; '
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "img-src 'self' 'unsafe-inline' data: https://cdn.example.com https://*.example.com; "
        . "connect-src 'self' 'unsafe-inline' wss://ws.example.com https://region1.google-analytics.com; "
        . "font-src 'self' 'unsafe-inline' data: https://fonts.gstatic.com; "
        . "frame-ancestors 'self'; base-uri 'self' 'unsafe-inline'; form-action 'self' 'unsafe-inline'; "
        . 'report-uri https://shop.example/csp_report_watch/';

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    private DirectiveFamilySplitter $splitter;

    protected function setUp(): void
    {
        $catalog = new DirectiveCatalog();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->splitter = new DirectiveFamilySplitter(
            $catalog,
            new FallbackResolver($catalog),
            $this->policyFactory(),
            $this->logger
        );
    }

    public function testReturnsThePolicyItselfWhenItFits(): void
    {
        $policy = $this->parsePolicy(self::HEADER);

        $this->assertSame([$policy], $this->splitter->split($policy, 4096));
    }

    /**
     * @dataProvider splittable
     * @param string $header
     * @param int $maxBytes
     */
    public function testPartsAllowAndBlockExactlyWhatTheOriginalDoes(string $header, int $maxBytes): void
    {
        $original = $this->parsePolicy($header);

        $parts = $this->splitter->split($original, $maxBytes);

        $this->assertGreaterThan(1, count($parts));
        $this->assertSameBrowserDecisions([$original], $parts);
        $this->assertSameBrowserDecisions([$original], $parts, 'http');
    }

    /**
     * @dataProvider splittable
     * @param string $header
     * @param int $maxBytes
     */
    public function testEveryPartFitsReportsAndHasNoDefaultSrc(string $header, int $maxBytes): void
    {
        $parts = $this->splitter->split($this->parsePolicy($header), $maxBytes);

        $this->assertGreaterThan(1, count($parts));
        foreach ($parts as $part) {
            $this->assertLessThanOrEqual($maxBytes, $part->byteLength());
            $this->assertSame(['https://shop.example/csp_report_watch/'], $part->sources('report-uri'));
            $this->assertFalse($part->has('default-src'));
        }
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function splittable(): array
    {
        return [
            'without child-src' => [self::HEADER, 600],
            'magento shape' => [self::MAGENTO_HEADER, 500],
        ];
    }

    public function testNonFetchDirectivesAppearExactlyOnce(): void
    {
        $parts = $this->splitter->split($this->parsePolicy(self::HEADER), 600);

        foreach (['frame-ancestors', 'upgrade-insecure-requests'] as $directive) {
            $carrying = array_filter($parts, static fn (PolicyInterface $part): bool => $part->has($directive));
            $this->assertCount(1, $carrying, $directive);
        }
    }

    public function testKeepsThePolicyWhenOneGroupAloneIsTooLarge(): void
    {
        $original = $this->parsePolicy(self::HEADER);
        $this->logger->expects($this->once())->method('warning');

        $this->assertSame([$original], $this->splitter->split($original, 450));
    }

    public function testSplitsAPolicyWithoutDefaultSrcWithoutAddingDirectives(): void
    {
        $hosts = static fn (string $prefix): string => implode(' ', array_map(
            static fn (int $i): string => sprintf('%s%d.example.com', $prefix, $i),
            range(1, 12)
        ));
        $original = $this->parsePolicy('img-src ' . $hosts('img') . '; connect-src ' . $hosts('api'));

        $parts = $this->splitter->split($original, 300);

        $this->assertCount(2, $parts);
        $names = array_merge(...array_map(static fn (PolicyInterface $part): array => $part->names(), $parts));
        sort($names);
        $this->assertSame(['connect-src', 'img-src'], $names);
        $this->assertSameBrowserDecisions([$original], $parts);
    }
}
