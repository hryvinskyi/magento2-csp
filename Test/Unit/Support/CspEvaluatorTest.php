<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Support;

use Hryvinskyi\Csp\Model\Policy\Policy;
use PHPUnit\Framework\TestCase;

/**
 * The oracle itself is checked against behaviour documented by the CSP Level 3 specification.
 */
class CspEvaluatorTest extends TestCase
{
    /**
     * @dataProvider decisions
     * @param array<string, list<string>> $directives
     * @param CspRequest $request
     * @param bool $expected
     * @param string $documentScheme
     */
    public function testDecision(array $directives, CspRequest $request, bool $expected, string $documentScheme = 'https'): void
    {
        $this->assertSame(
            $expected,
            (new CspEvaluator($documentScheme))->allows([new Policy($directives)], $request),
            $request->label()
        );
    }

    /**
     * @return array<string, array{0: array<string, list<string>>, 1: CspRequest, 2: bool, 3?: string}>
     */
    public static function decisions(): array
    {
        $script = static fn (string $url): CspRequest => new CspRequest('script', CspRequest::KIND_URL, $url);
        $inline = new CspRequest('script', CspRequest::KIND_INLINE);

        return [
            'unrestricted without directive' => [['img-src' => ["'self'"]], $script('https://evil.example/x.js'), true],
            'falls back to default-src' => [['default-src' => ["'self'"]], $script('https://evil.example/x.js'), false],
            'self' => [['script-src' => ["'self'"]], $script('https://shop.example/a.js'), true],
            'unsafe-inline allows inline' => [['script-src' => ["'unsafe-inline'"]], $inline, true],
            'nonce disables unsafe-inline' => [['script-src' => ["'unsafe-inline'", "'nonce-x'"]], $inline, false],
            'matching nonce' => [
                ['script-src' => ["'nonce-x'"]],
                new CspRequest('script', CspRequest::KIND_INLINE, '', 'x'),
                true,
            ],
            'eval needs unsafe-eval in script-src, not script-src-elem' => [
                ['script-src-elem' => ["'unsafe-eval'"], 'script-src' => ["'self'"]],
                new CspRequest('script', CspRequest::KIND_EVAL),
                false,
            ],
            'star does not match wss' => [['connect-src' => ['*']], new CspRequest('connect', CspRequest::KIND_URL, 'wss://ws.example.com/s'), false],
            'scheme-less host does not match wss on https page' => [
                ['connect-src' => ['ws.example.com']],
                new CspRequest('connect', CspRequest::KIND_URL, 'wss://ws.example.com/s'),
                false,
            ],
            'wss: scheme source matches any wss host' => [
                ['connect-src' => ['wss:']],
                new CspRequest('connect', CspRequest::KIND_URL, 'wss://evil.example/s'),
                true,
            ],
            'wildcard does not match bare domain' => [['script-src' => ['*.example.com']], $script('https://example.com/x.js'), false],
            'portless source needs default port' => [['script-src' => ['*.example.com']], $script('https://a.example.com:8443/x.js'), false],
            'path prefix' => [['script-src' => ['www.google.com/recaptcha/']], $script('https://www.google.com/recaptcha/api.js'), true],
            'path outside prefix' => [['script-src' => ['www.google.com/recaptcha/']], $script('https://www.google.com/complete/search'), false],
            'scheme-less host matches http on http page' => [['script-src' => ['cdn.example.com']], $script('http://cdn.example.com/lib/app.js'), true, 'http'],
            'https host does not match http on http page' => [['script-src' => ['https://cdn.example.com']], $script('http://cdn.example.com/lib/app.js'), false, 'http'],
            'strict-dynamic ignores host allowlist' => [['script-src' => ["'strict-dynamic'", 'cdn.example.com']], $script('https://cdn.example.com/lib/app.js'), false],
            'workers fall back through child-src' => [
                ['script-src' => ['cdn.example.com'], 'child-src' => ["'self'"]],
                new CspRequest('worker', CspRequest::KIND_URL, 'https://cdn.example.com/lib/app.js'),
                false,
            ],
        ];
    }

    public function testEveryHeaderMustAllow(): void
    {
        $request = new CspRequest('img', CspRequest::KIND_URL, 'https://cdn.example.com/a.png');

        $this->assertFalse((new CspEvaluator())->allows([
            new Policy(['img-src' => ['cdn.example.com']]),
            new Policy(['default-src' => ["'self'"]]),
        ], $request));
    }
}
