<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy;

use Hryvinskyi\Csp\Api\Data\PolicyInterface;

/**
 * Decides whether two policies, or a policy and the parts it was split into, allow the same requests.
 *
 * Fetch directives are compared by the canonical sources that govern each request type, `default-src` included as
 * the directive of prefetch and prerender requests; other source-list directives by their canonical sources; every
 * other directive by its exact tokens.
 */
class PolicyEquivalence
{
    /**
     * @param DirectiveCatalog $catalog
     * @param FallbackResolver $fallbackResolver
     * @param SourceListCanonicalizer $canonicalizer
     * @param SourceListCover $cover
     */
    public function __construct(
        private readonly DirectiveCatalog $catalog,
        private readonly FallbackResolver $fallbackResolver,
        private readonly SourceListCanonicalizer $canonicalizer,
        private readonly SourceListCover $cover
    ) {
    }

    /**
     * Whether both policies allow the same requests.
     *
     * @param PolicyInterface $expected
     * @param PolicyInterface $actual
     * @return bool
     */
    public function isEquivalent(PolicyInterface $expected, PolicyInterface $actual): bool
    {
        return $this->differences($expected, $actual) === [];
    }

    /**
     * Whether the policy allows the same requests without the directive.
     *
     * Same answer as {@see isEquivalent()} against the policy without it, but only the request types the directive
     * governs are compared: no other request type, and no directive outside the fallback chains, can change.
     *
     * @param PolicyInterface $policy
     * @param string $directive
     * @return bool
     */
    public function allowsTheSameWithout(PolicyInterface $policy, string $directive): bool
    {
        if (!$policy->has($directive)) {
            return true;
        }
        if (!$this->catalog->isFetchDirective($directive)) {
            return false;
        }
        $without = $policy->without($directive);
        foreach ($this->catalog->requestDirectives() as $requestDirective) {
            if ($this->fallbackResolver->effectiveDirective($policy, $requestDirective) === $directive
                && $this->effective($policy, $requestDirective) !== $this->effective($without, $requestDirective)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Names of the request types and directives the two policies treat differently.
     *
     * @param PolicyInterface $expected
     * @param PolicyInterface $actual
     * @return list<string>
     */
    public function differences(PolicyInterface $expected, PolicyInterface $actual): array
    {
        return $this->splitDifferences($expected, [$actual]);
    }

    /**
     * Names of the request types and directives where the parts, enforced together, differ from the original.
     *
     * A browser enforces every part: a request passes only if each part that restricts it allows it. The parts are
     * equivalent when, for every request type the original restricts, one part allows exactly what the original
     * allows and every other part restricting it allows that much or more ({@see SourceListCover}); a request type
     * the original leaves unrestricted is restricted by no part.
     *
     * @param PolicyInterface $original
     * @param list<PolicyInterface> $parts
     * @return list<string>
     */
    public function splitDifferences(PolicyInterface $original, array $parts): array
    {
        $differences = [];
        foreach ($this->catalog->requestDirectives() as $requestDirective) {
            $expected = $this->effective($original, $requestDirective);
            $actual = array_map(
                fn (PolicyInterface $part): ?array => $this->effective($part, $requestDirective),
                $parts
            );
            if (!$this->restrictedAlike($requestDirective, $expected, $actual)) {
                $differences[] = $requestDirective;
            }
        }

        foreach ($this->catalog->standaloneSourceListDirectives() as $directive) {
            $expected = $this->standalone($original, $directive);
            $actual = array_map(
                fn (PolicyInterface $part): ?array => $this->standalone($part, $directive),
                $parts
            );
            if (!$this->enforcedAlike($expected, $actual)) {
                $differences[] = $directive;
            }
        }

        foreach ($this->otherDirectives($original, $parts) as $directive) {
            if (!$this->presentAlike($original, $parts, $directive)) {
                $differences[] = $directive;
            }
        }

        return $differences;
    }

    /**
     * Canonical sources governing the request type, or null when unrestricted.
     *
     * @param PolicyInterface $policy
     * @param string $requestDirective
     * @return list<string>|null
     */
    private function effective(PolicyInterface $policy, string $requestDirective): ?array
    {
        $sources = $this->fallbackResolver->effectiveSources($policy, $requestDirective);

        return $sources === null ? null : $this->canonicalizer->canonicalize($requestDirective, $sources);
    }

    /**
     * Canonical sources of a directive without fallback, or null when absent.
     *
     * @param PolicyInterface $policy
     * @param string $directive
     * @return list<string>|null
     */
    private function standalone(PolicyInterface $policy, string $directive): ?array
    {
        return $policy->has($directive)
            ? $this->canonicalizer->canonicalize($directive, $policy->sources($directive))
            : null;
    }

    /**
     * Whether the parts together restrict a request type as the original does: one part exactly, the others with
     * the same sources or a cover of them.
     *
     * @param string $requestDirective
     * @param list<string>|null $expected Null when the original does not restrict
     * @param list<list<string>|null> $actual One entry per part, null when that part does not restrict
     * @return bool
     */
    private function restrictedAlike(string $requestDirective, ?array $expected, array $actual): bool
    {
        $restricting = array_values(array_filter($actual, static fn (?array $sources): bool => $sources !== null));
        if ($expected === null) {
            return $restricting === [];
        }
        $exact = false;
        foreach ($restricting as $sources) {
            if ($sources === $expected) {
                $exact = true;
                continue;
            }
            if (!$this->cover->covers($requestDirective, $sources, $expected)) {
                return false;
            }
        }

        return $exact;
    }

    /**
     * Whether the restrictions the parts apply together equal the expected one.
     *
     * @param list<string>|null $expected Null when the original does not restrict
     * @param list<list<string>|null> $actual One entry per part, null when that part does not restrict
     * @return bool
     */
    private function enforcedAlike(?array $expected, array $actual): bool
    {
        $restricting = array_values(array_filter($actual, static fn (?array $sources): bool => $sources !== null));
        if ($expected === null) {
            return $restricting === [];
        }
        if ($restricting === []) {
            return false;
        }
        foreach ($restricting as $sources) {
            if ($sources !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * Names of directives that are not source lists, in the original or any part.
     *
     * @param PolicyInterface $original
     * @param list<PolicyInterface> $parts
     * @return list<string>
     */
    private function otherDirectives(PolicyInterface $original, array $parts): array
    {
        $names = $original->names();
        foreach ($parts as $part) {
            $names = array_merge($names, $part->names());
        }

        return array_values(array_filter(
            array_unique($names),
            fn (string $name): bool => !$this->catalog->isSourceListDirective($name)
        ));
    }

    /**
     * Whether a non-source-list directive keeps its exact value: every part that has it carries the original
     * tokens, at least one part has it when the original does, and per-part directives are in every part.
     *
     * @param PolicyInterface $original
     * @param list<PolicyInterface> $parts
     * @param string $directive
     * @return bool
     */
    private function presentAlike(PolicyInterface $original, array $parts, string $directive): bool
    {
        $carrying = array_values(array_filter(
            $parts,
            static fn (PolicyInterface $part): bool => $part->has($directive)
        ));
        if (!$original->has($directive)) {
            return $carrying === [];
        }
        if ($carrying === []) {
            return false;
        }
        if ($this->catalog->isPerPartDirective($directive) && count($carrying) !== count($parts)) {
            return false;
        }
        foreach ($carrying as $part) {
            if ($part->sources($directive) !== $original->sources($directive)) {
                return false;
            }
        }

        return true;
    }
}
