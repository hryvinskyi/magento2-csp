<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Support;

use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use PHPUnit\Framework\TestCase;

/**
 * Assertions that compare what a browser allows under two sets of CSP headers, over the request matrix.
 *
 * @mixin TestCase
 */
trait AssertsBrowserDecisions
{
    /**
     * Both header sets allow and block exactly the same requests.
     *
     * @param list<PolicyInterface> $before
     * @param list<PolicyInterface> $after
     * @param string $documentScheme
     * @return void
     */
    private function assertSameBrowserDecisions(array $before, array $after, string $documentScheme = 'https'): void
    {
        $this->assertSame([], $this->decisionChanges($before, $after, $documentScheme));
    }

    /**
     * Requests whose decision differs, as "allowed→blocked: label" lines.
     *
     * @param list<PolicyInterface> $before
     * @param list<PolicyInterface> $after
     * @param string $documentScheme
     * @return list<string>
     */
    private function decisionChanges(array $before, array $after, string $documentScheme = 'https'): array
    {
        $evaluator = new CspEvaluator($documentScheme);
        $changes = [];
        foreach (RequestMatrix::requests() as $request) {
            $was = $evaluator->allows($before, $request);
            $is = $evaluator->allows($after, $request);
            if ($was !== $is) {
                $changes[] = ($was ? 'allowed→blocked: ' : 'blocked→allowed: ') . $request->label();
            }
        }

        return $changes;
    }
}
