<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration\Model\Conversion;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Model\Conversion\ConversionOutcome;
use Hryvinskyi\Csp\Model\Conversion\ReportGroupConverter;
use Hryvinskyi\Csp\Test\Integration\ResolvesServices;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 * @magentoAppArea adminhtml
 */
class ReportGroupConverterTest extends TestCase
{
    use ResolvesServices;

    public function testCreatesThenExtendsTheEntryAndRemovesGroupsWithTheirReports(): void
    {
        $first = $this->recordGroup(1);
        $second = $this->recordGroup(0);
        $converter = $this->service(ReportGroupConverter::class);
        $groups = $this->service(ReportGroupRepositoryInterface::class);

        $created = $converter->convert($groups->getById($first));
        $extended = $converter->convert($groups->getById($second));

        $this->assertSame([ConversionOutcome::Created, ConversionOutcome::StoresExtended], [$created->outcome, $extended->outcome]);
        $this->assertSame($created->entryId, $extended->entryId);
        $entry = $this->service(WhitelistRepositoryInterface::class)->getById((int)$created->entryId);
        $this->assertSame([0], $entry->getStoreIds());
        $this->assertSame(Area::FRONTEND->value, $entry->getArea());
        $this->assertNull($groups->findById($first));
        $this->assertSame(
            [['reports' => '0']],
            $this->rows($this->connection()->select()
                ->from($this->table('hryvinskyi_csp_violation_report'), ['reports' => 'COUNT(*)'])
                ->where('group_id IN (?)', [$first, $second]))
        );
    }

    /**
     * Insert a storefront group for cdn.example.org in the store, with one report.
     *
     * @param int $storeId
     * @return int
     */
    private function recordGroup(int $storeId): int
    {
        $this->connection()->insert($this->table('hryvinskyi_csp_violation_report_group'), [
            'policy' => 'script-src', 'value' => 'cdn.example.org', 'store_id' => $storeId, 'area' => 'frontend',
        ]);
        $groupId = $this->lastInsertId();
        $this->connection()->insert($this->table('hryvinskyi_csp_violation_report'), [
            'group_id' => $groupId,
            'blocked_uri' => 'https://cdn.example.org/a.js',
            'document_uri' => 'http://localhost/store-' . $storeId,
            'effective_directive' => 'script-src-elem',
        ]);

        return $groupId;
    }
}
