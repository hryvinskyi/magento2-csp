<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration\Controller\Adminhtml;

use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Api\ViolationReviewInterface;
use Hryvinskyi\Csp\Model\System\Message\CspReport;
use Hryvinskyi\Csp\Test\Integration\ResolvesServices;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * The new-violations message goes away when the admin opens the violation reports and returns with the next
 * new violation; what one admin has seen does not hide it from another.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 */
class NewViolationNoticeTest extends AbstractBackendController
{
    use ResolvesServices;

    /** @var list<int> */
    private array $groups = [];

    protected function tearDown(): void
    {
        if ($this->groups !== []) {
            $this->connection()->delete(
                $this->table('hryvinskyi_csp_violation_report_group'),
                ['group_id IN (?)' => $this->groups]
            );
        }
        $this->connection()->delete($this->table('flag'), ['flag_code LIKE ?' => 'hryvinskyi_csp_reviewed_group_%']);
        parent::tearDown();
    }

    public function testOpeningTheReportsHidesTheMessageUntilTheNextViolation(): void
    {
        $this->group(Status::PENDING);
        $this->assertTrue($this->message()->isDisplayed());

        $this->dispatch('backend/hryvinskyi_csp/reportgroup/index');
        $this->assertFalse($this->message()->isDisplayed());

        $this->group(Status::DENIED);
        $this->assertFalse($this->message()->isDisplayed(), 'Only pending violations are announced.');

        $this->group(Status::PENDING);
        $message = $this->message();
        $this->assertTrue($message->isDisplayed());
        $this->assertStringStartsWith('New Content Security Policy violations: 1.', $message->getText());
    }

    public function testWhatOneAdminHasSeenDoesNotHideItFromAnother(): void
    {
        $review = $this->service(ViolationReviewInterface::class);
        $this->group(Status::PENDING);
        $this->group(Status::PENDING);

        $review->markReviewed(900001);

        $this->assertSame(0, $review->countUnreviewed(900001));
        $this->assertSame(2, $review->countUnreviewed(900002) - $this->pendingBefore($this->groups[0]));
    }

    /**
     * A fresh message, as on the next admin page.
     *
     * @return CspReport
     */
    private function message(): CspReport
    {
        return $this->newInstance(CspReport::class);
    }

    /**
     * @param Status $status
     * @return void
     */
    private function group(Status $status): void
    {
        $this->connection()->insert($this->table('hryvinskyi_csp_violation_report_group'), [
            'policy' => 'img-src', 'value' => uniqid('host', true) . '.example.org', 'store_id' => 0,
            'area' => 'frontend', 'status' => $status->value,
        ]);
        $id = $this->connection()->fetchOne('SELECT LAST_INSERT_ID()');
        $this->groups[] = is_numeric($id) ? (int)$id : 0;
    }

    /**
     * Pending groups that existed before the test's first group, which another admin has not seen either.
     *
     * @param int $firstGroupId
     * @return int
     */
    private function pendingBefore(int $firstGroupId): int
    {
        $count = $this->connection()->fetchOne($this->connection()->select()
            ->from($this->table('hryvinskyi_csp_violation_report_group'), ['COUNT(*)'])
            ->where('status = ?', Status::PENDING->value)
            ->where('group_id < ?', $firstGroupId));

        return is_numeric($count) ? (int)$count : 0;
    }
}
