<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy;

use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\SourceListCanonicalizer;
use PHPUnit\Framework\TestCase;

class SourceListCanonicalizerTest extends TestCase
{
    private SourceListCanonicalizer $canonicalizer;

    protected function setUp(): void
    {
        $this->canonicalizer = new SourceListCanonicalizer(new DirectiveCatalog(), new HostSourceParser());
    }

    public function testDropsScriptOnlyExpressionsOutsideScriptAndStyleDirectives(): void
    {
        $this->assertSame(
            ["'self'"],
            $this->canonicalizer->canonicalize(
                'img-src',
                ["'self'", "'unsafe-inline'", "'unsafe-eval'", "'nonce-abc'", "'sha256-xyz='"]
            )
        );
    }

    public function testKeepsScriptOnlyExpressionsInScriptDirectives(): void
    {
        $this->assertSame(
            ["'nonce-abc'", "'self'", "'sha256-xyz='", "'unsafe-inline'"],
            $this->canonicalizer->canonicalize('script-src-elem', ["'self'", "'unsafe-inline'", "'nonce-abc'", "'sha256-xyz='"])
        );
    }

    public function testLowerCasesSchemesAndHostsButKeepsSchemesNoncesAndPaths(): void
    {
        $this->assertSame(
            ['cdn.example.com', 'cdn.example.com/Lib/', 'http://cdn.example.com', 'https://cdn.example.com', 'wss://ws.example.com'],
            $this->canonicalizer->canonicalize(
                'connect-src',
                ['https://CDN.example.com', 'http://cdn.example.com', 'cdn.example.com/Lib/', 'CDN.example.com', 'wss://ws.example.com']
            )
        );
        $this->assertSame(["'nonce-AbC'"], $this->canonicalizer->canonicalize('script-src', ["'nonce-AbC'"]));
    }

    public function testNoneNextToOtherSourcesIsIgnoredAndANoneOnlyListIsEmpty(): void
    {
        $this->assertSame(["'self'"], $this->canonicalizer->canonicalize('img-src', ["'none'", "'self'"]));
        $this->assertSame([], $this->canonicalizer->canonicalize('img-src', ["'none'"]));
        $this->assertSame([], $this->canonicalizer->canonicalize('img-src', ["'unsafe-inline'"]));
    }
}
