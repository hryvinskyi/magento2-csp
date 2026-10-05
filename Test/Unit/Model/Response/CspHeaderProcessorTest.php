<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Response;

use Hryvinskyi\Csp\Api\Config\HeaderSplittingConfigInterface;
use Hryvinskyi\Csp\Api\Config\OptimizationConfigInterface;
use Hryvinskyi\Csp\Api\OversizedHeaderNoticeInterface;
use Hryvinskyi\Csp\Api\ResponseHeaderLinesInterface;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\FallbackResolver;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\Optimization\RemoveDuplicateSources;
use Hryvinskyi\Csp\Model\Policy\PolicyOptimizer;
use Hryvinskyi\Csp\Model\Policy\PolicyParser;
use Hryvinskyi\Csp\Model\Policy\Split\DirectiveFamilySplitter;
use Hryvinskyi\Csp\Model\Policy\Split\VerifiedPolicySplitter;
use Hryvinskyi\Csp\Model\Response\CspHeaderProcessor;
use Hryvinskyi\Csp\Model\Response\LaminasHeaderLines;
use Hryvinskyi\Csp\Test\Unit\Model\Policy\ParsesPolicies;
use Laminas\Http\Header\ContentSecurityPolicy;
use Laminas\Http\Header\ContentSecurityPolicyReportOnly;
use Laminas\Http\Header\HeaderInterface;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Runs the processor on Magento's real response class, so Laminas parses and sends the header lines as in production.
 */
class CspHeaderProcessorTest extends TestCase
{
    use ParsesPolicies;

    private const CSP = 'Content-Security-Policy';

    /**
     * @var OversizedHeaderNoticeInterface&MockObject
     */
    private OversizedHeaderNoticeInterface $notice;

    protected function setUp(): void
    {
        $this->notice = $this->createMock(OversizedHeaderNoticeInterface::class);
    }

    public function testLeavesHeadersAloneWhenOptimisationAndSplittingAreOff(): void
    {
        $response = $this->response("img-src 'self' 'self'");

        $this->processor(optimize: false, split: false)->processHeaders($response);

        $this->assertSame(["img-src 'self' 'self';"], $this->lines($response));
    }

    public function testRewritesTheHeaderOptimised(): void
    {
        $response = $this->response("img-src 'self' 'self' data:");

        $this->processor(optimize: true, split: false)->processHeaders($response);

        $this->assertSame(["img-src 'self' data:;"], $this->lines($response));
    }

    public function testRewritesOnlyTheConfiguredHeaders(): void
    {
        $response = $this->response("img-src 'self' 'self'");
        $response->setHeader('Content-Security-Policy-Report-Only', "font-src 'self' 'self'", true);
        $response->setHeader('X-Other-Policy', "font-src 'self' 'self'", true);

        $this->processor(optimize: true, split: false)->processHeaders($response);

        $this->assertSame(["img-src 'self';"], $this->lines($response));
        $this->assertSame(["font-src 'self'"], $this->lines($response, 'Content-Security-Policy-Report-Only'));
        $this->assertSame(["font-src 'self' 'self'"], $this->lines($response, 'X-Other-Policy'));
    }

    public function testSendsEachPartOnItsOwnLine(): void
    {
        $response = $this->response('img-src ' . $this->hosts('img') . '; connect-src ' . $this->hosts('api'));
        $this->notice->expects($this->never())->method('record');

        $this->processor(optimize: false, split: true, maxBytes: 300)->processHeaders($response);

        $directives = array_map(
            static fn (string $line): string => strstr($line, ' ', true) ?: $line,
            $this->lines($response)
        );
        sort($directives);
        $this->assertSame(['connect-src', 'img-src'], $directives);
    }

    public function testKeepsTheHeaderWholeWhenTheResponseCannotCarrySeveralLines(): void
    {
        $response = $this->response('img-src ' . $this->hosts('img') . '; connect-src ' . $this->hosts('api'));
        $this->notice->expects($this->once())->method('record');

        $this->processor(optimize: false, split: true, maxBytes: 300, canSplit: false)->processHeaders($response);

        $this->assertCount(1, $this->lines($response));
    }

    public function testKeepsTheHeaderWholeWhenAPartHoldsADirectiveLaminasRejects(): void
    {
        $response = $this->response('img-src ' . $this->hosts('img') . '; fenced-frame-src ' . $this->hosts('frame'));
        $this->notice->expects($this->once())->method('record');

        $this->processor(optimize: false, split: true, maxBytes: 300)->processHeaders($response);

        $this->assertCount(1, $this->lines($response));
    }

    public function testLeavesAHeaderThatAlreadyHasSeveralLinesAlone(): void
    {
        $response = new Response();
        $this->headerLines()->allowSeveralLines($response, self::CSP);
        $response->setHeader(self::CSP, "img-src 'self' 'self'", false);
        $response->setHeader(self::CSP, "font-src 'self'", false);

        $this->processor(optimize: true, split: false)->processHeaders($response);

        $this->assertSame(["img-src 'self' 'self';", "font-src 'self';"], $this->lines($response));
    }

    /**
     * @param bool $optimize
     * @param bool $split
     * @param int $maxBytes
     * @param bool $canSplit
     * @return CspHeaderProcessor
     */
    private function processor(bool $optimize, bool $split, int $maxBytes = 4096, bool $canSplit = true): CspHeaderProcessor
    {
        $optimizationConfig = $this->createStub(OptimizationConfigInterface::class);
        $optimizationConfig->method('isValueOptimizationEnabled')->willReturn($optimize);
        $splittingConfig = $this->createStub(HeaderSplittingConfigInterface::class);
        $splittingConfig->method('isHeaderSplittingEnabled')->willReturn($split);
        $splittingConfig->method('getMaxHeaderSize')->willReturn($maxBytes);
        $catalog = new DirectiveCatalog();
        $parser = new HostSourceParser();
        $headerLines = $canSplit ? $this->headerLines() : $this->singleLineResponse($this->headerLines());

        return new CspHeaderProcessor(
            $optimizationConfig,
            $splittingConfig,
            new PolicyParser($this->policyFactory()),
            new PolicyOptimizer($optimizationConfig, ['duplicates' => new RemoveDuplicateSources($catalog, $parser)]),
            new VerifiedPolicySplitter(
                new DirectiveFamilySplitter($catalog, new FallbackResolver($catalog), $this->policyFactory(), new NullLogger()),
                $this->equivalence(),
                new NullLogger()
            ),
            $headerLines,
            $this->notice,
            new NullLogger(),
            ['enforced' => self::CSP, 'report_only' => 'Content-Security-Policy-Report-Only']
        );
    }

    /**
     * Header lines of Magento's response, configured as in di.xml.
     *
     * @return LaminasHeaderLines
     */
    private function headerLines(): LaminasHeaderLines
    {
        return new LaminasHeaderLines([
            'content-security-policy' => ContentSecurityPolicy::class,
            'content-security-policy-report-only' => ContentSecurityPolicyReportOnly::class,
        ]);
    }

    /**
     * Header lines of a response that can carry only one line of a header.
     *
     * @param ResponseHeaderLinesInterface $lines
     * @return ResponseHeaderLinesInterface
     */
    private function singleLineResponse(ResponseHeaderLinesInterface $lines): ResponseHeaderLinesInterface
    {
        return new class ($lines) implements ResponseHeaderLinesInterface {
            /**
             * @param ResponseHeaderLinesInterface $lines
             */
            public function __construct(private readonly ResponseHeaderLinesInterface $lines)
            {
            }

            /**
             * @inheritDoc
             */
            public function values(HttpResponse $response, string $headerName): array
            {
                return $this->lines->values($response, $headerName);
            }

            /**
             * @inheritDoc
             */
            public function allowSeveralLines(HttpResponse $response, string $headerName): bool
            {
                return false;
            }

            /**
             * @inheritDoc
             */
            public function replace(HttpResponse $response, string $headerName, array $values): bool
            {
                return $this->lines->replace($response, $headerName, $values);
            }
        };
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

    /**
     * Values of the lines of a header the response would send.
     *
     * @param Response $response
     * @param string $headerName
     * @return list<string>
     */
    private function lines(Response $response, string $headerName = self::CSP): array
    {
        $lines = [];
        foreach ($response->getHeaders() as $header) {
            if ($header instanceof HeaderInterface && $header->getFieldName() === $headerName) {
                $lines[] = $header->getFieldValue();
            }
        }

        return $lines;
    }

    /**
     * Twelve hosts under a prefix.
     *
     * @param string $prefix
     * @return string
     */
    private function hosts(string $prefix): string
    {
        return implode(' ', array_map(static fn (int $i): string => sprintf('%s%d.example.com', $prefix, $i), range(1, 12)));
    }
}
