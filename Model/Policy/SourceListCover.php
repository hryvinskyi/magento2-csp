<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy;

/**
 * A permissive source list that allows at least every request another source list allows in a directive.
 *
 * A browser enforces every part of a split header, so a part may leave a request type to another part by giving it
 * a cover: the other part's exact sources still decide. The part that keeps `default-src` needs covers, because
 * `default-src` alone governs prefetch and prerender requests and would otherwise also govern every request type
 * whose directive lives in another part.
 *
 * Conservative: a list with `'strict-dynamic'` or a keyword not listed here is never covered.
 */
class SourceListCover
{
    private const ANY_HTTP = '*';
    private const UNSAFE_INLINE = "'unsafe-inline'";
    private const UNSAFE_EVAL = "'unsafe-eval'";

    /**
     * Keywords whose allowances `*`, `'unsafe-inline'` and `'unsafe-eval'` include.
     */
    private const COVERED_KEYWORDS = [
        "'self'",
        "'none'",
        "'unsafe-inline'",
        "'unsafe-eval'",
        "'wasm-unsafe-eval'",
        "'unsafe-hashes'",
        "'report-sample'",
        "'inline-speculation-rules'",
    ];

    /**
     * Schemes `*` matches on any page: the http(s) schemes and the page's own, which is one of them.
     */
    private const STAR_SCHEMES = ['http', 'https'];

    /**
     * Schemes `'self'` reaches beyond the page's own: an upgrade to a websocket on the same host.
     */
    private const SELF_SCHEMES = ['ws', 'wss'];

    /**
     * Schemes a source expression of the key scheme also matches (CSP Level 3, scheme-part match).
     */
    private const UPGRADES = ['ws' => ['wss']];

    /**
     * @param DirectiveCatalog $catalog
     * @param HostSourceParser $hostSourceParser
     */
    public function __construct(
        private readonly DirectiveCatalog $catalog,
        private readonly HostSourceParser $hostSourceParser
    ) {
    }

    /**
     * The cover of the sources in the directive.
     *
     * @param string $directive
     * @param list<string> $sources
     * @return list<string>
     */
    public function coverOf(string $directive, array $sources): array
    {
        $cover = [self::ANY_HTTP];
        foreach ($this->reachedSchemes($sources) as $scheme) {
            $cover[] = $scheme . ':';
        }
        if ($this->catalog->isScriptOrStyleContext($directive)) {
            $cover[] = self::UNSAFE_INLINE;
            $cover[] = self::UNSAFE_EVAL;
        }

        return $cover;
    }

    /**
     * Whether the cover allows every request the sources allow in the directive.
     *
     * @param string $directive
     * @param list<string> $cover
     * @param list<string> $sources
     * @return bool
     */
    public function covers(string $directive, array $cover, array $sources): bool
    {
        $coverTokens = array_map('strtolower', $cover);
        if (!in_array(self::ANY_HTTP, $coverTokens, true)
            || ($this->catalog->isScriptOrStyleContext($directive) && !$this->allowsAnyInlineAndEval($cover))
        ) {
            return false;
        }
        foreach ($sources as $token) {
            if (SourceKind::of($token) === SourceKind::Keyword
                && !in_array(strtolower($token), self::COVERED_KEYWORDS, true)
            ) {
                return false;
            }
        }
        foreach ($this->reachedSchemes($sources) as $scheme) {
            if (!in_array($scheme . ':', $coverTokens, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the list allows every inline element, attribute and eval: `'unsafe-inline'` and `'unsafe-eval'`
     * present, and nothing that makes a browser ignore `'unsafe-inline'`.
     *
     * @param list<string> $sources
     * @return bool
     */
    private function allowsAnyInlineAndEval(array $sources): bool
    {
        $tokens = array_map('strtolower', $sources);
        if (!in_array(self::UNSAFE_INLINE, $tokens, true) || !in_array(self::UNSAFE_EVAL, $tokens, true)) {
            return false;
        }
        foreach ($tokens as $token) {
            $kind = SourceKind::of($token);
            if ($kind === SourceKind::Nonce || $kind === SourceKind::Hash || $token === "'strict-dynamic'") {
                return false;
            }
        }

        return true;
    }

    /**
     * Schemes other than http(s) that the sources can match, sorted.
     *
     * @param list<string> $sources
     * @return list<string>
     */
    private function reachedSchemes(array $sources): array
    {
        $schemes = [];
        foreach ($sources as $token) {
            foreach ($this->schemesOf($token) as $scheme) {
                $schemes[$scheme] = true;
            }
        }
        $reached = array_values(array_diff(array_map('strval', array_keys($schemes)), self::STAR_SCHEMES));
        sort($reached, SORT_STRING);

        return $reached;
    }

    /**
     * Schemes one source expression can match, besides the page's own.
     *
     * @param string $token
     * @return list<string>
     */
    private function schemesOf(string $token): array
    {
        $lower = strtolower($token);
        if ($lower === "'self'") {
            return self::SELF_SCHEMES;
        }
        $kind = SourceKind::of($token);
        $scheme = null;
        if ($kind === SourceKind::Scheme) {
            $scheme = substr($lower, 0, -1);
        }
        if ($kind === SourceKind::Host) {
            $scheme = $this->hostSourceParser->parse($token)['scheme'] ?? null;
        }

        return $scheme === null ? [] : [$scheme, ...(self::UPGRADES[$scheme] ?? [])];
    }
}
