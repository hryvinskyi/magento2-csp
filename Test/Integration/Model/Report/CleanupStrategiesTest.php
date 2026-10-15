<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration\Model\Report;

use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Api\ReportCleanupInterface;
use Hryvinskyi\Csp\Test\Integration\ResolvesServices;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class CleanupStrategiesTest extends TestCase
{
    use ResolvesServices;

    public function testDeletesReportsByAgeAndTheirEmptyPendingGroups(): void
    {
        $pending = $this->group(Status::PENDING);
        $denied = $this->group(Status::DENIED);
        $this->report($pending, 'old', 40);
        $this->report($denied, 'old-denied', 40);
        $recent = $this->group(Status::PENDING);
        $this->report($recent, 'recent', 1);

        $cleanup = $this->service(ReportCleanupInterface::class);
        $this->assertSame(2, $cleanup->countAffected('date', 30));
        $this->assertSame(2, $cleanup->clean('date', 30));

        $this->assertSame([(string)$denied, (string)$recent], $this->groupIds([$pending, $denied, $recent]));
    }

    public function testKeepsTheNewestReportsByCount(): void
    {
        $group = $this->group(Status::SKIP);
        foreach ([5, 4, 3, 2, 1] as $daysAgo) {
            $this->report($group, 'r' . $daysAgo, $daysAgo);
        }

        $this->assertSame(3, $this->service(ReportCleanupInterface::class)->clean('count', 2));

        $this->assertSame(
            [['document_uri' => 'https://example.com/r1'], ['document_uri' => 'https://example.com/r2']],
            $this->rows($this->connection()->select()
                ->from($this->table('hryvinskyi_csp_violation_report'), ['document_uri'])
                ->where('group_id = ?', $group)
                ->order('document_uri'))
        );
    }

    /**
     * @param Status $status
     * @return int
     */
    private function group(Status $status): int
    {
        $this->connection()->insert($this->table('hryvinskyi_csp_violation_report_group'), [
            'policy' => 'img-src', 'value' => uniqid('host', true) . '.example.org', 'store_id' => 1,
            'area' => 'frontend', 'status' => $status->value,
        ]);

        return $this->lastInsertId();
    }

    /**
     * @param int $groupId
     * @param string $name
     * @param int $daysAgo
     * @return void
     */
    private function report(int $groupId, string $name, int $daysAgo): void
    {
        $this->connection()->insert($this->table('hryvinskyi_csp_violation_report'), [
            'group_id' => $groupId,
            'blocked_uri' => 'https://img.example.org/' . $name,
            'document_uri' => 'https://example.com/' . $name,
            'effective_directive' => 'img-src',
            'updated_at' => gmdate('Y-m-d H:i:s', time() - $daysAgo * 86400),
        ]);
    }

    /**
     * Ids among the given that still exist.
     *
     * @param list<int> $ids
     * @return list<string|null>
     */
    private function groupIds(array $ids): array
    {
        return array_column($this->rows($this->connection()->select()
            ->from($this->table('hryvinskyi_csp_violation_report_group'), ['group_id'])
            ->where('group_id IN (?)', $ids)
            ->order('group_id')), 'group_id');
    }
}
