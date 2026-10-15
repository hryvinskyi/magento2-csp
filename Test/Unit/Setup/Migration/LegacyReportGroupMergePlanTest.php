<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Setup\Migration;

use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;
use Hryvinskyi\Csp\Setup\Migration\LegacyReportGroupMergePlan;
use PHPUnit\Framework\TestCase;

class LegacyReportGroupMergePlanTest extends TestCase
{
    public function testMergesGroupsTheDatabaseGaveTheSameMergeKey(): void
    {
        $plan = $this->plan()->plan([
            $this->group(7, 2, 'script-src', Status::SKIP, 3),
            $this->group(2, 2, 'script-src', Status::PENDING, 5),
            $this->group(9, 2, 'script-src', Status::DENIED, 1),
            $this->group(4, 4, 'img-src', Status::PENDING, 2),
        ]);

        $this->assertSame(
            [
                ['survivor' => 2, 'merged' => [7, 9], 'policy' => 'script-src', 'status' => Status::DENIED->value, 'count' => 9],
                ['survivor' => 4, 'merged' => [], 'policy' => 'img-src', 'status' => Status::PENDING->value, 'count' => 2],
            ],
            $plan
        );
    }

    public function testNeverMergesGroupsTheDatabaseKeptApart(): void
    {
        $plan = $this->plan()->plan([
            $this->group(1, 1, 'img-src', Status::PENDING, 1),
            $this->group(2, 2, 'img-src', Status::PENDING, 1),
        ]);

        $this->assertSame([1, 2], array_column($plan, 'survivor'));
        $this->assertSame([[], []], array_column($plan, 'merged'));
    }

    public function testMapsSubDirectivesAndLeavesTheRest(): void
    {
        $this->assertSame(
            ['script-src-attr' => 'script-src', 'worker-src' => 'child-src', 'SCRIPT-SRC' => 'script-src'],
            $this->plan()->governingDirectives(['script-src-attr', 'worker-src', 'SCRIPT-SRC', 'img-src', 'navigate-to'])
        );
    }

    public function testUnknownStatusCountsAsPending(): void
    {
        $plan = $this->plan()->plan([
            ['group_id' => 1, 'merge_key' => 1, 'governing_policy' => 'img-src', 'status' => 42, 'count' => 1],
        ]);

        $this->assertSame(Status::PENDING->value, $plan[0]['status']);
    }

    /**
     * @return LegacyReportGroupMergePlan
     */
    private function plan(): LegacyReportGroupMergePlan
    {
        return new LegacyReportGroupMergePlan(new WhitelistableDirectives(new DirectiveCatalog()));
    }

    /**
     * @param int $id
     * @param int $mergeKey
     * @param string $governingPolicy
     * @param Status $status
     * @param int $count
     * @return array{group_id: int, merge_key: int, governing_policy: string, status: int, count: int}
     */
    private function group(int $id, int $mergeKey, string $governingPolicy, Status $status, int $count): array
    {
        return [
            'group_id' => $id,
            'merge_key' => $mergeKey,
            'governing_policy' => $governingPolicy,
            'status' => $status->value,
            'count' => $count,
        ];
    }
}
