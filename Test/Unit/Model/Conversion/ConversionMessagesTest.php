<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Conversion;

use Hryvinskyi\Csp\Model\Conversion\ConversionMessages;
use Hryvinskyi\Csp\Model\Conversion\ConversionOutcome;
use Hryvinskyi\Csp\Model\Conversion\ConversionResult;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\TestCase;

class ConversionMessagesTest extends TestCase
{
    public function testSummarizesOutcomesAndListsRefusals(): void
    {
        $messages = [];
        $manager = $this->createStub(ManagerInterface::class);
        foreach (['addSuccessMessage' => 'success', 'addNoticeMessage' => 'notice', 'addWarningMessage' => 'warning', 'addErrorMessage' => 'error'] as $method => $type) {
            $manager->method($method)->willReturnCallback(function (string $message) use (&$messages, $type, $manager): ManagerInterface {
                $messages[] = [$type, $message];

                return $manager;
            });
        }

        (new ConversionMessages())->add($manager, [
            new ConversionResult(ConversionOutcome::Created, 1),
            new ConversionResult(ConversionOutcome::Created, 2),
            new ConversionResult(ConversionOutcome::CoveredByWildcard),
            new ConversionResult(ConversionOutcome::Refused, null, __('"eval" is inline code or eval.')),
        ]);

        $this->assertSame(
            [
                ['error', '"eval" is inline code or eval.'],
                ['success', '2 whitelist entry(ies) created.'],
                ['notice', '1 report group(s) were already allowed by an enabled entry and have been removed.'],
            ],
            $messages
        );
    }
}
