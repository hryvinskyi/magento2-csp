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
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\FallbackResolver;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\Optimization\RemoveDuplicateSources;
use Hryvinskyi\Csp\Model\Policy\PolicyOptimizer;
use Hryvinskyi\Csp\Model\Policy\PolicyParser;
use Hryvinskyi\Csp\Model\Policy\PolicySplitter;
use Hryvinskyi\Csp\Model\Response\CspHeaderProcessor;
use Hryvinskyi\Csp\Model\Response\LaminasPluginRegistrar;
use Hryvinskyi\Csp\Model\Response\OversizedHeaderNotice;
use Hryvinskyi\Csp\Test\Unit\Model\Policy\ParsesPolicies;
use Laminas\Http\Header\HeaderInterface;
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
     * @var OversizedHeaderNotice&MockObject
     */
    private OversizedHeaderNotice $notice;

    protected function setUp(): void
    {
        $this->notice = $this->createMock(OversizedHeaderNotice::class);
    }

    public function testLeavesHeadersAloneWhenOptimisationAndSplittingAreOff(): void
    {
        $response = $this->response("img-src 'self' 'self'");

        $this->processor(optimize: false, split: false)->process($response);

        $this->assertSame(["img-src 'self' 'self';"], $this->lines($response));
    }

    public function testRewritesTheHeaderOptimised(): void
    {
        $response = $this->response("img-src 'self' 'self' data:");

        $this->processor(optimize: true, split: false)->process($response);

        $this->assertSame(["img-src 'self' data:;"], $this->lines($response));
    }

    public function testSendsEachPartOnItsOwnLine(): void
    {
        $response = $this->response('img-src ' . $this->hosts('img') . '; connect-src ' . $this->hosts('api'));
        $this->notice->expects($this->never())->method('record');

        $this->processor(optimize: false, split: true, maxBytes: 300)->process($response);

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

        $this->processor(optimize: false, split: true, maxBytes: 300, canSplit: false)->process($response);

        $this->assertCount(1, $this->lines($response));
    }

    public function testKeepsTheHeaderWholeWhenAPartHoldsADirectiveLaminasRejects(): void
    {
        $response = $this->response('img-src ' . $this->hosts('img') . '; fenced-frame-src ' . $this->hosts('frame'));
        $this->notice->expects($this->once())->method('record');

        $this->processor(optimize: false, split: true, maxBytes: 300)->process($response);

        $this->assertCount(1, $this->lines($response));
    }

    public function testLeavesAHeaderThatAlreadyHasSeveralLinesAlone(): void
    {
        $response = new Response();
        (new LaminasPluginRegistrar())->registerPlugins($response);
        $response->setHeader(self::CSP, "img-src 'self' 'self'", false);
        $response->setHeader(self::CSP, "font-src 'self'", false);

        $this->processor(optimize: true, split: false)->process($response);

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
        $registrar = $canSplit ? new LaminasPluginRegistrar() : $this->createStub(LaminasPluginRegistrar::class);
        $catalog = new DirectiveCatalog();
        $parser = new HostSourceParser();

        return new CspHeaderProcessor(
            $optimizationConfig,
            $splittingConfig,
            new PolicyParser($this->policyFactory()),
            new PolicyOptimizer($optimizationConfig, ['duplicates' => new RemoveDuplicateSources($catalog, $parser)]),
            new PolicySplitter(
                $catalog,
                new FallbackResolver($catalog),
                $this->equivalence(),
                $this->policyFactory(),
                new NullLogger()
            ),
            $registrar,
            $this->notice,
            new NullLogger()
        );
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
     * Values of the CSP lines the response would send.
     *
     * @param Response $response
     * @return list<string>
     */
    private function lines(Response $response): array
    {
        $lines = [];
        foreach ($response->getHeaders() as $header) {
            if ($header instanceof HeaderInterface && $header->getFieldName() === self::CSP) {
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
