<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration\Setup\Patch\Data;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Setup\Patch\Data\MarkLegacyReportGroups;
use Hryvinskyi\Csp\Test\Integration\ResolvesServices;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class MarkLegacyReportGroupsTest extends TestCase
{
    use ResolvesServices;

    public function testMovesGroupsToAllStoresAndMergesThoseThatCollide(): void
    {
        $groupTable = $this->table('hryvinskyi_csp_violation_report_group');
        $reportTable = $this->table('hryvinskyi_csp_violation_report');
        $this->connection()->delete($groupTable);
        $parent = $this->recordGroup('parent', 'script-src', 'inline', 1, Status::PENDING, 5);
        $this->recordGroup('attribute', 'script-src-attr', 'inline', 1, Status::DENIED, 2);
        $this->recordGroup('other-store', 'script-src', 'inline', 0, Status::SKIP, 1);
        $image = $this->recordGroup('image', 'img-src', 'https://cdn.example.com', 1, Status::PENDING, 4);

        $this->newInstance(MarkLegacyReportGroups::class)->apply();

        $this->assertSame(
            [
                [(string)$parent, 'script-src', 'inline', '0', Area::ALL->value, (string)Status::DENIED->value, '8'],
                [(string)$image, 'img-src', 'https://cdn.example.com', '0', Area::ALL->value, (string)Status::PENDING->value, '4'],
            ],
            array_map(
                static fn (array $row): array => array_values($row),
                $this->rows(
                    $this->connection()->select()
                        ->from($groupTable, ['group_id', 'policy', 'value', 'store_id', 'area', 'status', 'count'])
                        ->order('group_id')
                )
            )
        );
        $this->assertSame(
            [['reports' => '3']],
            $this->rows(
                $this->connection()->select()
                    ->from($reportTable, ['reports' => 'COUNT(*)'])
                    ->where('group_id = ?', $parent)
            )
        );
    }

    /**
     * The group table uses Magento's case- and accent-insensitive collation, which ignores trailing spaces.
     */
    public function testMergesGroupsWhoseValuesTheTableCollationEquates(): void
    {
        $groupTable = $this->table('hryvinskyi_csp_violation_report_group');
        $this->connection()->delete($groupTable);
        $first = $this->recordGroup('accented', 'img-src', 'https://café.example.com', 1, Status::PENDING, 2);
        $this->recordGroup('plain', 'img-src', 'https://cafe.example.com', 0, Status::PENDING, 3);
        $this->recordGroup('upper', 'IMG-SRC', 'HTTPS://CAFE.EXAMPLE.COM ', 1, Status::SKIP, 4, Area::ADMINHTML);

        $this->newInstance(MarkLegacyReportGroups::class)->apply();

        $this->assertSame(
            [[(string)$first, 'img-src', '0', Area::ALL->value, (string)Status::SKIP->value, '9']],
            array_map(
                static fn (array $row): array => array_values($row),
                $this->rows(
                    $this->connection()->select()
                        ->from($groupTable, ['group_id', 'policy', 'store_id', 'area', 'status', 'count'])
                        ->order('group_id')
                )
            )
        );
    }

    /**
     * Insert a group as 1.3.1 recorded it, with one report.
     *
     * @param string $name
     * @param string $policy
     * @param string $value
     * @param int $storeId
     * @param Status $status
     * @param int $count
     * @param Area $area
     * @return int
     */
    private function recordGroup(
        string $name,
        string $policy,
        string $value,
        int $storeId,
        Status $status,
        int $count,
        Area $area = Area::FRONTEND
    ): int {
        $this->connection()->insert($this->table('hryvinskyi_csp_violation_report_group'), [
            'policy' => $policy,
            'value' => $value,
            'store_id' => $storeId,
            'area' => $area->value,
            'status' => $status->value,
            'count' => $count,
        ]);
        $groupId = $this->lastInsertId();
        $this->connection()->insert($this->table('hryvinskyi_csp_violation_report'), [
            'group_id' => $groupId,
            'blocked_uri' => $value,
            'document_uri' => 'https://example.com/' . $name,
            'effective_directive' => $policy,
        ]);

        return $groupId;
    }
}
