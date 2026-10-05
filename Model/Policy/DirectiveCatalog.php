<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy;

/**
 * What the CSP specification says about each directive: fallbacks, source lists, and how a header may be split.
 */
class DirectiveCatalog
{
    public const DEFAULT_SRC = 'default-src';
    public const SCRIPT_SRC = 'script-src';
    public const STYLE_SRC = 'style-src';

    /**
     * Fallback list of each fetch directive, the directive itself excluded (CSP Level 3, directive fallback list).
     */
    private const FALLBACKS = [
        'script-src-elem' => ['script-src', 'default-src'],
        'script-src-attr' => ['script-src', 'default-src'],
        'script-src' => ['default-src'],
        'style-src-elem' => ['style-src', 'default-src'],
        'style-src-attr' => ['style-src', 'default-src'],
        'style-src' => ['default-src'],
        'worker-src' => ['child-src', 'script-src', 'default-src'],
        'child-src' => ['default-src'],
        'frame-src' => ['child-src', 'default-src'],
        'fenced-frame-src' => ['frame-src', 'child-src', 'default-src'],
        'connect-src' => ['default-src'],
        'font-src' => ['default-src'],
        'img-src' => ['default-src'],
        'manifest-src' => ['default-src'],
        'media-src' => ['default-src'],
        'object-src' => ['default-src'],
        'default-src' => [],
    ];

    /**
     * Directives a browser resolves requests against, each listed before the directives that fall back to it.
     * `script-src`, `style-src` and `child-src` are included for browsers that predate the `-elem`/`-attr` and
     * `worker-src` directives and resolve requests through them.
     */
    private const REQUEST_DIRECTIVES = [
        'script-src',
        'style-src',
        'child-src',
        'connect-src',
        'font-src',
        'img-src',
        'manifest-src',
        'media-src',
        'object-src',
        'script-src-elem',
        'script-src-attr',
        'style-src-elem',
        'style-src-attr',
        'frame-src',
        'worker-src',
        'fenced-frame-src',
    ];

    /**
     * Source-list directives that never fall back to `default-src`.
     */
    private const STANDALONE_SOURCE_LISTS = ['frame-ancestors', 'form-action', 'base-uri', 'navigate-to'];

    /**
     * Directives every part of a split header repeats, so each part reports its own violations.
     */
    private const PER_PART = ['report-uri', 'report-to'];

    /**
     * Fetch directives linked by fallback that a split must keep in one header part.
     */
    private const FAMILIES = [
        'script-src' => 'script',
        'script-src-elem' => 'script',
        'script-src-attr' => 'script',
        'worker-src' => 'script',
        'child-src' => 'script',
        'frame-src' => 'script',
        'fenced-frame-src' => 'script',
        'style-src' => 'style',
        'style-src-elem' => 'style',
        'style-src-attr' => 'style',
    ];

    /**
     * Directives whose sources may hold inline, eval, nonce and hash expressions that a browser honours.
     */
    private const SCRIPT_AND_STYLE = [
        'script-src',
        'script-src-elem',
        'script-src-attr',
        'worker-src',
        'style-src',
        'style-src-elem',
        'style-src-attr',
        'default-src',
    ];

    /**
     * Whether the directive restricts requests and takes part in fallback.
     *
     * @param string $name
     * @return bool
     */
    public function isFetchDirective(string $name): bool
    {
        return array_key_exists($name, self::FALLBACKS);
    }

    /**
     * Whether the directive's value is a list of source expressions.
     *
     * @param string $name
     * @return bool
     */
    public function isSourceListDirective(string $name): bool
    {
        return $this->isFetchDirective($name) || in_array($name, self::STANDALONE_SOURCE_LISTS, true);
    }

    /**
     * Directives the browser consults, in order, when the given fetch directive is absent.
     *
     * @param string $name
     * @return list<string>
     */
    public function fallbacks(string $name): array
    {
        return self::FALLBACKS[$name] ?? [];
    }

    /**
     * Directives that decide which requests a policy allows, each before the directives that fall back to it.
     *
     * @return list<string>
     */
    public function requestDirectives(): array
    {
        return self::REQUEST_DIRECTIVES;
    }

    /**
     * Source-list directives without fallback.
     *
     * @return list<string>
     */
    public function standaloneSourceListDirectives(): array
    {
        return self::STANDALONE_SOURCE_LISTS;
    }

    /**
     * Whether every part of a split header repeats the directive.
     *
     * @param string $name
     * @return bool
     */
    public function isPerPartDirective(string $name): bool
    {
        return in_array($name, self::PER_PART, true);
    }

    /**
     * Group a directive belongs to when a header is split; directives of one group stay in one part.
     *
     * @param string $name
     * @return string
     */
    public function familyOf(string $name): string
    {
        return self::FAMILIES[$name] ?? $name;
    }

    /**
     * Whether inline, eval, nonce and hash expressions have an effect in the directive.
     *
     * @param string $name
     * @return bool
     */
    public function isScriptOrStyleContext(string $name): bool
    {
        return in_array($name, self::SCRIPT_AND_STYLE, true);
    }
}
