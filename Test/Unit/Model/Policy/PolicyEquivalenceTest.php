<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy;

use Hryvinskyi\Csp\Test\Unit\Support\AssertsBrowserDecisions;
use PHPUnit\Framework\TestCase;

/**
 * Whenever the module calls two policies equivalent, the oracle must find no request they decide differently; the
 * module may be stricter than the oracle (call equal policies different), never laxer.
 */
class PolicyEquivalenceTest extends TestCase
{
    use ParsesPolicies;
    use AssertsBrowserDecisions;

    /**
     * @dataProvider equivalentPairs
     * @param string $first
     * @param string $second
     */
    public function testEquivalentPairsDecideEveryRequestAlike(string $first, string $second): void
    {
        $a = $this->parsePolicy($first);
        $b = $this->parsePolicy($second);

        $this->assertTrue($this->equivalence()->isEquivalent($a, $b));
        $this->assertSameBrowserDecisions([$a], [$b]);
        $this->assertSameBrowserDecisions([$a], [$b], 'http');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function equivalentPairs(): array
    {
        return [
            'img-src ignoring inline equals its fallback' => [
                "default-src 'self' 'unsafe-inline' 'unsafe-eval'; img-src 'self' 'unsafe-inline'",
                "default-src 'self' 'unsafe-inline' 'unsafe-eval'",
            ],
            'case of keywords and hosts' => ["img-src 'SELF' CDN.example.com", "img-src 'self' cdn.example.com"],
            'repeated sources' => ["img-src 'self' 'self' data:", "img-src 'self' data:"],
            'none next to a source' => ["img-src 'none' 'self'", "img-src 'self'"],
        ];
    }

    /**
     * @dataProvider differentPairs
     * @param string $first
     * @param string $second
     * @param string $documentScheme
     */
    public function testPairsTheOracleSeparatesAreNotEquivalent(string $first, string $second, string $documentScheme): void
    {
        $a = $this->parsePolicy($first);
        $b = $this->parsePolicy($second);

        $this->assertNotSame([], $this->decisionChanges([$a], [$b], $documentScheme));
        $this->assertFalse($this->equivalence()->isEquivalent($a, $b));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function differentPairs(): array
    {
        return [
            'widened default-src reaches implicit directives' => [
                "default-src 'self'; font-src 'self' cdn.example.com",
                "default-src 'self' cdn.example.com",
                'https',
            ],
            'child-src removed re-routes workers' => [
                "default-src 'self'; script-src 'self' cdn.example.com; child-src 'self'",
                "default-src 'self'; script-src 'self' cdn.example.com",
                'https',
            ],
            'script-src differs only in eval' => [
                "default-src 'self'; script-src 'self' 'unsafe-eval'",
                "default-src 'self'",
                'https',
            ],
            'https stripped on an http page' => [
                'script-src https://cdn.example.com',
                'script-src cdn.example.com',
                'http',
            ],
            'path scope removed' => [
                'script-src www.google.com/recaptcha/',
                'script-src www.google.com',
                'https',
            ],
        ];
    }

    public function testDefaultSrcCopiedIntoEveryPartIsNotAnEquivalentSplit(): void
    {
        $original = $this->parsePolicy(
            "default-src 'self'; script-src 'self' cdn.example.com; img-src 'self' cdn.example.com; report-uri /r"
        );
        $parts = [
            $this->parsePolicy("script-src 'self' cdn.example.com; default-src 'self'; report-uri /r"),
            $this->parsePolicy("img-src 'self' cdn.example.com; default-src 'self'; report-uri /r"),
        ];

        $this->assertNotSame([], $this->decisionChanges([$original], $parts));
        $this->assertContains('script-src-elem', $this->equivalence()->splitDifferences($original, $parts));
    }

    public function testExplicitCopiesWithoutDefaultSrcLeavePrefetchUnrestricted(): void
    {
        $original = $this->parsePolicy("default-src 'self'; img-src cdn.example.com");
        $parts = [
            $this->parsePolicy('img-src cdn.example.com'),
            $this->parsePolicy(
                "script-src 'self'; style-src 'self'; child-src 'self'; connect-src 'self'; font-src 'self'; "
                . "manifest-src 'self'; media-src 'self'; object-src 'self'"
            ),
        ];

        $this->assertContains(
            'blocked→allowed: prefetch url https://evil.example/x.js',
            $this->decisionChanges([$original], $parts)
        );
        $this->assertSame(['default-src'], $this->equivalence()->splitDifferences($original, $parts));
    }

    public function testACoverNextToTheExactPartIsAnEquivalentSplit(): void
    {
        $original = $this->parsePolicy("default-src 'self'; img-src cdn.example.com data:");
        $parts = [
            $this->parsePolicy('img-src cdn.example.com data:'),
            $this->parsePolicy("default-src 'self'; img-src * data:"),
        ];

        $this->assertSame([], $this->decisionChanges([$original], $parts));
        $this->assertSame([], $this->equivalence()->splitDifferences($original, $parts));
    }

    public function testACoverAloneIsNotEquivalent(): void
    {
        $original = $this->parsePolicy("default-src 'self'; img-src cdn.example.com data:");
        $parts = [
            $this->parsePolicy("default-src 'self'; img-src * data:"),
            $this->parsePolicy("connect-src 'self'"),
        ];

        $this->assertNotSame([], $this->decisionChanges([$original], $parts));
        $this->assertContains('img-src', $this->equivalence()->splitDifferences($original, $parts));
    }

    public function testReportUriMustBeInEveryPart(): void
    {
        $original = $this->parsePolicy("img-src 'self'; font-src 'self'; report-uri /r");
        $parts = [
            $this->parsePolicy("img-src 'self'; report-uri /r"),
            $this->parsePolicy("font-src 'self'"),
        ];

        $this->assertSame(['report-uri'], $this->equivalence()->splitDifferences($original, $parts));
    }

    public function testFlagDirectivesMustSurvive(): void
    {
        $original = $this->parsePolicy("upgrade-insecure-requests; sandbox allow-scripts; img-src 'self'");
        $withoutFlags = $this->parsePolicy("img-src 'self'");

        $this->assertSame(
            ['upgrade-insecure-requests', 'sandbox'],
            $this->equivalence()->differences($original, $withoutFlags)
        );
    }

    /**
     * @dataProvider policiesToTrim
     * @param string $header
     */
    public function testRemovalCheckAgreesWithTheFullComparison(string $header): void
    {
        $policy = $this->parsePolicy($header);
        $equivalence = $this->equivalence();
        $names = array_merge($policy->names(), ['img-src', 'worker-src', 'frame-ancestors', 'sandbox']);

        foreach (array_unique($names) as $name) {
            $this->assertSame(
                $equivalence->isEquivalent($policy, $policy->without($name)),
                $equivalence->allowsTheSameWithout($policy, $name),
                $name . ' in ' . $header
            );
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function policiesToTrim(): array
    {
        return [
            'children equal to default' => [
                "default-src 'self' cdn.example.com; img-src 'self' cdn.example.com; "
                . "script-src 'self' cdn.example.com 'unsafe-inline'; style-src cdn.example.com 'self'",
            ],
            'script-only keywords ignored outside scripts' => [
                "default-src 'self' 'unsafe-inline' 'unsafe-eval'; img-src 'self' 'unsafe-inline'; font-src 'self'",
            ],
            'fallback chains' => [
                "default-src 'self'; script-src 'self' js.example.com; script-src-elem 'self' js.example.com; "
                . "child-src 'self'; worker-src 'self'; frame-src 'self'; script-src-attr 'none'",
            ],
            'standalone and flag directives' => [
                "default-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; "
                . 'upgrade-insecure-requests; report-uri /csp; sandbox allow-scripts',
            ],
            'no default-src' => [
                "img-src 'self' data:; connect-src 'self' wss://ws.example.com; media-src 'none'",
            ],
        ];
    }
}
