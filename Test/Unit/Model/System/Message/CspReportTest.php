<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\System\Message;

use Hryvinskyi\Csp\Api\Config\ReportingConfigInterface;
use Hryvinskyi\Csp\Api\ViolationReviewInterface;
use Hryvinskyi\Csp\Model\System\Message\CspReport;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

/**
 * The message counts only the violations the current admin has not been shown, and only when it may show them.
 */
class CspReportTest extends TestCase
{
    public function testShowsTheNumberOfViolationsTheAdminHasNotSeen(): void
    {
        $review = $this->review([7 => 3]);
        $message = $this->message(review: $review);

        $this->assertTrue($message->isDisplayed());
        $this->assertSame(
            'New Content Security Policy violations: 3. <a href="https://admin.test/csp/groups">Review violation reports</a>',
            $message->getText()
        );
        $this->assertSame([7], $review->counted, 'The count is read once per request.');
    }

    public function testIsHiddenWhenTheAdminHasSeenEveryViolation(): void
    {
        $this->assertFalse($this->message(review: $this->review([7 => 0]))->isDisplayed());
    }

    public function testIsHiddenWhenTheNoticeIsTurnedOff(): void
    {
        $review = $this->review([7 => 3]);

        $this->assertFalse($this->message(enabled: false, review: $review)->isDisplayed());
        $this->assertSame([], $review->counted);
    }

    public function testIsHiddenFromAdminsWhoMayNotSeeReports(): void
    {
        $review = $this->review([7 => 3]);

        $this->assertFalse($this->message(allowed: false, review: $review)->isDisplayed());
        $this->assertSame([], $review->counted);
    }

    public function testIsHiddenOutsideAnAdminSession(): void
    {
        $review = $this->review([7 => 3]);

        $this->assertFalse($this->message(userType: UserContextInterface::USER_TYPE_INTEGRATION, review: $review)
            ->isDisplayed());
        $this->assertFalse($this->message(userId: null, review: $review)->isDisplayed());
        $this->assertSame([], $review->counted);
    }

    public function testIsAMajorMessageSoMagentoCanRenderItsPopup(): void
    {
        $this->assertSame(CspReport::SEVERITY_MAJOR, $this->message(review: $this->review([]))->getSeverity());
    }

    /**
     * @param array<int, int> $unreviewed Count per admin user id
     * @return ViolationReviewInterface&object{counted: list<int>}
     */
    private function review(array $unreviewed): ViolationReviewInterface
    {
        return new class ($unreviewed) implements ViolationReviewInterface {
            /** @var list<int> */
            public array $counted = [];

            /**
             * @param array<int, int> $unreviewed
             */
            public function __construct(private readonly array $unreviewed)
            {
            }

            public function markReviewed(int $adminUserId): void
            {
            }

            public function countUnreviewed(int $adminUserId): int
            {
                $this->counted[] = $adminUserId;

                return $this->unreviewed[$adminUserId] ?? 0;
            }
        };
    }

    /**
     * @param ViolationReviewInterface $review
     * @param bool $enabled
     * @param bool $allowed
     * @param int $userType
     * @param int|null $userId
     * @return CspReport
     */
    private function message(
        ViolationReviewInterface $review,
        bool $enabled = true,
        bool $allowed = true,
        int $userType = UserContextInterface::USER_TYPE_ADMIN,
        ?int $userId = 7
    ): CspReport {
        $config = $this->createMock(ReportingConfigInterface::class);
        $config->method('isNewViolationNoticeEnabled')->willReturn($enabled);
        $userContext = $this->createMock(UserContextInterface::class);
        $userContext->method('getUserId')->willReturn($userId);
        $userContext->method('getUserType')->willReturn($userType);
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->method('isAllowed')->with('Hryvinskyi_Csp::reports')->willReturn($allowed);
        $url = $this->createMock(UrlInterface::class);
        $url->method('getUrl')->with('hryvinskyi_csp/reportgroup/index')->willReturn('https://admin.test/csp/groups');
        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeUrl')->willReturnArgument(0);

        return new CspReport($config, $review, $userContext, $authorization, $url, $escaper);
    }
}
