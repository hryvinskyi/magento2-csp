<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\UiComponent\Listing\Column;

use Hryvinskyi\Csp\Model\UiComponent\Listing\Column\WhitelistActions;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\TestCase;

class WhitelistActionsTest extends TestCase
{
    use RowActionsAssertions;

    public function testDeletionIsPostedBehindAConfirmationAndEditingIsALink(): void
    {
        $column = new WhitelistActions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $this->urlBuilder(),
            new Escaper(),
            [],
            ['name' => 'actions']
        );

        $actions = $this->rowActions(
            $column->prepareDataSource(['data' => ['items' => [['rule_id' => '3', 'identifier' => '<b>cdn</b>']]]]),
            'actions'
        );

        $this->assertSame(['delete'], $this->postedActionNames($actions));
        $this->assertEveryPostedActionHasAConfirmation($actions);
        $this->assertSame('hryvinskyi_csp/whitelist/edit?id=3', $this->href($actions, 'edit'));
        $this->assertSame('hryvinskyi_csp/whitelist/delete?id=3', $this->href($actions, 'delete'));
        $confirm = $this->action($actions, 'delete')['confirm'] ?? null;
        $this->assertIsArray($confirm);
        $this->assertStringNotContainsString('<b>', $this->text($confirm['message'] ?? null));
    }
}
