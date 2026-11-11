<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\UiComponent\Listing\Column;

use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Model\UiComponent\Listing\Column\ReportGroupActions;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\TestCase;

class ReportGroupActionsTest extends TestCase
{
    use RowActionsAssertions;

    public function testEveryChangingActionIsPostedBehindAConfirmation(): void
    {
        $actions = $this->actionsOfRow(7);

        $this->assertSame(['convert', 'skip', 'deny', 'delete'], $this->postedActionNames($actions));
        $this->assertEveryPostedActionHasAConfirmation($actions);
    }

    public function testActionsTargetTheirGroup(): void
    {
        $actions = $this->actionsOfRow(7);

        $this->assertSame('hryvinskyi_csp/report/index?group_id=7', $this->href($actions, 'view'));
        $this->assertSame('hryvinskyi_csp/reportgroup/convertToWhitelist?id=7', $this->href($actions, 'convert'));
        $this->assertSame(
            'hryvinskyi_csp/reportgroup/changeStatus?id=7&status=' . Status::SKIP->value,
            $this->href($actions, 'skip')
        );
        $this->assertSame(
            'hryvinskyi_csp/reportgroup/changeStatus?id=7&status=' . Status::DENIED->value,
            $this->href($actions, 'deny')
        );
        $this->assertSame('hryvinskyi_csp/reportgroup/delete?id=7', $this->href($actions, 'delete'));
    }

    public function testConversionConfirmationWarnsThatTheGroupGoes(): void
    {
        $confirm = $this->action($this->actionsOfRow(7), 'convert')['confirm'] ?? null;

        $this->assertIsArray($confirm);
        $this->assertStringContainsString(
            'the group is removed with all its reports',
            $this->text($confirm['message'] ?? null)
        );
    }

    public function testViewingReportsIsAPlainLink(): void
    {
        $actions = $this->actionsOfRow(7);

        $this->assertArrayNotHasKey('post', $this->action($actions, 'view'));
        $this->assertArrayNotHasKey('confirm', $this->action($actions, 'view'));
    }

    /**
     * Actions the column gives the row of a report group.
     *
     * @param int $groupId
     * @return array<mixed>
     */
    private function actionsOfRow(int $groupId): array
    {
        $column = new ReportGroupActions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $this->urlBuilder(),
            [],
            ['name' => 'actions']
        );

        return $this->rowActions(
            $column->prepareDataSource(['data' => ['items' => [['group_id' => (string)$groupId]]]]),
            'actions'
        );
    }
}
