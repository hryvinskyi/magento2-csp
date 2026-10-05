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
}
