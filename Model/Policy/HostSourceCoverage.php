<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy;

/**
 * Decides whether a wildcard host-source allows everything another host-source allows.
 *
 * `*.example.com` covers `a.example.com` and `*.a.example.com`, never `example.com` itself. A wildcard without a
 * port covers only hosts without a port; a wildcard without a path covers every path, a wildcard with a path covers
 * the same path, or paths below it when it ends with "/". Schemes must be equal, except that a wildcard without a
 * scheme covers an https:// host on any page.
 */
class HostSourceCoverage
{
    /**
     * @param HostSourceParser $hostSourceParser
     */
    public function __construct(private readonly HostSourceParser $hostSourceParser)
    {
    }

    /**
     * Whether the wildcard token allows every request the host token allows.
     *
     * @param string $wildcardToken
     * @param string $hostToken
     * @return bool
     */
    public function covers(string $wildcardToken, string $hostToken): bool
    {
        if ($wildcardToken === $hostToken) {
            return false;
        }
        $wildcard = $this->hostSourceParser->parse($wildcardToken);
        $host = $this->hostSourceParser->parse($hostToken);
        if ($wildcard === null || $host === null || !$this->hostSourceParser->isWildcardHost($wildcard)) {
            return false;
        }

        return $this->schemeCovered($wildcard['scheme'], $host['scheme'])
            && $this->hostCovered(substr($wildcard['host'], 1), $host['host'])
            && $this->portCovered($wildcard['port'], $host['port'])
            && $this->pathCovered($wildcard['path'], $host['path']);
    }

    /**
     * Whether the host (or wildcard host) lies strictly below the ".domain" suffix.
     *
     * @param string $suffix Wildcard host without the leading "*", e.g. ".example.com"
     * @param string $host
     * @return bool
     */
    private function hostCovered(string $suffix, string $host): bool
    {
        $bare = str_starts_with($host, '*.') ? substr($host, 1) : '.' . $host;

        return $bare !== $suffix && str_ends_with($bare, $suffix);
    }

    /**
     * Whether the wildcard's port allows the host's port.
     *
     * @param string|null $wildcardPort
     * @param string|null $hostPort
     * @return bool
     */
    private function portCovered(?string $wildcardPort, ?string $hostPort): bool
    {
        return $wildcardPort === '*' || $wildcardPort === $hostPort;
    }

    /**
     * Whether the wildcard's path allows the host's path.
     *
     * @param string|null $wildcardPath
     * @param string|null $hostPath
     * @return bool
     */
    private function pathCovered(?string $wildcardPath, ?string $hostPath): bool
    {
        if ($wildcardPath === null) {
            return true;
        }
        if ($hostPath === null) {
            return false;
        }

        return str_ends_with($wildcardPath, '/')
            ? str_starts_with($hostPath, $wildcardPath)
            : $hostPath === $wildcardPath;
    }

    /**
     * Whether the wildcard's scheme allows every request the host's scheme allows.
     *
     * @param string|null $wildcardScheme
     * @param string|null $hostScheme
     * @return bool
     */
    private function schemeCovered(?string $wildcardScheme, ?string $hostScheme): bool
    {
        return $wildcardScheme === $hostScheme || ($wildcardScheme === null && $hostScheme === 'https');
    }
}
