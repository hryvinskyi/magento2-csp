<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy;

/**
 * Canonical form of a source list in the context of one directive, for comparing what two lists allow.
 *
 * Conservative: two lists with the same canonical form allow the same requests on any page, but lists that allow the
 * same requests may still differ. Keywords, schemes and hosts are lower-cased; nonce and hash values and URL paths
 * are kept byte for byte, and schemes are never removed. Expressions a browser ignores in the directive (inline,
 * eval, nonces and hashes outside script and style directives) are dropped; `'none'` next to other sources is
 * dropped, and a list that allows nothing is empty.
 */
class SourceListCanonicalizer
{
    private const SCRIPT_AND_STYLE_KEYWORDS = [
        "'unsafe-inline'",
        "'unsafe-eval'",
        "'wasm-unsafe-eval'",
        "'unsafe-hashes'",
        "'strict-dynamic'",
        "'report-sample'",
        "'inline-speculation-rules'",
    ];

    private const NONE = "'none'";

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
     * Sorted, de-duplicated canonical tokens of a source list as the directive applies it.
     *
     * @param string $directive Directive whose rules apply to the list
     * @param list<string> $sources
     * @return list<string>
     */
    public function canonicalize(string $directive, array $sources): array
    {
        $scriptOrStyle = $this->catalog->isScriptOrStyleContext($directive);
        $canonical = [];
        foreach ($sources as $token) {
            $kind = SourceKind::of($token);
            $value = $this->canonicalToken($token, $kind);
            if (!$scriptOrStyle && $this->appliesToScriptsAndStylesOnly($value, $kind)) {
                continue;
            }
            $canonical[$value] = true;
        }

        if (count($canonical) > 1) {
            unset($canonical[self::NONE]);
        }
        if (array_keys($canonical) === [self::NONE]) {
            $canonical = [];
        }

        $tokens = array_map('strval', array_keys($canonical));
        sort($tokens, SORT_STRING);

        return $tokens;
    }

    /**
     * Canonical spelling of one token.
     *
     * @param string $token
     * @param SourceKind $kind
     * @return string
     */
    private function canonicalToken(string $token, SourceKind $kind): string
    {
        if ($kind === SourceKind::Nonce || $kind === SourceKind::Hash) {
            return $token;
        }
        if ($kind === SourceKind::Host) {
            return $this->canonicalHost($token);
        }

        return strtolower($token);
    }

    /**
     * Host-source lower-cased up to the path.
     *
     * @param string $token
     * @return string
     */
    private function canonicalHost(string $token): string
    {
        $parts = $this->hostSourceParser->parse($token);

        return $parts === null ? $token : $this->hostSourceParser->compose($parts);
    }

    /**
     * Whether a browser honours the expression only in script and style directives.
     *
     * @param string $canonicalToken
     * @param SourceKind $kind
     * @return bool
     */
    private function appliesToScriptsAndStylesOnly(string $canonicalToken, SourceKind $kind): bool
    {
        return $kind === SourceKind::Nonce
            || $kind === SourceKind::Hash
            || in_array($canonicalToken, self::SCRIPT_AND_STYLE_KEYWORDS, true);
    }
}
