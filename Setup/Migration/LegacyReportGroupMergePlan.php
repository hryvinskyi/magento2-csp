<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Setup\Migration;

use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;

/**
 * Plans how report groups recorded before 2.0.0 become groups of every store and area.
 *
 * Those groups were recorded through the default store's URL whatever store or area raised them, so their scope is
 * unknown and they move to store 0 and area `all`. Their directive becomes the governing directive. Groups that then
 * share directive and value merge into the one with the lowest id: occurrence counts add up and the strictest status
 * wins. Which groups share directive and value is decided by the database under the table's collation, as its unique
 * key decides it (case, trailing spaces and, depending on the collation, accents), and handed to the plan as each
 * group's merge key.
 */
class LegacyReportGroupMergePlan
{
    /**
     * @param WhitelistableDirectives $directives
     */
    public function __construct(private readonly WhitelistableDirectives $directives)
    {
    }

    /**
     * Governing directive of each recorded directive that has one other than itself.
     *
     * @param list<string> $policies Directives as recorded
     * @return array<string, string> Recorded directive => governing directive
     */
    public function governingDirectives(array $policies): array
    {
        $governing = [];
        foreach ($policies as $policy) {
            $directive = $this->directives->governing($policy);
            if ($directive !== null && $directive !== $policy) {
                $governing[$policy] = $directive;
            }
        }

        return $governing;
    }

    /**
     * One entry per group that remains: its id, the ids merged into it, and its new directive, status and count.
     *
     * @param list<array{group_id: int, merge_key: int, governing_policy: string, status: int, count: int}> $groups
     * @return list<array{survivor: int, merged: list<int>, policy: string, status: int, count: int}>
     */
    public function plan(array $groups): array
    {
        usort($groups, static fn (array $a, array $b): int => $a['group_id'] <=> $b['group_id']);
        $plan = [];
        foreach ($groups as $group) {
            $status = Status::tryFrom($group['status']) ?? Status::PENDING;
            $key = $group['merge_key'];
            if (!isset($plan[$key])) {
                $plan[$key] = [
                    'survivor' => $group['group_id'],
                    'merged' => [],
                    'policy' => $group['governing_policy'],
                    'status' => $status->value,
                    'count' => $group['count'],
                ];
                continue;
            }
            $plan[$key]['merged'][] = $group['group_id'];
            $plan[$key]['status'] = Status::from($plan[$key]['status'])->stricter($status)->value;
            $plan[$key]['count'] += $group['count'];
        }

        return array_values($plan);
    }
}
