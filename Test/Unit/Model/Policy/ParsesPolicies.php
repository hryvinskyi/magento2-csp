<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy;

use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use Hryvinskyi\Csp\Api\Data\PolicyInterfaceFactory;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\FallbackResolver;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\Policy;
use Hryvinskyi\Csp\Model\Policy\PolicyEquivalence;
use Hryvinskyi\Csp\Model\Policy\PolicyParser;
use Hryvinskyi\Csp\Model\Policy\SourceListCanonicalizer;
use PHPUnit\Framework\TestCase;

/**
 * Builds the real policy services for tests and parses header values into policies.
 *
 * @mixin TestCase
 */
trait ParsesPolicies
{
    /**
     * Factory double that builds real policies.
     *
     * @return PolicyInterfaceFactory
     */
    private function policyFactory(): PolicyInterfaceFactory
    {
        return new class () extends PolicyInterfaceFactory {
            public function __construct()
            {
            }

            /**
             * @param array<string, mixed> $data
             * @return PolicyInterface
             */
            public function create(array $data = []): PolicyInterface
            {
                $directives = [];
                $given = $data['directives'] ?? [];
                foreach (is_array($given) ? $given : [] as $name => $sources) {
                    $directives[(string)$name] = is_array($sources)
                        ? array_values(array_filter($sources, 'is_string'))
                        : [];
                }

                return new Policy($directives);
            }
        };
    }

    /**
     * Parse a header value with the real parser.
     *
     * @param string $header
     * @return PolicyInterface
     */
    private function parsePolicy(string $header): PolicyInterface
    {
        return (new PolicyParser($this->policyFactory()))->parse($header);
    }

    /**
     * Real equivalence service.
     *
     * @return PolicyEquivalence
     */
    private function equivalence(): PolicyEquivalence
    {
        $catalog = new DirectiveCatalog();

        return new PolicyEquivalence(
            $catalog,
            new FallbackResolver($catalog),
            new SourceListCanonicalizer($catalog, new HostSourceParser())
        );
    }
}
