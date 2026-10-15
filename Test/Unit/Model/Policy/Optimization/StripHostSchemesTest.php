<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy\Optimization;

use Hryvinskyi\Csp\Api\Config\OptimizationConfigInterface;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\DocumentSchemeInterface;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\Optimization\StripHostSchemes;
use Hryvinskyi\Csp\Test\Unit\Model\Policy\ParsesPolicies;
use Hryvinskyi\Csp\Test\Unit\Support\AssertsBrowserDecisions;
use PHPUnit\Framework\TestCase;

class StripHostSchemesTest extends TestCase
{
    use ParsesPolicies;
    use AssertsBrowserDecisions;

    private const HEADER = "default-src 'self'; "
        . "script-src 'self' https://cdn.example.com/lib/ https://www.google.com/recaptcha/ http://cdn.example.com; "
        . "connect-src 'self' wss://ws.example.com https://*.example.com:8443; report-uri https://shop.example/r";

    public function testStripsHttpsOnlyAndKeepsPortsPathsAndOtherSchemes(): void
    {
        $policy = $this->step(true)->apply($this->parsePolicy(self::HEADER));

        $this->assertSame(
            ["'self'", 'cdn.example.com/lib/', 'www.google.com/recaptcha/', 'http://cdn.example.com'],
            $policy->sources('script-src')
        );
        $this->assertSame(["'self'", 'wss://ws.example.com', '*.example.com:8443'], $policy->sources('connect-src'));
        $this->assertSame(['https://shop.example/r'], $policy->sources('report-uri'));
    }

    public function testKeepsWhatAnHttpsPageAllows(): void
    {
        $original = $this->parsePolicy(self::HEADER);

        $this->assertSameBrowserDecisions([$original], [$this->step(true)->apply($original)]);
    }

    public function testIsOffOnHttpPages(): void
    {
        $this->assertTrue($this->step(true)->isEnabled());
        $this->assertFalse($this->step(false)->isEnabled());
    }

    /**
     * @param bool $securePage
     * @return StripHostSchemes
     */
    private function step(bool $securePage): StripHostSchemes
    {
        $config = $this->createStub(OptimizationConfigInterface::class);
        $config->method('isSchemeStrippingEnabled')->willReturn(true);
        $scheme = $this->createStub(DocumentSchemeInterface::class);
        $scheme->method('isSecure')->willReturn($securePage);

        return new StripHostSchemes($config, new DirectiveCatalog(), new HostSourceParser(), $scheme);
    }
}
