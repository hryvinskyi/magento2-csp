<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\UiComponent\Listing\Column;

use Hryvinskyi\Csp\Model\UiComponent\Listing\Column\ReportActions;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\TestCase;

class ReportActionsTest extends TestCase
{
    use RowActionsAssertions;

    public function testEveryChangingActionIsPostedBehindAConfirmation(): void
    {
        $actions = $this->actionsOfRow(42);

        $this->assertSame(['convert', 'delete'], $this->postedActionNames($actions));
        $this->assertEveryPostedActionHasAConfirmation($actions);
    }

    public function testActionsTargetTheirReport(): void
    {
        $actions = $this->actionsOfRow(42);

        $this->assertSame('hryvinskyi_csp/report/view?id=42', $this->href($actions, 'view'));
        $this->assertSame('hryvinskyi_csp/report/convertToWhitelist?id=42', $this->href($actions, 'convert'));
        $this->assertSame('hryvinskyi_csp/report/delete?id=42', $this->href($actions, 'delete'));
    }

    public function testConversionConfirmationNamesTheReportAndWarnsThatItsGroupGoes(): void
    {
        $confirm = $this->action($this->actionsOfRow(42), 'convert')['confirm'] ?? null;

        $this->assertIsArray($confirm);
        $message = $this->text($confirm['message'] ?? null);
        $this->assertStringContainsString('report 42', $message);
        $this->assertStringContainsString('the group is removed with all its reports', $message);
    }

    public function testViewingTheReportIsAPlainLink(): void
    {
        $actions = $this->actionsOfRow(42);

        $this->assertArrayNotHasKey('post', $this->action($actions, 'view'));
        $this->assertArrayNotHasKey('confirm', $this->action($actions, 'view'));
    }

    /**
     * Actions the column gives the row of a report.
     *
     * @param int $reportId
     * @return array<mixed>
     */
    private function actionsOfRow(int $reportId): array
    {
        $column = new ReportActions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $this->urlBuilder(),
            [],
            ['name' => 'actions']
        );

        return $this->rowActions(
            $column->prepareDataSource(['data' => ['items' => [['report_id' => (string)$reportId]]]]),
            'actions'
        );
    }
}
