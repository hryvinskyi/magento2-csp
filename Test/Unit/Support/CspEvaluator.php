<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Support;

use Hryvinskyi\Csp\Api\Data\PolicyInterface;

/**
 * Test oracle: how a CSP Level 3 browser decides a request on a page served from {scheme}://shop.example.
 *
 * Written independently of the module's policy services — its own fallback table, keyword rules and URL matching —
 * so tests can judge the module's optimisation and splitting by what a browser would allow, not by the module's
 * own view of equivalence. Every policy in the list is enforced: a request passes only if each one allows it.
 */
class CspEvaluator
{
    private const PAGE_HOST = 'shop.example';

    private const CHAINS = [
        'script:url' => ['script-src-elem', 'script-src', 'default-src'],
        'script:inline' => ['script-src-elem', 'script-src', 'default-src'],
        'script:attribute' => ['script-src-attr', 'script-src', 'default-src'],
        'script:eval' => ['script-src', 'default-src'],
        'script:wasm' => ['script-src', 'default-src'],
        'style:url' => ['style-src-elem', 'style-src', 'default-src'],
        'style:inline' => ['style-src-elem', 'style-src', 'default-src'],
        'style:attribute' => ['style-src-attr', 'style-src', 'default-src'],
        'worker:url' => ['worker-src', 'child-src', 'script-src', 'default-src'],
        'frame:url' => ['frame-src', 'child-src', 'default-src'],
        'fenced-frame:url' => ['fenced-frame-src', 'frame-src', 'child-src', 'default-src'],
        'img:url' => ['img-src', 'default-src'],
        'font:url' => ['font-src', 'default-src'],
        'connect:url' => ['connect-src', 'default-src'],
        'media:url' => ['media-src', 'default-src'],
        'object:url' => ['object-src', 'default-src'],
        'manifest:url' => ['manifest-src', 'default-src'],
    ];

    private const DEFAULT_PORTS = ['http' => 80, 'https' => 443, 'ws' => 80, 'wss' => 443];

    /**
     * @param string $documentScheme Scheme the page is served with: "https" or "http"
     */
    public function __construct(private readonly string $documentScheme = 'https')
    {
    }

    /**
     * Whether every policy allows the request.
     *
     * @param list<PolicyInterface> $policies
     * @param CspRequest $request
     * @return bool
     */
    public function allows(array $policies, CspRequest $request): bool
    {
        $chain = self::CHAINS[$request->type . ':' . $request->kind] ?? null;
        if ($chain === null) {
            throw new \InvalidArgumentException('Unknown request ' . $request->label());
        }
        foreach ($policies as $policy) {
            foreach ($chain as $directive) {
                if ($policy->has($directive)) {
                    if (!$this->allowedBy($policy->sources($directive), $request)) {
                        return false;
                    }
                    break;
                }
            }
        }

        return true;
    }

    /**
     * Whether one source list allows the request.
     *
     * @param list<string> $sources
     * @param CspRequest $request
     * @return bool
     */
    private function allowedBy(array $sources, CspRequest $request): bool
    {
        $keywords = array_map('strtolower', array_filter($sources, static fn (string $s): bool => str_starts_with($s, "'")));
        $nonceOrHash = array_filter(
            $sources,
            static fn (string $s): bool => preg_match("/^'(nonce-|sha(256|384|512)-)/i", $s) === 1
        ) !== [];
        $inline = in_array("'unsafe-inline'", $keywords, true) && !$nonceOrHash;
        $strictDynamic = in_array("'strict-dynamic'", $keywords, true);

        return match ($request->kind) {
            CspRequest::KIND_EVAL => in_array("'unsafe-eval'", $keywords, true),
            CspRequest::KIND_WASM => in_array("'unsafe-eval'", $keywords, true)
                || in_array("'wasm-unsafe-eval'", $keywords, true),
            CspRequest::KIND_INLINE => ($request->nonce !== null && in_array("'nonce-{$request->nonce}'", $sources, true))
                || ($request->hash !== null && in_array("'sha256-{$request->hash}'", $sources, true))
                || ($inline && !($request->type === 'script' && $strictDynamic)),
            CspRequest::KIND_ATTRIBUTE => $inline
                || ($request->hash !== null
                    && in_array("'unsafe-hashes'", $keywords, true)
                    && in_array("'sha256-{$request->hash}'", $sources, true)),
            default => !(in_array($request->type, ['script', 'worker'], true) && $strictDynamic)
                && $this->urlAllowed($sources, $request->url),
        };
    }

    /**
     * Whether any source expression matches the URL.
     *
     * @param list<string> $sources
     * @param string $url
     * @return bool
     */
    private function urlAllowed(array $sources, string $url): bool
    {
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        foreach ($sources as $source) {
            $lower = strtolower($source);
            if ($lower === "'self'") {
                if ($this->matchesSelf($url, $scheme)) {
                    return true;
                }
                continue;
            }
            if (str_starts_with($source, "'")) {
                continue;
            }
            if ($source === '*') {
                if (in_array($scheme, ['http', 'https'], true) || $scheme === $this->documentScheme) {
                    return true;
                }
                continue;
            }
            if (preg_match('/^[a-z][a-z0-9+.\-]*:$/', $lower) === 1) {
                if ($this->schemePartMatches(substr($lower, 0, -1), $scheme)) {
                    return true;
                }
                continue;
            }
            if ($this->hostSourceMatches($source, $url, $scheme)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 'self': the page's own host, same scheme or an upgrade to a secure one, default port.
     *
     * @param string $url
     * @param string $scheme
     * @return bool
     */
    private function matchesSelf(string $url, string $scheme): bool
    {
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        $port = parse_url($url, PHP_URL_PORT);
        $schemeOk = $scheme === $this->documentScheme
            || ($this->documentScheme === 'http' && in_array($scheme, ['https', 'ws', 'wss'], true))
            || ($this->documentScheme === 'https' && $scheme === 'wss');

        return $host === self::PAGE_HOST && $schemeOk && ($port === null || $port === (self::DEFAULT_PORTS[$scheme] ?? null));
    }

    /**
     * CSP3 "scheme-part match": A is the expression's (or page's) scheme, B the URL's.
     *
     * @param string $expression
     * @param string $url
     * @return bool
     */
    private function schemePartMatches(string $expression, string $url): bool
    {
        return $expression === $url
            || ($expression === 'http' && $url === 'https')
            || ($expression === 'ws' && in_array($url, ['wss', 'http', 'https'], true))
            || ($expression === 'wss' && $url === 'https');
    }

    /**
     * Host-source match: scheme, host (with a leading wildcard label), port and path.
     *
     * @param string $source
     * @param string $url
     * @param string $urlScheme
     * @return bool
     */
    private function hostSourceMatches(string $source, string $url, string $urlScheme): bool
    {
        if (preg_match('~^(?:([a-z][a-z0-9+.\-]*)://)?([^:/]+)(?::(\d+|\*))?(/.*)?$~i', $source, $m) !== 1) {
            return false;
        }
        $sourceScheme = strtolower($m[1]);
        $sourceHost = strtolower($m[2]);
        $sourcePort = $m[3] ?? '';
        $sourcePath = $m[4] ?? '';

        if (!$this->schemePartMatches($sourceScheme !== '' ? $sourceScheme : $this->documentScheme, $urlScheme)) {
            return false;
        }

        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        $hostOk = str_starts_with($sourceHost, '*.')
            ? str_ends_with($host, substr($sourceHost, 1))
            : $host === $sourceHost;
        if (!$hostOk) {
            return false;
        }

        $urlPort = parse_url($url, PHP_URL_PORT) ?? (self::DEFAULT_PORTS[$urlScheme] ?? null);
        if ($sourcePort === '') {
            if ($urlPort !== (self::DEFAULT_PORTS[$urlScheme] ?? null)) {
                return false;
            }
        } elseif ($sourcePort !== '*' && (int)$sourcePort !== $urlPort) {
            return false;
        }

        if ($sourcePath === '') {
            return true;
        }
        $path = (string)parse_url($url, PHP_URL_PATH);

        return str_ends_with($sourcePath, '/') ? str_starts_with($path, $sourcePath) : $path === $sourcePath;
    }
}
