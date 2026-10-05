<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy\Optimization;

use Hryvinskyi\Csp\Api\Config\OptimizationConfigInterface;
use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use Hryvinskyi\Csp\Api\PolicyOptimizationStepInterface;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\DocumentSchemeInterface;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\SourceKind;

/**
 * Removes the https:// scheme from host sources on pages served over HTTPS.
 *
 * On an HTTPS page `cdn.example.com` allows exactly what `https://cdn.example.com` allows. On an HTTP page it would
 * also allow http://, so the step does nothing there. Ports and paths are kept; http://, wss:// and other schemes
 * are left alone.
 */
class StripHostSchemes implements PolicyOptimizationStepInterface
{
    /**
     * @param OptimizationConfigInterface $config
     * @param DirectiveCatalog $catalog
     * @param HostSourceParser $hostSourceParser
     * @param DocumentSchemeInterface $documentScheme
     */
    public function __construct(
        private readonly OptimizationConfigInterface $config,
        private readonly DirectiveCatalog $catalog,
        private readonly HostSourceParser $hostSourceParser,
        private readonly DocumentSchemeInterface $documentScheme
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->config->isSchemeStrippingEnabled() && $this->documentScheme->isSecure();
    }

    /**
     * @inheritDoc
     */
    public function apply(PolicyInterface $policy): PolicyInterface
    {
        foreach ($policy->names() as $name) {
            if (!$this->catalog->isSourceListDirective($name)) {
                continue;
            }
            $sources = $policy->sources($name);
            $stripped = array_map(fn (string $token): string => $this->strip($token), $sources);
            if ($stripped !== $sources) {
                $policy = $policy->withSources($name, $stripped);
            }
        }

        return $policy;
    }

    /**
     * Token without an https scheme.
     *
     * @param string $token
     * @return string
     */
    private function strip(string $token): string
    {
        if (SourceKind::of($token) !== SourceKind::Host) {
            return $token;
        }
        $parts = $this->hostSourceParser->parse($token);
        if ($parts === null || $parts['scheme'] !== 'https') {
            return $token;
        }
        $parts['scheme'] = null;

        return $this->hostSourceParser->compose($parts);
    }
}
