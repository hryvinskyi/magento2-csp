<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Admin;

use Hryvinskyi\Csp\Model\Admin\EscapedMessages;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\TestCase;

class EscapedMessagesTest extends TestCase
{
    public function testEveryToneQueuesEscapedText(): void
    {
        $queued = [];
        $manager = $this->createStub(ManagerInterface::class);
        foreach (['addSuccessMessage', 'addNoticeMessage', 'addWarningMessage', 'addErrorMessage'] as $method) {
            $manager->method($method)->willReturnCallback(function (string $message) use (&$queued, $method, $manager): ManagerInterface {
                $queued[] = [$method, $message];

                return $manager;
            });
        }
        $messages = new EscapedMessages($manager, new Escaper());

        $messages->success(__('Saved %1', '<b>x</b>'));
        $messages->notice('<i>n</i>');
        $messages->warning('a & b');
        $messages->failure(new CouldNotSaveException(__('Duplicate entry \'%1\'', '<svg onload=alert(1)>')));

        $this->assertSame(
            [
                ['addSuccessMessage', 'Saved &lt;b&gt;x&lt;/b&gt;'],
                ['addNoticeMessage', '&lt;i&gt;n&lt;/i&gt;'],
                ['addWarningMessage', 'a &amp; b'],
                ['addErrorMessage', 'Duplicate entry &#039;&lt;svg onload=alert(1)&gt;&#039;'],
            ],
            $queued
        );
    }
}
