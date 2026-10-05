<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ScriptHash;

/**
 * Finds the inline scripts of an HTML fragment that a hash can allow.
 *
 * Left out: scripts with a `src` (allowed by host), with a `nonce` (allowed per request), of a type the browser does
 * not run (`application/json`, `text/x-magento-init`…), empty ones, and ones containing a per-request value such as
 * the form key, whose hash would never match again.
 */
class InlineScriptExtractor
{
    private const SCRIPT_PATTERN = '~<script\b([^>]*)>(.*?)</script\s*>~is';
    private const EXECUTABLE_TYPES = [
        '',
        'text/javascript',
        'application/javascript',
        'text/ecmascript',
        'application/ecmascript',
        'module',
    ];

    /**
     * Scripts to hash and the reasons others were left out.
     *
     * @param string $html
     * @param list<string> $perRequestValues Values that change on every request
     * @return array{scripts: list<string>, skipped: list<string>}
     */
    public function extract(string $html, array $perRequestValues = []): array
    {
        $result = ['scripts' => [], 'skipped' => []];
        preg_match_all(self::SCRIPT_PATTERN, $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $reason = $this->skipReason($match[1], $match[2], $perRequestValues);
            if ($reason === null) {
                $result['scripts'][] = $match[2];
            } else {
                $result['skipped'][] = $reason;
            }
        }

        return $result;
    }

    /**
     * Why the script is left out, or null when it is hashed.
     *
     * @param string $attributes
     * @param string $content
     * @param list<string> $perRequestValues
     * @return string|null
     */
    private function skipReason(string $attributes, string $content, array $perRequestValues): ?string
    {
        if ($this->hasAttribute($attributes, 'src')) {
            return 'external script';
        }
        if ($this->hasAttribute($attributes, 'nonce')) {
            return 'script with a nonce';
        }
        $type = $this->attribute($attributes, 'type');
        if (!in_array(strtolower(trim($type)), self::EXECUTABLE_TYPES, true)) {
            return sprintf('script of type "%s"', $type);
        }
        if (trim($content) === '') {
            return 'empty script';
        }
        foreach ($perRequestValues as $value) {
            if ($value !== '' && str_contains($content, $value)) {
                return 'script with a per-request value such as the form key';
            }
        }

        return null;
    }

    /**
     * @param string $attributes
     * @param string $name
     * @return bool
     */
    private function hasAttribute(string $attributes, string $name): bool
    {
        return preg_match('~(?:^|\s)' . preg_quote($name, '~') . '(?:\s*=|\s|$)~i', $attributes) === 1;
    }

    /**
     * Value of an attribute, empty when absent.
     *
     * @param string $attributes
     * @param string $name
     * @return string
     */
    private function attribute(string $attributes, string $name): string
    {
        $pattern = '~(?:^|\s)' . preg_quote($name, '~') . '\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))~i';
        if (preg_match($pattern, $attributes, $match, PREG_UNMATCHED_AS_NULL) !== 1) {
            return '';
        }

        return $match[1] ?? $match[2] ?? $match[3] ?? '';
    }
}
