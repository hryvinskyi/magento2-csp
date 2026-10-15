<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy\Split;

use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use Hryvinskyi\Csp\Api\PolicySplitterInterface;
use Hryvinskyi\Csp\Model\Policy\Split\VerifiedPolicySplitter;
use Hryvinskyi\Csp\Test\Unit\Model\Policy\ParsesPolicies;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Runs the verifying splitter over strategies that return fixed parts, with the real equivalence check.
 */
class VerifiedPolicySplitterTest extends TestCase
{
    use ParsesPolicies;

    private const HEADER = 'img-src a.example.com b.example.com; connect-src c.example.com d.example.com; '
        . 'report-uri /csp';

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testKeepsPartsThatTogetherAllowWhatThePolicyAllows(): void
    {
        $parts = [
            $this->parsePolicy('img-src a.example.com b.example.com; report-uri /csp'),
            $this->parsePolicy('connect-src c.example.com d.example.com; report-uri /csp'),
        ];
        $this->logger->expects($this->never())->method('warning');

        $this->assertSame($parts, $this->splitter($parts)->split($this->parsePolicy(self::HEADER), 60));
    }

    public function testReturnsThePolicyWithoutAskingTheStrategyWhenItFits(): void
    {
        $policy = $this->parsePolicy(self::HEADER);
        $strategy = $this->createMock(PolicySplitterInterface::class);
        $strategy->expects($this->never())->method('split');

        $splitter = new VerifiedPolicySplitter($strategy, $this->equivalence(), $this->logger);

        $this->assertSame([$policy], $splitter->split($policy, 4096));
    }

    public function testReturnsThePolicyWhenThePartsAllowSomethingElse(): void
    {
        $policy = $this->parsePolicy(self::HEADER);
        $parts = [
            $this->parsePolicy('img-src a.example.com b.example.com; report-uri /csp'),
            $this->parsePolicy('connect-src c.example.com; report-uri /csp'),
        ];
        $this->logger->expects($this->once())->method('warning')->with($this->stringContains('connect-src'));

        $this->assertSame([$policy], $this->splitter($parts)->split($policy, 60));
    }

    public function testReturnsThePolicyWhenAPartExceedsTheLimit(): void
    {
        $policy = $this->parsePolicy(self::HEADER);
        $parts = [
            $this->parsePolicy('img-src a.example.com b.example.com; report-uri /csp'),
            $this->parsePolicy('connect-src c.example.com d.example.com; report-uri /csp'),
        ];
        $this->logger->expects($this->once())->method('warning')->with($this->stringContains('exceed 50 bytes'));

        $this->assertSame([$policy], $this->splitter($parts)->split($policy, 50));
    }

    public function testReturnsThePolicyWhenTheStrategyReturnsOnePart(): void
    {
        $policy = $this->parsePolicy(self::HEADER);

        $this->assertSame([$policy], $this->splitter([$policy])->split($policy, 60));
    }

    /**
     * Verifying splitter over a strategy that always returns the given parts.
     *
     * @param list<PolicyInterface> $parts
     * @return VerifiedPolicySplitter
     */
    private function splitter(array $parts): VerifiedPolicySplitter
    {
        $strategy = $this->createStub(PolicySplitterInterface::class);
        $strategy->method('split')->willReturn($parts);

        return new VerifiedPolicySplitter($strategy, $this->equivalence(), $this->logger);
    }
}
