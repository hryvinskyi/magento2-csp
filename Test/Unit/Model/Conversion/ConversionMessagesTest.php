<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Conversion;

use Hryvinskyi\Csp\Model\Admin\EscapedMessages;
use Hryvinskyi\Csp\Model\Conversion\ConversionMessages;
use Hryvinskyi\Csp\Model\Conversion\ConversionOutcome;
use Hryvinskyi\Csp\Model\Conversion\ConversionResult;
use Hryvinskyi\Csp\Model\Conversion\RefusalReason;
use Magento\Framework\Escaper;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\TestCase;

class ConversionMessagesTest extends TestCase
{
    /**
     * @var list<array{string, string}>
     */
    private array $queued = [];

    public function testSummarizesOutcomesAndListsRefusals(): void
    {
        $this->conversionMessages()->add([
            new ConversionResult(ConversionOutcome::Created, 1),
            new ConversionResult(ConversionOutcome::Created, 2),
            new ConversionResult(ConversionOutcome::CoveredByWildcard),
            new ConversionResult(ConversionOutcome::Refused, null, __('eval is inline code.')),
        ]);

        $this->assertSame(
            [
                ['error', 'eval is inline code.'],
                ['success', '2 whitelist entry(ies) created.'],
                ['notice', '1 report group(s) were already allowed by an enabled entry and have been removed.'],
            ],
            $this->queued
        );
    }

    public function testARefusalQuotingMarkupFromAReportIsEscaped(): void
    {
        $refusal = RefusalReason::InvalidValue->message('script-src', 'https://<svg onload=alert(document.domain)>');

        $this->conversionMessages()->add([new ConversionResult(ConversionOutcome::Refused, null, $refusal)]);

        $this->assertCount(1, $this->queued);
        [$type, $text] = $this->queued[0];
        $this->assertSame('error', $type);
        $this->assertStringNotContainsString('<svg', $text);
        $this->assertStringContainsString('https://&lt;svg onload=alert(document.domain)&gt;', $text);
    }

    /**
     * Conversion messages that queue into $this->queued.
     *
     * @return ConversionMessages
     */
    private function conversionMessages(): ConversionMessages
    {
        $this->queued = [];
        $manager = $this->createStub(ManagerInterface::class);
        $types = [
            'addSuccessMessage' => 'success',
            'addNoticeMessage' => 'notice',
            'addWarningMessage' => 'warning',
            'addErrorMessage' => 'error',
        ];
        foreach ($types as $method => $type) {
            $manager->method($method)->willReturnCallback(function (string $message) use ($type, $manager): ManagerInterface {
                $this->queued[] = [$type, $message];

                return $manager;
            });
        }

        return new ConversionMessages(new EscapedMessages($manager, new Escaper()));
    }
}
