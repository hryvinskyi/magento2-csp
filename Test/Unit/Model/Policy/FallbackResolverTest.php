<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy;

use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\FallbackResolver;
use PHPUnit\Framework\TestCase;

class FallbackResolverTest extends TestCase
{
    use ParsesPolicies;

    private FallbackResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new FallbackResolver(new DirectiveCatalog());
    }

    public function testWorkersFallBackThroughChildSrcThenScriptSrc(): void
    {
        $withScript = $this->parsePolicy("default-src 'self'; script-src js.example.com");
        $withChild = $this->parsePolicy("default-src 'self'; script-src js.example.com; child-src frames.example.com");

        $this->assertSame('script-src', $this->resolver->effectiveDirective($withScript, 'worker-src'));
        $this->assertSame('child-src', $this->resolver->effectiveDirective($withChild, 'worker-src'));
    }

    public function testScriptElementsUseScriptSrcThenDefaultSrc(): void
    {
        $policy = $this->parsePolicy("default-src 'self' cdn.example.com");

        $this->assertSame('default-src', $this->resolver->effectiveDirective($policy, 'script-src-elem'));
        $this->assertSame(["'self'", 'cdn.example.com'], $this->resolver->effectiveSources($policy, 'script-src-elem'));
    }

    public function testUnrestrictedRequestTypeHasNoEffectiveDirective(): void
    {
        $policy = $this->parsePolicy('img-src data:');

        $this->assertNull($this->resolver->effectiveDirective($policy, 'connect-src'));
        $this->assertNull($this->resolver->effectiveSources($policy, 'connect-src'));
    }
}
