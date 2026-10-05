<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Report\Parser;

use Hryvinskyi\Csp\Api\Data\ViolationInterface;
use Hryvinskyi\Csp\Model\Report\Parser\LegacyReportParser;
use Hryvinskyi\Csp\Model\Report\Parser\ParserPool;
use Hryvinskyi\Csp\Model\Report\Parser\ReportingApiParser;
use Hryvinskyi\Csp\Model\Report\Parser\ViolationBuilder;
use Hryvinskyi\Csp\Test\Unit\Support\RealViolationFactory;
use PHPUnit\Framework\TestCase;

class ParsersTest extends TestCase
{
    public function testReadsALegacyReport(): void
    {
        $violations = $this->pool()->forContentType('application/csp-report')?->parse((string)json_encode(['csp-report' => [
            'document-uri' => 'https://shop.example.com/cart?id=1',
            'blocked-uri' => 'https://cdn.example.org/a.js',
            'violated-directive' => 'script-src-elem',
            'effective-directive' => 'script-src-elem',
            'original-policy' => "script-src 'self'",
            'disposition' => 'report',
            'status-code' => 200,
            'line-number' => 12,
        ]]));

        $this->assertNotNull($violations);
        $this->assertCount(1, $violations);
        $this->assertSame(
            ['https://shop.example.com/cart?id=1', 'https://cdn.example.org/a.js', 'script-src-elem', 'report', 200, 12],
            $this->fields($violations[0])
        );
    }

    public function testFallsBackToTheViolatedDirective(): void
    {
        $violations = (new LegacyReportParser(new ViolationBuilder(new RealViolationFactory())))->parse(
            (string)json_encode(['csp-report' => ['blocked-uri' => 'inline', 'violated-directive' => 'style-src-attr \'self\'']])
        );

        $this->assertSame('style-src-attr', $violations[0]->getEffectiveDirective());
    }

    public function testReadsCspReportsOfABatchAndSkipsOtherTypes(): void
    {
        $body = (string)json_encode([
            ['type' => 'csp-violation', 'body' => [
                'documentURL' => 'https://shop.example.com/',
                'blockedURL' => 'eval',
                'effectiveDirective' => 'script-src',
                'disposition' => 'enforce',
                'statusCode' => 200,
                'lineNumber' => 3,
            ]],
            ['type' => 'deprecation', 'body' => ['id' => 'x']],
            ['type' => 'csp-violation', 'body' => ['documentURL' => 'https://shop.example.com/']],
        ]);

        $violations = $this->pool()->forContentType('Application/Reports+JSON; charset=utf-8')?->parse($body);

        $this->assertNotNull($violations);
        $this->assertCount(1, $violations);
        $this->assertSame(['https://shop.example.com/', 'eval', 'script-src', 'enforce', 200, 3], $this->fields($violations[0]));
    }

    /**
     * @dataProvider malformedBodies
     * @param string $contentType
     * @param string $body
     */
    public function testRefusesMalformedBodies(string $contentType, string $body): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->pool()->forContentType($contentType)?->parse($body);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function malformedBodies(): array
    {
        return [
            'not JSON' => ['application/csp-report', '{'],
            'no csp-report' => ['application/json', '{"report": {}}'],
            'batch that is an object' => ['application/reports+json', '{"type": "csp-violation"}'],
            'too deep' => ['application/json', str_repeat('[', 20) . str_repeat(']', 20)],
        ];
    }

    public function testHasNoParserForOtherContentTypes(): void
    {
        $this->assertNull($this->pool()->forContentType('text/plain'));
    }

    /**
     * @return ParserPool
     */
    private function pool(): ParserPool
    {
        $builder = new ViolationBuilder(new RealViolationFactory());

        return new ParserPool(['legacy' => new LegacyReportParser($builder), 'reporting_api' => new ReportingApiParser($builder)]);
    }

    /**
     * @param ViolationInterface $violation
     * @return array{string, string, string, string, int, int|null}
     */
    private function fields(ViolationInterface $violation): array
    {
        return [
            $violation->getDocumentUri(),
            $violation->getBlockedUri(),
            $violation->getEffectiveDirective(),
            $violation->getDisposition(),
            $violation->getStatusCode(),
            $violation->getLineNumber(),
        ];
    }
}
