<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy;

use Hryvinskyi\Csp\Model\Policy\Policy;
use Hryvinskyi\Csp\Test\Unit\Support\CspEvaluator;
use Hryvinskyi\Csp\Test\Unit\Support\RequestMatrix;
use PHPUnit\Framework\TestCase;

/**
 * A cover must allow, by the independent oracle, every request the list it covers allows.
 */
class SourceListCoverTest extends TestCase
{
    use ParsesPolicies;

    /**
     * @dataProvider lists
     * @param string $directive
     * @param list<string> $sources
     */
    public function testTheCoverAllowsEverythingTheListAllows(string $directive, array $sources): void
    {
        $cover = $this->sourceListCover()->coverOf($directive, $sources);

        $this->assertTrue($this->sourceListCover()->covers($directive, $cover, $sources));
        foreach (['https', 'http'] as $documentScheme) {
            $evaluator = new CspEvaluator($documentScheme);
            foreach (RequestMatrix::requests() as $request) {
                if ($evaluator->allows([new Policy([$directive => $sources])], $request)) {
                    $this->assertTrue(
                        $evaluator->allows([new Policy([$directive => $cover])], $request),
                        $documentScheme . ' page: ' . $request->label()
                    );
                }
            }
        }
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function lists(): array
    {
        return [
            'magento script-src' => [
                'script-src',
                ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https://cdn.example.com/lib/', '*.example.com'],
            ],
            'nonce and hash' => ['script-src', ["'self'", "'nonce-" . RequestMatrix::NONCE . "'", "'sha256-" . RequestMatrix::HASH . "'"]],
            'unsafe-hashes' => ['style-src', ["'unsafe-hashes'", "'sha256-" . RequestMatrix::HASH . "'"]],
            'schemes' => ['img-src', ["'self'", 'data:', 'blob:', 'https:']],
            'websockets' => ['connect-src', ["'self'", 'wss://ws.example.com', 'ws:']],
            'star' => ['connect-src', ['*']],
            'none' => ['object-src', ["'none'"]],
            'scheme-less hosts' => ['frame-src', ['www.youtube.com', 'js.example.com']],
            'worker through child-src' => ['child-src', ["'self'", 'blob:', 'http:']],
        ];
    }

    /**
     * @dataProvider notCovered
     * @param string $directive
     * @param list<string> $cover
     * @param list<string> $sources
     */
    public function testRefusesWhatItCannotProveCovered(string $directive, array $cover, array $sources): void
    {
        $this->assertFalse($this->sourceListCover()->covers($directive, $cover, $sources));
    }

    /**
     * @return array<string, array{string, list<string>, list<string>}>
     */
    public static function notCovered(): array
    {
        return [
            'strict-dynamic trusts scripts from any URL' => [
                'script-src',
                ['*', "'unsafe-inline'", "'unsafe-eval'"],
                ["'strict-dynamic'", "'nonce-x'"],
            ],
            'unknown keyword' => ['script-src', ['*', "'unsafe-inline'", "'unsafe-eval'"], ["'trusted-types-eval'"]],
            'scheme the cover lacks' => ['img-src', ['*'], ['data:']],
            'websocket the cover lacks' => ['connect-src', ['*'], ["'self'"]],
            'no star' => ['img-src', ['data:'], ['data:']],
            'no unsafe-eval in a script directive' => ['script-src', ['*', "'unsafe-inline'"], ["'self'"]],
            'nonce disables the cover\'s unsafe-inline' => [
                'script-src',
                ['*', "'unsafe-inline'", "'unsafe-eval'", "'nonce-y'"],
                ["'unsafe-inline'"],
            ],
        ];
    }

    public function testKeywordsAreLeftOutOfACoverOutsideScriptsAndStyles(): void
    {
        $this->assertSame(['*', 'blob:', 'data:', 'ws:', 'wss:'], $this->sourceListCover()->coverOf(
            'img-src',
            ["'self'", "'unsafe-inline'", 'data:', 'blob:', 'https://cdn.example.com']
        ));
    }
}
