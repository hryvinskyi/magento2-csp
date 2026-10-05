<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy;

use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use Hryvinskyi\Csp\Api\Data\PolicyInterfaceFactory;
use Psr\Log\LoggerInterface;

/**
 * Splits a policy that is larger than a header may be into several policies a browser enforces together.
 *
 * A browser applies every CSP header and lets a request through only if each header that restricts it allows it.
 * To keep what the policy allows, `default-src` is replaced by explicit copies of its value in every directive that
 * fell back to it, directives linked by fallback stay in one part, and `report-uri`/`report-to` repeat in every part.
 * The result is checked with {@see PolicyEquivalence}; when it does not hold, or one group alone exceeds the limit,
 * the policy is returned unsplit and the reason is logged.
 */
class PolicySplitter
{
    private const SEPARATOR_BYTES = 2;

    /**
     * @param DirectiveCatalog $catalog
     * @param FallbackResolver $fallbackResolver
     * @param PolicyEquivalence $equivalence
     * @param PolicyInterfaceFactory $policyFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly DirectiveCatalog $catalog,
        private readonly FallbackResolver $fallbackResolver,
        private readonly PolicyEquivalence $equivalence,
        private readonly PolicyInterfaceFactory $policyFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Parts that together allow what the policy allows, each at most the given size; the policy alone when it fits
     * or cannot be split without changing what it allows.
     *
     * @param PolicyInterface $policy
     * @param int $maxBytes
     * @return list<PolicyInterface>
     */
    public function split(PolicyInterface $policy, int $maxBytes): array
    {
        if ($policy->byteLength() <= $maxBytes) {
            return [$policy];
        }

        $explicit = $this->withoutDefaultSrc($policy);
        $perPart = $this->perPartDirectives($explicit);
        $suffixBytes = $this->bytesOf($perPart);
        if ($suffixBytes > 0) {
            $suffixBytes += self::SEPARATOR_BYTES;
        }

        $groups = $this->groups($explicit);
        usort($groups, static fn (array $left, array $right): int => $right['bytes'] <=> $left['bytes']);

        $bins = [];
        foreach ($groups as $group) {
            if ($group['bytes'] + $suffixBytes > $maxBytes) {
                $this->logger->warning(sprintf(
                    'CSP header not split: the directive group "%s" alone needs %d of %d bytes.',
                    implode(' ', array_keys($group['directives'])),
                    $group['bytes'] + $suffixBytes,
                    $maxBytes
                ));

                return [$policy];
            }
            $bins = $this->place($bins, $group, $maxBytes - $suffixBytes);
        }

        $parts = array_map(
            fn (array $bin): PolicyInterface => $this->policyFactory->create([
                'directives' => array_merge($bin['directives'], $perPart),
            ]),
            $bins
        );

        $differences = $this->equivalence->splitDifferences($policy, $parts);
        if ($differences !== []) {
            $this->logger->warning(sprintf(
                'CSP header not split: the parts would treat "%s" differently.',
                implode('", "', $differences)
            ));

            return [$policy];
        }

        return count($parts) > 1 ? $parts : [$policy];
    }

    /**
     * Policy without `default-src`, each request type that relied on it given an explicit copy of what governed it.
     *
     * Request directives are visited parent first, so a copy made for a directive serves the directives that fall
     * back to it, and a directive whose resolution a copy would change gets its own copy.
     *
     * @param PolicyInterface $policy
     * @return PolicyInterface
     */
    private function withoutDefaultSrc(PolicyInterface $policy): PolicyInterface
    {
        if (!$policy->has(DirectiveCatalog::DEFAULT_SRC)) {
            return $policy;
        }

        $explicit = $policy->without(DirectiveCatalog::DEFAULT_SRC);
        foreach ($this->catalog->requestDirectives() as $requestDirective) {
            $expected = $this->fallbackResolver->effectiveSources($policy, $requestDirective);
            if ($expected !== null && $expected !== $this->fallbackResolver->effectiveSources($explicit, $requestDirective)) {
                $explicit = $explicit->withSources($requestDirective, $expected);
            }
        }

        return $explicit;
    }

    /**
     * Directives repeated in every part.
     *
     * @param PolicyInterface $policy
     * @return array<string, list<string>>
     */
    private function perPartDirectives(PolicyInterface $policy): array
    {
        return array_filter(
            $policy->directives(),
            fn (string $name): bool => $this->catalog->isPerPartDirective($name),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Groups of directives that must share a part, with their size.
     *
     * @param PolicyInterface $policy
     * @return list<array{directives: array<string, list<string>>, bytes: int}>
     */
    private function groups(PolicyInterface $policy): array
    {
        $byFamily = [];
        foreach ($policy->directives() as $name => $sources) {
            if ($this->catalog->isPerPartDirective($name)) {
                continue;
            }
            $family = $this->catalog->isFetchDirective($name)
                ? 'family:' . $this->catalog->familyOf($name)
                : 'directive:' . $name;
            $byFamily[$family][$name] = $sources;
        }

        return array_values(array_map(
            fn (array $directives): array => ['directives' => $directives, 'bytes' => $this->bytesOf($directives)],
            $byFamily
        ));
    }

    /**
     * Bins after placing the group in the first bin with room, or in a new bin.
     *
     * @param list<array{directives: array<string, list<string>>, bytes: int}> $bins
     * @param array{directives: array<string, list<string>>, bytes: int} $group
     * @param int $capacity
     * @return list<array{directives: array<string, list<string>>, bytes: int}>
     */
    private function place(array $bins, array $group, int $capacity): array
    {
        foreach ($bins as $index => $bin) {
            $bytes = $bin['bytes'] + self::SEPARATOR_BYTES + $group['bytes'];
            if ($bytes <= $capacity) {
                $bins[$index] = [
                    'directives' => array_merge($bin['directives'], $group['directives']),
                    'bytes' => $bytes,
                ];

                return $bins;
            }
        }
        $bins[] = $group;

        return $bins;
    }

    /**
     * Header bytes of the directives joined with "; ".
     *
     * @param array<string, list<string>> $directives
     * @return int
     */
    private function bytesOf(array $directives): int
    {
        $bytes = 0;
        foreach ($directives as $name => $sources) {
            $bytes += ($bytes > 0 ? self::SEPARATOR_BYTES : 0)
                + strlen($sources === [] ? $name : $name . ' ' . implode(' ', $sources));
        }

        return $bytes;
    }
}
