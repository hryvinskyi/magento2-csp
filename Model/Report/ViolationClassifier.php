<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Api\Data\ViolationInterface;
use Hryvinskyi\Csp\Model\Store\UrlHost;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;

/**
 * Names the report group of a violation: its governing directive and the blocked value.
 *
 * The value is a host for http(s) resources, `scheme://host` for other resources with a host, `scheme:` for
 * data, blob and similar resources, and the browser's keyword for inline code and eval.
 */
class ViolationClassifier
{
    private const KEYWORDS = ['inline', 'eval', 'wasm-eval', 'trusted-types-policy', 'trusted-types-sink'];
    private const WEB_SCHEMES = ['http', 'https'];

    /**
     * Schemes some browsers report without the colon.
     */
    private const BARE_SCHEMES = ['data', 'blob', 'filesystem', 'mediastream'];

    /**
     * @param WhitelistableDirectives $directives
     * @param UrlHost $urlHost
     */
    public function __construct(
        private readonly WhitelistableDirectives $directives,
        private readonly UrlHost $urlHost
    ) {
    }

    /**
     * Group of the violation, or null when it has no whitelistable directive or no recognizable blocked value.
     *
     * @param ViolationInterface $violation
     * @return array{policy: string, value: string}|null
     */
    public function classify(ViolationInterface $violation): ?array
    {
        $policy = $this->directives->governing($violation->getEffectiveDirective());
        $value = $this->blockedValue($violation->getBlockedUri());

        return $policy === null || $value === null ? null : ['policy' => $policy, 'value' => $value];
    }

    /**
     * Value of the blocked resource.
     *
     * @param string $blockedUri
     * @return string|null
     */
    private function blockedValue(string $blockedUri): ?string
    {
        $blocked = strtolower(trim($blockedUri));
        if (in_array($blocked, self::KEYWORDS, true)) {
            return $blocked;
        }
        if (in_array($blocked, self::BARE_SCHEMES, true)) {
            return $blocked . ':';
        }
        $scheme = parse_url($blocked, PHP_URL_SCHEME);
        if (!is_string($scheme) || preg_match('/^[a-z][a-z0-9+.\-]*$/', $scheme) !== 1) {
            return null;
        }
        $host = $this->urlHost->of($blocked);
        if ($host === null) {
            return $scheme . ':';
        }

        return in_array($scheme, self::WEB_SCHEMES, true) ? $host : $scheme . '://' . $host;
    }
}
