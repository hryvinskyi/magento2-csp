<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy;

use Hryvinskyi\Csp\Model\Policy\SourceKind;
use PHPUnit\Framework\TestCase;

class SourceKindTest extends TestCase
{
    /**
     * @dataProvider tokens
     * @param string $token
     * @param SourceKind $expected
     */
    public function testClassifiesTokens(string $token, SourceKind $expected): void
    {
        $this->assertSame($expected, SourceKind::of($token));
    }

    /**
     * @return array<string, array{string, SourceKind}>
     */
    public static function tokens(): array
    {
        return [
            'self' => ["'self'", SourceKind::Keyword],
            'unsafe-inline' => ["'unsafe-inline'", SourceKind::Keyword],
            'none' => ["'none'", SourceKind::Keyword],
            'nonce' => ["'nonce-abc123=='", SourceKind::Nonce],
            'hash' => ["'sha256-AbC123+/='", SourceKind::Hash],
            'upper-case hash prefix' => ["'SHA384-AbC='", SourceKind::Hash],
            'scheme data' => ['data:', SourceKind::Scheme],
            'scheme https' => ['https:', SourceKind::Scheme],
            'scheme wss' => ['wss:', SourceKind::Scheme],
            'wildcard' => ['*', SourceKind::Wildcard],
            'host' => ['cdn.example.com', SourceKind::Host],
            'host with scheme' => ['https://cdn.example.com', SourceKind::Host],
            'websocket host' => ['wss://ws.example.com', SourceKind::Host],
            'wildcard host' => ['*.example.com', SourceKind::Host],
        ];
    }
}
