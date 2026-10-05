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
    public function testMergesGroupsThatShareTheGoverningDirectiveAndValue(): void
    {
        $plan = $this->plan()->plan([
            $this->group(7, 'script-src-attr', 'inline', Status::SKIP, 3),
            $this->group(2, 'script-src', 'inline', Status::PENDING, 5),
            $this->group(9, 'SCRIPT-SRC', 'Inline ', Status::DENIED, 1),
            $this->group(4, 'img-src', 'https://cdn.example.com', Status::PENDING, 2),
        ]);

        $this->assertSame(
            [
                ['survivor' => 2, 'merged' => [7, 9], 'policy' => 'script-src', 'status' => Status::DENIED->value, 'count' => 9],
                ['survivor' => 4, 'merged' => [], 'policy' => 'img-src', 'status' => Status::PENDING->value, 'count' => 2],
            ],
            $plan
        );
    }

    public function testRemapsSubDirectivesAndKeepsWhatHasNoGoverningDirective(): void
    {
        $plan = $this->plan()->plan([
            $this->group(1, 'worker-src', 'blob:', Status::PENDING, 1),
            $this->group(2, 'navigate-to', 'https://example.org', Status::PENDING, 1),
        ]);

        $this->assertSame(['child-src', 'navigate-to'], array_column($plan, 'policy'));
        $this->assertSame([[], []], array_column($plan, 'merged'));
    }

    public function testUnknownStatusCountsAsPending(): void
    {
        $plan = $this->plan()->plan([
            ['group_id' => 1, 'policy' => 'img-src', 'value' => 'x', 'status' => 42, 'count' => 1],
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
     * @param string $policy
     * @param string $value
     * @param Status $status
     * @param int $count
     * @return array{group_id: int, policy: string, value: string, status: int, count: int}
     */
    private function group(int $id, string $policy, string $value, Status $status, int $count): array
    {
        return ['group_id' => $id, 'policy' => $policy, 'value' => $value, 'status' => $status->value, 'count' => $count];
    }
}
