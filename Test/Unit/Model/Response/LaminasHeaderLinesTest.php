<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Response;

use Hryvinskyi\Csp\Model\Response\LaminasHeaderLines;
use Laminas\Http\Header\ContentSecurityPolicy;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use PHPUnit\Framework\TestCase;

/**
 * Runs the adapter on Magento's real response class.
 */
class LaminasHeaderLinesTest extends TestCase
{
    private const CSP = 'Content-Security-Policy';
    private const REPORT_ONLY = 'Content-Security-Policy-Report-Only';

    private LaminasHeaderLines $lines;

    protected function setUp(): void
    {
        $this->lines = new LaminasHeaderLines(['content-security-policy' => ContentSecurityPolicy::class]);
    }

    public function testReadsEveryLineOfTheHeaderOnly(): void
    {
        $response = new Response();
        $this->lines->allowSeveralLines($response, self::CSP);
        $response->setHeader(self::CSP, "img-src 'self'", false);
        $response->setHeader(self::CSP, "font-src 'self'", false);
        $response->setHeader('X-Frame-Options', 'DENY', false);

        $this->assertSame(["img-src 'self';", "font-src 'self';"], $this->lines->values($response, 'content-security-policy'));
        $this->assertSame([], $this->lines->values($response, self::REPORT_ONLY));
    }

    public function testSendsEachValueOnItsOwnLineOnceSeveralLinesAreAllowed(): void
    {
        $response = $this->response("img-src 'self'");

        $this->assertTrue($this->lines->allowSeveralLines($response, self::CSP));
        $this->assertTrue($this->lines->replace($response, self::CSP, ["img-src 'self'", "font-src 'self'"]));
        $this->assertSame(["img-src 'self';", "font-src 'self';"], $this->lines->values($response, self::CSP));
    }

    public function testReportsThatSeveralValuesOfAHeaderWithoutAMultipleHeaderClassAreNotSentAsSeparateLines(): void
    {
        $response = new Response();

        $this->assertFalse($this->lines->replace($response, self::REPORT_ONLY, ["img-src 'self'", "font-src 'self'"]));
    }

    public function testReportsALineTheMultipleHeaderClassRejects(): void
    {
        $response = $this->response("img-src 'self'");
        $this->lines->allowSeveralLines($response, self::CSP);

        $this->assertFalse($this->lines->replace($response, self::CSP, ["img-src 'self'", "fenced-frame-src 'self'"]));
    }

    public function testWritesOneValueWithoutSeveralLines(): void
    {
        $response = $this->response("img-src 'self' 'self'");

        $this->assertTrue($this->lines->replace($response, self::CSP, ["img-src 'self'"]));
        $this->assertSame(["img-src 'self';"], $this->lines->values($response, self::CSP));
    }

    public function testCannotAllowSeveralLinesOfAHeaderWithoutAMultipleHeaderClass(): void
    {
        $this->assertFalse($this->lines->allowSeveralLines($this->response("img-src 'self'"), self::REPORT_ONLY));
    }

    /**
     * Response carrying one CSP line, as core renders it.
     *
     * @param string $value
     * @return Response
     */
    private function response(string $value): Response
    {
        $response = new Response();
        $response->setHeader(self::CSP, $value, true);

        return $response;
    }
}
