<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration\Model\Report;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Api\Data\ViolationInterface;
use Hryvinskyi\Csp\Api\Data\ViolationInterfaceFactory;
use Hryvinskyi\Csp\Model\Report\ViolationRecorderInterface;
use Hryvinskyi\Csp\Test\Integration\ResolvesServices;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class ViolationRecorderTest extends TestCase
{
    use ResolvesServices;

    public function testCountsRepeatsAndKeepsTheSameReportApartPerGroup(): void
    {
        $recorder = $this->service(ViolationRecorderInterface::class);
        $violation = $this->service(ViolationInterfaceFactory::class)->create([
            'documentUri' => 'https://shop.example.com/',
            'blockedUri' => 'https://cdn.example.org/a.js',
            'effectiveDirective' => 'script-src-elem',
        ]);

        $this->assertTrue($recorder->record($violation, 'script-src', 'cdn.example.org', 1, Area::FRONTEND));
        $this->assertTrue($recorder->record($violation, 'script-src', 'cdn.example.org', 1, Area::FRONTEND));
        $this->assertTrue($recorder->record($violation, 'script-src', 'cdn.example.org', 0, Area::ADMINHTML));

        $groups = $this->rows(
            $this->connection()->select()
                ->from(['g' => $this->table('hryvinskyi_csp_violation_report_group')], ['store_id', 'area', 'group_count' => 'count'])
                ->join(['r' => $this->table('hryvinskyi_csp_violation_report')], 'r.group_id = g.group_id', ['report_count' => 'count'])
                ->where('g.value = ?', 'cdn.example.org')
                ->order('g.store_id DESC')
        );
        $this->assertSame(
            [
                ['store_id' => '1', 'area' => 'frontend', 'group_count' => '2', 'report_count' => '2'],
                ['store_id' => '0', 'area' => 'adminhtml', 'group_count' => '1', 'report_count' => '1'],
            ],
            $groups
        );
    }

    public function testADeniedGroupRecordsNothing(): void
    {
        $this->connection()->insert($this->table('hryvinskyi_csp_violation_report_group'), [
            'policy' => 'img-src', 'value' => 'ads.example.org', 'store_id' => 1, 'area' => 'frontend',
            'status' => Status::DENIED->value, 'count' => 4,
        ]);
        $violation = $this->service(ViolationInterfaceFactory::class)->create([
            'documentUri' => 'https://shop.example.com/',
            'blockedUri' => 'https://ads.example.org/p.gif',
            'effectiveDirective' => 'img-src',
        ]);

        $this->assertFalse(
            $this->service(ViolationRecorderInterface::class)->record($violation, 'img-src', 'ads.example.org', 1, Area::FRONTEND)
        );
        $this->assertSame(
            [['count' => '4']],
            $this->rows($this->connection()->select()
                ->from($this->table('hryvinskyi_csp_violation_report_group'), ['count'])
                ->where('value = ?', 'ads.example.org'))
        );
    }

    public function testADenialRecordedBefore200StopsRecordingInEveryStoreAndArea(): void
    {
        $this->group('img-src', 'legacy.example.org', 0, Area::ALL, Status::DENIED, 7);
        $recorder = $this->service(ViolationRecorderInterface::class);
        $violation = $this->violation('https://legacy.example.org/p.gif', 'img-src');

        $this->assertFalse($recorder->record($violation, 'img-src', 'legacy.example.org', 1, Area::FRONTEND));
        $this->assertFalse($recorder->record($violation, 'img-src', 'legacy.example.org', 0, Area::ADMINHTML));
        $this->assertSame(
            [['store_id' => '0', 'area' => 'all', 'count' => '7']],
            $this->groupRows('legacy.example.org')
        );
    }

    public function testAStoreWideDenialInTheAreaStopsRecordingInEachStore(): void
    {
        $this->group('img-src', 'wide.example.org', 0, Area::FRONTEND, Status::DENIED, 2);

        $this->assertFalse(
            $this->service(ViolationRecorderInterface::class)->record(
                $this->violation('https://wide.example.org/p.gif', 'img-src'),
                'img-src',
                'wide.example.org',
                1,
                Area::FRONTEND
            )
        );
        $this->assertSame([['store_id' => '0', 'area' => 'frontend', 'count' => '2']], $this->groupRows('wide.example.org'));
    }

    public function testASkipRecordedBefore200KeepsRecordingWithoutAskingForReviewAgain(): void
    {
        $this->group('script-src', 'skipped.example.org', 0, Area::ALL, Status::SKIP, 3);
        $this->group('script-src', 'skipped.example.org', 0, Area::ADMINHTML, Status::PENDING, 1);
        $recorder = $this->service(ViolationRecorderInterface::class);
        $violation = $this->violation('https://skipped.example.org/a.js', 'script-src-elem');

        $this->assertTrue($recorder->record($violation, 'script-src', 'skipped.example.org', 1, Area::FRONTEND));
        $this->assertTrue($recorder->record($violation, 'script-src', 'skipped.example.org', 0, Area::ADMINHTML));
        $this->assertSame(
            [
                ['store_id' => '0', 'area' => 'adminhtml', 'status' => (string)Status::SKIP->value, 'count' => '2'],
                ['store_id' => '0', 'area' => 'all', 'status' => (string)Status::SKIP->value, 'count' => '3'],
                ['store_id' => '1', 'area' => 'frontend', 'status' => (string)Status::SKIP->value, 'count' => '1'],
            ],
            $this->groupRows('skipped.example.org', true)
        );
    }

    public function testADecisionForTheOtherAreaOfTheSameStoreDoesNotApply(): void
    {
        $this->group('img-src', 'other-area.example.org', 1, Area::ADMINHTML, Status::DENIED, 1);

        $this->assertTrue(
            $this->service(ViolationRecorderInterface::class)->record(
                $this->violation('https://other-area.example.org/p.gif', 'img-src'),
                'img-src',
                'other-area.example.org',
                1,
                Area::FRONTEND
            )
        );
        $this->assertSame(
            [
                ['store_id' => '1', 'area' => 'adminhtml', 'status' => (string)Status::DENIED->value, 'count' => '1'],
                ['store_id' => '1', 'area' => 'frontend', 'status' => (string)Status::PENDING->value, 'count' => '1'],
            ],
            $this->groupRows('other-area.example.org', true)
        );
    }

    /**
     * Insert a report group.
     *
     * @param string $policy
     * @param string $value
     * @param int $storeId
     * @param Area $area
     * @param Status $status
     * @param int $count
     * @return void
     */
    private function group(string $policy, string $value, int $storeId, Area $area, Status $status, int $count): void
    {
        $this->connection()->insert($this->table('hryvinskyi_csp_violation_report_group'), [
            'policy' => $policy, 'value' => $value, 'store_id' => $storeId, 'area' => $area->value,
            'status' => $status->value, 'count' => $count,
        ]);
    }

    /**
     * Violation of a page of the default store.
     *
     * @param string $blockedUri
     * @param string $directive
     * @return ViolationInterface
     */
    private function violation(string $blockedUri, string $directive): ViolationInterface
    {
        return $this->service(ViolationInterfaceFactory::class)->create([
            'documentUri' => 'https://shop.example.com/',
            'blockedUri' => $blockedUri,
            'effectiveDirective' => $directive,
        ]);
    }

    /**
     * Groups of a value ordered by store and area.
     *
     * @param string $value
     * @param bool $withStatus
     * @return list<array<string, string|null>>
     */
    private function groupRows(string $value, bool $withStatus = false): array
    {
        return $this->rows(
            $this->connection()->select()
                ->from(
                    $this->table('hryvinskyi_csp_violation_report_group'),
                    $withStatus ? ['store_id', 'area', 'status', 'count'] : ['store_id', 'area', 'count']
                )
                ->where('value = ?', $value)
                ->order(['store_id ASC', 'area ASC'])
        );
    }
}
